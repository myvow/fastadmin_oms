<?php
/**
 * 收货人信息数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Consignee extends Abstracts
{
    /**
     * 数据转换
     *
     * @return void
     * @author  
     **/
    public function convert($ordersdf)
    {
        if ($ordersdf['consignee'])
        {
            $area = $ordersdf['consignee']['area_state'] . '/' . $ordersdf['consignee']['area_city'] . '/' . $ordersdf['consignee']['area_district'];
            
            $this->_newOrder['consignee']['name']      = $ordersdf['consignee']['name'];
            $this->_newOrder['consignee']['area']      = $area;
            $this->_newOrder['consignee']['addr']      = $ordersdf['consignee']['addr'];
            $this->_newOrder['consignee']['zip']       = $ordersdf['consignee']['zip'];
            $this->_newOrder['consignee']['telephone'] = $ordersdf['consignee']['telephone'];
            $this->_newOrder['consignee']['email']     = $ordersdf['consignee']['email'];
            $this->_newOrder['consignee']['r_time']    = $ordersdf['consignee']['r_time'];
            $this->_newOrder['consignee']['mobile']    = $ordersdf['consignee']['mobile'];
        }
    }

    /**
     * 修改收货人
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        $process_status = array('unconfirmed','confirmed','splitting','splited');
        if ($ordersdf['consignee'] && in_array($this->_platform->_tgOrder['process_status'], $process_status) && $this->_platform->_tgOrder['ship_status'] == '0')
        {
            $area = $ordersdf['consignee']['area_state'] . '/' . $ordersdf['consignee']['area_city'] . '/' . $ordersdf['consignee']['area_district'];
            kernel::single('ome_func')->region_validate($area);

            $consignee = array();
            $consignee['name']      = $ordersdf['consignee']['name'];
            $consignee['area']      = $area;
            $consignee['addr']      = $ordersdf['consignee']['addr'];
            $consignee['zip']       = $ordersdf['consignee']['zip'];
            $consignee['telephone'] = $ordersdf['consignee']['telephone'];
            $consignee['email']     = $ordersdf['consignee']['email'];
            $consignee['r_time']    = $ordersdf['consignee']['r_time'];
            $consignee['mobile']    = $ordersdf['consignee']['mobile'];

            $diff_consignee = array_udiff_assoc($consignee, $this->_platform->_tgOrder['consignee'],array($this,'comp_array_value'));

            if ($diff_consignee) {
                $this->_newOrder['consignee'] = $diff_consignee;
            }
        }
    }
}