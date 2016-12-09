<?php
// +----------------------------------------------------------------------
// | 系统消息分类管理接口
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Contracts;

/**
 * 系统消息分类管理接口
 *
 * 负责处理系统消息分类
 */
interface CategoryInterface
{
    /**
     * 通过标识查找数据
     *
     * @param string $name
     * @return mixed
     */
    public function findByName($name);

    /**
     * 通过标识数组查找
     *
     * @param array $name
     * @return mixed
     */
    public function findByNames(array $name);

    /**
     * 通过id查找数据
     *
     * @param int $category_id
     * @return mixed
     */
    public function find($category_id);

    /**
     * 添加分类
     *
     * @param array $info
     * @return mixed
     */
    public function add($info);

    /**
     * 通过id删除分类
     *
     * @param array|int $category_id 需要删除的id数组或数字
     * @return mixed
     */
    public function delete($category_id);

    /**
     * 通过id修改分类
     *
     * @param  array $data
     * @return mixed
     */
    public function update(array $data);

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
    public function getLists($where = null, $limit = null, $paginate = null, $order = 'desc', Closure $filter_scope = null);

}
