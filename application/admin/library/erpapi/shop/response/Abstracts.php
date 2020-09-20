<?php
/**
 * 订单接口处理入口类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response;

use app\admin\library\erpapi\channel\Abstracts;

abstract class Abstracts
{
    public $__channelObj;
    
    public $__apilog;

    public function init(Abstracts $channel)
    {
        $this->__channelObj = $channel;
        
        return $this;
    }

    /**
     * 去首尾空格
     * 
     * @param Array
     * @return void
     **/
    static function trim(&$arr)
    {        
        foreach ($arr as $key => &$value)
        {
            if (is_array($value)) {
                self::trim($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
        }
    }

    /**
     * 过滤空
     *
     * @return bool
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
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
}