<?php
/**
 * 作废，不再需要
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Crm extends Abstracts
{
    public function convert($ordersdf)
    {
        $crm = array();
        if ($platform->_tgOrder && (isset($platform->_newOrder['total_amount']) || isset($platform->_newOrder['order_objects']) || isset($platform->_newOrder['consignee']) ) ) {
            $crm = array(
                'order_id'=>$platform->_tgOrder['order_id'],
            );
        }
        
        return $crm;
    }

    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id,$crm)
    {}

    /**
     * 更新后操作
     *
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$crm)
    {
        $orderItemObj   = app::get('ome')->model("order_items");
        $orderObjectObj = app::get('ome')->model("order_objects");
        $Obj_preprocess = app::get('ome')->model('order_preprocess');
    
        // 删除CRM相关记录记录(shop_goods_id=-1是， CRM赠品类型)
        $orderItemObj->delete(array('order_id'=>$order_id,'shop_goods_id'=>'-1','item_type' => 'gift'));
        $orderObjectObj->delete(array('order_id'=>$order_id,'shop_goods_id'=>'-1','obj_type' => 'gift'));
        $Obj_preprocess->delete(array('preprocess_order_id'=>$order_id,'preprocess_type'=>'crm'));

        // 重新获取CRM赠品
        kernel::single('ome_preprocess_crm')->process($order_id,$msg,1);
    }
}