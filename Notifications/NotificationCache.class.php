<?php
// +----------------------------------------------------------------------
// | 消息缓存操作类
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Notifications;

use Common\Library\UserNotifications\Contracts\NotificationCacheInterface;
use Common\Library\UserNotifications\Models\NotificationModel;
use Think\Cache;

/**
 * 消息缓存操作类
 */
class NotificationCache implements NotificationCacheInterface
{
    /**
     * 防止缓存穿透的预设字符
     *
     * @var string
     */
    const NIL = 'nil';

    /**
     * 缓存key的待格式化字串
     */
    protected $keys = [
        'MSG' => 'msg:%d',
        'USER_MSGLIST' => 'user:%d:msg',
        'USER_MSGLIST_NOREAD' => 'user:%d:msg:noread',
    ];

    /**
     * 缓存key的超时时间
     */
     protected $key_timeout = [
         'MSG_TIME' => 43200,
         'USER_MSGLIST_TIME' => 21600,
         'USER_MSGLIST_NOREAD_TIME' => 21600,
     ];

    /**
     * TP框架的缓存对象
     *
     * @var redis
     */
    protected $cache;

    /**
     * 需要缓存的消息字段
     *
     * @var redis
     */
    protected $msg_fields = ['id', 'extra_title', 'extra_content', 'to_id', 'from_id', 'read', 'created_at', 'url', '_ex'];

    /**
     * 构造函数
     *
     * @param Cache $cache TP框架的缓存对象
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 通过消息ID查找消息
     *
     * @param int $notification_id
     * @return array
     */
    public function find($notification_id)
    {
        $key = sprintf($this->keys['MSG'], $notification_id);
        if (!$this->cache->exists($key)) {
            return false;
        }
        return $this->cache->hMGet($key, $this->msg_fields);
    }

    /**
     * 刷新单个消息缓存
     *
     * @param array $notification
     * @return bool
     */
    public function flushOne(array $notification)
    {
        $key = sprintf($this->keys['MSG'], $notification['id']);
        $notification = array_intersect_key($notification, array_flip($this->msg_fields));

        $this->cache->hMSet($key, $notification);
        $this->cache->setTimeout($key, $this->key_timeout['MSG_TIME']);

        return $notification;
    }

    /**
     * 刷新多个消息缓存
     *
     * @param array $notifications
     * @return bool
     */
    public function flushMultiple(array $notifications)
    {
        foreach ($notifications as $notification) {
            $this->flushOne($notification);
        }
    }

    /**
     * 根据消息id读取通知，标识为已读状态
     *
     * @param array $notification
     * @return int 影响的消息条数
     */
    public function readOne(array $notification)
    {
        // 从未读集合删除当前消息
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $notification['to_id']);
        $this->cache->sRem($noread_key, $notification['id']);

        // 设定消息属性
        $key = sprintf($this->keys['MSG'], $notification['id']);
        return $this->cache->hSet($key, 'read', 1);
    }

    /**
     * 根据接受者id读取所有消息，标识为已读状态
     *
     * @param int $to_id
     * @return int 影响的消息条数
     */
    public function readAll($to_id)
    {
        $rs = true;

        // 删除未读集合
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $to_id);
        $this->cache->delete($noread_key);

        // 设定所有消息属性
        $set_key = sprintf($this->keys['USER_MSGLIST'], $to_id);
        $set = $this->cache->sMembers($set_key);
        foreach ($set as $notification_id) {
            $key = sprintf($this->keys['MSG'], $notification_id);
            if ($this->cache->hSet($key, 'read', 1) === false) {
                $rs[] = $notification_id;
            }
        }

        return $rs;
    }

    /**
     * 根据消息id删除消息
     *
     * @param array $notification
     * @return bool
     */
    public function delete($notification)
    {
        $rs = true;

        $key = sprintf($this->keys['MSG'], $notification['id']);
        if ($this->cache->delete($key) === false) {
            $rs[] = "删除 $key 失败";
        }

        // 从消息集合删除消息
        $set_key = sprintf($this->keys['USER_MSGLIST'], $notification['to_id']);
        if ($this->cache->sRem($set_key, $notification['id']) === false) {
            $rs[] = "删除 $set_key 失败";
        }

        // 从未读集合删除当前消息
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $notification['to_id']);
        if ($this->cache->sRem($noread_key, $notification['id']) === false) {
            $rs[] = "删除 $noread_key 失败";
        }
    }

    /**
     * 删除某个接受者用户的所有消息
     *
     * @param int $to_id
     * @return bool
     */
    public function deleteAll($to_id)
    {
        // 删除所有消息和消息集合
        $set_key = sprintf($this->keys['USER_MSGLIST'], $to_id);
        $list = $this->cache->sMembers($set_key);
        $list = array_map(
            function($notification_id){
                return sprintf($this->keys['MSG'], $notification_id);
            },
            $list
        );
        $list[] = $set_key;

        // 删除未读集合
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $to_id);
        $list[] = $noread_key;

        return $this->cache->delete($list);
    }

    /**
     * 获取某个用户的所有消息id的集合
     *
     * @param int    $to_id
     * @param int    $limit
     * @param int    $paginate
     * @param string $orderDate
     * @return mixed
     */
    public function getAllSet($to_id, $limit, $paginate, $orderDate = 'desc')
    {
        $set_key = sprintf($this->keys['USER_MSGLIST'], $to_id);

        if (!$this->cache->exists($set_key)) {
            return false;
        }

        $params = ['sort' => $orderDate];
        if (!is_null($limit) && !is_null($paginate)) {
            $params['limit'] = [(int)(($paginate-1)*$limit), (int)($limit)];
        }
        return $this->cache->sort($set_key, $params);
    }

    /**
     * 初始化某个用户的所有消息集合缓存
     *
     * @param int $to_id
     * @param array|string $notification_ids
     * @return bool
     */
    public function flushAllSet($to_id, $notification_ids)
    {
        $set_key = sprintf($this->keys['USER_MSGLIST'], $to_id);
        // 删除
        $this->cache->delete($set_key);
        // 重设
        $notification_ids = (array)$notification_ids;
        array_unshift($notification_ids, $set_key);
        $rs = call_user_func_array([$this->cache, 'sAdd'], $notification_ids);
        $this->cache->setTimeout($set_key, $this->key_timeout['USER_MSGLIST_TIME']);
        return $rs;
    }

    /**
     * 添加一个消息id到某个用户的所有消息集合缓存
     *
     * @param int $to_id
     * @param int $notification_ids
     * @return bool
     */
    public function addAllSet($to_id, $notification_id)
    {
        $set_key = sprintf($this->keys['USER_MSGLIST'], $to_id);
        if (!$this->cache->exists($set_key)) {
            return false;
        }

        if ($this->cache->sMembers($set_key) == [NIL]) {
            $this->cache->delete($set_key);
        }

        $rs = $this->cache->sAdd($set_key, $notification_id);
        $this->cache->setTimeout($set_key, $this->key_timeout['USER_MSGLIST_TIME']);
        return $rs;
    }

    /**
     * 获取某个用户未读消息的总数
     *
     * @param int     $to_id
     * @return mixed
     */
    public function countNotRead($to_id)
    {
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $to_id);
        $count = $this->cache->sCard($noread_key);
        if ($count==1) {
            return $this->cache->sMembers($noread_key);
        }
        return $count;
    }

    /**
     * 初始化某个用户的未读消息集合缓存
     *
     * @param int $to_id
     * @param array|string $notification_ids
     * @return bool
     */
    public function flushNoReadSet($to_id, $notification_ids)
    {
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $to_id);
        // 删除
        $this->cache->delete($noread_key);
        // 重设
        $notification_ids = (array)$notification_ids;
        array_unshift($notification_ids, $noread_key);
        $rs = call_user_func_array([$this->cache, 'sAdd'], $notification_ids);
        $this->cache->setTimeout($noread_key, $this->key_timeout['USER_MSGLIST_NOREAD_TIME']);
        return $rs;
    }

    /**
     * 添加一个消息id到某个用户的未读消息集合缓存
     *
     * @param int $to_id
     * @param int $notification_id
     * @return bool
     */
    public function addNoReadSet($to_id, $notification_id)
    {
        $noread_key = sprintf($this->keys['USER_MSGLIST_NOREAD'], $to_id);
        if (!$this->cache->exists($noread_key)) {
            return false;
        }

        if ($this->cache->sMembers($noread_key) == [NIL]) {
            $this->cache->delete($noread_key);
        }

        $rs = $this->cache->sAdd($noread_key, $notification_id);
        $this->cache->setTimeout($noread_key, $this->key_timeout['USER_MSGLIST_NOREAD_TIME']);
        return $rs;
    }


}
