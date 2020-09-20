<?php
/**
 * 发票数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Tax extends Abstracts
{
    public function convert($ordersdf)
    {
        if($ordersdf['is_tax'] === 'true' || $ordersdf['is_tax'] === true  || $ordersdf['is_tax'] === '1'){
            $this->_newOrder['is_tax'] = 'true';
        }else{
            $this->_newOrder['is_tax'] = 'false';
        }
        
        $this->_newOrder['cost_tax']         = (float)$ordersdf['cost_tax'];
        $this->_newOrder['tax_no']           = $ordersdf['tax_no'];
        $this->_newOrder['tax_title']        = $ordersdf['tax_title'];
        $this->_newOrder['ship_tax']         = $ordersdf['payer_register_no'];
        $this->_newOrder['business_type']    = $ordersdf['payer_register_no'] ? 1 : 0;
        
        $shop_id = $ordersdf['shop_id'];
        
        //这里判断开票方式是电子or纸质 获取开票信息
        $invoice_kind = intval($ordersdf['invoice_kind']);
        $mode = '0';
        if($invoice_kind == 1)
        {
            $mode = '1';
        }
        
        /***
        //安装发票
        if(app::get('invoice')->is_installed())
        {
            $rs_invoice_setting = kernel::single('invoice_common')->getInOrderSetByShopId($shop_id,$mode);
            //前端店铺下tab发票配置页 前端店铺下单发票设置
            if($this->_newOrder['is_tax'] == 'false' && $rs_invoice_setting['force_tax_switch'] == '1'){
                $this->_newOrder['is_tax'] = 'true';
                $this->_newOrder['tax_title'] = $rs_invoice_setting['force_tax_title'];
            }
        }
        ***/
        
        //需要开票的并且是要电子发票的
        if($this->_newOrder['is_tax'] == 'true' && $invoice_kind == 1)
        {
            $this->_newOrder['invoice_mode'] = '1';
        }
    }
    
    public function update()
    {
        if ($ordersdf['tax_title'] && $ordersdf['tax_title'] != $this->_platform->_tgOrder['tax_title']) {
            $this->_newOrder['tax_title'] = $ordersdf['tax_title'];
        }
        
        $ordersdf['is_tax'] = ($ordersdf['is_tax'] === 'true' || $ordersdf['is_tax'] === true) ? 'true' : 'false';

        if ($ordersdf['is_tax'] != $this->_platform->_tgOrder['is_tax']) {
            $this->_newOrder['is_tax'] = $ordersdf['is_tax'];
        }

        if ($ordersdf['tax_no'] && $ordersdf['tax_no'] != $this->_platform->_tgOrder['tax_no']) {
            $this->_newOrder['tax_no'] = $ordersdf['tax_no'];
        }

        if ($ordersdf['payer_register_no'] && $ordersdf['payer_register_no'] != $this->_platform->_tgOrder['ship_tax']) {
            $this->_newOrder['ship_tax'] = $ordersdf['payer_register_no'];
            $this->_newOrder['business_type'] = $ordersdf['payer_register_no'] ? 1 : 0;
        }
    }
}