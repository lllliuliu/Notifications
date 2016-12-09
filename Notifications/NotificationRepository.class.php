<?php
// +----------------------------------------------------------------------
// | 消息数据库操作类
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Notifications;

use Common\Library\UserNotifications\Contracts\NotificationDBInterface;
use Common\Library\UserNotifications\Models\NotificationModel;
use Common\Library\UserNotifications\Logs\DefaultLog as Log;

/**
 * 消息数据库操作类
 */
class NotificationRepository implements NotificationDBInterface
{
    /**
     * 基于TP框架的数据表模型
     *
     * @var NotificationModel
     */
    protected $notification;

    /**
     * 构造函数
     *
     * @param NotificationModel $notification 基于TP框架的数据表模型
     * @return void
     */
    public function __construct(NotificationModel $notification)
    {
        $this->notification = $notification;
    }

    /**
     * 通过消息ID查找消息
     *
     * @param int $notification_id
     * @return array
     */
    public function find($notification_id)
    {
        return $this->notification->where(['id' => $notification_id])->find();
    }

    /**
     * 根据消息id读取通知，标识为已读状态
     *
     * @param int $notification_id
     * @return int 影响的消息条数
     */
    public function readOne(array $notification)
    {
        $notification['read'] = 1;
        return $this->notification->save($notification);
    }

    /**
     * 根据接受者id读取所有消息，标识为已读状态
     *
     * @param int $to_id
     * @return int 影响的消息条数
     */
    public function readAll($to_id)
    {
        return $this->notification->where(['to_id' => $to_id])->setField('read', 1);
    }

    /**
     * 根据消息id删除消息
     *
     * @param array $notification
     * @return bool
     */
    public function delete($notification)
    {
        return $this->notification->delete($notification['id']);
    }

    /**
     * 删除某个接受者用户的所有消息
     *
     * @param int $to_id
     * @return bool
     */
    public function deleteAll($to_id)
    {
        return $this->notification->where(['to_id' => $to_id])->delete();
    }

    /**
     * 获取某个用户的所有消息
     *
     * @param int    $to_id
     * @param int    $limit
     * @param int    $paginate
     * @param string $orderDate
     * @return mixed
     */
    public function getAll($to_id, $limit = null, $paginate = null, $orderDate = 'desc')
    {
        $query = $this->notification->where(['to_id' => $to_id])->order('id ' . $orderDate);

        if (!is_null($limit) && !is_null($paginate)) {
            $query->limit($paginate*$limit, ($paginate*$limit)+$limit-1);
        }

        $rows = $query->select();
        if ($rows) {
            $rows = $this->arrayByPrimary($rows);
        }

        return $rows;
    }

    /**
     * 解析结果数组，使用id作为结果数组的key
     *
     * @param array  $rows
     * @param string $primary 主键。默认为id
     * @return array
     */
    protected function arrayByPrimary($rows, $primary = 'id')
    {
        $news = [];
        foreach ($rows as $key => $row) {
            $news[$row[$primary]] = $row;
        }
        return $news;
    }



    /**
     * 获取某个用户未读消息
     *
     * @param int $to_id
     * @return array
     */
    public function getNotRead($to_id)
    {
        $params = [
            'to_id' => $to_id,
            'read' => 0
        ];
        return $this->notification->where($params)->getField('id',true);
    }

    /**
     * 存储单个消息
     *
     * @param  array $notification
     * @return Notification
     */
    public function storeSingle(array $notification)
    {
        if($this->notification->create($notification)){
            $result = $this->notification->add();
        } else {
            $error = '发送单个消息失败：' . $this->notification->getError();
            Log::errorLog($error);
            $result = false;
        }

        return $result;
    }

    /**
     * 一次存储多个消息
     *
     * @param  array $notifications
     * @return mixed
     */
    public function storeMultiple(array $notifications)
    {
        return $this->notification->addAll($notifications);
    }

}
