<?php
// +----------------------------------------------------------------------
// | 消息数据库操作接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

/**
 * 消息数据库操作接口
 */
interface NotificationDBInterface extends StoreNotificationInterface
{
    /**
     * 通过消息ID查找消息
     *
     * @param int $notification_id
     * @return array
     */
    public function find($notification_id);

    /**
     * 根据消息id读取通知，标识为已读状态
     *
     * @param array $notification
     * @return int 影响的消息条数
     */
    public function readOne(array $notification);

    /**
     * 根据接受者id读取所有消息，标识为已读状态
     *
     * @param int $to_id
     * @return int 影响的消息条数
     */
    public function readAll($to_id);

    /**
     * 根据消息id删除消息
     *
     * @param array $notification
     * @return bool
     */
    public function delete($notification);

    /**
     * 删除某个接受者用户的所有消息
     *
     * @param int $to_id
     * @return bool
     */
    public function deleteAll($to_id);

    /**
     * 获取某个用户的所有消息
     *
     * @param int    $to_id
     * @param int    $limit
     * @param int    $paginate
     * @param string $orderDate
     * @return mixed
     */
    public function getAll($to_id, $limit, $paginate, $orderDate = 'desc');

    /**
     * 获取某个用户未读消息
     *
     * @param int $to_id
     * @return array
     */
    public function getNotRead($to_id);
}
