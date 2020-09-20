<?php
/**
 * Shopex处理类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\shopex;

use app\admin\library\erpapi\shop\response\Order as Orders;

class Order extends Orders
{
    /**
     * 可接收未付款订单
     *
     * @var string
     **/
    protected $_accept_unpayed_order = true;

    /**
     * 创建订单的插件
     *
     * @return void
     * @author 
     **/
    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        // 如果是0元订单，注销支付单插件
        if (bccomp('0.000', $this->_ordersdf['total_amount'],3) == 0) {
            $key = array_search('payment', $plugins);
            if ($key !== false) {
                unset($plugins[$key]);
            }
        }

        return $plugins;
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'promotion';
        $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        $plugins[] = 'cod';

        return $plugins;
    }

    /**
     * 更新接收,以前端状态为主
     *
     * @return void
     * @author 
     **/
    protected function _canUpdate()
    {
        if (!in_array($this->_ordersdf['status'], array('active','finish','close','dead'))) {
            $this->__apilog['result']['msg'] = '不明订单状态不接收';
            return false;
        }

        if ($this->_ordersdf['status'] == 'close') {
            $this->__apilog['result']['msg'] = '关闭订单不接收';
            return false;
        }

        if ($this->_tgOrder['status'] == 'dead') {
            $this->__apilog['result']['msg'] = 'ERP取消订单，不做更新';
            return false;
        }

        if ($this->_update_accept_dead_order === false && $this->_ordersdf['status'] == 'dead') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }

        if ($this->_ordersdf['ship_status'] == '0' &&  $this->_tgOrder['ship_status'] != '0') {
            $this->__apilog['result']['msg'] = 'ERP订单已发货，不做更新';
            return false;
        }

        return true;
    }

    protected function _analysis()
    {
        parent::_analysis();

        // 判断是否有退款
        if ($this->_ordersdf['payed'] > $this->_ordersdf['total_amount']) {
            $this->_ordersdf['pay_status'] = '6';
            $this->_ordersdf['pause']      = 'true';
        }
    }
}