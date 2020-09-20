<?php
/**
 * 订单类型插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

use app\admin\library\ome\Orders;

class Ordertype extends Abstracts
{
    public function convert($ordersdf)
	{
	    $ordertypesdf = array();
	    $orderType = get_normal_order_type();
	    
	    $ordersdf['shop']['shop_id'] = $ordersdf['shop_id'];
	    
	    if((in_array($ordersdf['order_type'], $orderType) || empty($ordersdf['order_type'])))
	    {
	        if($platform->_newOrder['order_type'] == 'vopczc')
	        {
	            $ordertypesdf['vopczc_order_status'] = 'true';
			}
		}
		elseif($ordersdf['order_type'] == 'platform')
		{
		    if($ordersdf['ship_status'] == '1') {
		        $ordertypesdf['platform_consign'] = 'true';
			}
		}
		
		return $ordertypesdf;
	}
	
	/**
	 * 订单创建完成后,处理的事项
	 * 
	 * @param int $order_id
     * @param array $ordertypesdf
     * @return bool
	 */
	public function postCreate($order_id, $ordertypesdf)
	{
	    //没有用到...
	    $orderLib = new Orders;
	    
	    $filter = array('order_id'=>$order_id);
		$order = $orderLib->getOrderInfo($filter);
		
		
		/***
		//平台自发货订单,模拟进行完成发货
		if($ordertypesdf['platform_consign'] == 'true') {
		    kernel::single('ome_order_platform')->deliveryConsign($order_id);
		}
		
		//唯品会订单,获取订单的状态并更新到订单扩展表里
		if($ordertypesdf['vopczc_order_status'] == 'true') {
		    kernel::single('ome_service_order')->exportOrder(array($order));
		}
		***/
		
		return true;
	}
	
	/**
	 * 订单更新成功后,处理的事项
	 * 
	 * @param int $order_id
	 * @param array $brush
	 * @return bool
	 */
	public function postUpdate($order_id, $brush)
	{
	    return true;
	}
}