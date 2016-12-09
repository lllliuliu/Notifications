<?php
// +----------------------------------------------------------------------
// | 构建规则
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Builder;

use InvalidArgumentException;

/**
 * 构建规则方法类
 *
 * 提供检测方法和属性，用于检测消息必须的字段，和其他一些通用方法
 */
trait BuilderRules
{
    /**
     * 消息必须的字段
     *
     * @var array
     */
    private $requiredFields = ['from_id', 'to_id', 'category_id', 'category'];

    /**
     * 字符串检查
     *
     * @param $value
     * @return bool
     */
    protected function isString($value)
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('参数必须为字符串');
        }

        return true;
    }

    /**
     * LINUX时间戳检查
     *
     * @param $value
     * @return bool | InvalidArgumentException
     */
    protected function isTimestamp($value)
    {
        if (((string) (int) $value === $value) && ($value <= PHP_INT_MAX) && ($value >= ~PHP_INT_MAX)) {
            return true;
        }

        throw new InvalidArgumentException('参数必须为LINUX时间戳');
    }

    /**
     * 数字检查
     *
     * @param $value
     * @return bool
     */
    protected function isNumeric($value)
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('参数必须为数字');
        }

        return true;
    }

    /**
     * 返回构建消息的必须字段数组
     *
     * 可以通过customRequiredFields属性扩展必须字段
     *
     * @return array
     */
    public function getRequiredFields()
    {
        $require = $this->customRequiredFields ? $this->requiredFields + $this->customRequiredFields : $this->requiredFields;
        return array_unique($require);
    }

    /**
     * 检查数组的key是否包含必须字段
     *
     * @param array $array
     * @return bool
     */
    public function hasRequiredFields($array)
    {
        foreach ($this->getRequiredFields() as $field) {
            if (! array_key_exists($field, $array)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查字符串是否必须字段
     *
     * @param string $offset
     * @return bool
     */
    public function isRequiredField($offset)
    {
        return in_array($offset, $this->getRequiredFields());
    }

    /**
     * 检查是否关联数组，为josn化准备
     *
     * 必须是纯粹的关联数组
     *
     * @param array $arr
     * @return bool
     */
    protected function isReadyArrToFormatInJson(array $arr)
    {
        if (array_keys($arr) !== range(0, count($arr) - 1)) {
            return true;
        } else {
            $error = "值必须是多维关联数组";
            throw new InvalidArgumentException($error);
        }
    }

    /**
     * 检查是否多维数组
     *
     * @param $arr
     * @return bool
     */
    public function isMultidimensionalArray($arr)
    {
        $rv = array_filter($arr, 'is_array');
        if (count($rv) > 0) {
            return true;
        }

        return false;
    }

    /**
     * 检查参数是否为数组或者可以迭代的内部类
     *
     * @param $var
     * @return bool
     */
    protected function isIterable($var)
    {
        return is_array($var) || $var instanceof Traversable;
    }
}
