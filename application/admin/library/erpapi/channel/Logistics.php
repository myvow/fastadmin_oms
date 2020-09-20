<?php
/**
 * @author ykm 2015-12-15
 * @describe 物流中心
 */
class erpapi_channel_logistics extends erpapi_channel_abstract
{
    public $channel;

    public function init($node_id,$channel_id)
    {
        $objChannel = app::get('logisticsmanager')->model('channel');
        $channel = $objChannel->dump(array('channel_id'=>$channel_id, 'status'=>'true'));
        if (!$channel) return false;
        $this->__adapter = 'matrix';
        $this->__platform = $channel['channel_type'];
        $this->channel = $channel;
        return true;
    }
}