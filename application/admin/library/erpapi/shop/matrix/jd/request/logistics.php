<?php
class erpapi_shop_matrix_360buy_request_logistics extends erpapi_shop_request_logistics
{
    public function searchAddress_callback($response, $callback_params){
        
        if ($response['rsp']=='succ') {
            $data = json_decode($response['data'],true);
            
            foreach ((array)$data['address_list'] as $key => $value ) {
                $data['address_list'][$key]['cancel_def']   = $value['address_type'] == '0' ? 'true' : 'false';
                $data['address_list'][$key]['address_type'] = $value['address_type'];
            }

            $response['data'] = json_encode($response['data']);
        }

        return parent::searchAddress_callback($response, $callback_params);   
    }
}