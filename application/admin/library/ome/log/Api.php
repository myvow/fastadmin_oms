<?php
/**
 * Api同步日志Lib库
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome\log;

use think\Db;

class Api
{
    /**
     * 写同步日志
     * 
     * @param array $params
     * @return bool
     */
    function write_log($params)
    {
        $dateline = time();
        $log_id = $this->gen_id(); //唯一log_id
        
        $original_bn = $params['original_bn']; //同步单据号
        $title = $params['title']; //同步日志标题
        $status = ($params['status'] ? $params['status'] : 'running'); //处理状态
        $worker = $params['method']; //接口名,例如：shop.order.add
        $api_params = ($params['params'] ? serialize($params['params']) : ''); //接口参数
        $msg = ($params['msg'] ? $params['msg'] : '');
        $log_type = $params['log_type']; //日志类型
        $api_type = ($params['api_type'] ? $params['api_type'] : 'request'); //同步类型
        $memo = $params['memo']; //备注
        
        //组织数据
        $log_sdf = array(
                'log_id' => $log_id,
                'original_bn' => $original_bn,
                'task_name' => $title,
                'status' => $status,
                'worker' => $worker,
                'params' => $api_params,
                'msg' => $msg,
                'log_type' => $log_type,
                'api_type' => $api_type,
                'memo' => $memo,
                'createtime' => $dateline,
                'last_modified' => $dateline,
        );
        
        //save
        $isSave = Db::name('erpapi_api_log')->insert($logsdf);
        if($isSave === false){
            return false;
        }
        
        return true;
    }
    
    /**
     * 生成同步日志唯一ID
     */
    public function gen_id()
    {
        $microtime = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval = uniqid('', true);
        $unique_key .= strval($randval);
        
        return md5($unique_key);
    }
}