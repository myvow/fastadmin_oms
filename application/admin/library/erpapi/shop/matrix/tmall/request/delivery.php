<?php
/**
 * 发货单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_tmall_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_delivery_apiname($sdf)
    {
        if($sdf['is_virtual']) { //虚拟发货
            return SHOP_LOGISTICS_DUMMY_SEND;
        }
        // 如果开启了线上发货
        if ('on' == app::get('ome')->getConf('ome.delivery.method') && $sdf['orderinfo']['sync'] == 'none') {
            $api_name = SHOP_LOGISTICS_ONLINE_SEND;
        } else {
            $api_name = $sdf['orderinfo']['is_cod'] == 'true' ? SHOP_LOGISTICS_ONLINE_SEND : SHOP_LOGISTICS_OFFLINE_SEND;
        }
        
        // 如果是家装，调用家装接口
        if ($sdf['jzpartner']) {
            $api_name = SHOP_WLB_ORDER_JZ_CONSIGN;
        }

        #检查是不是天猫国际
        if($sdf['corp_type'] == 1 && (($sdf['orderinfo']['order_bool_type'] & ome_order_bool_type::__INTERNAL_CODE) ==  ome_order_bool_type::__INTERNAL_CODE)){
            $api_name = SHOP_WLB_THREEPL_OFFLINE_SEND;
        }
        return $api_name;
    }

    /**
     * 发货请求参数
     *
     * @return void
     * @author 
     **/
    protected function get_confirm_params($sdf)
    {
        if ($sdf['jzpartner']) {// 家装参数
            
            $jz_top_args = array(
                 'mail_no'         => $sdf['logi_no'],
                'zy_consign_time' => date('Y-m-d H:i:s',$sdf['delivery_time']),
                'package_remark'  => '',
                'zy_company'      => $sdf['logi_name'],
                'zy_phone_number' => $this->__channelObj->channel['mobile'] ? $this->__channelObj->channel['mobile'] : $this->__channelObj->channel['tel'],

            );

            $param = array(
                'tid'             =>$sdf['orderinfo']['order_bn'],
                'lg_tp_dto'     => json_encode($sdf['jzpartner']['lg_tp_dto']),
                'ins_tp_dto'=>json_encode($sdf['jzpartner']['ins_tp_dto']),
                'jz_top_args' => json_encode($jz_top_args),
            );
        } elseif(($sdf['orderinfo']['order_bool_type'] & ome_order_bool_type::__INTERNAL_CODE) ==  ome_order_bool_type::__INTERNAL_CODE){
            $param['trade_id'] = $sdf['orderinfo']['order_bn'];# 交易单号
            $param['waybill_no'] = $sdf['logi_no'];# 运单号
            $params['res_id'] = '';# 
            //$params['res_code'] = '';# 资源code
            //$params['from_id'] = $sdf['from_id']; # 发件人地址库id
        }else {
            $param = parent::get_confirm_params($sdf);
            
            // 拆单子单回写
            if($sdf['is_split'] == 1) {
                $param['is_split']  = $sdf['is_split'];
                $param['oid_list']  = implode(',',$sdf['oid_list']);
            }

            // 判断是否开启唯一码回写
            if ($sdf['feature']) $param['feature'] = $sdf['feature'];
        }

        return $param;
    }

    /**
     * 数据处理
     *
     * @return void
     * @author 
     **/
    protected function format_confirm_sdf(&$sdf)
    {
        parent::format_confirm_sdf($sdf);

        // 如果是家装
        if ('1' == app::get('ome')->getConf('shop.jzorder.config.'.$this->__channelObj->channel['shop_id'])) {
            $partner = $this->jzpartner_query($sdf);

            if ($partner) {
                $sdf['jzpartner'] = $partner;
            }
        }
    }
}