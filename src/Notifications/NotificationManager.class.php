<?php
// +----------------------------------------------------------------------
// | 消息管理类
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Notifications;

use Common\Library\UserNotifications\Contracts\NotificationDBInterface;
use Common\Library\UserNotifications\Contracts\NotificationCacheInterface;
use Common\Library\UserNotifications\Contracts\NotificationInterface;
use Common\Library\UserNotifications\Exceptions\CommonException;
use Common\Library\UserNotifications\Logs\DefaultLog as Log;
use Common\Library\UserNotifications\Parsers\NotificationParser;
use Closure;

/**
 * 消息管理类
 *
 * 消息的CURD，存储，缓存等处理
 */
class NotificationManager implements NotificationInterface
{
    /**
     * 防止缓存穿透的预设字符
     *
     * @var string
     */
    const NIL = 'nil';

    /**
     * 数据库操作对象
     * @var NotificationDBInterface
     */
    protected $notification_repo;

    /**
     * 缓存操作对象
     * @var NotificationCacheInterface
     */
    protected $notification_cache;

    /**
     * 数据解析对象
     * @var NotificationParser
     */
    protected $parser;

    /**
     * 构造函数
     *
     * @param NotificationDB $notification_repo 数据库操作对象
     * @param NotificationCacheInterface $notification_cache 消息缓存处理对象
     * @param NotificationParser $parser 数据解析对象
     * @return void
     */
    public function __construct(NotificationDBInterface $notification_repo, NotificationCacheInterface $notification_cache, NotificationParser $parser)
    {
        $this->notification_repo = $notification_repo;
        $this->notification_cache = $notification_cache;
        $this->parser = $parser;
    }

    /**
     * 获取/查看授权检查
     *
     * @todo 实现
     * @param  int $user_id 当前用户id
     * @param  int $to_id 接收用户id
     * @return void
     */
    public function authorGet($user_id, $to_id)
    {
        return true;
    }

    /**
     * 通过消息ID查找消息
     *
     * @param int $notification_id
     * @return array
     */
    public function find($notification_id)
    {
        $error = '消息没找到';
        $notification = $this->notification_cache->find($notification_id);

        if (empty($notification)) {
            $notification = $this->notification_repo->find($notification_id);
            $notification = $this->parser->parse($notification);

            if (empty($notification)) {
                // 防止缓存穿透
                $this->notification_cache->flushOne(['id' => $notification_id, '_ex' => NIL]);
                throw new CommonException($error);
            }
            $notification = $this->notification_cache->flushOne($notification);
        } elseif($notification['_ex']==NIL) {
            throw new CommonException($error);
        }

        return $notification;
    }

    /**
     * 根据消息id读取通知，标识为已读状态
     *
     * @param int $notification_id
     * @return int 影响的消息条数
     */
    public function readOne($notification_id)
    {
        $notification = $this->find($notification_id);
        if ($notification['read']==1) {
            return 0;
        }

        $rs = $this->notification_repo->readOne($notification_id);

        if ($rs===false) {
            $error = '操作失败！';
            throw new CommonException($error);
        } elseif ($rs>0) {
            if ($this->notification_cache->readOne($notification) === false) {
                $error = "更新消息ID为 $notification_id 的缓存失败";
                Log::errorLog($error);
            }
        }

        return $rs;
    }

    /**
     * 根据接受者id读取所有消息，标识为已读状态
     *
     * @param int $to_id
     * @return int 影响的消息条数
     */
    public function readAll($to_id)
    {
        $rs = $this->notification_repo->readAll($to_id);

        if ($rs===false) {
            $error = '操作失败！';
            throw new CommonException($error);
        } elseif ($rs>0) {
            $cache_rs = $this->notification_cache->readAll($to_id);
            if ($cache_rs !== true) {
                $error = "读取用户 $to_id 的所有消息失败，出错消息ID：" . json_decode($cache_rs);
                Log::errorLog($error);
            }
        }

        return $rs;
    }

    /**
     * 根据消息id删除消息
     *
     * @param $notification_id
     * @return bool
     */
    public function delete($notification_id)
    {
        $notification = $this->find($notification_id);

        $rs = $this->notification_repo->delete($notification);

        if ($rs===false) {
            $error = '操作失败！';
            throw new CommonException($error);
        } elseif ($rs>0) {
            $cache_rs = $this->notification_cache->delete($notification);
            if ($cache_rs !== true) {
                $error = "删除消息 $notification_id 失败，出错详情：" . json_decode($cache_rs);
                Log::errorLog($error);
            }
        }

        return $rs;
    }

    /**
     * 删除某个接受者用户的所有消息
     *
     * @param int $to_id
     * @return bool
     */
    public function deleteAll($to_id)
    {
        $rs = $this->notification_repo->deleteAll($to_id);

        if ($rs===false) {
            $error = '操作失败！';
            throw new CommonException($error);
        } elseif ($rs>0) {
            if ($this->notification_cache->deleteAll($to_id) === false) {
                $error = "删除用户id为 $to_id 的所有消息失败";
                Log::errorLog($error);
            }
        }

        return $rs;
    }

    /**
     * 获取某个用户的所有消息
     *
     * @param int    $to_id
     * @param int    $limit         每页条数
     * @param int    $paginate      页数
     * @param string $orderDate
     * @param Closure $filterScope
     * @return mixed
     */
    public function getAll($to_id, $limit = null, $paginate = null, $orderDate = 'desc', Closure $filterScope = null)
    {
        $query_paginate = is_numeric($paginate) ? ceil($paginate) : null;
        $query_limit = is_numeric($limit) ? ceil($limit) : null;

        // 所有消息集合缓存查询
        $all_set = $this->notification_cache->getAllSet($to_id, $query_limit, $query_paginate, $orderDate);
        if ($all_set == NIL) {
            return false;
        }

        // 缓存超时重查
        $notifications = [];
        if ($all_set===false) {
            $notifications = $this->notification_repo->getAll($to_id);
            //var_dump('缓存超时重查',$notifications);exit();
            if (empty($notifications)) {
                // 防止缓存穿透
                $this->notification_cache->flushAllSet($to_id, NIL);
                return false;
            }
            $this->notification_cache->flushAllSet($to_id, array_keys($notifications));

            $all_set = $this->notification_cache->getAllSet($to_id, $query_limit, $query_paginate, $orderDate);
        }

        // 查找具体数据
        $rows = [];
        foreach ($all_set as $notification_id) {
            $notification = $this->notification_cache->find($notification_id);

            if (empty($notification)) {
                $notification = $notifications[$notification_id]
                                ? $notifications[$notification_id]
                                : $this->notification_repo->find($notification_id);

                $notification = $this->parser->parse($notification);

                if (empty($notification)) {
                    // 防止缓存穿透
                    $this->notification_cache->flushOne(['id' => $notification_id, '_ex' => NIL]);
                } else {
                    $rows[$notification_id] = $this->notification_cache->flushOne($notification);
                }
            } else {
                $rows[$notification_id] = $notification;
            }
        }

        return array_map($filterScope, $rows);
    }

    /**
     * 获取某个用户未读消息的总数
     *
     * @param int     $to_id
     * @return mixed
     */
    public function countNotRead($to_id)
    {
        $count = $this->notification_cache->countNotRead($to_id);
        if (is_array($count)) {
            $count = in_array(NIL, $count) ? 0 : count($count);
        } else {
            // 缓存超时重查
            if ($count===0) {
                $noread_ids = $this->notification_repo->getNotRead($to_id);
                if (empty($noread_ids)) {
                    // 防止缓存穿透
                    $this->notification_cache->flushNoReadSet($to_id, NIL);
                    return 0;
                }
                $this->notification_cache->flushNoReadSet($to_id, $noread_ids);
                return count($noread_ids);
            }
        }

        return $count;
    }

    /**
     * 获取某个用户的未读消息
     *
     * @param int    $to_id
     * @param int    $limit
     * @param int    $paginate
     * @param string $orderDate
     * @param Closure $filterScope
     * @return mixed
     */
    public function getNotRead($to_id, $limit = null, $paginate = null, $orderDate = 'desc', Closure $filterScope = null)
    {
        return;
    }

    /**
     * 获取某个用户最后收到的消息
     *
     * @param int     $to_id
     * @param Closure $filterScope
     * @return mixed
     */
    public function getLastNotification($to_id, Closure $filterScope = null)
    {
        return;
    }
}
