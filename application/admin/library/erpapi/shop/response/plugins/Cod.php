<?php
/**
 * 货到付款插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Cod extends Abstracts
{
    public function convert($ordersdf)
    {
        $codsdf = array();
        $spe_shops = array('vjia','360buy','dangdang','yihaodian');
        
        if($ordersdf['shipping']['is_cod'] == 'true')
        {
            if (in_array($ordersdf['node_type'], $spe_shops))
            {
                foreach((array) $ordersdf['other_list'] as $val)
                {
                    if($val['type']=='unpaid'){
                        $unpaidprice = $val['unpaidprice'];
                        break;
                    }
                }
                
                $codsdf['receivable'] = (isset($unpaidprice)) ? $unpaidprice : ($ordersdf['total_amount'] - $ordersdf['payed']);
            }else{
                $codsdf['receivable'] = $ordersdf['total_amount'];
            }
            $codsdf['order_id'] = null;
        }
        
        return $codsdf; 
    }
    
    
    
    
    
    
    
	/**
	 * 到付保存
	 *
	 * @return void
	 * @author 
	 **/
	public function postCreate($order_id,$codinfo)
	{
		$orderExtendObj = app::get('ome')->model('order_extend'); 
	
		$codinfo['order_id'] = $order_id;
	
		$orderExtendObj->save($codinfo);
	}
	
	  /**
	   * 到付更新
	   *
	   * @param Array 
	   * @return void
	   * @author 
	   **/
	  public function postUpdate($order_id,$codinfo)
	  {
		$orderExtendObj = app::get('ome')->model('order_extend'); 
	
		$codinfo['order_id'] = $order_id;
	
		$orderExtendObj->save($codinfo);
	  }
}