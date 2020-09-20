<?php
/**
 * @author ykm 2016-01-19
 * @describe 短信发送接口
 */
class erpapi_channel_sms extends erpapi_channel_abstract
{
    public $channel;

    public function init($node_id,$channel_id)
    {
        $param = unserialize($channel_id);
        if (!$param) { return false; }
        $this->__adapter = '';
        $this->__platform = '';
        $this->channel['account'] = $param;
        return true;
    }
}