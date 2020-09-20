<?php
/**
 * 把淘宝的业务，整合到一个类中
 * 
 * [拆单]保存淘宝平台的原始属性值
 * 
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Tboid extends Abstracts
{
    public function convert($ordersdf)
    {
        $tbsdf    = array();
        
        //[拆单]保存淘宝平台的原始属性值
        if($ordersdf['shop_type'] == 'taobao' && !empty($ordersdf['order_objects']))
        {
            $tbsdf['order_id']    = $ordersdf['order_id'];
            $tbsdf['order_bn']    = $ordersdf['order_bn'];
            $tbsdf['shop_type']   = $ordersdf['shop_type'];
            $tbsdf['order_objects'] = $ordersdf['order_objects'];
        }
        
        return $tbsdf;
    }
    
    
    
    
    
    
    /**
     * 订单完成后处理
     *
     * @return void
     * @author
     **/
    public function postCreate($order_id, $tbsdf)
    {
        //执行保存属性值
        kernel::single('ome_order_split')->hold_order_delivery($tbsdf);
    }
}