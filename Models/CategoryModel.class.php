<?php
// +----------------------------------------------------------------------
// | 系统消息分类表
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------

namespace Common\Library\UserNotifications\Models;
use Think\Model;

/**
 * 系统消息分类表
 */
class CategoryModel extends Model{
    /**
     * 表名
     * @var string
     */
    protected $tableName = 'user_notification_categories';

    /* 自动验证规则 */
    protected $_validate = array(
        array('name', 'require', '标识不能为空', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
        array('name', '', '标识已经存在', self::VALUE_VALIDATE, 'unique', self::MODEL_BOTH),
        array('title', 'require', '分类类型标题不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('content', 'require', '分类类型内容不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );

    /* 自动完成 */
    protected $_auto = array(
        array('created_at', NOW_TIME, self::MODEL_INSERT),
        array('updated_at', NOW_TIME, self::MODEL_BOTH),
    );
}
