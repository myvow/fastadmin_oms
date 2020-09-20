<?php
/**
 * 订单组件抽象类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

abstract class Abstracts
{
    //平台
    protected $_platform = null;
    
    //订单所有信息
    protected $_newOrder = array();
    
    /**
     * 数据转换
     *
     * @return void
     */
    public function convert($ordersdf=null){
        
    }
    
    /**
     * 更新数据转换
     *
     * @return array
     */
    public function update(){
        
    }

    /**
     * 平台
     * 
     * @param obj $platform
     * @return object
     **/
    public function setPlatform($platform)
    {
        $this->_platform = $platform;
        
        return $this;
    }

    /**
     * 比较数组值
     *
     * @return number
     **/
    public function comp_array_value($a,$b)
    {
        if ($a == $b) {
            return 0;
        }
        
        return $a > $b ? 1 : -1 ;
    }

    /**
     * 过滤空
     *
     * @param $var
     * @return bool
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }
}