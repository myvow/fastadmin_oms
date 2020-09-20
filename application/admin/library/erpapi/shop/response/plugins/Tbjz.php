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

class Tbjz extends Abstracts
{
    public function convert($ordersdf)
    {
      $jzorder_list = array();

      foreach ((array)$ordersdf['other_list'] as $other ) {
          if ($other['type'] == 'category') {
              $jzorder_list[] = array(
                'cid' => $other['cid'],
                'oid' => $other['oid'],
              );
          }
      }
              
      return $jzorder_list;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$jzorder_list)
    {
        $jzObj = app::get('ome')->model('tbjz_orders');


        foreach ($jzorder_list as $key=>$order ) {
          $jzorder_list[$key]['order_id'] = $order_id;
        }

        $sql = ome_func::get_insert_sql($jzObj,$jzorder_list);
        kernel::database()->exec($sql);
    }
}