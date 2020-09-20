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

class Tbgift extends Abstracts
{
    public function convert($ordersdf)
    {
      $tbgift = array();

      if ('true' == app::get('ome')->getConf('ome.preprocess.tbgift')) {
          $tbgift['gift']     = $ordersdf['other_list'];
          $tbgift['order_id'] = null;
      }

      return $tbgift;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$tbgift)
    {
      kernel::single('ome_preprocess_tbgift')->save($order_id,$tbgift['gift']);
    }
}