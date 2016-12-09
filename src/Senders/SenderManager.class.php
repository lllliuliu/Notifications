<?php
// +----------------------------------------------------------------------
// | 发送者
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Senders;

use Common\Library\UserNotifications\Contracts\SenderInterface;
use Common\Library\UserNotifications\Exceptions\CommonException;
use Common\Library\UserNotifications\Contracts\StoreNotificationInterface;
use Common\Library\UserNotifications\Contracts\NotificationCacheInterface;

/**
 * 发送者
 */
class SenderManager implements SenderInterface
{
    /**
     * 消息存储实例
     *
     * @var Think/Model
     */
    protected $store;

    /**
     * 消息缓存实例
     *
     * @var Think/Model
     */
    protected $cache;

    /**
     * 构造函数
     *
     * @param  NotificationCacheInterface $cache 消息缓存实例
     * @param  StoreNotificationInterface $store 消息存储实例
     * @return void
     */
    public function __construct(NotificationCacheInterface $cache, StoreNotificationInterface $store)
    {
         $this->cache = $cache;
         $this->store = $store;
    }

    /**
     * 发送单个消息
     *
     * @param string $notification 消息
     * @return bool
     */
    public function sendOne(array $notification)
    {
        $id = $this->store->storeSingle($notification);
        if ($id > 0) {
            // 添加消息到所有消息集合
            $this->cache->addNoReadSet($notification['to_id'], $id);
            // 添加消息到未读消息集合
            $this->cache->addAllSet($notification['to_id'], $id);

            return true;
        }

        return false;
    }

    /**
     * 发送多条消息
     *
     * @param string $notifications 多个消息
     * @return void
     */
    public function sendMultiple(array $notifications)
    {
        $rows = $this->store->storeMultiple($notifications);
        if ($rows > 0) {
            foreach ($notifications as $n => $notification) {
                // 批量添加数据时mysql返回第一条数据的id
                $id = $rows + $n;
                // 添加消息到所有消息集合
                $this->cache->addNoReadSet($notification['to_id'], $id);
                // 添加消息到未读消息集合
                $this->cache->addAllSet($notification['to_id'], $id);
            }
            return true;
        }

        return false;
    }
}
