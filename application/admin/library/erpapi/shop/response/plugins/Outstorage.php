<?php
/**
 * 作废，不再需要
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Outstorage extends Abstracts
{
    public function convert($ordersdf)
    {
        $outstorage = array();
        $outstorage['order_bn'] = $ordersdf['order_bn'];
        $outstorage['shop_id'] = $ordersdf['shop_id'];
        $outstorage['order_id'] = null;
        
        return $outstorage;
    }

    /**
     * 订单创建完成后,处理的事项
     *
     * @param int $order_id
     * @param array $outstorage
     * @return bool
     */
    public function postCreate($order_id, $outstorage)
    {
        return true;
    }
    
    /**
     * 订单更新成功后,处理的事项
     *
     * @param int $order_id
     * @param array $outstorage
     * @return bool
     */
    public function postUpdate($order_id, $outstorage)
    {
        return true;
    }
}