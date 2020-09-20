<?php 
/**
 * 店铺Model类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\model\ome;

use app\admin\model\ome\Abstracts;

class Shop extends Abstracts
{
    //当前模型名称
    protected $name = 'ome_shop';
    
    //数据表主键
    protected $pk = 'shop_id';
    
    /**
     * [自定义]默认排序方式
     */
    protected $_order_by = 'shop_id DESC';
    
}
