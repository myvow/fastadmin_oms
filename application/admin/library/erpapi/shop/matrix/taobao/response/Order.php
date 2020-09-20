<?php
/**
 * 订单接口处理入口类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\taobao;

use app\admin\library\erpapi\shop\response\Order as Orders;
use app\admin\library\ome\Common;

class Order extends Orders
{
    /**
     * 订单obj明细唯一标识
     *
     * @var string
     **/
    public $object_comp_key = 'bn-oid-obj_type';
    
    /**
     * 订单item唯一标识
     *
     * @var string
     **/
    public $item_comp_key = 'bn-shop_product_id-item_type';

    //未知(jason)
    public function business_flow($sdf)
    {
        if ($sdf['t_type'] == 'fenxiao' || $sdf['order_source'] == 'taofenxiao') {
            $order_type = 'b2b';
        } else {
            $order_type = 'b2c';
        }
        
        return 'erpapi_shop_matrix_taobao_response_order_'.$order_type;
    }
    
    /**
     * 处理接收的订单数据
     * 
     * @param array $sdf
     * @return array
     */
    protected function _analysis()
    {
        parent::_analysis();
        
        $this->_ordersdf['is_service_order'] = ($this->_ordersdf['is_service_order'] || $this->_ordersdf['service_order_objects']['service_order']);
        
        if($this->_ordersdf['ship_status'] == '2' && $this->_ordersdf['is_service_order'])
        {
            $this->_ordersdf['ship_status'] = '0';
            foreach($this->_ordersdf['order_objects'] as $object)
            {
                if($object['source_status'] != 'WAIT_SELLER_SEND_GOODS') {
                    $this->_ordersdf['ship_status'] = '2';
                }
            }
        }
        
        //@todo:商品总额扣掉服务费:淘宝的服务费算在总额上
        $total_fee = 0;
        foreach ((array)$this->_ordersdf['service_order_objects']['service_order'] as $s)
        {
            $total_fee += (float)$s['total_fee'];
        }
        if ($total_fee>0) $this->_ordersdf['cost_item'] -= $total_fee;
        
        /***
        //发票处理
        $mdl_invoice_order_taobao = app::get('invoice')->model('order_taobao');
        $rs_invoice_order_taobao = $mdl_invoice_order_taobao->dump(array("platform_tid"=>$this->_ordersdf["order_bn"]));
        if(!empty($rs_invoice_order_taobao)){
            $this->_ordersdf["is_tax"] = 'true';
            $this->_ordersdf["tax_title"] = $rs_invoice_order_taobao["payer_name"];
            $this->_ordersdf["payer_register_no"] = $rs_invoice_order_taobao["payer_register_no"];
            $this->_ordersdf["invoice_kind"] = $rs_invoice_order_taobao["invoice_kind"];
        }
        ***/
    }

    protected function _operationSel()
    {
        parent::_operationSel();
        
        $funLib = new Func;
        
        ///////////////////////////////////////////
        // 解决订单备注没更新(淘宝平台问题，备注修改订单最后时间不变),
        // 同时防止比较明细，失败订单恢复后又重新更新为失败订单
        ///////////////////////////////////////////
        $memochg = false;

        if ($this->_tgOrder)
        {
            $last_custom_mark = array();
            $last_mark_text=array();
            $custom_mark = array();
            
            if ($this->_tgOrder['custom_mark'] && is_string($this->_tgOrder['custom_mark'])) {
                $custom_mark = unserialize($this->_tgOrder['custom_mark']);
            }
            
            $mark_text = array();
            if ($this->_tgOrder['mark_text'] && is_string($this->_tgOrder['mark_text'])) {
                $mark_text = unserialize($this->_tgOrder['mark_text']);
            }
            
            foreach ((array) $custom_mark as $key => $value) {
                if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);
                
                if ( intval($value['op_time']) > intval($last_custom_mark['op_time']) ) {
                    $last_custom_mark = $value;
                }
            }
            
            foreach ((array) $mark_text as $key => $value)
            {
                if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);
                
                if ( intval($value['op_time']) > intval($last_mark_text['op_time']) ) {
                    $last_mark_text = $value;
                }
            }
            
            if ( ($this->_ordersdf['custom_mark'] && $this->_ordersdf['custom_mark'] != $last_custom_mark['op_content']) || 
                 ($this->_ordersdf['mark_text'] && $this->_ordersdf['mark_text'] != $last_mark_text['op_content']) ) {
                $memochg = true;
            }
        }
        
        ///////////////////////////////////////////
        // 解决订单地址修改不更新(淘宝平台问题，订单地址修改最后时间不变),
        ///////////////////////////////////////////
        if (empty($this->_operationSel) && $this->_tgOrder)
        {
            $consignee = array();
            $area = $this->_ordersdf['consignee']['area_state'] . '/' . $this->_ordersdf['consignee']['area_city'] . '/' . $this->_ordersdf['consignee']['area_district'];
            $funLib->region_validate($area);
            
            $consignee['area']      = $area;
            $consignee['name']      = $this->_ordersdf['consignee']['name'];
            $consignee['addr']      = $this->_ordersdf['consignee']['addr'];
            $consignee['telephone'] = $this->_ordersdf['consignee']['telephone'];
            $consignee['mobile']    = $this->_ordersdf['consignee']['mobile'];
            
            $diff_consignee = array_diff_assoc(array_filter($consignee), $this->_tgOrder['consignee']);
            if ($diff_consignee) $memochg = true;
        }
        
        // 即不是更新，也是不是创建,才做这样逻辑判断
        if(!$this->_operationSel && $memochg)
        {
            $this->_operationSel = 'update';
        }
        
        return true;
    }

    public function _canAccept()
    {
        if($this->__channelObj->channel['business_type']=='zx' && in_array($this->_ordersdf['order_source'],array('tbdx','tbjx'))) {
            $this->__apilog['result']['msg'] = '直销店铺不接收分销订单';
            return false;
        }

        if($this->__channelObj->channel['business_type']=='fx' && !in_array($this->_ordersdf['order_source'],array('tbdx','tbjx'))) {
            $this->__apilog['result']['msg'] = '分销店铺不接收直销订单';
            return false;
        }

        foreach($this->_ordersdf['order_objects'] as $object)
        {
            if (in_array($object['zhengji_status'], array('1','3'))){
                $this->__apilog['result']['msg'] = '征集中和征集失败订单不收!';
                return false;
            }
            
            if(!empty($object['is_sh_ship'])){
                if($object['is_sh_ship'] == 'true'){
                    $this->__apilog['result']['msg'] = '菜鸟自动流转订单,不接受';
                    return false;
                }
            }
        }
        
        if(in_array($this->_ordersdf['step_trade_status'],array('FRONT_NOPAID_FINAL_NOPAID','FRONT_PAID_FINAL_NOPAID'))){
            $this->__apilog['result']['msg'] = '定金未付尾款未付或定金已付尾款未付订单不接收';
            return false;
        }

        if($this->_ordersdf['other_list'])
        {
            foreach((array) $this->_ordersdf['other_list'] as $val)
            {
                // 淘宝处方类订单
                if($val['type'] == 'rx_audit' && $val['rx_audit_status'] == '0'){
                    $this->__apilog['result']['msg'] = '处方药未审核状态，不接受';
                    return false;
                }
            }
        }
        
        return parent::_canAccept();
    }

    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();
        
        $plugins[] = 'tbgift';
        
        return $plugins;
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        
        //判断如果是已完成只更新时间
        if ($this->_ordersdf['status'] == 'finish' && $this->_ordersdf['end_time']>0)
        {
            $plugins = array();
            $plugins[] = 'confirmreceipt';
        }
        
        return $plugins;
    }

    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype','oversold');
        
        if(in_array($this->_tgOrder['process_status'], array('unconfirmed')))
        {
            $rs = app::get('ome')->model('order_extend')->getList('extend_status',array('order_id'=>$this->_tgOrder['order_id']));
            // 如果ERP收货人信息未发生变动时，则更新淘宝收货人信息
            if ($rs[0]['extend_status'] != 'consignee_modified') {
                $components[] = 'consignee';
            }
        }
        
        $items_flag = false;
        foreach($this->_ordersdf['order_objects'] as $objects){
            foreach($this->_tgOrder['order_objects'] as $tgobjects){
                if($objects['oid'] == $tgobjects['oid']){
                    if($tgobjects["quantity"] == $objects["quantity"] && $tgobjects["price"] == $objects["price"] && $tgobjects["bn"] != $objects["bn"]){
                        $items_flag = true;
                    }
                }
            }
        }
        
        if($items_flag){
            $components[] = 'items';
        }
        
        if($this->_tgOrder['status']=='finish'){
            unset($components);
        }
        
        return $components;
    }

    protected function get_convert_components()
    {
        $components = parent::get_convert_components();
        $components[] = 'oversold';
        return $components;
    }
}
