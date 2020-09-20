<?php
/**
 * 订单扩展插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Orderextend extends Abstracts
{
    public function convert($ordersdf)
    {
        $extend = array();
        
        if ($ordersdf['sellermemberid']) {
            $extend['sellermemberid'] = $ordersdf['sellermemberid'];
        }
        
        //菜鸟直送推送时间
        if ($ordersdf['cn_info']) {
            $extend['push_time'] = strtotime($ordersdf['cn_info']['push_time']);
        }
        
        //交易订单指定运单信息
        if ($ordersdf['shipping']['shipping_id']) {
            $extend['platform_logi_no'] = $ordersdf['shipping']['shipping_id'];
        }
        
        return $extend;
    }

    
    
    
    
    
    
    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$extendinfo)
    {
        $orderExtendObj = app::get('ome')->model('order_extend'); 
        
        if ($extendinfo['contents']) {      
          // 判断contents是否有值
          $row = $orderExtendObj->getList('contents',array('order_id'=>$order_id));
          if ($row && $row[0]['contents']) {
            $contents = @unserialize($row[0]['contents']);

            $newcontents = array_merge((array)$contents,$extendinfo['contents']);

            $extendinfo['contents'] = serialize($newcontents);
            if ($contents == $newcontents) {
               unset($extendinfo['contents']);
            }
            
          }
        }

        if ($extendinfo) {
          $extendinfo['order_id'] = $order_id;
          $orderExtendObj->save($extendinfo);
        }
        
    }

  /**
   *
   * @param Array 
   * @return void
   * @author 
   **/
  public function postUpdate($order_id,$extendinfo)
  {
    $orderExtendObj = app::get('ome')->model('order_extend'); 



    if ($extendinfo['contents']) {      
      // 判断contents是否有值
      $row = $orderExtendObj->getList('contents',array('order_id'=>$order_id));
      if ($row && $row[0]['contents']) {
        $contents = @unserialize($row[0]['contents']);

        $newcontents = array_merge((array)$contents,$extendinfo['contents']);

        $extendinfo['contents'] = serialize($newcontents);
        if ($contents == $newcontents) {
           unset($extendinfo['contents']);
        }
      }
    }

    if ($extendinfo) {
      $extendinfo['order_id'] = $order_id;
      $orderExtendObj->save($extendinfo);
    }  
  }
}