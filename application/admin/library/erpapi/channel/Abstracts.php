<?php
/**
 * 渠道抽象类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\channel;

abstract class Abstracts
{
    /**
     * 路由 matrix|openapi|prism
     *
     * @var string
     **/
    protected $__adapter = '';
    
    /**
     * 请求平台
     *
     * @var string
     **/
    protected $__platform = '';

    /**
     * 平台版本
     *
     * @var string
     **/
    protected $__ver = '1';

    /**
     * 适配器
     * 
     * @return void
     * @author 
     **/
    public function get_adapter()
    {
        return $this->__adapter;
    }

    /**
     * 平台标识(淘宝:tabao,京东:360buy)
     * 
     * @return void
     * @author 
     **/
    public function get_platform()
    {
        return $this->__platform;
    }
    
    /**
     * 版本号
     *
     * @return void
     * @author 
     **/
    public function get_ver()
    {
        return $this->__ver;
    }

    /**
     * 初始化请求配置
     *
     * @return void
     * @author 
     **/
    abstract public function init($node_id, $channel_id);
}