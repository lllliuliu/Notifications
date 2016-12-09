<?php
// +----------------------------------------------------------------------
// | 系统消息分类管理
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Broadcast;

use Common\Library\UserNotifications\Contracts\BroadcastInterface;
use Common\Library\UserNotifications\Exceptions\CommonException;
use Common\Library\UserNotifications\Models\BroadcastModel;

/**
 * 系统消息管理类
 *
 * 消息分类处理
 */
class BroadcastManager
{
    /**
     * 数据库操作模型
     *
     * @var Think/Model
     */
    protected $broadcast_model;

    /**
     * 构造函数
     *
     * @param  BroadcastModel $broadcast_model 注入数据库操作类
     * @return void
     */
    public function __construct(BroadcastModel $broadcast_model)
    {
         $this->broadcast_model = $broadcast_model;
    }

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
    public function getLists($where = null, $limit = null, $paginate = null, $order = 'desc', Closure $filter_scope = null)
    {
        $query = $this->broadcast_model->order('id ' . $order);

        $where && $query->where($where);

        $query_paginate = is_numeric($paginate) ? ceil($paginate) : null;
        $query_limit = is_numeric($limit) ? ceil($limit) : null;
        if (!is_null($query_limit) && !is_null($query_paginate)) {
            $query->limit($query_paginate*$query_limit, ($query_paginate*$query_limit)+$query_limit-1);
        }

        $rows = $query->select();
        if ($rows) {
            $rows = array_map($filterScope, $rows);
            $rows = $this->arrayByPrimary($rows);
        }

        return $rows;
    }

    /**
     * 解析结果数组，使用id作为结果数组的key
     *
     * @param array  $rows
     * @param string $primary 主键。默认为id
     * @return array
     */
    protected function arrayByPrimary($rows, $primary = 'id')
    {
        $news = [];
        foreach ($rows as $key => $row) {
            $news[$row[$primary]] = $row;
        }
        return $news;
    }

    /**
     * 通过id查找分类
     *
     * @param int $id
     * @throws CommonException
     * @return mixed
     */
    public function find($id)
    {
        $Broadcast = $this->broadcast_model->where(['id' => $id])->find();

        if (empty($Broadcast)) {
            $error = '广播消息没有找到';
            throw new CommonException($error);
        }

        return $Broadcast;
    }

    /**
     * 添加分类数据
     *
     * @param  array data
     * @return mixed
     */
    public function add($data)
    {
        if($this->broadcast_model->create($data)){
            $result = $this->broadcast_model->add();
        } else {
            $result = $this->broadcast_model->getError();
        }

        return $result;
    }
}
