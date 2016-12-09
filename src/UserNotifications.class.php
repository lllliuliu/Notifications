<?php
// +----------------------------------------------------------------------
// |  前台用户的消息通知处理
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications;

use Common\Library\UserNotifications\Contracts\NotificationInterface;
use Common\Library\UserNotifications\Contracts\SenderInterface;
use Common\Library\UserNotifications\Contracts\CategoryInterface;
use Common\Library\UserNotifications\Contracts\BroadcastInterface;
use Common\Library\UserNotifications\Exceptions\CommonException;
use Common\Library\UserNotifications\Builder\NotificationBuilder;
use Closure;

/**
 * 消息的生产，传输，消费处理容器
 *
 * @TODO 加入用户类型，前后台通用
 *       完善并独立授权类
 *       完善异常和日志
 */
class UserNotifications extends NotificationBuilder
{
    /**
     * 消息处理类
     *
     * @var object
     */
    protected $notification;

    /**
     * 发送处理类
     *
     * @var object
     */
    protected $sender;

    /**
     * 消息分类处理类
     *
     * @var object
     */
    protected $category;

    /**
     * 广播消息处理类
     *
     * @var object
     */
    protected $broadcast;

    /**
     * 当前用户对象
     *
     * @var object
     */
    protected $user;

    /**
     * 初始化
     * @return void
     */
    public function __construct(NotificationInterface $notification,
                        SenderInterface $sender,
                        CategoryInterface $category,
                        BroadcastInterface $broadcast,
                        $user = null)
    {
        $this->notification = $notification;
        $this->sender = $sender;
        $this->category = $category;
        $this->broadcast = $broadcast;
        $this->user = $user;
    }

    /**
     * 接收者id授权检查
     *
     * @param  int $to_id 接收用户id
     * @return void
     */
    public function authorByToId($to_id)
    {
        if ($this->user->id > 1 && $this->user->id != $to_id) {
            $error = '操作失败！';
            throw new CommonException($error);
        }
    }

    /**
     * 消息id授权检查
     *
     * @param  int $notification_id 消息id
     * @return void
     */
    public function authorByNotificationId($notification_id)
    {
        if ($this->user->id > 1) {
            $all_set = $this->notification->getAll($this->user->id);
            if (!array_key_exists($notification_id, $all_set)) {
                $error = '操作失败！';
                throw new CommonException($error);
            }
        }
    }

    /**
     * 管理员身份检查
     *
     * @return void
     */
    public function isAdmin()
    {
        if ($this->user->id !== 0) {
            $error = '操作失败！';
            throw new CommonException($error);
        }
    }

    /**
     * 获取所有消息
     *
     * @param  int      $to_id        接收用户id
     * @param  null     $limit        获取的条数
     * @param  int|null $paginate     获取的页码
     * @param  string   $order        排序
     * @param Closure   $filter_scope 数据额外处理函数
     * @return mixed
     */
    public function getAll($to_id, $limit = null, $paginate = null, $order = 'desc', Closure $filter_scope = null)
    {
        $this->authorByToId($to_id);
        return $this->notification->getAll($to_id, $limit, $paginate, $order, $filter_scope);
    }

    /**
     * 获取某个用户未读消息的总数
     *
     * @param int     $to_id
     * @return mixed
     */
    public function countNotRead($to_id)
    {
        $this->authorByToId($to_id);
        return $this->notification->countNotRead($to_id);
    }

    /**
     * 根据消息id读取通知，标识为已读状态
     *
     * @param int $notification_id
     * @return int 影响的消息条数
     */
    public function readOne($notification_id)
    {
        $this->authorByNotificationId($notification_id);
        return $this->notification->readOne($notification_id);
    }

    /**
     * 根据接受者id读取所有消息，标识为已读状态
     *
     * @param int $to_id
     * @return int 影响的消息条数
     */
    public function readAll($to_id)
    {
        $this->authorByToId($to_id);
        return $this->notification->readAll($to_id);
    }

    /**
     * 根据消息id删除消息
     *
     * @param $notification_id
     * @return bool
     */
    public function delete($notification_id)
    {
        $this->authorByNotificationId($notification_id);
        return $this->notification->delete($notification_id);
    }

    /**
     * 删除某个接受者用户的所有消息
     *
     * @param int $to_id
     * @return bool
     */
    public function deleteAll($to_id)
    {
        $this->authorByToId($to_id);
        return $this->notification->deleteAll($to_id);
    }


    /**
     * 获取消息分类列表
     *
     * @param  int|null     $where        获取的条数
     * @param  int|null     $limit        获取的条数
     * @param  int|null     $paginate     获取的页码
     * @param  string       $order        排序
     * @param  Closure      $filter_scope 数据额外处理函数
     * @return mixed
     */
    public function getListsCategory($where = null, $limit = null, $paginate = null, $order = 'desc', Closure $filter_scope = null)
    {
        $this->isAdmin();
        return $this->category->getLists($where, $limit, $paginate, $order, $filter_scope);
    }

    /**
     * 通过id查找分类
     *
     * @param int $category_id
     * @throws CommonException
     * @return mixed
     */
    public function findCategory($category_id)
    {
        $this->isAdmin();
        if (!is_numeric($category_id)) {
            return false;
        }
        return $this->category->find($category_id);
    }

    /**
     * 添加一个消息分类
     *
     * @param array $data
     * @return mixed 如果失败为false，成功则为插入的id
     */
    public function addCategory($data)
    {
        $this->isAdmin();
        return $this->category->add($data);
    }

    /**
     * 修改息分类
     *
     * @param  array $data
     * @return mixed
     */
    public function updateCategory(array $data)
    {
        $this->isAdmin();
        if (!is_numeric($data['id'])) {
            return false;
        }
        return $this->category->update($data);
    }

    /**
     * 通过id删除分类
     *
     * @param array|int $category_id 需要删除的id数组或数字
     * @return mixed
     */
    public function deleteCategory($category_id)
    {
        $this->isAdmin();
        return $this->category->delete($category_id);
    }


    /**
     * 获取广播消息列表
     *
     * @param  int|null     $where        获取的条数
     * @param  int|null     $limit        获取的条数
     * @param  int|null     $paginate     获取的页码
     * @param  string       $order        排序
     * @param  Closure      $filter_scope 数据额外处理函数
     * @return mixed
     */
    public function getListsBroadcast($where = null, $limit = null, $paginate = null, $order = 'desc', Closure $filter_scope = null)
    {
        $this->isAdmin();
        return $this->broadcast->getLists($where, $limit, $paginate, $order, $filter_scope);
    }

    /**
     * 添加一个广播消息
     *
     * @param array $data
     * @return mixed 如果失败为false，成功则为插入的id
     */
    public function addBroadcast($data)
    {
        $this->isAdmin();
        return $this->broadcast->add($data);
    }


    /**
     * 发送单条消息
     *
     * @param array $info
     * @return mixed
     */
    public function sendOne(array $info)
    {
        $info = (count($info) > 0) ? $info : $this->toArray();

        $notificationSent = $this->sender->sendOne($info);

        $this->refresh();

        return $notificationSent;
    }

    /**
     * 发送多条消息
     *
     * @param array $info
     * @return mixed
     */
    public function sendMultiple(array $info)
    {
        // 当前只能管理员发
        $this->isAdmin();

        $info = (count($info) > 0) ? $info : $this->toArray();

        $notificationsSent = $this->sender->sendMultiple($info);

        $this->refresh();

        return $notificationsSent;
    }


    /**
     * 动态设置构建数组的属性
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * 获取构建数组的属性
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }



}
