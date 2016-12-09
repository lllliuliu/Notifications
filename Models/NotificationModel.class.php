<?php
// +----------------------------------------------------------------------
// | 用户消息表
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------

namespace Common\Library\UserNotifications\Models;

use Common\Library\UserNotifications\Parsers\NotificationParser as Parser;
use Think\Model;

/**
 * 用户消息表
 */
class NotificationModel extends Model{
    /**
     * 表名
     * @var string
     */
    protected $tableName = 'user_notifications';

    /* 自动验证规则 */
    protected $_validate = array(
        array('from_id', 'number', '发送者id必须为数字', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('to_id', 'number', '接收者id必须为数字', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('category_id', 'number', '分类id必须为数字', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('read', '[0,1]', '是否阅读参数错误', self::EXISTS_VALIDATE, 'in', self::MODEL_BOTH),
        array('stack_id', 'number', '广播消息堆标识参数错误', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
    );
}
