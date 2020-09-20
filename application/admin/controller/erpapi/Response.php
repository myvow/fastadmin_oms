<?php 
namespace app\admin\controller\erpapi;

use think\Config;

//use app\common\controller\Backend;
//use app\api\controller\Common;

/**
 * ERP接口响应处理
 */
class Response 
{
    public function add()
    {
        //加载此应用下的配置
        //Config::load(APP_PATH . 'erpapi/config.php');
        //return Config::get('erpapi_datalist');
        
        return array('rsp'=>'success', 'msg'=>'响应成功了abc===application...');
    }
    
    public static function edit()
    {
        return array('rsp'=>'success', 'msg'=>'这是编辑订单===application...');
    }
}