<?php
/**
 * 订单插件抽象类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\plugins;

abstract class Abstracts
{
    /**
     * 数据转换
     * 
     * @return array
     */
    public function convert()
    {
        return array();
    }
    
    /**
     * 订单保存之后处理
     * 
     * @return void
     **/
    public function postCreate($order_id, $params){
        
    }

    /**
     * 订单更新之后处理
     * 
     * @return void
     **/
    public function postUpdate($order_id,$params){
        
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
     */
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }
}