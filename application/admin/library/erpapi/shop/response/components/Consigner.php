<?php
/**
 * 发货人信息数据转换类
*
* @author wofeel<wofeel@126.com>
* @license http://www.baidu.com
* @version v1.0
*/
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;
use app\ome\library\Common;

class Consigner extends Abstracts
{
    /**
     * 数据转换
     *
     * @return void
     * @author 
     **/
    public function convert($ordersdf)
    {   
        if ($ordersdf['consigner'])
        {
            $area = $ordersdf['consigner']['area_state'] . '/' . $ordersdf['consigner']['area_city'] . '/' . $ordersdf['consigner']['area_district'];
            
            $this->_newOrder['consigner']['name']   = $ordersdf['consigner']['name'];
            $this->_newOrder['consigner']['area']   = $area;
            $this->_newOrder['consigner']['addr']   = $ordersdf['consigner']['addr'];
            $this->_newOrder['consigner']['zip']    = $ordersdf['consigner']['zip'];
            $this->_newOrder['consigner']['tel']    = $ordersdf['consigner']['telephone'];
            $this->_newOrder['consigner']['email']  = $ordersdf['consigner']['email'];
            $this->_newOrder['consigner']['mobile'] = $ordersdf['consigner']['mobile'];
            $this->_newOrder['consigner']['r_time'] = $ordersdf['consigner']['r_time'];
        }
    }

    /**
     * 更新发货人
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        if($ordersdf['consigner'])
        {
            $funLib = new Func;
            
            $area = $ordersdf['consigner']['area_state'] . '/' . $ordersdf['consigner']['area_city'] . '/' . $ordersdf['consigner']['area_district'];
            
            $funLib->region_validate($area);

            $consigner['name']   = $ordersdf['consigner']['name'];
            $consigner['area']   = $area;
            $consigner['addr']   = $ordersdf['consigner']['addr'];
            $consigner['zip']    = $ordersdf['consigner']['zip'];
            $consigner['tel']    = $ordersdf['consigner']['telephone'];
            $consigner['email']  = $ordersdf['consigner']['email'];
            $consigner['mobile'] = $ordersdf['consigner']['mobile'];
            
            $diff_consigneer = array_udiff_assoc($consigner, $this->_platform->_tgOrder['consigner'],array($this,'comp_array_value'));
            $consigner['r_time'] = $ordersdf['consigner']['r_time'];
            
            if ($diff_consigneer) {
                $this->_newOrder['consigner'] = $diff_consigneer;
            }  
        }
    }
}