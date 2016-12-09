<?php
// +----------------------------------------------------------------------
// | 广播消息表
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------

namespace Common\Library\UserNotifications\Models;
use Think\Model;

/**
 * 广播消息表
 */
class BroadcastModel extends Model{
    /**
     * 表名
     * @var string
     */
    protected $tableName = 'user_notification_broadcast';

    /* 自动验证规则 */
    protected $_validate = array(
        array('title', 'require', '分类类型标题不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('content', 'require', '分类类型内容不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );

    /* 自动完成 */
    protected $_auto = array(
        array('created_at', NOW_TIME, self::MODEL_INSERT),
        array('updated_at', NOW_TIME, self::MODEL_BOTH),
    );
}
