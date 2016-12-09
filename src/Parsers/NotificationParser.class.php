<?php
// +----------------------------------------------------------------------
// | 消息解析处理
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Parsers;

use Common\Library\UserNotifications\Exceptions\CommonException;
use Common\Library\UserNotifications\Contracts\CategoryInterface;
use Common\Library\UserNotifications\Contracts\BroadcastInterface;

/**
 * 消息解析
 *
 * 结合消息分类类型的标题和内容解析消息
 */
class NotificationParser
{
    /**
     * 消息分类指定的动态标识规则
     */
    const RULE = '/\{@(.+?)@\}/';

    /**
     * 是否严格的检查模式匹配
     *
     * @var bool
     */
    protected static $strictMode = false;

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
     * 初始化
     * @return void
     */
    public function __construct(CategoryInterface $category, BroadcastInterface $broadcast)
    {
        $this->category = $category;
        $this->broadcast = $broadcast;

        // 数据量不大，获取全部集合
        $this->category_all = $this->category->getLists();
        $this->broadcast_all = $this->broadcast->getLists();
    }

    /**
     * 根据消息分类解析消息
     *
     * @todo 分类集合变动少；如果分类集合数据量大需要在不同类型控制类添加缓存，然后每个单独获取
     * @param $item
     * @return string
     */
    public function parse($item)
    {
        // 不同的类型的集合
        $cate_all = $item['category'] == 0 ? $this->category_all : $this->broadcast_all;
        $cate = $cate_all[$item['category_id']];

        $item['extra_title'] = $this->parseString($cate['title'],$this->extraToArray($item['extra_title']));
        $item['extra_content'] = $this->parseString($cate['content'],$this->extraToArray($item['extra_content']));

        return $item;
    }

    /**
     * 根据动态标识解析字符串
     *
     * @param string $body 需要解析的字符串
     * @param string $value 实际的值
     * @return mixed
     */
    protected function parseString($body, $value)
    {
        $specialValues = $this->getValues($body);

        if (count($specialValues) > 0) {
            // 目前只解析用户的
            $specialValues = array_filter($specialValues, function ($value) {
                return substr($value, 0, 5) == 'user.';
            });

            foreach ($specialValues as $replacer) {
                $replace = $value[$replacer];
                // 严格模式下，消息里面没有规定的动态数据会抛出异常
                if (empty($replace) && static::$strictMode) {
                    $error = "模式 $replacer 不匹配，请检查消息或消息分类";
                    throw new CommonException($error);
                }
                $body = $this->replaceBody($body, $replace, $replacer);
            }
        }

        return $body;
    }


    /**
     * 严格检测模式设置
     *
     * @param bool|true $set
     */
    public static function setStrictExtra($set = true)
    {
        static::$strictMode = $set;
    }

    /**
     * 获取动态标识
     *
     * @param $body
     * @return mixed
     */
    protected function getValues($body)
    {
        $values = [];
        preg_match_all(self::RULE, $body, $values);

        return $values[1];
    }

    /**
     * 转换数据类型到数组
     *
     * @param $extra
     * @return array|mixed
     */
    protected function extraToArray($extra)
    {
        if ($this->isJson($extra)) {
            $extra = json_decode($extra, true);
        }
        return $extra;
    }

    /**
     * 解析动态值
     *
     * @param $body
     * @param $replacer
     * @param $valueMatch
     * @return mixed
     */
    protected function replaceBody($body, $valueMatch, $replacer)
    {
        $body = str_replace('{@'.$replacer.'@}', $valueMatch, $body);

        return $body;
    }

    /**
     * 检查值是否为json值
     *
     * @param $value
     * @return bool
     */
    protected function isJson($value)
    {
        if (! is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
