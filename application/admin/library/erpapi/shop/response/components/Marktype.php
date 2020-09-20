<?php
/**
 * 订单备注旗标数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Marktype extends Abstracts
{
    /**
     * 订单格式转换
     * 
     * @return void
     **/
    public function convert($ordersdf)
    {
        if ($ordersdf['mark_type']) {
            $this->_newOrder['mark_type'] = $ordersdf['mark_type'];
        }
    }
    
    /**
     * 更新订单旗标
     * 
     * @return void
     **/
    public function update()
    {
        if ($ordersdf['mark_type'] && $ordersdf['mark_type'] != $this->_platform->_tgOrder['mark_type']) {
            $this->_newOrder['mark_type'] = $ordersdf['mark_type'];
        }
    }
}