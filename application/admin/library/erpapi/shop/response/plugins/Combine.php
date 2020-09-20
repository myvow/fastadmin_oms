<?php
/**
 * 系统自动审单插件类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

use think\Config;

use app\admin\library\erpapi\shop\response\plugins\Abstracts;

class Combine extends Abstracts
{
    public function convert($ordersdf)
    {
        $combine    = array();
        
        //开启系统自动审单(默认:忽略可合并的订单)
        $cfg_combine = self::$_config['shop_order_auto_combine'];
        
        if($cfg_combine == 'true')
        {
            //过滤手动拉下来的订单
            if($ordersdf['auto_combine'] !== false)
            {
                $combine['order_id']    = null;
            }
        }
        
        if($ordersdf['cnAuto'] == 'true'){
            $combine['cnAuto'] = 'true';
        }
        
        return $combine;
    }
    
    
    
    
    
    
    /**
     * 订单完成后处理
     *
     * @return void
     * @author
     **/
    public function postCreate($order_id, $combine)
    {
        $pay_status    = false;
        
        //支付状态读取订单表(预售功能定制)
        $order_info    = app::get('ome')->model('orders')->dump(array('order_id'=>$order_id), 'pay_status, status');
        
        //订单必须已支付OR货到付款,并且过滤单拉的订单
        if(($order_info['pay_status'] == '1' || $order_info['shipping']['is_cod'] == 'true') && $order_info['status'] == 'active')
        {
            //执行自动审单
            kernel::single('ome_order')->auto_order_combine($order_id,$combine);
        }
    }
}