<?php
/**
 * 作废，不再使用
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Confirmreceipt extends Abstracts
{
    public function convert($ordersdf)
    {
        $extend = array();

        if ($ordersdf['end_time']){
            $extend['end_time'] = $ordersdf['end_time'];
        }

        return $extend;
    }


  /**
   *
   * @param Array
   * @return void
   * @author
   **/
    public function postUpdate($order_id,$extendinfo)
    {
        $orderObj = app::get('ome')->model('orders');

        if ($extendinfo['end_time']){
            $orderObj->update(array('end_time'=>$extendinfo['end_time']),array('order_id'=>$order_id));
        }
    }
}
