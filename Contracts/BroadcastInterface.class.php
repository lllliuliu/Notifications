<?php
// +----------------------------------------------------------------------
// | 广播消息管理接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

/**
 * 广播消息管理接口
 *
 * 负责处理广播消息
 */
interface BroadcastInterface
{
    /**
     * 通过id查找数据
     *
     * @param int $id
     * @return mixed
     */
    public function find($id);

    /**
     * 添加分类
     *
     * @param array $info
     * @return mixed
     */
    public function add($info);

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
    public function getLists($where = null, $limit = null, $paginate = null, $order = 'desc', Closure $filter_scope = null);

}
