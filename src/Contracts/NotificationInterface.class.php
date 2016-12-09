<?php
// +----------------------------------------------------------------------
// | 消息接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

use Closure;

/**
 * 消息处理接口
 */
interface NotificationInterface
{

    /**
     * 获取/查看授权检查
     *
     * @todo 实现
     * @param  int $user_id 当前用户id
     * @param  int $to_id 接收用户id
     * @return void
     */
    public function authorGet($user_id, $to_id);

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
     * @param int $notification_id
     * @return int 影响的消息条数
     */
    public function readOne($notification_id);

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
     * @param $notification_id
     * @return bool
     */
    public function delete($notification_id);

    /**
     * 删除某个接受者用户的所有消息
     *
     * @param int $to_id
     * @return bool
     */
    public function deleteAll($to_id);

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
     public function getNotRead($to_id, $limit = null, $paginate = null, $orderDate = 'desc', Closure $filterScope = null);

    /**
     * 获取某个用户的所有消息
     *
     * @param int    $to_id
     * @param int    $limit
     * @param int    $paginate
     * @param string $orderDate
     * @param Closure $filterScope
     * @return mixed
     */
    public function getAll($to_id, $limit, $paginate, $orderDate = 'desc', Closure $filterScope = null);

    /**
     * 获取某个用户最后收到的消息
     *
     * @param int     $to_id
     * @param Closure $filterScope
     * @return mixed
     */
    public function getLastNotification($to_id, Closure $filterScope = null);

    /**
     * 获取某个用户未读消息的总数
     *
     * @param int     $to_id
     * @return mixed
     */
    public function countNotRead($to_id);
}
