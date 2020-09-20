<?php
/**
 * ABSTRACT
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
abstract class erpapi_shop_request_abstract
{
    /**
     * 渠道
     *
     * @var string
     **/
    protected $__channelObj;
    protected $__resultObj;
    protected $__caller;

    final public function init(erpapi_channel_abstract $channel, erpapi_config $config, erpapi_result $result)
    {
        $this->__channelObj = $channel;
        
        $this->__resultObj = $result;

        // 默认以JSON格式返回
        $this->__caller = kernel::single('erpapi_caller',array('uniqid'=>uniqid('shop')))
                            ->set_config($config)
                            ->set_result($result);
    }

    /**
     * 成功输出
     *
     * @return void
     * @author 
     **/
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }

    /**
     * 失败输出
     *
     * @return void
     * @author 
     **/
    final public function error($msg, $msgcode, $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'err_msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }

    /**
     * 生成唯一键
     *
     * @return void
     * @author 
     **/
    final public function uniqid(){
        $microtime  = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval    = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * 回调
     * @param $response Array
     * @param $callback_params Array
     * @return Array
     **/
    public function callback($response, $callback_params)
    {
        $rsp             = $response['rsp'];
        //新增发货失败处理
        $errorCode = kernel::single('erpapi_errcode')->getErrcode('shop');//错误码
        $failApiModel = app::get('erpapi')->model('api_fail');
        if($rsp == 'fail' && $response['msg_code'] && array_keys($errorCode) && in_array($response['msg_code'],array_keys($errorCode))){

            if(!$callback_params['obj_type']){
                $callback_params['obj_type'] = $errorCode[$response['msg_code']]['obj_type'];

            }
            $failApiModel->publish_api_fail($callback_params['method'],$callback_params,$response);
        }
        if($rsp == 'succ' || $rsp == 'success' || in_array($response['res'],array('W90010'))){//因成功时需要删除失败列表里记录

            if(in_array($response['res'],array('W90010'))){
                $response['rsp'] = 'succ';
            }
            $failApiModel->publish_api_fail($callback_params['method'],$callback_params,$response);
        }
        return $response;
    }
}