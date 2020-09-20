<?php 
/**
 * 接口版本映射关系
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi;

class Mapping
{
    public static $shopex = array(
        'shopex_b2c' => '485',
        'ecos.b2c'   => 'ecstore',
        'public_b2c' => 'publicb2c',
        'bbc' => 'bbc',
    );
    
    public static $party = array(
        'qq_buy' => 'qqbuy',
    );
    
    public static $versionm = array(
        'shopex_b2c' => array(      // 485 前端版本 => 淘管版本
            '1' => '1',
            '2' => '2'
        ),
        'shopex_b2b' => array(      // b2b
            '1' => '1',
            '3.2' => '2',
        ),
        'ecos.b2c' => array(        // ecstore
            '1' => '1',
            '2' => '2',
        ),
    );
    
    private static $_rsp_service_mapping = array(
            'api.ome.order' => 'order',
            'api.ome.refund' => 'refund',
            'api.ome.payment' => 'payment',
            'api.ome.aftersale' => 'aftersale',
            'api.ome.aftersalev2' => 'aftersalev2',
            'api.ome.logistics' => 'logistics',
            'api.ome.remark' => 'remark',
    );
    
    private static $_rsp_method_mapping = array(
            'shop.order.add' => 'shop.order.add',
            'shop.order.status_update' => 'shop.order.status_update',
            'shop.payment.add' => 'shop.payment.add',
            'shop.aftersale.add' => 'shop.aftersale.add',
            'shop.aftersale.status_update' => 'shop.aftersale.statusUpdate',
            'shop.refund.add' => 'shop.refund.add',
            'hqepay.logistics.push' => 'hqepay.logistics.push',
    );
    
    /**
     * 获取淘管对应版本
     * 
     * @param String $node_type 店铺类型
     * @param String $api_version 前端店铺版本
     * @return number
     **/
    public function getVersion($node_type, $api_version)
    {
        if(!isset(self::$versionm[$node_type])) return 1;
        
        $mapping = self::$versionm[$node_type];
        krsort($mapping);
        
        $tgver = 1;
        foreach ($mapping as $s_ver => $t_ver) {
            if (version_compare($api_version, $s_ver,'>=')) {
                $tgver = $t_ver; break;
            }
        }
        
        return $tgver;
    }
    
    /**
     * SERVICE映射关系
     *
     * @return string
     **/
    public static function getRspServiceMapping($service)
    {
        $type = isset(self::$_rsp_service_mapping[$service]) ? self::$_rsp_service_mapping[$service] : '';
        
        return $type;
    }
    
    /**
     * 映射请求方法Method到response
     * 
     * @return bool
     */
    public static function rspServiceMapping($service, $method, $node_id)
    {
        if (isset(self::$_rsp_method_mapping[$method])) return self::$_rsp_method_mapping[$method];
        
        return false;
    }
}
