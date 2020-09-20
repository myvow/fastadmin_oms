<?php
/**
 * 配送方式数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Shipping extends Abstracts
{
    public function convert($ordersdf)
    {
        if ($ordersdf['shipping'])
        {
            $this->_newOrder['shipping']['shipping_name'] = $ordersdf['shipping']['shipping_name'];
            $this->_newOrder['shipping']['cost_shipping'] = (float)$ordersdf['shipping']['cost_shipping'];
            $this->_newOrder['shipping']['is_protect']    = $ordersdf['shipping']['is_protect'] ? $ordersdf['shipping']['is_protect'] : 'false';
            $this->_newOrder['shipping']['cost_protect']  = (float)$ordersdf['shipping']['cost_protect'];
            $this->_newOrder['shipping']['is_cod']        = $ordersdf['shipping']['is_cod'] == 'true' ? 'true' : 'false';
        }
    }
    
    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        if ($ordersdf['shipping'])
        {
             $shipping['shipping_name'] = $ordersdf['shipping']['shipping_name'];
             $shipping['cost_shipping'] = $ordersdf['shipping']['cost_shipping'];
             $shipping['is_protect']    = $ordersdf['shipping']['is_protect'];
             $shipping['cost_protect']  = $ordersdf['shipping']['cost_protect'];
             $shipping['is_cod']        = $ordersdf['shipping']['is_cod'];
             
             $shipping = array_filter($shipping,array($this,'filter_null'));
             $diff = array_udiff_assoc($shipping, $this->_platform->_tgOrder['shipping'],array($this,'comp_array_value'));
             
             if ($diff)
             {
                 $this->_newOrder['shipping'] = array_merge((array)$this->_newOrder['shipping'],$diff);
             }
        }
    }
}