<?php
/**
 * 支付单插件处理类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;
use app\admin\library\ome\Orders;

class Payment extends Abstracts
{
    static private $__codPartPay = array('yihaodian','dangdang');

    /**
     * 支付单数据转换
     * 
     * @param array $ordersdf
     * @return array
     */
    public function convert($ordersdf)
    {
        $orderLib = new Orders;
        $paymentsdf = array();
        
        //支付单结构
        $payment_list = isset($ordersdf['payments']) ? $ordersdf['payments'] : array($ordersdf['payment_detail']);
        
        //订单是已支付、有支付单或者货到付款
        if(($payment_list && $ordersdf['pay_status'] == '1') || $ordersdf['shipping']['is_cod'] == 'true')
        {
            //支付方式
            $payment_cfg = $orderLib->get_payment($ordersdf['pay_bn'], $ordersdf['node_type']);
            
            foreach ($payment_list as $payment)
            {
                $payment['op_name'] = trim($payment['op_name']);
                
                if(!$payment['pay_time']) $payment['pay_time'] = time();
                
                $t_begin = $t_end = date2time($payment['pay_time']);
                
                if ($payment['trade_no'] === 'null') unset($payment['trade_no']);
                
                $paymentsdf[] = array(
                    'payment_bn'    => $payment['trade_no'],
                    'shop_id'       => $ordersdf['shop_id'],
                    'order_id'      => null,
                    'account'       => $payment['account'],
                    'bank'          => $payment['bank'],
                    'pay_account'   => $payment['pay_account'],
                    'currency'       => 'CNY',
                    'money'         => (float)$payment['money'],
                    'paycost'       => $payment['paycost'],
                    'cur_money'     => (float)$payment['money'],
                    'pay_type'      => $payment_cfg['pay_type'],
                    'payment'       => $payment_cfg['id'],
                    'pay_bn'        => $payment_cfg['pay_bn'],
                    'paymethod'     => $payment['paymethod'],
                    't_begin'       => $t_begin ? $t_begin : time(),
                    't_end'         => $t_end ? $t_end : time(),
                    'download_time' => time(),
                    'status'        => 'succ',
                    'trade_no'      => $payment['trade_no'],
                    'memo'          => $payment['memo'],
                    'op_id'         => $payment['op_id'] ? $payment['op_id'] : '',
                    'op_name'       => $payment['op_name'] ? $payment['op_name'] : $ordersdf['node_type'],
                );
            }
        }

        
        
        /***
        // 更新的时候
        if ($platform->_tgOrder)
        {
            $paymentObj = app::get('ome')->model('payments');
            
            
            $tgPayments = $paymentObj->getList('payment_bn',array('order_id'=>$platform->_tgOrder['order_id']));
            $paymentbns = $tgPayments ? array_map('current',$tgPayments) : array();

            foreach ($paymentsdf as $key => $value) {
                if (in_array($value['payment_bn'],$paymentbns)) {
                    unset($paymentsdf[$key]);continue;
                }
            }
        }
        ***/
        
        return $paymentsdf;
    }

    
    
    
    /**
     * 未使用
     * 
     * @param Array $params
     * 
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$payments)
    {
        $paymentObj = app::get('ome')->model('payments');
        $bank       = app::get('ome')->model('bank_account');
        foreach ($payments as $key => $value) {
            $payments[$key]['order_id']   = $order_id;
            $payments[$key]['payment_bn'] = $value['payment_bn'] ? $value['payment_bn'] : $paymentObj->gen_id();

            $bankAccount = array('bank'=>$value['bank'], 'account'=>$value['account']);
            if(!$bank->dump($bankAccount)) {
                $bank->save($bankAccount);
            }
        }
        
        $sql = ome_func::get_insert_sql($paymentObj,$payments);

        kernel::database()->exec($sql);
    }

    /**
     * 未使用
     * 
     * @param Array $params
     * 
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$payments)
    {
        $orderLib = new Orders;
        
        $shop_id = $payments[0]['shop_id'];
        
        $filter = array('shop_id'=>$shop_id);
        $shop = $orderLib->getShopInfo($filter);
        
        
        
        
        
        
        
        $paymentObj = app::get('ome')->model('payments');

        if (in_array($shop['node_type'],array('ecshop_b2c','bbc'))) {
            $paymentObj->delete(array('order_id'=>$order_id));
        } 

        foreach ($payments as $key => $value) {

            $payments[$key]['order_id']   = $order_id;

            if (in_array($shop['node_type'],array('ecshop_b2c','bbc'))) {
                $payments[$key]['payment_bn'] = $value['payment_bn'] ? $value['payment_bn'] : $paymentObj->gen_id();
            }
        }

        if ($payments) { 
            $sql = ome_func::get_insert_sql($paymentObj,$payments);

            kernel::database()->exec($sql);

            $logModel = app::get('ome')->model('operation_log');
            $logModel->write_log('order_pay@ome',$order_id,'支付单添加');      
        }
    }
}