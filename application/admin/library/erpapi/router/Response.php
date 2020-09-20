<?php 
/**
 * Response路由分派加载对应处理类 
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\router;

use think\Exception;
use think\Cache;
use think\Db;

use app\admin\library\erpapi\channel\abstracts;
use app\admin\library\ome\log\Api as Apilog;

class Response
{
    /**
     * 渠道节点
     *
     * @var string
     **/
    private $__node_id;
    
    /**
     * 接口名，如:shop.order.add
     *
     * @var string
     **/
    private $__api_name;
    
    /**
     * 渠道ID
     *
     * @var string
     **/
    private $__channel_id;
    
    /**
     * 并发KEY
     * 
     * @var string
     **/
    private $__concurrent_key = '';
    
    public function set_node_id($node_id)
    {
        $this->__node_id = $node_id;
        return $this;
    }
    
    public function set_channel_id($channel_id)
    {
        $this->__channel_id = $channel_id;
        return $this;
    }
    
    public function set_api_name($api_name)
    {
        $this->__api_name = $api_name;
        return $this;
    }
    
    /**
     * 分派路由(加载对应处理类)
     * 
     * @param array $params
     * @param bool $sign_check
     * @return array
     */
    public function dispatch($params, $sign_check=false)
    {
        $apiLogLib = new Apilog;
        
        $result = array('rsp'=>'fail', 'msg'=>'', 'msg_code'=>'407', 'data'=>null);
        
        list($usec, $sec) = explode(" ", microtime());
        $this->__start_time = $usec + $sec;
        
        try {
            //节点和ID都不存在，抛出异常
            if (!$this->__node_id && !$this->__channel_id){
                throw new Exception('节点参数必填');
            }
            
            //接口名不存在抛出异常
            if (!$this->__api_name){
                throw new Exception('接口名称必填');
            }
            
            list($channel_type, $business, $method) = $this->_parse_api_name();
            
            //实例化渠道类
            $channel_name = "\\app\\admin\\library\\erpapi\\channel\\". ucfirst($channel_type);
            try {
                if(!class_exists($channel_name)){
                    throw new Exception('实例化渠道类不存在');
                }
                $channel_class = new $channel_name;
            }
            catch (Exception $e)
            {
                $result['msg'] = $e->getMessage();
                return $result;
            }
            
            if (!($channel_class instanceof abstracts))
            {
                throw new Exception($channel_name .'不是abstract的实例');
            }
            
            $channelRs = $channel_class->init($this->__node_id, $this->__channel_id);
            if (!$channelRs){
                throw new Exception('节点不存在');
            }
            
            //默认数据转换类
            $error_msg = '';
            $object_class = $this->_get_object_class($channel_class, $params, $error_msg);
            if(!$object_class)
            {
                $result['msg'] = $error_msg;
                return $result;
            }
            
            // 防并发
            $this->__concurrent_key = '';
            if (method_exists($object_class, 'concurrentKey'))
            {
                $this->__concurrent_key = $object_class->concurrentKey($params);
                if ($this->__concurrent_key)
                {
                    // 判断是否在任务执行中
                    $original_bn = $object_class->__apilog['original_bn'];
                    $lastmodify  = $object_class->__lastmodify;
                    
                    $cacheData = Cache::get($this->__concurrent_key);
                    if (is_array($cacheData) && $cacheData['status']=='running')
                    {
                        $this->__concurrent_key = '';
                        
                        $result['msg'] = '订单正在处理，请稍后请求!';
                        return $result;
                    }
                    
                    $cacheData = array(
                            'status' => 'running',
                            'lastmodify' => $lastmodify,
                    );
                    Cache::set($this->__concurrent_key, $cacheData, 60);
                }
            }
            
            $object_class->init($channel_class);
            if(method_exists($object_class, $method))
            {
                // 数据转成标准格式
                $convert_params = $object_class->{$method}($params);
            } else {
                $result['msg'] = '请求的方法:'. $method .'不存在';
                return $result;
            }
            
            //记录同步日志
            $title = $object_class->__apilog['title'];
            $original_bn = $object_class->__apilog['original_bn'];
            $convert_result = $object_class->__apilog['result'];
            if (!$convert_params)
            {
                $msg = '接收参数：'.var_export($params,true).'<hr/>转换后参数：'.var_export($convert_params, true).'<hr/>返回结果：'.var_export($convert_result, true);
                $logParams = array(
                        'original_bn' => $original_bn,
                        'api_type' => 'response',
                        'status' => 'fail',
                        'log_type' => $business,
                        'title' => $title,
                        'method' => $this->__api_name,
                        'params' => '',
                        'msg' => $msg,
                        'memo' => '',
                );
                $apiLogLib->_write_log($logParams);
                
                //设置缓存key为过期
                if ($this->__concurrent_key) {
                    Cache::set($this->__concurrent_key, '', 1);
                }
                
                $result['msg'] = '错误：'.$convert_result['msg'];
                $result['data'] = $convert_result['data'];
                return $result;
            }
            
            //params数据验证
            try {
                $params_name = "\\app\\admin\\library\\erpapi\\$channel_type\\response\\params\\". ucfirst($business);
                if (class_exists($params_name))
                {
                    $paramObj = new $params_name;
                    $valid = $paramObj->check($convert_params, $method);
                    if ($valid['rsp'] != 'succ')
                    {
                        //记录同步日志
                        $msg = '接收参数：'.var_export($params,true).'<hr/>转换后参数：'.var_export($convert_params, true).'<hr/>返回结果：'.var_export($convert_result, true);
                        $logParams = array(
                                'original_bn' => $original_bn,
                                'api_type' => 'response',
                                'status' => 'fail',
                                'log_type' => $business,
                                'title' => $title,
                                'method' => $this->__api_name,
                                'params' => '',
                                'msg' => $msg,
                                'memo' => '',
                        );
                        $apiLogLib->_write_log($logParams);
                        
                        $result['msg'] = '校验错误：'.$valid['msg'];
                        $result['data'] = $convert_result['data'];
                        return $result;
                    }
                }
            } catch (Exception $e) {
                //error
            }
            
            //实例化最终处理类
            $process_name = "\\app\\admin\\library\\erpapi\\$channel_type\\response\\process\\". ucfirst($business);
            try {
                if(!class_exists($process_name)){
                    throw new Exception('最终处理类不存在');
                }
                $processObj = new $process_name;
            }
            catch (Exception $e)
            {
                $result['msg'] = $e->getMessage();
                return $result;
            }
            
            //最终的处理
            $result = $processObj->{$method}($convert_params);
            
            //记录同步日志
            $status  = ($result['rsp'] == 'succ' ? 'success' : 'fail');
            $msg = '接收参数：'.var_export($params,true).'<hr/>转换后参数：'.var_export($convert_params, true).'<hr/>返回结果：'.var_export($result, true);
            $logParams = array(
                    'original_bn' => $original_bn,
                    'api_type' => 'response',
                    'status' => $status,
                    'log_type' => $business,
                    'title' => $title,
                    'method' => $this->__api_name,
                    'params' => '',
                    'msg' => $msg,
                    'memo' => '',
            );
            $apiLogLib->_write_log($logParams);
            
            /***
            //失败放入队列里重试处理
            if ($result['rsp'] != 'succ')
            {
                $apiParms = $result['data'];
                $result['data'] = $convert_result['data'];
                
                $errorCode = kernel::single('erpapi_errcode')->getErrcode($channel_type);//错误码
                if($errorCode && (in_array($result['res'],array_keys($errorCode)) || in_array($result['msg_code'],array_keys($errorCode))))
                {
                    if(!$apiParms['obj_type']){
                        $apiParms['obj_type'] = $errorCode[$result['msg_code']]['obj_type'];
                    }
                    
                    $failApiModel = app::get('erpapi')->model('api_fail');
                    $failApiModel->publish_api_fail($this->__api_name,$apiParms,$result);
                }
                
                return $result;
            }
            ***/
            
            $result['data'] = $result['data'] ? $result['data'] : $convert_result['data'];
            
            return $result;
        }
        catch (Exception $e)
        {
            $result['msg'] = $e->getMessage();
            //$result['msg'] .= '('.$e->getTraceAsString().')'; //详细代码报错信息
            
            return $result;
        }
    }
    
    /**
     * 获取接口信息
     * $this->__api_name = 'shop.order.add';
     * 
     * @return array('Shop', 'order', 'add')
     */
    private function _parse_api_name()
    {
        list($channel_type, $business, $method) = explode('.', $this->__api_name);
        
        return array($channel_type, $business, $method);
    }
    
    /**
     * 获取处理类
     * 
     * @return void
     **/
    
    /**
     * 获取处理业务的类
     * 
     * @param obj $channel_class
     * @param array $params
     * @return array
     */
    private function _get_object_class($channel_class, $params, &$error_msg=null)
    {
        //method方法：shop.order.add 则为 $channel_type='shop',$business='order',$method='add'
        list($channel_type, $business, $method) = $this->_parse_api_name();
        
        $adapter = $channel_class->get_adapter(); //适配器,默认:matrix
        $platform = $channel_class->get_platform(); //来源平台,例如:taobao
        $ver = $channel_class->get_ver(); //平台版本,默认:1
        
        //平台父类(默认处理类)
        $default_object_name = "\\app\\admin\\library\\erpapi\\$channel_type\\response\\". ucfirst($business);
        
        //平台处理类(对应类:app\admin\library\erpapi\shop\matrix\taobao\response\Order)
        $object_name_arr = array('app', 'admin', 'library', 'erpapi', $channel_type, $adapter, $platform, 'response', $business);
        $object_name = implode('\\', array_filter($object_name_arr));
        
        try {
            if (class_exists($object_name))
            {
                $object_class = new $object_name;
                
                if(!is_subclass_of($object_class, $default_object_name)){
                    throw new Exception('平台处理类未继承父类');
                }
            }
        }catch (Exception $e){
            $error_msg = $e->getMessage();
            return false;
        }
        
        //shopex平台
        if(strpos($platform, 'shopex_') !== false)
        {
            $parentPlatForm = 'shopex';
            
            //shopex平台处理类
            $object_name_arr = array('app', 'admin', 'library', 'erpapi', $channel_type, $adapter, $parentPlatForm, 'response', $business);
            $object_name = implode('\\', array_filter($object_name_arr)); //对应类:app\erpapi\library\shop\matrix\shopex\response\Order
            
            try {
                if (class_exists($object_name))
                {
                    $object_class = new $object_name;
                    
                    if(!is_subclass_of($object_class, $default_object_name)){
                        throw new Exception('shopex平台处理类未继承父类');
                    }
                }
            }catch (Exception $e){
                $error_msg = $e->getMessage();
                return false;
            }
        }
        
        /***
        //对象内业务流转(有b2c、b2b的区别)
        if (method_exists($object_class, 'business_flow'))
        {
            $business_name = $object_class->business_flow($params);
            
            try {
                if (class_exists($business_name)) {
                    $object_class = kernel::single($business_name,array($channel_class));
                    
                    if (!is_subclass_of($object_class, $default_object_name)) throw new Exception("{$object_name} is a subclass of {$default_object_name}");
                }
            } catch (Exception $e) {}
        }
        ***/
        
        //平台类不存在,则加载默认父类
        if(!is_object($object_class))
        {
            try {
                if (!class_exists($default_object_name)){
                    throw new Exception('平台父类不存在');
                }
                $object_class = new $default_object_name;
                
                return $object_class;
            }catch (Exception $e){
                $error_msg = $e->getMessage();
                return false;
            }
        }
        
        return $object_class;
    }
}