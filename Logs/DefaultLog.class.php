<?php
// +----------------------------------------------------------------------
// | 通用日志处理
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Logs;

use Think\Log;

/**
 * 通用日志处理
 */
class DefaultLog
{
    /**
     * 日志文件夹名
     *
     * @var string
     */
    const NAME = 'UserNotifications';

    /**
     * 错误日志记录
     *
     * @param string $info 日志信息
     * @return null
     */
    public function errorLog($info)
    {
        $log_path = realpath(LOG_PATH) . '/'. self::NAME .'/' . date('y_m_d') . '.log';
        Log::write($info, 'WARN', '', $log_path);
        return;
    }
}
