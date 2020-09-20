<?php
/**
 * 订单最终处理类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\process;

use think\Db;

use app\admin\library\ome\Order;

class Order
{
    function __construct()
    {
        //--
    }
    
    /**
     * 订单接收
     *
     * @return array
     **/
    public function add($ordersdf)
    {
        if ($ordersdf['order_id'])
        {
            //更新订单
            return $this->_updateOrder($ordersdf);
        }
        else
        {
            //创建新订单
            return $this->_createOrder($ordersdf);
        }
    }
    
    /**
     * 创建订单
     * 
     * @param array $ordersdf
     * @return array
     */
    private function _createOrder($ordersdf)
    {
        $orderLib = new Order;
        
        $plugins = $ordersdf['plugins'];
        unset($ordersdf['plugins']);
        
        if (!$ordersdf){
            return array('rsp'=>'fail','msg'=>'创建失败：格式化数据为空');
        }
        
        //插入订单数据
        $rs = $orderLib->create_order($ordersdf);
        if (!$rs)
        {
            $errorinfo = kernel::database()->errorinfo();
            
            return array('rsp'=>'fail','msg'=>$errorinfo ? $errorinfo : '订单已经存在','data'=>array('tid'=>$ordersdf['order_bn']));
        }
        
        
        
        
        
        
        //创建订单后的操作
        if ($plugins && is_array($plugins))
        {
            foreach ($plugins as $name => $params)
            {
                if ($ordersdf['order_id'] && $params)
                    kernel::single('erpapi_shop_response_plugins_order_'.$name)->postCreate($ordersdf['order_id'],$params);
            }
        }
        
        
        

        // 更新订单下载时间
        $shopModel = app::get('ome')->model('shop');
        $shopModel->update(array('last_download_time'=>time()), array('shop_id'=>$ordersdf['shop_id']));

        if($service = kernel::servicelist('service.order')){
            foreach ($service as $instance){
                if (method_exists($instance, 'after_add_order')){
                    $instance->after_add_order($ordersdf);
                }
            }
        }

        return array('rsp'=>'succ','msg'=>'返回值：订单创建成功！订单ID：'.$ordersdf['order_id'],'data'=>array('tid'=>$ordersdf['order_bn']));
    }
    
    /**
     * 更新订单
     * 
     * @return array
     **/
    private function _updateOrder($ordersdf)
    {
        $plugins = $ordersdf['plugins']; unset($ordersdf['plugins']);
        $newordersdf = $ordersdf; unset($newordersdf['status'],$newordersdf['order_id']);

        $modelOrder = app::get('ome')->model('orders');
        $upFilter = array('order_id' => $ordersdf['order_id']);

        if ($newordersdf) {
            if ($newordersdf['pay_status'] == '6' && $newordersdf['pause']=='true'){
                $modelOrder->pauseOrder($ordersdf['order_id']);
            }
            $newordersdf['order_id'] = $ordersdf['order_id'];
            $plainData = $modelOrder->sdf_to_plain($newordersdf);
            $rs = $modelOrder->update($plainData, $upFilter);
            $modelOrder->_save_depends($newordersdf);
        }

        // 保存后插件处理
        if ($plugins && is_array($plugins)) {
            foreach ($plugins as $name => $params) {
                if ($ordersdf['order_id'] && $params)
                    kernel::single('erpapi_shop_response_plugins_order_'.$name)->postUpdate($ordersdf['order_id'],$params);
            }
        }

        if ($ordersdf) $this->_afterUpdate($ordersdf);

        $msg = $ordersdf['status'] == 'dead' ? '订单取消成功' : '订单更新成功,影响行数:'.intval($rs);

        return array('rsp' => 'succ','msg'=>$msg,);
    }

    /**
     * 更新后，是否撤回发货单
     * 
     * @return bool
     **/
    private function _afterUpdate($ordersdf)
    {
        $orderLib = new Order;
        
        $orderModel = app::get('ome')->model('orders');

        
        
        //订单信息
        $filter = array('order_id'=>$ordersdf['order_id']);
        $tgorder = $orderLib->getOrderInfo($filter);
        
        // 如果订单已经拆分
        $oOrder_sync = app::get('ome')->model('order_sync_status');
        $oOrder_sync->update(array('sync_status'=>'2'),array('order_id'=>$tgorder['order_id']));

        //更新订单hash值
        $this->combinehash_update($tgorder);

        // 写一下日志
        $write_log = array();
        if ($ordersdf['consignee']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['order_id'],
                'obj_name'  => $tgorder['order_bn'],
                'operation' => 'order_modify@ome',
                'memo'      => "订单收货人信息被修改",
            );
        }

        if ($ordersdf['mark_text']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['order_id'],
                'obj_name'  => $tgorder['order_bn'],
                'operation' => 'order_modify@ome',
                'memo'      => "订单商家备注被修改",
            );
        }

        if ($ordersdf['mark_type']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['order_id'],
                'obj_name'  => $tgorder['order_bn'],
                'operation' => 'order_modify@ome',
                'memo'      => "订单旗标被修改",
            );
        }

        if ($ordersdf['order_objects']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['order_id'],
                'obj_name'  => $tgorder['order_bn'],
                'operation' => 'order_modify@ome',
                'memo'      => "前端订单商品信息修改",
            );
        }

        $opObj = app::get('ome')->model('operation_log');
        if ($write_log) {
            $opObj->batch_write_log2($write_log);
        }


        // 如果到付已经发货更新销售单上的时候
        if ($ordersdf['paytime'] && $tgorder['is_cod'] == 'true' && $tgorder['ship_status'] == '1') {
            $saleModel = app::get('ome')->model('sales');
            $saleModel->update(array('paytime'=>$ordersdf['paytime']),array('order_id'=>$tgorder['order_id']));
        }

        // 非活动订单，已发货，部分发货不做处理
        if ($tgorder['status'] != 'active' || !in_array($tgorder['ship_status'],array('0','2')) ) return true;

        if ($ordersdf['pay_status'] == '5' || $ordersdf['status'] == 'dead') {

            if($tgorder['ship_status'] == 0){

              $orderModel->cancel($tgorder['order_id'],'订单全额退款后取消！',false,'async');

           } elseif($tgorder['ship_status'] == 2) {
               $this->_reback_serial($tgorder['order_id']);
               $orderModel->rebackDeliveryByOrderId($tgorder['order_id']);
               
               if($tgorder['process_status'] == 'splited'){
                   $orderModel->update(array('process_status'=>'splitting'), array('order_id'=>$tgorder['order_id']));
               }
           }
            return true;
        }

        // 如果已经拆分
        if (in_array($tgorder['process_status'],array('splited','splitting'))) {
            
            $reback_delivery = false;
            if ($ordersdf['consignee']['name'] 
                || $ordersdf['consignee']['area'] 
                || $ordersdf['consignee']['addr'] 
                || $ordersdf['consignee']['telephone'] 
                || $ordersdf['consignee']['mobile']) {   // 收货人信息发生变更
                $reback_delivery = true;
            }elseif ($ordersdf['pay_status'] == '4') { // 部分退款  
                $reback_delivery = true;
            }elseif ($ordersdf['order_objects']) { // 明细发生变更
                $reback_delivery = true;
            }

            if ($reback_delivery) {
                $this->_reback_serial($tgorder['order_id']);
                $orderModel->rebackDeliveryByOrderId($tgorder['order_id']);

                return true;
            }

            // 有备注
            $orderPauseAllow = app::get('ome')->getConf('ome.orderpause.to.syncmarktext');
            if ($ordersdf['mark_text'] && $orderPauseAllow !== 'false') {
                $orderModel->pauseOrder($tgorder['order_id']);

                return true;
            }
        }
        
        return true;
    }
    
    private function _reback_serial($order_id)
    {
        $serialObj    = app::get('ome')->model('product_serial');
        $serialLogObj = app::get('ome')->model('product_serial_log');

        $sql = sprintf("SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id) WHERE dord.order_id=%s AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status IN('ready','progress')",$order_id);

        $rows = kernel::database()->select($sql);

        if (!$rows) return ;

        $deliveryIds = array_map('current',$rows);

        $filter = array(
            'act_type'  => '0',
            'bill_type' => '0',
            'bill_no'   => $deliveryIds,
        );
        $serialLogs = $serialLogObj->getList('item_id',$filter);

        if (!$serialLogs) return ;

        $itemIds = array();
        foreach ($serialLogs as $value) {
            $itemIds[] = $value['item_id'];
        }

        $serialObj->update(array('status'=>'0'),array('item_id'=>$itemIds,'status'=>'1'));
    }
    
    public function status_update($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        if ($sdf['status'] == 'dead') {
            $orderModel->cancel($sdf['order_id'],'前端订单取消',false,'async');
        }

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function pay_status_update($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function ship_status_update($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function custom_mark_add($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function custom_mark_update($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function memo_add($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function memo_update($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    public function payment_update($sdf)
    {
        if (!$sdf['order_id']) return array('rsp'=>'fail','msg'=>'订单ID不存在');

        $orderModel = app::get('ome')->model('orders');

        $affect_row = $orderModel->update($sdf,array('order_id'=>$sdf['order_id']));

        return array('rsp'=>'succ','msg'=>'更新成功，影响行数：'.$affect_row);
    }

    private function combinehash_update($sdf)
    {
        //组织hash的计算入参
        $params = array(
            'member_id' => $sdf['member_id'],
            'shop_id' => $sdf['shop_id'],
            'shop_type' => $sdf['shop_type'],
            'order_source' => $sdf['order_source'],
            'order_bn' => $sdf['order_bn'],
            'self_delivery' => $sdf['self_delivery'],
            'is_cod' => $sdf['is_cod'],
            'consignee' => array(
                'name' => $sdf['ship_name'],
                'area' => $sdf['ship_area'],
                'addr' => $sdf['ship_addr'],
                'telephone' => $sdf['ship_tel'],
                'mobile' => $sdf['ship_mobile'],
            ),
        );

        $orderLib = kernel::single('ome_order');
        $combieHashIdxInfo = $orderLib->genOrderCombieHashIdx($params);
        if($combieHashIdxInfo){
            $update['order_combine_hash'] = $combieHashIdxInfo['combine_hash'];
            $update['order_combine_idx'] = $combieHashIdxInfo['combine_idx'];
        }

        $orderModel = app::get('ome')->model('orders');
        $orderModel->update($update,array('order_id'=>$sdf['order_id']));
    }
}