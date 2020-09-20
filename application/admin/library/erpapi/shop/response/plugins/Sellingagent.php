<?php
/**
 * 代销商插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;
use app\admin\library\ome\Common;

class Sellingagent extends Abstracts
{
    public function convert($ordersdf)
    {
        $funLib = new Func;
        
        $sellingagent = array();
        
        if ($ordersdf['selling_agent']['member_info']['uname'])
        {
            $sellingagent['uname']          = $ordersdf['selling_agent']['member_info']['uname'];
            $sellingagent['name']           = $ordersdf['selling_agent']['member_info']['name'];
            $sellingagent['level']          = $ordersdf['selling_agent']['member_info']['level'];
            $sellingagent['birthday']       = $ordersdf['selling_agent']['member_info']['birthday'];
            $sellingagent['sex']            = $ordersdf['selling_agent']['member_info']['sex'];
            $sellingagent['email']          = $ordersdf['selling_agent']['member_info']['email'];
            $sellingagent['addr']           = $ordersdf['selling_agent']['member_info']['addr'];
            $sellingagent['zip']            = $ordersdf['selling_agent']['member_info']['zip'];
            $sellingagent['mobile']         = $ordersdf['selling_agent']['member_info']['mobile'];
            $sellingagent['tel']            = $ordersdf['selling_agent']['member_info']['telephone'];
            $sellingagent['qq']             = $ordersdf['selling_agent']['member_info']['qq'];
            $sellingagent['website_name']   = $ordersdf['selling_agent']['website']['name'];
            $sellingagent['website_domain'] = $ordersdf['selling_agent']['website']['domain'];
            $sellingagent['website_logo']   = $ordersdf['selling_agent']['website']['logo'];
            $sellingagent['addon']          = '';
            
            if ($sellingagent['area_state'] && $sellingagent['area_city'] && $sellingagent['area_district'])
            {
                $area = $sellingagent['area_state'] . '/' . $sellingagent['area_city'] . '/'.$sellingagent['area_district'];
                $funLib->region_validate($area);
                
                $sellingagent['area'] = $area;
            }
            
            //代销商发货人和发货地址都必须存在
            if($ordersdf['seller_address'] && $ordersdf['seller_name'])
            {
                $sellingagent['seller_name']     = $ordersdf['seller_name'];       #卖家姓名
                $sellingagent['seller_mobile']   = $ordersdf['seller_mobile'];     #卖家电话号码
                $sellingagent['seller_phone']    = $ordersdf['seller_phone'];      #卖家电话号码
                $sellingagent['seller_zip']      = $ordersdf['seller_zip'];        #卖家的邮编
                $sellingagent['seller_address']  = $ordersdf['seller_address'];    #发货人的详细地址
                $sellingagent['print_status']    = '1';
                
                if($sellingagent['seller_state'] && $sellingagent['seller_city'] && $sellingagent['seller_district'])
                {
                    $area = $sellingagent['seller_state'] . '/' . $sellingagent['seller_city'] . '/'.$sellingagent['seller_district'];
                    $funLib->region_validate($area);
                    
                    $sellingagent['area'] = $area;
                }
            }
        }
        
        
        /***
        // 如果是更新
        if ($sellingagent && $platform->_tgOrder['order_id'])
        {
            $agentModel = app::get('ome')->model('order_selling_agent');
            $oldagent = $agentModel->getList('*',array('order_id'=>$platform->_tgOrder['order_id']),0,1);

            $sellingagent = array_filter($sellingagent,array($this,'filter_null'));
            $sellingagent = array_udiff_assoc((array) $sellingagent, (array) $oldagent[0],array($this,'comp_array_value'));
        }
        ***/
        
        return $sellingagent;
    }


    
    
    
    
    
    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$sellingagent)
    {
        $agentModel = app::get('ome')->model('order_selling_agent'); 

        $sellingagent['order_id'] = $order_id;
        
        $agentModel->insert($sellingagent);
    }

  /**
   *
   * @param Array 
   * @return void
   * @author 
   **/
  public function postUpdate($order_id,$sellingagent)
  {
    $agentModel = app::get('ome')->model('order_selling_agent'); 
    $agentModel->update($sellingagent,array('order_id'=>$order_id));
  }
}