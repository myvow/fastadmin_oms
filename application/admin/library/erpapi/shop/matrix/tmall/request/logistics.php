<?php
/**
 * @author ykm 2017/2/16
 * @describe 物流相关 请求接口类
 */

class erpapi_shop_matrix_tmall_request_logistics extends erpapi_shop_request_logistics {

    public function getCorpServiceCode($sdf) {
        $params = array(
            'cp_code' => $sdf['cp_code']
        );
        $title = '获取物流商服务类型';
        $result = $this->__caller->call(STORE_CN_WAYBILL_II_SEARCH,$params,array(),$title, 10, $params['cp_code']);

        return $result;
    }
}