<?php
/**
 * 发货单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_360buy_request_delivery extends erpapi_shop_request_delivery
{

    protected $_delivery_errcode = array(
        'w06000'=>'成功',
        'w06001'=>'其他',
        'w06101'=>'已经出库',
        'w06102'=>'出库订单不存在或已被删除',
        'w06104'=>'订单状态不为等待发货',
        'w06105'=>'订单已经发货',
        'w06106'=>'正在出库中', 
    );

    /**
     * 发货请求参数
     *
     * @return void
     * @author 
     **/
    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);

        $param['360buy_business_type'] = $this->__channelObj->channel['addon']['type'];

        if ('SOPL' == $this->__channelObj->channel['addon']['type']) {
            $param['package_num'] = $sdf['itemNum'];
        }

        return $param;
    }

   /**
     * 发货回调
     *
     * @return void
     * @author
     **/
    public function confirm_callback($response, $callback_params)
    {

        $failApiModel = app::get('erpapi')->model('api_fail');
        $order_id        = $callback_params['order_id'];
        $err_msg = $response['err_msg'];
        $rsp             = $response['rsp'];
        $rsp=='success' ? 'succ' : $rsp;
        if($callback_params['company_code'] == 'JDCOD'){

            if($rsp == 'fail' && ($err_msg == '运单没有在青龙系统生成' || $err_msg == '平台连接后端服务不可用')){
                $response['msg_code'] = 'G40012';
            }
        }
        $callback_params['obj_type'] = 'JDDELIVERY';
        $rs = parent::confirm_callback($response,$callback_params);
        return $rs;
    }
}