<?php

/**
 * Model
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

class DbModel
{
    protected $table   = '';
    protected $primary = 'id';
    public static $_find_by_id;
    protected $field = [];
    protected $validate_add = [];
    protected $validate_edit = [];
    protected $unique_message = [];
    protected $ignore_after_find_hook;
    protected $has_one;
    protected $has_many;
    public $ignore_relation = true;
    public $_relation_with = [];
    public static $init; 
    /**
     * 字段映射 名字=>数据库中字段名
     * 仅支持find方法
     */
    protected $field_ln = [];
    /*
    https://github.com/vlucas/valitron
    */
    protected $validate = [];
    public function __construct()
    {
        $this->init();
    }
    /**
     * 取表名
     */
    public function get_table_name()
    {
        return $this->table;
    }
    /**
     * INIT
     */
    protected function init() {}
    /**
     * 查寻前
     */
    public function beforeFind(&$where) {}
    /**
     * 查寻后
     */
    public function afterFind(&$data)
    {
        $this->ignore_after_find_hook[$this->table . $data['id']] = true;
    }
    /**
     * model instance
     */
    public static function model()
    {
        static::$init = new static();
        return static::$init;
    }
    /**
     * 开启关联查寻
     */
    public function relation($opt = [])
    {
        $this->ignore_relation = false;
        $this->_relation_with = $opt;
        return $this;
    }
    /**
     * 仅用于分于
     */
    public function resetRelation()
    {
        $this->ignore_relation = true;
    }
    /**
     * 处理关联
     */
    public function doRelation(&$data)
    {
        $_relation_with = $this->_relation_with;
        if (!$this->ignore_relation) {
            $has_many = $this->has_many;
            if ($has_many) {
                foreach ($has_many as $k => $v) {
                    $cls = "\\" . $v[0];
                    $key = $v[1];
                    $pk = $v[2] ?: 'id';
                    $option = $v[3] ?: [];
                    $val = $data[$pk];
                    if ($key && $key  && $val) {
                        $where = $option;
                        $where[$key] = $val;
                        if ($_relation_with && in_array($k, $_relation_with)) {
                            unset($_relation_with[array_search($k, $_relation_with)]);
                            $data[$k] = $cls::model()->relation($_relation_with)->find($where);
                        } else {
                            $data[$k] = $cls::model()->find($where);
                        }
                    }
                }
            }
            $has_one = $this->has_one;
            if ($has_one) {
                foreach ($has_one as $k => $v) {
                    $cls = "\\" . $v[0];
                    $key = $v[1];
                    $pk = $v[2] ?: 'id';
                    $option = $v[3] ?: [];
                    $val = $data[$key];
                    if ($key && $key  && $val) {
                        $where = $option;
                        $where[$pk] = $val;
                        if ($_relation_with && in_array($k, $_relation_with)) {
                            $data[$k] = $cls::model()->relation($_relation_with)->find($where, 1);
                        } else {
                            $data[$k] = $cls::model()->find($where, 1);
                        }
                    }
                }
            }
        }
    }
    /**
     * 查寻后
     */
    public function afterFindInner(&$data)
    {
        $ln = $this->field_ln;
        if ($ln) {
            $data['_has_ln'] = true;
            foreach ($ln as $k => $v) {
                if ($data[$v]) {
                    $data[$k] = $data[$v];
                }
            }
        }
    }

    /**
     * 写入数据前
     */
    public function beforeInsert(&$data)
    {
        $validate = $this->validate_add ?: $this->validate;
        if ($this->field && $validate) {
            $unique = $validate['unique'];
            unset($validate['unique']);
            $vali = validate($this->field, $data, $validate);
            if ($vali) {
                json($vali);
            }
            if ($unique) {
                foreach ($unique as $i => $v) {
                    $where = [];
                    $f1 = "";
                    foreach ($v as $f) {
                        $where[$f] = $data[$f];
                        if (!$f1) {
                            $f1 = $f;
                        }
                    }
                    $res = $this->find($where);
                    if ($res) {
                        json_error(['msg' => $this->unique_message[$i] ?: '记录已存在', 'key' => $f1]);
                    }
                }
            }
        }
    }
    /**
     * 写入数据后
     */
    public function afterInsert($id) {}

    /**
     * 更新数据前
     */
    public function beforeUpdate(&$data, $where)
    {
        $id = $where[$this->primary];
        $validate = $this->validate_edit ?: $this->validate;
        if ($this->field && $validate) {
            $unique = $validate['unique'];
            unset($validate['unique']);
            $vali  = validate($this->field, $data, $validate);
            if ($vali) {
                json($vali);
            }
            if ($unique) {
                foreach ($unique as $i => $v) {
                    $con = [];
                    $f1 = "";
                    foreach ($v as $f) {
                        $con[$f] = $data[$f];
                        if (!$f1) {
                            $f1 = $f;
                        }
                    }
                    $res = $this->find($con, 1);
                    if ($res && $res[$this->primary] != $id) {
                        json_error(['msg' => $this->unique_message[$i] ?: '记录已存在', 'key' => $f1]);
                    }
                }
            }
        }
    }
    /**
     * 更新数据后
     */
    public function afterUpdate($row_count, $data, $where) {}
    /**
     * 删除前
     */
    public function beforeDelete(&$where) {}
    /**
     * 删除后
     */
    public function afterDelete($where) {}

    /**
     * 更新数据
     */
    public function update($data, $where = '', $ignore_hook = false)
    {
        if (!$where) {
            return false;
        }
        $new_data = [];
        $this->_where($where);
        if (!$ignore_hook) {
            if($where['id']){
                $new_data['id'] = $where['id'];
            }
            $this->beforeUpdate($new_data, $where);
            $this->beforeSave($new_data, $where);
        }
        $row_count = db_update($this->table, $data, $where);
        if (!$ignore_hook) {
            $this->afterUpdate($new_data, $where);
            $this->afterSave($new_data, $where);
        }
        return $row_count;
    }
    /**
     * 写入数据
     */
    public function insert($data, $ignore_hook = false)
    {
        if (!$ignore_hook) {
            $this->beforeInsert($data);
            $this->beforeSave($data);
        }
        $data_db = db_allow($this->table, $data);
        if (!$data_db) {
            return false;
        }
        $id = db_insert($this->table, $data_db);
        if (!$ignore_hook) {
            $this->afterInsert($id);
            $data_db['id'] = $id;
            $this->afterSave($data_db);
        }
        return $id;
    }
    /**
     * 批量写入数据
     */
    public function inserts($data, $ignore_hook = false)
    {
        $new_data = [];
        foreach ($data as &$v) {
            if (!$ignore_hook) {
                $this->before_insert($v);
            }
            $allow_data = db_allow($this->table, $v);
            if ($allow_data) {
                $new_data[] = $allow_data;
            }
        }
        if (!$new_data) {
            return false;
        }
        db()->insert($this->table, $new_data);
        return true;
    }
    /**
     * 分页
     */
    public function pager($join, $columns = null, $where = null, $ignore_hook = false)
    {
        if ($join['select']) {
            $columns = $join;
            unset($columns['select']);
            $join = $join['select'];
        }
        $this->_where($where);
        $all =  db_pager($this->table, $join, $columns, $where);
        if ($all['data']) {
            foreach ($all['data'] as &$v) {
                $this->doRelation($v);
                $this->afterFindInner($v);
                if (!$ignore_hook) {
                    $this->afterFind($v);
                }
            }
        }
        $this->resetRelation();
        return $all;
    }
    /**
     * SUM
     */
    public function sum($filed, $where = '')
    {
        $this->_where($where);
        return db_get_sum($this->table, $filed, $where);
    }
    /**
     * COUNT
     */
    public function count($where = '')
    {
        $this->_where($where);
        return db_get_count($this->table, $this->primary, $where);
    }
    /**
     * MAX
     */
    public function max($filed, $where = '')
    {
        $this->_where($where);
        return db_get_max($this->table, $filed, $where);
    }
    /**
     * MIN
     */
    public function min($filed, $where = '')
    {
        $this->_where($where);
        return db_get_min($this->table, $filed, $where);
    }
    /**
     * AVG
     */
    public function avg($filed, $where = '')
    {
        $this->_where($where);
        return db_get_avg($this->table, $filed, $where);
    }
    /**
     * 忽略HOOK 删除数据
     */
    public function forceDelete($where)
    {
        return $this->del($where, true);
    }
    /**
     * 忽略HOOK 删除数据
     */
    public function forceDel($where)
    {
        return $this->del($where, true);
    }
    /**
     * 删除数据
     */
    public function delete($where = '', $ignore_hook = false)
    {
        return $this->del($where, $ignore_hook);
    }
    /**
     * DEL
     */
    public function del($where = '', $ignore_hook = false)
    {
        $this->_where($where);
        if (!$ignore_hook) {
            $this->beforeDelete($where);
        }
        if (!$where) {
            return false;
        }
        $res = db_del($this->table, $where);
        if (!$ignore_hook) {
            $this->afterDelete($where);
        }
        return $res;
    }
    /**
     * 原生
     * select(['@phone']) distinct
     */
    public function select($join, $columns = null, $where = null)
    {
        $res = medoo_db()->select($this->table, $join, $columns, $where);
        return $res;
    }
    /**
     * 查寻一条记录
     */
    public function findOne($where = '', $ignore_hook = false)
    {
        return $this->find($where, 1, false, $ignore_hook);
    }
    /**
     * 根据ID查寻
     */
    public function findById($id, $ignore_hook = false)
    {
        $data = self::$_find_by_id[$id];
        if ($data) {
            return $data;
        } else {
            self::$_find_by_id[$id] = $data = $this->findOne($id, $ignore_hook = false);
            return $data;
        }
    }
    /**
     * 查寻多条记录
     */
    public function findAll($where = '', $ignore_hook = false)
    {
        return $this->find($where, '', false, $ignore_hook);
    }
    /**
     * 查寻记录
     */
    public function find($where = '', $limit = '', $use_select = false, $ignore_hook = false)
    {
        $data = $this->_find($where, $limit, $use_select, $ignore_hook);
        $this->resetRelation();
        return $data;
    }
    /**
     * 查寻记录
     */
    protected function _find($where = '', $limit = '', $use_select = false, $ignore_hook = false)
    {
        $select = "*";
        if ($where && is_array($where)) {
            $select = $where['select'] ?: "*";
            unset($where['select']);
        }
        if (!is_array($where) && $where) {
            $limit = 1;
        }
        $this->_where($where);
        if ($limit) {
            $where['LIMIT'] = $limit;
        }
        $this->beforeFind($where);
        $ln = $this->field_ln;
        if ($use_select) {
            foreach ($where as $k => $v) {
                if (is_string($v) && substr($v, 0, 1) == '@') {
                    $find = substr($v, 1);
                    if ($ln && $ln[$find]) {
                        $where[$k] = "@" . $ln[$find];
                    }
                }
                if (is_object($v)) {
                    $vv = $v->value;
                    if ($vv && is_string($vv) && strpos($vv, 'DISTINCT') !== false) {
                        preg_match_all("/<(.*)>/", $vv, $matches);
                        $a = $matches[0];
                        $b = $matches[1];
                        if ($a && $b) {
                            foreach ($b as $k_b => $b1) {
                                if ($ln[$b1]) {
                                    $vv = str_replace($a[$k_b], $ln[$b1], $vv);
                                }
                            }
                            $where[$k]->value = $vv;
                        }
                        $use_select = true;
                    }
                }
            }
        }
        if ($limit && $limit == 1) {
            if ($use_select) {
                $res = $this->select($where);
            } else {
                $res = db_get_one($this->table, $select, $where);
            }
            if (is_array($res)) {
                $this->doRelation($res);
                $this->afterFindInner($res);
                if (!$ignore_hook) {
                    if (is_array($res) && !$this->ignore_after_find_hook[$this->table . $res['id']]) {
                        $this->afterFind($res);
                    }
                }
            }
        } else {
            if ($use_select) {
                $res = $this->select($where);
            } else {
                $res = db_get($this->table, $select, $where);
            }
            foreach ($res as &$v) {
                if (is_array($v)) {
                    $this->doRelation($v);
                    $this->afterFindInner($v);
                    if (!$ignore_hook) {
                        if (is_array($v) && !$this->ignore_after_find_hook[$this->table . $v['id']]) {
                            $this->afterFind($v);
                        }
                    }
                }
            }
        }
        return $res;
    }

    protected function _where(&$where)
    {
        if ($where && !is_array($where)) {
            $where = [$this->primary => $where];
        }
        if (!$where) {
            $where = [];
        }
        $ln = $this->field_ln;
        if ($ln) {
            foreach ($where as $k => $v) {
                if (strpos($k, '[') !== false) {
                    $k1 = substr($k, 0, strpos($k, '['));
                    $k2 = substr($k, strpos($k, '['));
                    if ($ln[$k1]) {
                        unset($where[$k]);
                        $where[$ln[$k1] . $k2] = $v;
                    }
                } elseif ($ln[$k]) {
                    unset($where[$k]);
                    $where[$ln[$k]] = $v;
                }
            }
        }
    }

    /**
     * 向上取递归
     * 如当前分类是3，将返回 123所有的值
     * $arr = treeUp($v['catalog_id'],true);
     * foreach($arr as $vv){
     *   $title[] = $vv['title'];
     * }
     * id pid
     * 1  0
     * 2  1
     * 3  2
     */
    public function treeUp($id, $is_frist = true)
    {
        static $_data;
        if ($is_frist) {
            $_data = [];
        }
        $end = $this->find(['id' => $id], 1);
        $_data[] = $end;
        if ($end['pid'] > 0) {
            $this->treeUp($end['pid'], false);
        }
        return array_reverse($_data);
    }
    /**
     * 递归删除
     */
    public function treeDel($id = '', $where = [])
    {
        if ($where) {
            $catalog = $this->find($where);
        }
        $all = array_to_tree($catalog, $pk = 'id', $pid = 'pid', $child = 'children', $id);
        if ($id) {
            $this->delete(['id' => $id]);
        }
        if ($all) {
            $this->loopDeleteTree($all);
        }
    }
    /**
     * 数组转成tree
     */
    public function array_to_tree($new_list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0, $my_id = '')
    {
        $list = array_to_tree($new_list, $pk, $pid, $child, $root, $my_id);
        $list = array_values($list);
        return $list;
    }
    /**
     * 向下递归
     */
    public function getTreeId($id, $where = [], $get_field = 'id')
    {
        $list = $this->find($where);
        $tree = array_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $id);
        $tree[] = $this->find(['id' => $id], 1, false, true);
        $all = $this->loopTreeDownInner($tree, $get_field, $is_frist = true);
        return $all;
    }
    /**
     * 内部实现
     */
    protected function loopDeleteTree($list)
    {
        foreach ($list as $v) {
            $this->delete(['id' => $v['id']]);
            if ($v['children']) {
                $this->loopDeleteTree($v['children']);
            }
        }
    }
    /**
     * 内部实现
     */
    protected function loopTreeDownInner($all, $get_field, $is_frist = false)
    {
        static $_data;
        if ($is_frist) {
            $_data = [];
        }
        foreach ($all as $v) {
            $_data[] = $v[$get_field];
            if ($v['children']) {
                $this->loopTreeDownInner($v['children'], $get_field);
            }
        }
        return $_data;
    }
}
