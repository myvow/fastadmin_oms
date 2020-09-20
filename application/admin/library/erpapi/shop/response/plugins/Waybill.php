<?php
/**
 * 作废，不再使用
 * 
 * 爱库存订单信息中拿到快递单
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Waybill extends Abstracts
{
    public function convert($ordersdf)
    {
        $waybillSdf = array();

        if (!$ordersdf['position'] && !$ordersdf['position_no']) return $waybillSdf;

        $waybillSdf['position']       = $ordersdf['position'];
        $waybillSdf['position_no']    = $ordersdf['position_no'];
        $waybillSdf['waybill_number'] = $ordersdf['shipping']['shipping_id'];
        $waybillSdf['logistics_code'] = $ordersdf['shipping']['shipping_name'];

        return $waybillSdf;
    }

    /**
     * 订单完成后处理
     *
     * @return void
     * @author
     **/
    public function postCreate($order_id,$waybillSdf)
    {
        $waybillModel       = app::get('logisticsmanager')->model('waybill');
        $waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');

        foreach (explode(',', $waybillSdf['waybill_number']) as $logi_no) {
            $waybill = array(
                'waybill_number' => $logi_no,
                'logistics_code' => $waybillSdf['logistics_code'],
                'create_time'    => time(),
                'status'         => '1',
            );
            $waybillModel->save($waybill);

            $waybillExtend = array(
                'waybill_id'  => $waybill['id'],
                'position'    => $waybillSdf['position'],
                'position_no' => $waybillSdf['position_no'],
            );
            $waybillExtendModel->save($waybillExtend);
            
        }
    }


}