<?php
/**
 * 客户备注数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Custommemo extends Abstracts
{
    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert($ordersdf)
    {
        $custom_mark = $ordersdf['custom_mark'];
        
        if (!empty($custom_mark))
        {
            $custommemo[] = array(
                'op_name' => $ordersdf['shop_name'],
                'op_time' => date("Y-m-d H:i:s"),
                'op_content' => htmlspecialchars($custom_mark),
            );
        }

        if (in_array($ordersdf['node_type'], array('taobao','paipai')) && 'ems' == strtolower($ordersdf['shipping']['shipping_name']))
        {
            $custommemo[] = array(
                'op_name'    =>$ordersdf['shop_name'],
                'op_time'    =>date("Y-m-d H:i:s",time()),
                'op_content' =>'系统：用户选择了 EMS 的配送方式',
            );
        }

        if ($custommemo)
            $this->_newOrder['custom_mark'] = serialize($custommemo);
    }

    /**
     * 更改客户备注
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        $old_custom_mark = array();
        if ($this->_platform->_tgOrder['custom_mark'] && is_string($this->_platform->_tgOrder['custom_mark'])) {
            $old_custom_mark = unserialize($this->_platform->_tgOrder['custom_mark']);
        }

        $last_custom_mark = array();
        foreach ((array) $old_custom_mark as $key => $value) {
            if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

            if ( intval($value['op_time']) > intval($last_custom_mark['op_time']) && ($value['op_name'] == $ordersdf['shop_name'] || in_array($ordersdf['node_type'],ome_shop_type::shopex_shop_type()))) {
                $last_custom_mark = $value;
            }
        }

        $custom_mark = $ordersdf['custom_mark'];
        if (!is_null($custom_mark) && $custom_mark !== '' && $last_custom_mark['op_content'] != $custom_mark) {
            $custom = (array) $old_custom_mark;
            $custom[] = array(
                'op_name'    => $ordersdf['shop_name'],
                'op_content' => $custom_mark,
                'op_time'    => date('Y-m-d H:i:s')
            );
        }

        if ($custom) {
            $this->_newOrder['custom_mark'] = serialize($custom);
        }
    }

}