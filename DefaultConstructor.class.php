<?php
// +----------------------------------------------------------------------
// | 使用容器构造消息处理类
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications;

use Common\Library\UserNotifications\Container;
use Common\Library\UserNotifications\UserNotifications;
use Common\Library\UserNotifications\Exceptions\CommonException;

use Common\Library\UserNotifications\Contracts\NotificationDBInterface;
use Common\Library\UserNotifications\Contracts\NotificationCacheInterface;
use Common\Library\UserNotifications\Contracts\NotificationInterface;
use Common\Library\UserNotifications\Contracts\SenderInterface;
use Common\Library\UserNotifications\Contracts\CategoryInterface;
use Common\Library\UserNotifications\Contracts\BroadcastInterface;
use Common\Library\UserNotifications\Contracts\StoreNotificationInterface;

use Common\Library\UserNotifications\Models\NotificationModel;
use Common\Library\UserNotifications\Models\CategoryModel;
use Common\Library\UserNotifications\Models\BroadcastModel;

use Common\Library\UserNotifications\Notifications\NotificationCache;
use Common\Library\UserNotifications\Notifications\NotificationManager;
use Common\Library\UserNotifications\Notifications\NotificationRepository;
use Common\Library\UserNotifications\Categories\CategoryManager;
use Common\Library\UserNotifications\Broadcast\BroadcastManager;
use Common\Library\UserNotifications\Senders\SenderManager;

use Common\Library\UserNotifications\Parsers\NotificationParser;

use Think\Cache;
use Think\Cache\Driver\Redis as MRedis;
/**
 * 消息处理构造器
 *
 * 使用容器绑定各种接口的实现，预设参数等，构造消息处理实例
 */
class DefaultConstructor
{
    /**
     * 实例数组
     *
     * @var array
     */
     private static $_instance;

    /**
     * 初始化
     *
     * @param array|object $user 当前用户对象或数组
     * @return void
     */
    public function __construct($user){
        $this->c = new Container();

        // 注册绑定
        $this->register();

        // 绑定用户
        $this->user($user);
    }

    /**
     * 注册消息
     *
     * @param array|object $user 当前用户对象或数组
     * @return void
     */
    protected function user($user)
    {
        $this->c->setParameter('UserNotifications.user', $user);
    }

    /**
     * 注册绑定
     *
     * @return void
     */
    public function register()
    {
        $this->userNotification();
        $this->notification();
        $this->categories();
        $this->broadcast();
        $this->senders();
    }

    /**
     * 实例化全局消息处理类
     *
     * @return void
     */
    public function make()
    {
        // 如果要前后台通用，或者互相通讯，需要修改这里，加入用户类型
        // $map =  $user->type . $user->id;
        $map = $user->id;
        if (isset(self::$_instance[$map])) {
            return self::$_instance[$map];
        }

        self::$_instance[$map] =$this->c->make(UserNotifications::class);
        return self::$_instance[$map];
    }

    /**
     * 注册用户消息处理入口类
     *
     * @return void
     */
    protected function userNotification()
    {
        // 因为有额外参数user，所以注册入口类实例化脚本
        $this->c->singleton(UserNotifications::class, function(Container $c) {
            return new UserNotifications(
                $c->make(NotificationInterface::class),
                $c->make(SenderInterface::class),
                $c->make(CategoryInterface::class),
                $c->make(BroadcastInterface::class),
                $c->getParameter('UserNotifications.user')
            );
        });
    }

    /**
     * 注册消息
     *
     * @return void
     */
    protected function notification()
    {
        // 绑定消息缓存处理接口的实现
        $this->c->setParameter('MRedis.options', C('UserNotification.redis'));
        $this->c->singleton(Cache::class, function(Container $c) {
            return new MRedis($c->getParameter('MRedis.options'));
        });

        $this->c->singleton(NotificationCacheInterface::class, NotificationCache::class);

        // 绑定消息数据库操作接口的实现
        $this->c->singleton(NotificationModel::class, function(Container $c) {
            return new NotificationModel();
        });
        $this->c->singleton(NotificationDBInterface::class, NotificationRepository::class);

        // 解析类的实现
        $this->c->singleton(NotificationParser::class, function(Container $c) {
            return new NotificationParser(
                $c->make(CategoryInterface::class),
                $c->make(BroadcastInterface::class)
            );
        });

        // 绑定消息接口的实现
        $this->c->bind(NotificationInterface::class, NotificationManager::class);
    }

    /**
     * 注册系统消息分类管理类
     *
     * @return void
     */
    protected function categories()
    {
        // 绑定消息分类接口的实现
        $this->c->singleton(CategoryModel::class, function(Container $c) {
            return new CategoryModel();
        });
        $this->c->singleton(CategoryInterface::class, CategoryManager::class);
    }

    /**
     * 注册广播消息管理类
     *
     * @return void
     */
    protected function broadcast()
    {
        // 绑定广播消息接口的实现
        $this->c->singleton(BroadcastModel::class, function(Container $c) {
            return new BroadcastModel();
        });
        $this->c->singleton(BroadcastInterface::class, BroadcastManager::class);
    }

    /**
     * 注册发送者
     *
     * @return void
     */
    protected function senders()
    {
        // 绑定发送者接口实现，不支持父接口绑定子接口的实现
        $this->c->bind(StoreNotificationInterface::class, NotificationRepository::class);
        $this->c->bind(SenderInterface::class, SenderManager::class);
    }






}
