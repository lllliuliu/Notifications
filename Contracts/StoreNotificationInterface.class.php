<?php
// +----------------------------------------------------------------------
// | 消息存储接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

/**
 * 消息存储接口
 */
interface StoreNotificationInterface
{
    /**
     * 存储单个消息
     *
     * @param  array $notification
     * @return mixed
     */
    public function storeSingle(array $notification);

    /**
     * 一次存储多个消息
     *
     * @param  array $notifications
     * @return mixed
     */
    public function storeMultiple(array $notifications);
}
