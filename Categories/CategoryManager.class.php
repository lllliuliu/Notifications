<?php
// +----------------------------------------------------------------------
// | 系统消息分类管理
// +----------------------------------------------------------------------
// | Author: lllliuliu <lllliuliu@163.com>
// +----------------------------------------------------------------------
namespace Common\Library\UserNotifications\Categories;

use Common\Library\UserNotifications\Contracts\CategoryInterface;
use Common\Library\UserNotifications\Exceptions\CommonException;
use Common\Library\UserNotifications\Models\CategoryModel;

/**
 * 系统消息管理类
 *
 * 消息分类处理
 */
class CategoryManager
{
    /**
     * 数据库操作模型
     *
     * @var Think/Model
     */
    protected $category_model;

    /**
     * 构造函数
     *
     * @param  category_model $category_model 注入数据库操作类
     * @return void
     */
    public function __construct(CategoryModel $category_model)
    {
         $this->category_model = $category_model;
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
        $query = $this->category_model->order('id ' . $order);

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
     * 通过名字查找数据
     *
     * @param $name
     * @throws CommonException
     * @return mixed
     */
    public function findByName($name)
    {
        $category = $this->category_model->where(['name' => $name])->find();

        if (empty($category)) {
            $error = '分类没有找到';
            throw new CommonException($error);
        }

        return $category;
    }

    /**
     * 通过标识数组查找
     *
     * @param $name
     * @throws CommonException
     * @return mixed
     */
    public function findByNames(array $name)
    {
        return;
    }

    /**
     * 通过id查找分类
     *
     * @param int $category_id
     * @throws CommonException
     * @return mixed
     */
    public function find($category_id)
    {
        $category = $this->category_model->where(['id' => $category_id])->find();

        if (empty($category)) {
            $error = '分类没有找到';
            throw new CommonException($error);
        }

        return $category;
    }

    /**
     * 添加分类数据
     *
     * @param  array data
     * @return mixed
     */
    public function add($data)
    {
        if($this->category_model->create($data)){
            $result = $this->category_model->add();
        } else {
            $result = $this->category_model->getError();
        }

        return $result;
    }

    /**
     * 通过id删除分类
     *
     * @param array|int $category_id 需要删除的id数组或数字
     * @return mixed
     */
    public function delete($category_id)
    {
        $category_id = is_array($category_id) ? implode(',', $category_id) : $category_id;
        return $this->category_model->delete($category_id);
    }

    /**
     * 通过标识删除分类
     *
     * @param $name
     * @return mixed
     */
    public function deleteByName($name)
    {
        return $this->category_model->where(['name' => $name])->delete();
    }

    /**
     * 修改分类
     *
     * @param  array $data
     * @return mixed
     */
    public function update(array $data)
    {
        if($this->category_model->create($data)){
            $result = $this->category_model->save();
        } else {
            $result = $this->category_model->getError();
        }

        return $result;
    }
}
