<?php
/**
 * 管理员信息Lib类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome;

use app\admin\library\Auth;

class Users
{
    /**
     * 获取管理员登录信息
     * 
     * @return array
     */
    public function getDesktopUser()
    {
        $authLib = Auth::instance();
        $operInfo = array();
        
        if($authLib->isLogin())
        {
            $operInfo['op_id'] = $authLib->id;
            $operInfo['op_name'] = $authLib->username;
        }
        else 
        {
            $operInfo = $this->get_system();
        }
        
        return $operInfo;
    }
    
    /**
     * 获取内定的system账号信息
     */
    public function get_system()
    {
        $opInfo = array(
                'op_id' => 16777215,
                'op_name' => 'system'
        );
        
        return $opInfo;
    }
    
}