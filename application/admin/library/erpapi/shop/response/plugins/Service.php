<?php
/**
 * 延保服务插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Service extends Abstracts
{
    public function convert($ordersdf)
    {
        $servicesdf = array();
        
        if ($ordersdf['service_order_objects']['service_order'])
        {
            foreach ((array) $ordersdf['service_order_objects']['service_order'] as $key => $value)
            {
                $servicesdf[] = array(
                    'order_id'      =>  $platform->_tgOrder['order_id'],  //==============需要修改
                    'item_oid'      =>  $value['item_oid'] ,
                    'refund_id'     =>  $value['refund_id'],
                    'sale_price'    =>  $value['sale_price'],
                    'oid'           =>  $value['oid'],
                    'tmser_spu_code'=>  $value['tmser_spu_code'],
                    'num'           =>  $value['num'],
                    'total_fee'     =>  $value['total_fee'],
                    'type_alias'    =>  $value['type_alias'],
                    'title'         =>  $value['title'],
                    'service_id'    =>  $value['service_id'],
                    'type'          =>  $value['type'],
                );
            }
        }
        
        return $servicesdf;
    }

    
    
    
    
    
    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$servicesdf)
    {

        $serviceObj = app::get('ome')->model('order_service');
        $service_price = 0;
        foreach ($servicesdf as $key=>$value){
            $service_price+=$value['total_fee'];
            $servicesdf[$key]['order_id'] = $order_id;
        }
        $sql = ome_func::get_insert_sql($serviceObj,$servicesdf);

        kernel::database()->exec($sql);

        if($service_price>0){
            kernel::database()->exec("UPDATE sdb_ome_orders SET service_price=".$service_price." WHERE order_id=".$order_id);
        }
    }


}