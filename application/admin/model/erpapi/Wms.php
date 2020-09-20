<?php 
namespace app\admin\model\erpapi;

use think\Model;

class Wms extends Model
{
    public function __construct()
    {
        
    }
    
    public function getOrderInfo()
    {
        return array('rsp'=>'success', 'msg'=>'这是获取订单信息===application...');
    }
    
    public static function setOrderInfo()
    {
        return array('rsp'=>'success', 'msg'=>'这是设置订单信息===application...');
    }
}
