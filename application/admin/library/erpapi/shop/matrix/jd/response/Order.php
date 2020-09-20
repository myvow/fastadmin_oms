<?php
/**
 * JD处理类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\jd;

use app\admin\library\erpapi\shop\response\Order as Orders;

class Order extends Orders
{
    protected $_update_accept_dead_order = true;

    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function _canAccept()
    {
        if ($this->_ordersdf['store_order'] == '京仓订单') {
            $this->__apilog['result']['msg'] = '京仓订单不接收';

            return false;
        }
        
        if($this->_ordersdf['other_list']){
            foreach((array) $this->_ordersdf['other_list'] as $val){
          
                if($val['type'] == 'store' && $val['store_order'] == '京仓订单'){
                    $this->__apilog['result']['msg'] = '京仓订单不接收';
                    return false;
                }
            }
        }

        // 商户类型不是SOP
        if ($this->__channelObj->channel['addon']['type'] != 'SOP') {
            $this->__apilog['result']['msg'] = '商户类型不是SOP订单不接收';

            return false;
        }

        if ($this->_ordersdf['t_type'] == 'fenxiao' || $this->_ordersdf['order_source'] == 'taofenxiao') {
            $this->__apilog['result']['msg'] = '分销订单暂时不接收';
            return false;
        }

        return parent::_canAccept();
    }

    protected function _analysis()
    {
        parent::_analysis();

        if(!$this->_ordersdf['lastmodify']){
            $this->_ordersdf['lastmodify'] = date('Y-m-d H:i:s',time());
        }

        if (0 == bccomp($this->_ordersdf['payed'], $this->_ordersdf['total_amount'],3) && $this->_ordersdf['pay_status'] == '1' && $this->_ordersdf['custom_mark']){

            if (preg_match('/^售后返修换新/is', $this->_ordersdf['custom_mark'])){

                preg_match_all('/\d{4,18}/',$this->_ordersdf['custom_mark'],$mark);

                if ($mark[0][0] && $mark[0][1]){
                  
                    $reship_bn = $mark[0][0];
                    $relate_order_bn = $mark[0][1];
                    $this->_ordersdf['relate_order_bn'] = $relate_order_bn;
                    foreach ($this->_ordersdf['order_objects'] as $objkey=>$object) {
                        foreach ($object['order_items'] as $itemkey =>$item) {
                            $bn = $item['bn'];
                            $change_price = $this->format_change_price($relate_order_bn,$reship_bn,$item['bn']);
                           
                          
                            if ($change_price){
                                $price = $change_price['price'];
                                
                                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['price'] = $price;
                                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['amount'] = $price;
                                $this->_ordersdf['order_objects'][$objkey]['order_items'][$itemkey]['sale_price'] =$price;
                                $this->_ordersdf['total_amount'] = $this->_ordersdf['payed'] = $this->_ordersdf['cost_item'] = $price;
                                $this->_ordersdf['order_objects'][$objkey]['price'] = $price;
                                $this->_ordersdf['order_objects'][$objkey]['amount'] = $price;
                                $this->_ordersdf['order_objects'][$objkey]['sale_price'] = $price;
                            }
                            
                        }
                    }
                    //组一个支付单
                    $payments = array();
                    $payments[] = array(
                        'pay_time'  =>  $this->_ordersdf['createtime'],
                        'money'     =>  $price,
                    );
                    
                    $this->_ordersdf['payments'] = $payments;
                    
                }
                

            }

        }
        $trade_refunding = false;
        //获取货号
        foreach ($this->_ordersdf['order_objects'] as &$object) {
            foreach ($object['order_items'] as &$item) {
                //货号不存在
                $sku   = array();
                if (empty($item['bn'])) {
                    $sku   = $this->item_get($item['shop_product_id']);
                    
                    if ($sku['sku'] && $sku['sku']['outer_id']) {
                        //货号
                        $item['bn']   = $sku['sku']['outer_id'];
                        $object['bn'] = $sku['sku']['outer_id'];
                    }
                }

                if ($item['status'] == 'refund') {
                    $trade_refunding = true;
                }
            }
        }

        if ($trade_refunding) {
            $this->_ordersdf['pay_status'] = '6';
        }

        if ($this->_ordersdf['return_insurance_fee']){
            //service_order_objects
            $service_order = array();
            $service_order[] = array(
                'sale_price'    =>  $this->_ordersdf['return_insurance_fee'],
                'num'           =>  1,
                'total_fee'     =>  $this->_ordersdf['return_insurance_fee'],
                'title'         =>  '退换货无忧',


            );
            if ($service_order){
               $this->_ordersdf['service_order_objects']['service_order'] = $service_order;
            }

        }
        if ($this->_ordersdf['status'] == 'finish' && $this->_ordersdf['ship_status'] == '0') $this->_ordersdf['status'] = 'active';
    }

    protected function _operationSel()
    {
        parent::_operationSel();

        if ($this->_tgOrder) $this->_operationSel = 'update';
    }

    protected function get_update_components()
    {
        $components = array('markmemo');

        if ( ($this->_ordersdf['pay_status'] != $this->_tgOrder['pay_status']) ||($this->_ordersdf['shipping']['is_cod']=='true' && $this->_ordersdf['status'] == 'dead') ) {
            $refundApply = app::get('ome')->model('refund_apply')->getList('apply_id',array('order_id'=>$this->_tgOrder['order_id'],'status|noequal'=>'3'));
            // 如果没有退款申请单，以前端为主
            if (!$refundApply) {
                $components[] = 'master';
            }
        }

        return $components;
    }


    protected function item_get($sku_id)
    {
        if (empty($sku_id)) {
            return array();
        }

        $rs = kernel::single('erpapi_router_request')->set('shop',$this->__channelObj->channel['shop_id'])->product_item_sku_get(array('sku_id'=>$sku_id));

        if ($rs['rsp'] == 'fail' || !$rs['data']) return array();

        return $rs['data'];
    }

    protected function _canUpdate()
    {
        if ( $this->_ordersdf['status'] == 'dead' && $this->_ordersdf['shipping']['is_cod'] != 'true') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }

        return parent::_canUpdate();
    }

     function format_change_price($order_bn,$reship_bn,$bn){


        //$this->__channelObj->channel['shop_id']
        $returnObj = app::get('ome')->model('return_product');
        $order_detail = app::get('ome')->model('orders')->db_dump(array('order_bn'=>$order_bn,'shop_id'=>$this->__channelObj->channel['shop_id']),'order_id');
        if (!$order_detail) {
            $order_detail = app::get('archive')->model('orders')->db_dump(array('order_bn'=>$order_bn,'shop_id'=>$this->__channelObj->channel['shop_id']),'order_id');
            if (!$order_detail){
                return false;
            }
            $order_items = $returnObj->db->select("SELECT bn FROM sdb_archive_order_items WHERE order_id=".$order_detail['order_id']);
        }else{
            $order_items = $returnObj->db->select("SELECT bn FROM sdb_ome_order_items WHERE order_id=".$order_detail['order_id']);
        }

        $bn_list = array_map('current',$order_items);

        $order_id = $order_detail['order_id'];
        $return_detail = $returnObj->db_dump(array('return_bn'=>$reship_bn,'order_id'=>$order_id),'return_id,refundmoney');
        if (!$return_detail) return false;

        //$item = $returnObj->db->selectrow("SELECT price FROM sdb_ome_return_product_items WHERE return_id=".$return_detail['return_id']." AND bn in ('".implode('\'\'',$bn_list)."')");
        $item['price'] = $return_detail['refundmoney'];
        return $item;


    }
}
