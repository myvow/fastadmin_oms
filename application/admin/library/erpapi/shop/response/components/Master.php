<?php
/**
 * 订单主信息数据转换类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;
use app\admin\library\ome\Orders;

class Master extends Abstracts
{
    /**
     * 创建新订单时,主数据转换
     * 
     * @return void
     **/
    public function convert($ordersdf)
    {
        $orderLib = new Orders;
        
        //店铺信息
        $this->_newOrder['shop_id']   = $ordersdf['shop_id'];
        $this->_newOrder['shop_type'] = $ordersdf['shop_type'];
        
        //订单主信息
        $this->_newOrder['order_bn']         = $ordersdf['order_bn'];
        $this->_newOrder['cost_item']        = (float)$ordersdf['cost_item'];
        $this->_newOrder['discount']         = (float)$ordersdf['discount'];
        $this->_newOrder['total_amount']     = (float)$ordersdf['total_amount'];
        $this->_newOrder['pmt_goods']        = (float)$ordersdf['pmt_goods'];
        $this->_newOrder['pmt_order']        = (float)$ordersdf['pmt_order'];
        $this->_newOrder['cur_amount']       = (float)$ordersdf['cur_amount'];
        $this->_newOrder['score_u']          = (float)$ordersdf['score_u'];
        $this->_newOrder['score_g']          = (float)$ordersdf['score_g'];
        $this->_newOrder['currency']         = $ordersdf['currency'] ? $ordersdf['currency'] : 'CNY';
        $this->_newOrder['source']           = 'matrix';
        $this->_newOrder['status']           = $ordersdf['status'];
        $this->_newOrder['weight']           = (float)$ordersdf['weight'];
        $this->_newOrder['order_source']     = $ordersdf['order_source'];
        $this->_newOrder['cur_rate']         = $ordersdf['cur_rate'] ? $ordersdf['cur_rate'] : 1;
        $this->_newOrder['title']            = $ordersdf['title'];
        $this->_newOrder['coupons_name']     = $ordersdf['coupons_name'];
        $this->_newOrder['createway']        = 'matrix';
        $this->_newOrder['o2o_info']         = $ordersdf['o2o_info'];
        
        //平台运单号
        if ($ordersdf['shipping']['shipping_id']) {
            $this->_newOrder['logi_no'] = $ordersdf['shipping']['shipping_id'];
        }
        
        $this->_newOrder['order_type'] = $ordersdf['order_type'] ? $ordersdf['order_type'] : 'normal';
        
        //是否自发货
        if (isset($ordersdf['self_delivery'])) $this->_newOrder['self_delivery'] = $ordersdf['self_delivery'];
        
        $outer_lastmodify = $ordersdf['lastmodify'] ? $ordersdf['lastmodify'] : time();
        
        //时间信息
        $this->_newOrder['download_time'] = time();
        $this->_newOrder['createtime'] = date2time($ordersdf['createtime']);
        $this->_newOrder['outer_lastmodify'] = date2time($outer_lastmodify);

        //订单失效时间
        if ($ordersdf['order_limit_time']) {
            $this->_newOrder['order_limit_time'] = date2time($ordersdf['order_limit_time']);
        } else {
            $this->_newOrder['order_limit_time'] = time() + 60 * 60 * 12; //默认12小时
        }

        if ($ordersdf['relate_order_bn']){
            $this->_newOrder['relate_order_bn'] = $ordersdf['relate_order_bn'];
        }
        
        //支付方式
        $payment_cfg = $orderLib->get_payment($ordersdf['pay_bn'], $ordersdf['node_type']);
        
        $this->_newOrder['pay_bn'] = $payment_cfg['pay_bn'];
        $this->_newOrder['pay_status'] = $ordersdf['pay_status'];
        $this->_newOrder['payed'] = $ordersdf['payed'];

        //支付金额
        $this->_newOrder['payinfo']['pay_name']     = $ordersdf['payinfo']['pay_name'];
        $this->_newOrder['payinfo']['cost_payment'] = $ordersdf['payinfo']['cost_payment'];

        //支付单结构
        $payment_list = isset($ordersdf['payments']) ? $ordersdf['payments'] : (array)$ordersdf['payment_detail'];
        if($ordersdf['payment_detail'])
        {
            $payment_detail = $ordersdf['payment_detail'];
            unset($ordersdf['payment_detail']);
            
            $ordersdf['payment_detail'][] = $payment_detail;
        }
        
        if ($payment_list && is_array($payment_list))
        {
            $total_amount = $ordersdf['total_amount'];

            $pay_status = '0';
            $payed      = '0.000';
            $paytime    = null;

            foreach ($payment_list as $key => $value) {
                $payed += $value['money'];
                
                if ($value['pay_time']) {
                    $paytime = date2time($value['pay_time']);
                }
            }

            if ($total_amount <= $payed) {
                $pay_status = '1';

                if (!$paytime) $paytime = time();
            } elseif ($payed <= 0) {
                $pay_status = '0';
            } else {
                if (!$paytime) $paytime = time();

                $comp = bccomp(round($payed,3), $total_amount,3);

                $pay_status = $comp < 0 ? '3' : '1';
            }

            $this->_newOrder['pay_status'] = $pay_status;
            $this->_newOrder['payed'] = $payed;
            
            if ($paytime)
                $this->_newOrder['paytime'] = intval($paytime);
        }
        
        //加入手动单拉订单_标记_防止自动审单
        $this->_newOrder['auto_combine'] = ($ordersdf['auto_combine'] === false ? false : true);
        if($ordersdf['is_service_order'])
        {
            $this->_newOrder['is_service_order'] = $ordersdf['is_service_order'];
        }
        
        //订单种类(二进制)
        $this->_newOrder['order_bool_type'] = 0x0001;
    }

    /**
     * 更新订单时数据转换
     *
     * @return array
     */
    public function update($ordersdf)
    {
        $orderLib = new Orders;
        
        if (in_array($this->_platform->_tgOrder['pay_status'], array('6','7')) && in_array($ordersdf['pay_status'], array('1','3','4','5'))) {
            $this->_newOrder['pause'] = 'false';
        }

        $master = array();

        if ($ordersdf['order_limit_time']) {
            $order_limit_time = date2time($ordersdf['order_limit_time']);
            if ($order_limit_time != $this->_platform->_tgOrder['order_limit_time'] && $this->_platform->_tgOrder['pay_status'] == '0') {
                $master['order_limit_time'] = $order_limit_time;
            }
        }

        $master['pay_status']                = $ordersdf['pay_status'];
        $master['discount']                  = $ordersdf['discount'];
        $master['pmt_goods']                 = $ordersdf['pmt_goods'];
        $master['pmt_order']                 = $ordersdf['pmt_order'];
        $master['total_amount']              = $ordersdf['total_amount'];
        $master['cur_amount']                = $ordersdf['cur_amount'];
        $master['payed']                     = $ordersdf['payed'];
        $master['cost_item']                 = $ordersdf['cost_item'];
        $master['coupons_name']              = $ordersdf['coupons_name'];
        $master['is_tax']                    = $ordersdf['is_tax'] ? $ordersdf['is_tax'] : 'false';
        $master['tax_no']                    = $ordersdf['tax_no'];
        $master['cost_tax']                  = $ordersdf['cost_tax'];
        $master['tax_title']                 = $ordersdf['tax_title'];
        $master['weight']                    = $ordersdf['weight'];
        $master['title']                     = $ordersdf['title'];
        $master['score_u']                   = $ordersdf['score_u'];
        $master['score_g']                   = $ordersdf['score_g'];
        $master['status']                    = $ordersdf['status'];
        
        //支付方式
        $payment_cfg = $orderLib->get_payment($ordersdf['pay_bn'], $ordersdf['node_type']);
        
        $master['pay_bn'] = $payment_cfg['pay_bn'];

        //支付单结构
        $payment_list = isset($ordersdf['payments']) ? $ordersdf['payments'] : array($ordersdf['payment_detail']);
        if ($payment_list 
            && is_array($payment_list) 
            && $ordersdf['payed'] >= $this->_platform->_tgOrder['payed']
            && in_array($this->_platform->_tgOrder['pay_status'], array('0','3','8')) ) {
            
            $last_payment = array_pop($payment_list);
            $master['paytime'] = $last_payment['pay_time'] ? date2time($last_payment['pay_time']) : time();
        }

        $master = array_filter($master,array($this,'filter_null'));

        $diff_master = array_udiff_assoc($master, $this->_platform->_tgOrder,array($this,'comp_array_value'));

        if ($diff_master) {
            $this->_newOrder = array_merge($this->_newOrder,$diff_master);

            if (in_array($this->_newOrder['pay_status'], array('6','7'))) {
               $this->_newOrder['pause'] = 'true';
            }      
        }

        $payinfo = array();
        $payinfo['pay_name']     = $ordersdf['payinfo']['pay_name'];
        $payinfo['cost_payment'] = $ordersdf['payinfo']['cost_payment'];
        $payinfo = array_filter($payinfo,array($this,'filter_null'));
        $diff_payinfo = array_udiff_assoc($payinfo, $this->_platform->_tgOrder['payinfo'],array($this,'comp_array_value'));
        if ($diff_payinfo) {
            $this->_newOrder['payinfo'] = array_merge((array)$this->_newOrder['payinfo'],$diff_payinfo);
        }
    }
}