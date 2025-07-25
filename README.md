# 数据库操作

对 [Medoo](https://github.com/catfan/Medoo) `v2.1.12` 再封装，让操作更简单。

且独立维护`Medoo.php`，不再同步官方代码，如有BUG提交ISSUE

COPY FROM [thefunpower/db_medoo](https://github.com/thefunpower/db_medoo)


在原类`Medoo.php`上添加
~~~
$where['FOR UPDATE'] => TRUE 
$where['user_name[FIND_IN_SET]']=>(string)10 
$where['user_name[RAW]'] => '[a-z0-9]*'

~~~

安装 

~~~
composer require nexophp/db
~~~


配置

~~~
/**
* 数据库连接
*/
$medoo_db_config['db_name'] = 'test'; 
$medoo_db_config['db_host'] = '127.0.0.1';
$medoo_db_config['db_user'] = 'root';
$medoo_db_config['db_pwd']  = '111111';
$medoo_db_config['db_port'] = 3306; 
include __DIR__.'/nexophp/db/boot.php';
~~~

读库

~~~
// 从库读库 
$medoo_db_config['read_db_host'] = '127.0.0.1';
$medoo_db_config['read_db_name'] = ['read2','read1'];
$medoo_db_config['read_db_user'] = 'root';
$medoo_db_config['read_db_pwd'] = '111111';
$medoo_db_config['read_db_port'] = 3306;  
~~~

在使用只读库时

~~~
db_active_read();
~~~

切回默认数据库
~~~
db_active_default();
~~~

## 行锁
需要放在事务中
~~~
db_for_update($table,$id);
~~~

## 加+
~~~
"age[+]" => 1
~~~


## $where条件

~~~
'user_name[REGEXP]' => '[a-z0-9]*'
'user_name[FIND_IN_SET]'=>(string)10
'user_name[RAW]' => '[a-z0-9]*'
~~~

~~~
$where = [
	//like查寻
	'product_num[~]' => 379, 
	//等于查寻
	'product_num' => 3669, 
	//大于查寻
	'id[>]' => 1,
	'id[>=]' => 1,
	'id[<]' => 1,
	'id[<=]' => 1,
]; 
$where = []; 
$where['OR'] = [
	'product_num[~]'=>379,
	'product_num[>]'=>366,
];
$where['LIMIT'] = 10;
$where['ORDER'] = ['id'=>'DESC']; 
~~~

# OR
~~~
//(...  AND ...) OR (...  AND ...)
"OR #1" => [
    "AND #2" => $where,
    "AND #3" => $or_where
    ]
];
//(... OR  ...) AND (...  OR ...)
"AND #1" => [
    "OR #2" => $where,
    "OR #3" => $or_where
    ]
];
~~~

## where字段两个日期之间

字段是datetime类型  
~~~
$date1 = '2022-11-01';
$date2 = '2022-12-14';
db_between_date($field,$date1,$date2)
~~~

## where 两个月份之间

~~~
$date1 = '2022-11';
$date2 = '2022-12';
db_between_month($field,$date1,$date2
~~~

## 查寻一条记录

~~~
$res = db_get_one("products","*",$where);
$res = db_get_one("products",$where);
~~~

## 所有记录

~~~
$res = db_get("products","*",$where);
$res = db_get("products",$where);
~~~

## 分页

~~~
$res  = db_pager("products","*",$where);
~~~

## 使用原生方法 

原生方法将不会触发`action`

https://medoo.in/api/where

~~~
$res = db()->select("products",['id'],[]); 
~~~ 

## 查寻某个字段

~~~
$res  = db_get("qr_rule","qr_num",['GROUP'=>'qr_num']);
print_r($res); 
~~~

## 写入记录

~~~
db_insert($table, $data = [],$don_run_action = false)
~~~

## 更新记录

~~~
db_update($table, $data = [], $where = [],$don_run_action = false)
~~~

## 取最小值

~~~
db_get_min($table, $join  = "*", $column = null, $where = null)
~~~

其他一些如取最大值等

~~~
db_get_max
db_get_count
db_get_has
db_get_rand
db_get_sum
db_get_avg 
~~~

## 删除 

~~~
db_del($table, $where)
~~~


##  action 

### 写入记录前

~~~
do_action("db_insert.$table.before", $data);
do_action("db_save.$table.before", $data);
~~~ 

### 写入记录后

其中`$data`有 `id` 及 `data`

~~~
do_action("db_insert.$table.after", $action_data);
do_action("db_save.$table.after", $action_data);
~~~

数据格式

~~~
$action_data = []; 
$action_data['id']    = $id;
$action_data['data']  = $data;
~~~

### 更新记录前

~~~
do_action("db_update.$table.before", $data);
do_action("db_save.$table.before", $data);
~~~

### 更新记录后

其中`$data`有 `id`   `data` `where`
~~~
do_action("db_update.$table.after", $action_data);
do_action("db_save.$table.after", $action_data); 
~~~

数据格式

~~~
$action_data = [];
$action_data['where'] = $where; 
$action_data['id']    = $where['id'] ?: '';
$action_data['data']  = $data;
~~~

~~~
do_action("db_get_one.$table", $v); 
~~~

## 删除前

~~~
do_action("db_del.$table.before", $where);
do_action("db_del.$table.after", $where);
~~~


## 显示所有表名

~~~
show_tables($table)
~~~

## 取表中字段

~~~
get_table_fields($table, $has_key  = true)
~~~

## 返回数据库允许的数据，传入其他字段自动忽略

~~~
db_allow($table, $data)
~~~

## 显示数据库表结构，支持markdown格式

~~~
database_tables($name = null, $show_markdown = false)
~~~

## 数组排序

~~~
array_order_by($row,$order,SORT_DESC);
~~~

## 判断是json数据

~~~
is_json($data)
~~~


## SQL查寻

~~~
db_query($sql, $raw = null)
do_action("db_query", $all) 
~~~

其中`$sql`为`select * from table_name where user_id=:user_id`

`$raw` 为 `[':user_id'=>1]`




## 事务

需要`inner db`支持

~~~
db_action(function()use($data)){

});
~~~

## id锁

~~~
db_for_update($table,$id)
~~~

## 设置分页总记录数

~~~
db_pager_count($nums = null)
~~~ 

## 连表查寻

~~~
$data = db_pager("do_order",
["[><]do_mini_user" => ["uid" => "id"]],
[
    "do_order.id",
    "do_order.uid",
    "user" => [
        "do_mini_user.nickName",
        "do_mini_user.avatarUrl",
        "do_mini_user.openid",
    ]
],
$where);
~~~

## db_get复杂查寻

~~~
$lists = db_get('do_order', [ 
    'count' => 'COUNT(`id`)', // 对应 select COUNT(`id`) as count
    'total' => 'SUM(`total_fee`)',
    'date'  => "FROM_UNIXTIME(`inserttime`, '%Y-%m-%d')"
],$where); 
~~~

## field 排序
~~~
'ORDER'=>['id'=>[1,2]]
~~~

## 跨库数据库事务
调用方式
~~~
xa_db_action([
  'a'=>function(){
    echo "a<br>";
    db_insert("config",['title'=>1]);
  },
  'b'=>function(){
    echo "b<br>";
    db_insert("config",['title'=>'b']);
    //抛出异常时也会回滚
    //throw new Exception("错误");
  }
]);

$err = db_get_error();
if($err) {
    pr($err);
}
~~~

其中 `a` `b`是数据库连接

配置数据库

~~~
new_db([
  'db_host'=>"127.0.0.1",
  'db_name'=>"test1",
  'db_user'=>"root",
  'db_pwd'=>"111111",
  'db_port'=>"3306",
],'a');

new_db([
  'db_host'=>"127.0.0.1",
  'db_name'=>"test2",
  'db_user'=>"root",
  'db_pwd'=>"111111",
  'db_port'=>"3306",
],'b'); 
~~~

## 修改表名
~~~ 
add_action("db_table.a",function(&$table){
    $table = 'a_100';
});
~~~
 
###  创建分区表,自动排除已有的

~~~ 
db_struct_table_range_auto('wordpress','my_table',[
    '2023-11',
    '2023-12',
    '2024-01',
    '2024-02',
    '2024-03',
]);
~~~ 

返回创建分区SQL   

~~~
db_struct_table_range('my_table',[
    '2023-11',
    '2023-12',
    '2024-01',
],'created_at','p',true);
~~~

## 使用model

验证规则 

https://github.com/vlucas/valitron

~~~
<?php   
 
namespace model; 

class User extends \DbModel{ 
    protected $table = 'users';

    protected $field = [
        'name'  => '姓名',
        'phone' => '手机号',
        'email' => '邮件',
    ];

    protected $validate = [
        'required'=>[
            'name','phone','email',
        ],
        'email'=>[
            ['email'],
        ],
        'phonech'=>[
            ['phone']
        ],
        'unique'=>[
            ['phone',],
            ['email',], 
        ]
    ]; 

    protected $unique_message = [
        '手机号已存在',
        '邮件已存在',
    ];
    

    /**
    * 写入数据前
    */
    public function before_insert(&$data){ 
        parent::before_insert($data);
        $data['created_at'] = now();
        parent::before_insert($data);
    }
}
~~~



model事件，注意使用`parent::`

~~~
    /**
    * 查寻前
    */
    public function beforeFind(&$where){
    }
    /**
    * 查寻后
    */
    public function afterFind(&$data){
    }
    
    /**
    * 写入数据前
    */
    public function beforeInsert(&$data){
    }
    /**
    * 写入数据后
    */
    public function afterInsert($id){
    }
    
    /**
    * 更新数据前
    */
    public function beforeUpdate(&$data,$where){
    }
    /**
    * 更新数据后
    */
    public function afterUpdate($row_count,$data,$where){
    }
    /**
    * 删除前
    */
    public function beforeDelete(&$where)
    {        
    }
    /**
    * 删除后
    */
    public function afterDelete($where)
    {        
    }
~~~

字段映射

~~~
protected $field_ln = [
    'title' => 'name', 
];
~~~

`name`是数据库中真实存在的字段,`title`是自己定义了字段。

~~~
$model->find(['title[~]'=>'test']);
~~~

等同于
~~~
$model->find(['name[~]'=>'test']);
~~~

返回的记录中将同时有`name` `title`

model查询

~~~
$model->find($id) //返回一条记录 $id是int类型
$model->find(['name'=>'t'],$limit=1)  //返回一条记录
$model->find(['name'=>'t'])  //返回所有记录
~~~

关联定义

~~~
class invoice_detail extends \DbModel
{
    protected $table = 'invoice_detail';
    protected $has_one = [
        'detail_one' => [invoice::class,'invoice_id'],
    ];
    protected $has_many = [
        'product_info' => [invoice_product::class,'product_num','product_num',['LIMIT' => 2]]
    ];

    public function afterFind(&$data)
    {
        unset($data['id']);
    }
}

class invoice extends \core\sys\model\base
{
    protected $table = 'invoice';
    protected $has_many = [
        'detail' => [invoice_detail::class,'invoice_id','id',['LIMIT' => 2]]
    ];
}

class invoice_product extends \DbModel
{
    protected $table = 'invoice_products';
}

~~~

默认并不会自动查寻关联数据，如果查寻关联数据
~~~
$m = new yourmodelname();
$m->relation()->find();
~~~

insert

~~~
$model->insert($data, $ignore_hook = false)
~~~

update
~~~
$model->update($data,$where = '', $ignore_hook = false)
~~~

pager
~~~
$model->pager($join, $columns = null, $where = null, $ignore_hook = false)
~~~

sum

~~~
$model->sum($filed,$where = '')
~~~

count
~~~
$model->count($where = '')
~~~

delete
~~~
$model->del($where = '', $ignore_hook = false)
~~~

max
~~~
$model->max($filed,$where = '')
~~~

min 
~~~
$model->min($filed,$where = '')
~~~

DISTINCT 

~~~
$res = $m->find([ 
    'select'=>['@title',"name2" => db_raw("COUNT(DISTINCT <title>)"),],
    'status'=>1,
],$limit = '' ,true);
~~~

`select`数组中的`@title`是`GROUP BY`

## 数据库结构比较

生成数据库结构差量SQL

`project_base`为基础结构，其他的的`project_user_*`为需要被同步的结构

~~~
$sql = create_db_compare_sql([
    'db_host' => '127.0.0.1',
    'db_name' => 'project_base',
    'db_user' => 'root',
    'db_pwd' => '111111'
], [
    'project_user_',
], $is_like = true);
~~~

其中`$is_like`表示是否是`like`效果。

## 回到上一个连接

在SAAS平台开发时，存在数据库切换的情况。


~~~
db_active('default',true);
//这里操作数据库
db_active('main');
//这里操作数据库

//这时会回到default连接
db_active_rollback();
~~~

关键函数

~~~
db_active($name = 'default',$need_rollback_here = false)
~~~

## 表前缀

设置表前缀

~~~
db_prefix('wp_');
db_prefix('');
~~~

获取表前缀
~~~
db_prefix();
~~~

如果因为设置前缀导致有些表查寻有问题，可用`add_action`

~~~
add_action("db.table",function(&$table){});
~~~

## 统一数据库错误处理
~~~
add_action('db.err',function($err){
    //如果是字符串说明是链接失败，如果是数组说明是SQL错误
});
~~~

## model tree

数组转成tree

~~~
$model->array_to_tree($new_list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0, $my_id = '')
~~~

向上取递归

~~~
$model->get_tree_up($id, $is_frist = false)
~~~

向下递归

~~~
$model->get_tree_id($id, $where = [], $get_field = 'id')
~~~

## model分页复杂查寻
~~~
$where = [];
$select = [];
$where['GROUP'] = 'company_num';
$select[] = 'customer_name';
$select[] = 'company_num';
$select['total'] = 'COUNT(`total_num`)';
$select['amount'] = 'SUM(`total_price`)';
$data = $this->invoice->pager($select, $where);
~~~

GROUP BY 与 ORDER BY使用
~~~
$wq = $this->input['wq'];
$date = $this->input['date'];
$date_start = $date[0];
$date_end = $date[1];
$where_string = "";
$query = [];
$where = [];
$select = [];
$select[] = 'customer_name';
$select[] = 'company_num';
$select[] = '@company_num';
$select['total'] = 'COUNT(`total_num`)';
$select['amount'] = 'SUM(`total_price`)';
if($date_start) {
    $where['created_at[>=]'] = $date_start . " 00:00:01";
    $where_string .= " AND created_at >= :created_at ";
    $query[':created_at'] = $date_start . " 00:00:01";
}
if($date_end) {
    $where['created_at[<=]'] = $date_end . " 23:59:59";
    $where_string .= " AND created_at <= :created_at_1 ";
    $query[':created_at_1'] = $date_end . " 23:59:59";
}
if($wq) {
    $or['customer_name[~]'] = $wq;
    $or['company_num[~]'] = $wq;
    $where['OR'] = $or;
    $where_string .= " AND (customer_name LIKE :customer_name   OR company_num LIKE :company_num) ";
    $query[':customer_name'] = "%" . $wq . "%";
    $query[':company_num'] = "%" . $wq . "%";
}
//有GROUP BY ORDER BY时COUNT需要自行计算
$sql = "SELECT COUNT(DISTINCT(company_num)) AS total FROM invoice WHERE 1=1 " . $where_string . " LIMIT 1";
$count = db_query($sql, $query);
db_pager_count($count[0]['total']);
$where['ORDER'] = ['amount' => 'DESC'];
$where['GROUP'] = 'company_num';
$new_where = $where;
$new_where['select'] = $select;
$data = $this->invoice->pager($new_where);
~~~

## GROUP BY 多字段导致total数量不对

~~~
$group_by = "product_num,base_name";
$sql = "select count(id) as total from (select * from ".$table." GROUP BY ".$group_by.") as wms";
$res  = db_query($sql,[]);
$total = $res[0]['total']; 
$all['total'] = $total;
~~~

 