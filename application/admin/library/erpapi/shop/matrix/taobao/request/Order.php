<?php
/**
 * 订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_taobao_request_order extends erpapi_shop_request_order
{
    private $__status = array(
        0 => array('key' => 'X_TO_SYSTEM', 'value' => ''),
        1 => array('key' => 'X_SERVICE_AUDITED', 'value' => ''),
        2 => array('key' => 'X_FINANCE_AUDITED', 'value' => ''),
        3 => array('key' => 'X_ALLOCATION_NOTIFIED', 'value' => ''),
        4 => array('key' => 'X_WAIT_ALLOCATION', 'value' => ''),
        5 => array('key' => 'X_SORT_PRINTED', 'value' => ''),
        6 => array('key' => 'X_SEND_PRINTED', 'value' => ''),
        7 => array('key' => 'X_LOGISTICS_PRINTED', 'value' => ''),
        8 => array('key' => 'X_SORTED', 'value' => ''),
        9 => array('key' => 'X_EXAMINED', 'value' => ''),
        10 => array('key' => 'X_PACKAGED', 'value' => ''),
        11 => array('key' => 'X_WEIGHED', 'value' => ''),
        12 => array('key' => 'X_OUT_WAREHOUSE', 'value' => ''),
    );

    /**
     * 淘宝全链路
     *
     * @return void
     * @author 
     **/
    public function message_produce($sdf,$queue=false)
    {
        $args = func_get_args();array_pop($args);
        $_in_mq = $this->__caller->caller_into_mq('order_message_produce','shop',$this->__channelObj->channel['shop_id'],$args,$queue);
        if ($_in_mq) {
            return $this->succ('成功放入队列');
        }

        $status = $this->__status[$sdf['message_produce_status']]['key'];
        // 整理参数格式
        $title = sprintf('淘宝全链路%s[%s]',$status,$sdf['order_bn']); 


        $remark = $sdf['remark'] ? $sdf['remark'] : $this->__status[$sdf['message_produce_status']]['value'];

        $order_ids = array();
        foreach ((array) $sdf['order_objects'] as $key => $value) {
            if ($value['oid']) $order_ids[] = $value['oid'];
        }

        $params = array(
            'topic'       => 'taobao_jds_TradeTrace', 
            'tid'         => $sdf['order_bn'],
            'order_ids'   => implode(',',$order_ids),
            'status'      => $status,
            'action_time' => date("Y-m-d H:i:s"),
            'remark'      => $remark,
        );

        $callback = array(
           'class' => get_class($this),
           'method' => 'callback',
            'params' => array(
                'obj_bn' => $sdf['order_bn'],
            ),
        );

        return $this->__caller->call(SHOP_TMC_MESSAGE_PRODUCE, $params, $callback, $title,10,$sdf['order_bn'],false);
    }

    protected function __formatUpdateOrderShippingInfo($order) {
        $consignee_area = $order['consignee']['area'];
        if(strpos($consignee_area,":")){
            $t_area            = explode(":",$consignee_area);
            $t_area_1          = explode("/",$t_area[1]);
            $receiver_state    = $t_area_1[0];
            $receiver_city     = $t_area_1[1];
            $receiver_district = $t_area_1[2];
        }
        $params = array();
        $params['tid']               = $order['order_bn'];
        $params['receiver_name']     = $order['consignee']['name']?$order['consignee']['name']:'';
        $params['receiver_phone']    = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
        $params['receiver_mobile']   = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
        $params['receiver_state']    = $receiver_state ? $receiver_state : '';
        $params['receiver_city']     = $receiver_city ? $receiver_city : '';
        $params['receiver_district'] = $receiver_district ? $receiver_district : '';
        $params['receiver_address']  = $order['consignee']['addr']?$order['consignee']['addr']:'';
        $params['receiver_zip']      = $order['consignee']['zip']?$order['consignee']['zip']:'';
        return $params;
    }
}