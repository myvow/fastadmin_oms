<?php
/**
 * 退款插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Refundapply extends Abstracts
{
    public function convert($ordersdf)
    {
        $refundApplySdf = array();
        
        
        //这里要修改，更新订单时
        if ($platform->_tgOrder['order_id']) {
            $refundApplyModel = app::get('ome')->model('refund_apply');

            $refund_money = 0;
            if ($ordersdf['payed'] > $ordersdf['total_amount']) {
                $refund_money = bcsub($ordersdf['payed'], $ordersdf['total_amount'],3);
            }

            if($refund_money <= 0) return array();

            // 判断是否有申请中的
            $has = $refundApplyModel->getList('apply_id',array('order_id'=>$platform->_tgOrder['order_id'],'status'=>array('0','1','2')),0,1);
            if ($has) {
                return array();
            }

            $create_time = $ordersdf['lastmodify'] ? kernel::single('ome_func')->date2time($ordersdf['lastmodify']) : time();
            $refundApplySdf = array(
                'order_id'        => $platform->_tgOrder['order_id'],
                'refund_apply_bn' => $refundApplyModel->gen_id(),
                'pay_type'        => 'online',
                'money'           => $refund_money,//退款金额
                'refund_money'    => $refund_money,//退款金额
                'bcmoney'         => 0,//补偿费用
                'refunded'        => '0',
                'memo'            => '订单编辑产生的退款申请',
                'create_time'     => $create_time,
                'status'          => '2',
                'shop_id'         => $ordersdf['shop_id'],
            );
        }
        
        return $refundApplySdf;
    }

    
    
    
    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id,$refundapply)
    {
        
    }

    /**
     * 更新后操作
     *
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$refundapply)
    {
        $refundapply['order_id']        = $order_id;
        $refundapply['source']          = 'local';//来源：本地新建
        $refundapply['refund_refer']    = '0';//退款申请来源：普通流程产生的退款申请
        
        //创建退款单
        $is_update_order    = false;//是否更新订单付款状态
        kernel::single('ome_refund_apply')->createRefundApply($refundapply, $is_update_order, $error_msg);
        
        $logModel = app::get('ome')->model('operation_log');
        $logModel->write_log('order_edit@ome',$order_id,'退款申请');
    }
}