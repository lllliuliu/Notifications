<?php
// +----------------------------------------------------------------------
// | 消息构建类
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Builder;

use Common\Library\UserNotifications\Builder\BuilderRules;
use Common\Library\UserNotifications\Contracts\CategoryInterface;
use Common\Library\UserNotifications\Contracts\BroadcastInterface;
use Common\Library\UserNotifications\Exceptions\CommonException;
use Closure;
use Think\Model;

/**
 * 消息构建类
 *
 * 工厂类，发送消息之前构建和验证消息，也可以构建多个消息用于发送
 */
class NotificationBuilder
{
    use BuilderRules;

    /**
     * @var string notification to store
     */
    public $date;

    /**
     * 构建数据
     *
     * @var array
     */
    protected $notifications = [];

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var NotifynderCategory
     */
    private $notifynderCategory;

    /**
     * 构造函数
     *
     * @param CategoryInterface  $category  系统分类
     * @param BroadcastInterface $broadcast 广播
     */
    public function __construct(CategoryInterface $category, BroadcastInterface $broadcast)
    {
        $this->category = $category;
        $this->broadcast = $broadcast;
    }

    /**
     * 设置发送人
     *
     * @return $this
     */
    public function from()
    {
        $from = func_get_args();

        $this->setEntityAction($from, 'from');

        return $this;
    }

    /**
     * 设置接收人
     *
     * @return $this
     */
    public function to()
    {
        $from = func_get_args();

        $this->setEntityAction($from, 'to');

        return $this;
    }

    /**
     * 设置消息url
     *
     * @param $url
     * @return $this
     */
    public function url($url)
    {
        $this->isString($url);

        $this->setBuilderData('url', $url);

        return $this;
    }

    /**
     * 设置消息超时时间
     *
     * @param $datetime
     * @return $this
     */
    public function expire($datetime)
    {
        $this->isTimestamp($datetime);
        $this->setBuilderData('expire_time', $datetime);

        return $this;
    }

    /**
     * 设置消息分类
     *
     * @param string|int $category 消息分类
     * @param string|int $category_nameorid 分类标识名/id，暂时只有系统消息有标识名
     * @return $this
     */
    public function category($category, $category_nameorid)
    {
        // 消息类型
        $allow_cate = ['category', 'broadcast'];
        $category = is_numeric($category) ? $category :  implode(array_keys($allow_cate, $category));
        if (!is_numeric($category) || !in_array($category,  range(0, count($allow_cate) - 1))) {
            throw new CommonException('消息分类参数错误');
        }
        $this->setBuilderData('category', $category);

        // 系统消息标识
        if (!is_numeric($category_nameorid)) {
            $system_cate = $this->$allow_cate[$category]->findByName($category_nameorid);
            $category_nameorid = $system_cate['id'];
        }
        $this->setBuilderData('category_id', $category_nameorid);

        return $this;
    }

    /**
     * 设置消息标题
     *
     * @param string $title 标题
     * @return $this
     */
    public function title(array $title = [])
    {
        $this->isReadyArrToFormatInJson($title);

        $jsonExtraValues = empty($title) ? '' : json_encode($title);

        $this->setBuilderData('extra_title', $jsonExtraValues);

        return $this;
    }

    /**
     * 设置消息内容
     *
     * @param string $content 内容
     * @return $this
     */
    public function content(array $content = [])
    {
        $this->isReadyArrToFormatInJson($content);

        $jsonExtraValues = empty($content) ? '' : json_encode($content);

        $this->setBuilderData('extra_content', $jsonExtraValues);

        return $this;
    }

    /**
     * 自定义消息构建值设置
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setField($key, $value)
    {
        $this->setBuilderData($key, $value);

        return $this;
    }

    /**
     * 通过匿名函数构建数组
     *
     * 构建方法的灵活性更高
     *
     * @param callable|Closure $closure
     * @return array|false
     */
    public function raw(Closure $closure)
    {
        $builder = $closure($this);

        if (! is_null($builder)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 循环构建多个消息数组
     *
     * @param  string|object $dataToIterate 迭代用数组或内部类
     * @param  Closure $builder
     * @return $this
     */
    public function loop($dataToIterate, Closure $builder)
    {
        if (! $this->isIterable($dataToIterate)) {
            throw new CommonException('必须传入数组或者可迭代的内部类');
        }
        if (count($dataToIterate) <= 0) {
            throw new CommonException('数组或可迭代类必须包含多个元素');
        }

        $notifications = [];

        $newBuilder = new self($this->category, $this->broadcast);

        foreach ($dataToIterate as $key => $data) {
            $builder($newBuilder, $data, $key);
            $notifications[] = $newBuilder->toArray();
        }

        $this->notifications = $notifications;

        return $this;
    }

    /**
     * 构建消息数组
     *
     * @return mixed
     */
    public function toArray()
    {
        $hasMultipleNotifications = $this->isMultidimensionalArray($this->notifications);

        // 单个消息处理
        if (! $hasMultipleNotifications) {
            $this->setDate();

            if ($this->hasRequiredFields($this->notifications)) {
                return $this->notifications;
            }
        }

        // 多个消息处理
        if ($hasMultipleNotifications) {
            $allow = [];

            foreach ($this->notifications as $index => $notification) {
                $allow[$index] = $this->hasRequiredFields($notification);
            }

            if (! in_array(false, $allow)) {
                return $this->notifications;
            }
        }

        $error = '字段'.implode(',', $this->getRequiredFields()).'是必须的';
        throw new CommonException($error);
    }

    /**
     * 清空消息构建数组
     */
    public function refresh()
    {
        $this->notifications = [];

        return $this;
    }


    /**
     * 设置发送和接收者数据到构建数组
     *
     * 可以传入实体数组，就是可以传入不同的用户表数据
     *
     * @param $from
     * @param $property
     * @return array
     */
    protected function setEntityAction($from, $property)
    {
        // 检查是否实体数组
        if ($this->hasEntity($from)) {
            $this->isString($from[0]);
            $this->isNumeric($from[1]);

            $this->setBuilderData("{$property}_type", $from[0]);
            $this->setBuilderData("{$property}_id", $from[1]);
        // 检查是否模型实例, TP并没有模型实例即为数据的概念
        // } elseif ($from[0] instanceof Model) {
        //     $this->setBuilderData("{$property}_type", $from[0]->getName());
        //     $this->setBuilderData("{$property}_id", $from[0]->getId());
        } else {
            // 直接传入id
            $this->isNumeric($from[0]);
            $this->setBuilderData("{$property}_id", $from[0]);
        }
    }

    /**
     * 如果传入一个数组，说明可能是传入的不同实体
     *
     * @param  array $info
     * @return bool
     */
    protected function hasEntity(array $info)
    {
        return count($info) >= 2;
    }

    /**
     * 设置时间
     */
    protected function setDate()
    {
        $this->date = $data = time();

        $this->setBuilderData('updated_at', $data);
        $this->setBuilderData('created_at', $data);
    }

    /**
     * 获取时间
     *
     * @return string
     */
    protected function getDate()
    {
        return $this->date;
    }

    /**
     * 设置构建数组
     *
     * @param $field
     * @param $data
     */
    public function setBuilderData($field, $data)
    {
        return $this->notifications[$field] = $data;
    }

    /**
     * 获取构建数组的值
     *
     * 用于__get魔术方法
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->notifications[$offset];
    }

    /**
     * 获取构建数组的值
     *
     * 只能设置必须值，如果需要设置其他值，请自定义方法
     *
     * 用于__set魔术方法
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (method_exists($this, $offset)) {
            return $this->{$offset}($value);
        }

        if ($this->isRequiredField($offset)) {
            $this->notifications[$offset] = $value;
        }
    }

}
