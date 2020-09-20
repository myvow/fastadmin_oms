<?php
/**
 * 接口数据校验类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\params;

abstract class Abstracts
{
    public function check($params, $method)
    {
        $check_params = $this->{$method}();
        
        return $this->checkParams($check_params, $params);
    }
    
    protected function checkParams($check_params, $params) {
        if (!$check_params || !is_array($check_params) || !is_array($params)) return array('rsp' => 'succ');
        if(is_numeric(key($params))) {
            foreach($params as $pVal) {
                $rs = $this->checkParams($check_params, $pVal);
                if($rs['rsp'] != 'succ') {
                    return $rs;
                }
            }
            return array('rsp' => 'succ');
        }
        foreach ($check_params as $col => $valid) {
            if ($valid['required']=='true' && (!isset($params[$col]) || $params[$col] === '')) {
                $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} required";
                return array('rsp'=>'fail', 'msg'=>$msg);
            } 

            switch ($valid['type']) {
                case 'date':
                    if ($params[$col] && !preg_match('/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}$/', $params[$col])) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : '日期格式有误';
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    break;
                case 'string':
                    if (!is_string($params[$col])) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} must be string"; 
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    break;
                case 'enum':
                    $value = $valid['in_out'] ? $valid['in_out'] : 'in';
                    $compareValue = in_array($params[$col],$valid['value']) ? 'in' : 'out';
                    if ($compareValue != $value) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col}: only ".implode('|', $valid['value']).' can be choise';
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    break;
                case 'array':
                    if (!is_array($params[$col]) || !$params[$col]) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} must be array"; 
                        return array('rsp'=>'fail', 'msg'=>$msg);
                    }
                    $rs = $this->checkParams($valid['col'], $params[$col]);
                    if($rs['rsp'] != 'succ') {
                        return $rs;
                    }
                    break;
                case 'method':
                    if(method_exists($this, $valid['method'])) {
                        $msg = $valid['errmsg'] ? $valid['errmsg'] : "{$col} do not meet the requirements";
                        $rs = $this->{$valid['method']}($params);
                        if(!$rs) {
                            return array('rsp'=>'fail', 'msg'=>$msg);
                        } elseif(is_array($rs) && $rs['rsp'] != 'succ') {
                            return $rs;
                        }
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }
        return array('rsp'=>'succ','msg'=>'');
    }
}