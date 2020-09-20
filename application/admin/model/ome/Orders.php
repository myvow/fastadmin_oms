<?php 
/**
 * 订单Model类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\model\ome;

use app\admin\model\ome\Abstracts;

class Orders extends Abstracts
{
    //当前模型名称
    protected $name = 'ome_orders';
    
    //数据表主键
    protected $pk = 'order_id';
    
    /**
     * [自定义]默认排序方式
     */
    protected $_order_by = 'order_id DESC';
    
    /**
     * [自定义]关联数据库表查询
     * 
     * @todo：数据库表名对应Model对象名
     */
    protected $_has_many = [
            'order_objects' => 'OrderObjects',
            'order_items' => 'OrderItems',
            'order_extend' => 'OrderExtend',
    ];
    
    
    /**
     * 获取关联订单商品明细
     */
    public function orderObjects()
    {
        return $this->hasOne('OrderObjects', 'order_id');
    }
    
    /**
     * 获取关联订单货品明细
     */
    public function orderItems()
    {
        return $this->hasOne('OrderItems', 'order_id');
    }
    
}
