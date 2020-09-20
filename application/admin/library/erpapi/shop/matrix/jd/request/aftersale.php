<?php
/**
 * @desc
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrix_360buy_request_aftersale extends erpapi_shop_request_aftersale {
    protected function __afterSaleApi($status) {
        switch($status){
            case '3':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '5':
                $api_method = SHOP_REFUSE_RETURN_GOOD;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }

    protected function __formatAfterSaleParams($aftersale,$status) {

        $oper = kernel::single('ome_func')->getDesktopUser();

        if ( $status == 5 ) {
            $extra  = app::get('ome')->model('return_product_360buy')->db_dump(array ('return_id' => $aftersale['return_id']));
            $reship = app::get('ome')->model('reship')->db_dump(array ('return_id' => $aftersale['return_id']), 'reship_id');
            $order  = app::get('ome')->model('orders')->db_dump(array ('order_id'  => $aftersale['order_id']), 'order_bn');

            $params = array(
                'operate_pin'         => $oper['op_id'],
                'operate_nick'        => $oper['op_name'],
                'service_id'          => $aftersale['return_bn'],
                'approve_notes'       => '商家拒绝',
                'sys_version'         => $extra['refund_version'],
                'approve_reason_cid1' => $extra['approve_reason'],
                'tid'                 => $order['order_id'],
                'operate_remark'      => $aftersale['memo'],
            );

            $params['check_type'] = $reship ? 'cancel' : 'refuse';

        } elseif ($status == 3) {
            $extra = app::get('ome')->model('return_product_360buy')->dump(array ('return_id' => $aftersale['return_id']));

            if ( $extra['contact_id'] ) {
                $filter = array ('shop_id' => $extra['shop_id'],'contact_id' => $extra['contact_id']);
            } else {
                $filter = array ('shop_id' => $extra['shop_id'], 'get_def' => 'true');
            }
            $return_address = app::get('ome')->model('return_address')->db_dump($filter);

            $apply_detail   = @json_decode($extra['apply_detail'],true);

            $params = array(
                'operate_pin'           => $oper['op_id'],
                'operate_nick'          => $oper['op_name'],
                'service_id'            => $aftersale['return_bn'],
                'approve_notes'         => '商家同意',
                'sys_version'           => $extra['refund_version'],
                'approve_reason_cid1'   => $extra['approve_reason'],
                'return_contact_name'   => $return_address['contact_name'],
                'return_contact_tel'    => $return_address['mobile_phone'],
                'return_zipcode'        => $return_address['zip_code'],
                'return_province'       => $return_address['province'],
                'return_city'           => $return_address['city'],
                'return_county'         => $return_address['country'],
                'return_detail_address' => $return_address['addr'],
                'return_address_type'   => $return_address['address_type'],
                'apply_detail_id_list'  => array(),
            );

            $apply_detail_id_list = array ();
            foreach ((array)$apply_detail as $key => $value) {
                $apply_detail_id_list[] = $value['applyDetailId'];
            }
            $params['apply_detail_id_list'] = json_encode($apply_detail_id_list);



            switch ($extra['pickware_type']) {
                case '40': // 客户发货
                    $params['check_type'] = 'send';
                    break;
                case '4': // 上门取件
                    $params['check_type'] = 'homepick';

                    $pick_address   = @json_decode($extra['pick_address'],true);

                    $params['pickup_contact_name']   = $aftersale['order']['ship_name'];
                    $params['pickup_contact_tel']    = $aftersale['order']['ship_mobile'];
                    $params['pickup_province']       = $pick_address['provinceCode'];
                    $params['pickup_city']           = $pick_address['cityCode'];
                    $params['pickup_county']         = $pick_address['countyCode'];
                    $params['pickup_detail_address'] = $pick_address['detailAddress'];
                    break;
                case '7': // 客户送货
                    $params['check_type'] = 'delivery';
                    break;
            }
        }

        return $params;
    }
}