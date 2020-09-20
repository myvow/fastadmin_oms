<?php
/**
 * [天猫]预售订单数据转换类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Tbpresale extends Abstracts
{
    public function convert($ordersdf)
    {
        if($ordersdf['order_type'] == 'presale')
        {
            $this->_newOrder['order_type'] = 'presale';
            
            //订单未付尾款(更新为部分付款)
            if($ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_NOPAID')
            {
                $this->_newOrder['payed'] = $ordersdf['step_paid_fee'];
                $this->_newOrder['pay_status'] = 3;
            }
        }
    }
    
    public function update()
    {
        if($this->_platform->_tgOrder['order_type'] == 'presale' && $ordersdf['step_trade_status'] == 'FRONT_PAID_FINAL_PAID')
        {
            //查看扩展表里状态是否为1如果为1 需要更新状态
            $order_id = $this->_platform->_tgOrder['order_id'];
            
            
            $extendObj = app::get('ome')->model('order_extend');
            $extend = $extendObj->dump(array('order_id'=>$order_id));
            if ($extend['presale_auto_paid']>0 && $extend['presale_pay_status'] == '1')
            {
                $extendObj->update(array('presale_auto_paid'=>0,'presale_pay_status'=>'2'),array('order_id'=>$order_id));
            }
        }
    }
}