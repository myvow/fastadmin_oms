<?php 
/**
 * items订单货品明细Model类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\model\ome;

use app\admin\model\ome\Abstracts;

class OrderExtend extends Abstracts
{
    //当前模型名称
    protected $name = 'ome_order_extend';
    
    //数据表主键
    protected $pk = 'order_id';
    
}
