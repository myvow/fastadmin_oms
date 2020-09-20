<?php
/**
 * CONFIG
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_config extends erpapi_config
{
    public function init(erpapi_channel_abstract $channel){
        $this->__whitelist = kernel::single('erpapi_shop_whitelist')->getWhiteList($channel->channel['node_type']);
        return parent::init($channel);
    }
    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author 
     **/
    public function get_query_params($method, $params){
        $query_params = array(
            'app_id'       => 'ecos.ome',
            'method'       => $method,
            'date'         => date('Y-m-d H:i:s'),
            'format'       => 'json',
            'certi_id'     => base_certificate::certi_id(),
            'v'            => $this->__channelObj->channel['matrix_api_v'] ? $this->__channelObj->channel['matrix_api_v'] : '1',
            'from_node_id' => base_shopnode::node_id('ome'),
            'to_node_id'   => $this->__channelObj->channel['node_id'],
            'to_api_v'     => $this->__channelObj->channel['api_version'],
            'node_type'    => $this->__channelObj->channel['node_type'],
        );

        $app_xml = app::get('ome')->define();
        $query_params['from_api_v'] = $app_xml['api_ver'];

        return $query_params;
    }
}