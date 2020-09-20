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

class Fxw extends Abstracts
{
    public function convert($ordersdf)
    {
      $fxw = array();

      if ($ordersdf['dealer_order_id']) {
        $fxw['dealer_order_id'] = $ordersdf['dealer_order_id'];
      }
              
      return $fxw;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$fxw)
    {
      $fxw['order_id'] = $order_id;
      
      app::get('ome')->model('fxw_orders')->insert($fxw);
    }
}