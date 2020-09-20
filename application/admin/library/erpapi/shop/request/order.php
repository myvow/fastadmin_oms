<?php
/**
 * 订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_request_order extends erpapi_shop_request_abstract
{
    /**
     * 淘宝全链路
     *
     * @return void
     * @author 
     **/
    public function message_produce($sdf,$queue=false){}
    
    /**
     * 获取店铺订单详情
     *
     * @param String $order_bn 订单号
     * @return void
     * @author
     **/
    public function get_order_detial($order_bn)
    {
        $params['tid'] = $order_bn;

        $title = "店铺(".$this->__channelObj->channel['name'].")获取前端店铺".$order_bn."的订单详情";

        $order_type = ($this->__channelObj->channel['business_type'] == 'zx') ? 'direct' : 'agent';

        $api_name = $order_type == 'direct' ? SHOP_TRADE_FULLINFO_RPC : SHOP_FENXIAO_TRADE_FULLINFO_RPC;

        $rsp = $this->__caller->call($api_name,$params,array(),$title,10,$order_bn);

        $result = array();
        $result['rsp']        = $rsp['rsp'];
        $result['err_msg']    = $rsp['err_msg'];
        $result['msg_id']     = $rsp['msg_id'];
        $result['res']        = $rsp['res'];
        $result['data']       = json_decode($rsp['data'],1);
        $result['order_type'] = $order_type;
        
        return $result;
    }

    #获取订单状态
    public function getOrderStatus($arrOrderBn)
    {
        $order_bn = implode(',', $arrOrderBn);
        $params = array('tids' => $order_bn);
        $title = "店铺(" . $this->__channelObj->channel['name'] . ")获取前端店铺" . $order_bn . "的订单详情";
        $rsp = $this->__caller->call(SHOP_GET_ORDER_STATUS, $params, array(), $title, 10, $order_bn);
        return $this->doGetOrderStatusRet($rsp);
    }
    
    protected function doGetOrderStatusRet($rsp) {
        $rsp['data'] = json_decode($rsp['data'], 1);
        return $rsp;
    }
    
    #订单编辑
    public function updateIframe($order,$is_request=true,$ext=array()) {
        // 默认本地编辑
        $data = array('edit_type'=>'local');
        return array('rsp'=>'success','msg'=>'本地订单编辑','data'=>$data);
    }

    #订单更新
    public function updateOrder($order){}

    public function updateOrderStatus($order , $status='' , $memo='' , $mode='sync'){}

    public function updateOrderTax($order){}

    public function updateOrderShipStatus($order,$queue = false) {}

    public function updateOrderPayStatus($order){}

    public function updateOrderMemo($order,$memo){}

    public function addOrderMemo($order,$memo){}

    public function addOrderCustomMark($order,$memo){}

    protected function __formatUpdateOrderShippingInfo($order) {
        return array();
    }

    public function updateOrderShippingInfo($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params = $this->__formatUpdateOrderShippingInfo($order);
        if(empty($params)) {
            $rs['msg'] = 'no params';
            return $rs;
        }
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易收货人信息]:'.$params['receiver_name'].'(订单号:'.$order['order_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_TRADE_SHIPPING_ADDRESS_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    public function updateOrderConsignerinfo($order){}

    public function updateOrderSellagentinfo($order){}

    public function updateOrderLimitTime($order,$order_limit_time){}

    #获取店铺指定时间范围内的订单
    public function getOrderList($start_time,$end_time) {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>array(),'is_update_time'=>'false');
        $orderModel = app::get('ome')->model('orders');
        $params = array(
            'start_time' => date("Y-m-d H:i:s",$start_time),
            'end_time'   => date("Y-m-d H:i:s",$end_time),
            'page_size'  => 100,
            'fields'     => 'tid,status,pay_status,ship_status,modified',
            'page_no'    => 1,
        );

        $channel = $this->__channelObj->channel;


        $trades = array();$lastmodify = null;
        do {
            $title = sprintf('获取店铺%s(%s-%s内)的订单%s',$channel['name'],$params['start_time'],$params['end_time'],$params['page_no']);

            $return_data = $this->__caller->call(SHOP_GET_TRADES_SOLD_RPC,$params,array(),$title,10,$channel['shop_id']);
            if ($return_data['data']) $return_data['data'] = @json_decode($return_data['data'], true);

            if ($return_data['rsp'] != 'succ') break;

            if (($params['page_no']-1)*$params['page_size']>intval($return_data['data']['total_results'])) break;

            $tids = array();
            foreach((array)$return_data['data']['trades'] as $t){
                $trades[$t['tid']] = $t;
                
                $tids[] = $t['tid'];

                $lastmodify = strtotime($t['modified']);
            }

            if ($tids) {
                $erporders = $orderModel->getList('outer_lastmodify,order_bn',array('order_bn'=>$tids,'shop_id'=>$channel['shop_id']));
                // 判断是否漏单
                foreach ($erporders as $order) {
                    if ($order['outer_lastmodify']>=strtotime($trades[$order['order_bn']]['modified'])) {
                        unset($trades[$order['order_bn']]);
                    }
                }
            }

            $params['page_no']++;
        } while (true);

        $return = array(
            'rsp'        => $return_data['rsp'] == 'succ' ? 'success' : 'fail',
            'msg'        => ($return_data['rsp'] == 'succ' && !$trades) ? '未发现漏单' : $return_data['msg'],
            'msg_id'     => $return_data['msg_id'],
            'data'       => $trades,
            'lastmodify' => $lastmodify,
        );

        return $return;
    }

    public function cleanStockFreeze($order){}
}