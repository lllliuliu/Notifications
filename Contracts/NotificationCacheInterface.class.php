<?php
// +----------------------------------------------------------------------
// | 消息缓存处理接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

/**
 * 消息缓存处理接口
 */
interface NotificationCacheInterface
{
    /**
     * 通过消息ID查找消息
     *
     * @param int $notification_id
     * @return array
     */
    public function find($notification_id);

    /**
     * 刷新单个消息缓存
     *
     * @param array $notification
     * @return bool
     */
    public function flushOne(array $notification);

    /**
     * 刷新多个消息缓存
     *
     * @param array $notifications
     * @return bool
     */
    public function flushMultiple(array $notifications);

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
     * 获取某个用户的所有消息id的集合
     *
     * @param int    $to_id
     * @param int    $limit
     * @param int    $paginate
     * @param string $orderDate
     * @return mixed
     */
    public function getAllSet($to_id, $limit, $paginate, $orderDate = 'desc');

    /**
     * 初始化某个用户的所有消息集合缓存
     *
     * @param int $to_id
     * @param array|string $notification_ids
     * @return bool
     */
    public function flushAllSet($to_id, $notification_ids);

    /**
     * 添加一个消息id到某个用户的所有消息集合缓存
     *
     * @param int $to_id
     * @param int $notification_ids
     * @return bool
     */
    public function addAllSet($to_id, $notification_id);

    /**
     * 获取某个用户未读消息的总数
     *
     * @param int     $to_id
     * @return mixed
     */
    public function countNotRead($to_id);

    /**
     * 初始化某个用户的未读消息集合缓存
     *
     * @param int $to_id
     * @param array|int $notification_ids
     * @return bool
     */
    public function flushNoReadSet($to_id, $notification_ids);

    /**
     * 添加一个消息id到某个用户的未读消息集合缓存
     *
     * @param int $to_id
     * @param int $notification_id
     * @return bool
     */
    public function addNoReadSet($to_id, $notification_id);

}
