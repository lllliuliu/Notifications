<?php
// +----------------------------------------------------------------------
// | 发送消息接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

/**
 * 发送消息接口
 */
interface SenderInterface
{
    /**
     * 发送单个消息
     *
     * @param string $notification 消息
     * @return bool
     */
    public function sendOne(array $notification);

    /**
     * 发送多条消息
     *
     * @param string $notifications 多个消息
     * @return void
     */
    public function sendMultiple(array $notifications);
}
