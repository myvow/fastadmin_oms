<?php
/**
 * 店铺业务处理
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\channel;

use think\Db;
use app\admin\library\erpapi\channel\Abstracts;

class Shop extends Abstracts 
{
    public $_channel;
    private static $_shops = array();
    private static $_shopInfo = array();
    
    private static $shop_mapping = array(
            'ecos.b2c' => 'shopex_ecstore',
            'bbc' => 'shopex_bbc',
            'espier.yyk' => 'shopex_yyk',
            'espier.gw' => 'shopex_gw',
            'espier.caodong' => 'shopex_caodong',
    );

    private static $versionm = array(
        'shopex_b2b' => array( // b2b
            '1' => '1',
            '2' => '2',
        ),
        'ecos.b2c' => array( // ecstore
            '1' => '1',
            '2' => '2',
        ),
    );

    public function init($node_id, $shop_id)
    {
        $filter = $shop_id ? array('shop_id'=>$shop_id) : array('node_id'=>$node_id);
        
        $this->get_shop($filter);
        
        if (!self::$_shopInfo || !self::$_shopInfo['node_id']) return false;
        
        $this->_channel = self::$_shopInfo;
        $this->__adapter = 'matrix';
        $this->__platform = self::$_shopInfo['node_type'];
        
        if (self::$shop_mapping[$this->__platform])
        {
            $this->__platform = self::$shop_mapping[$this->__platform];
        }
        else
        {
            if($this->__platform=='taobao' && self::$_shopInfo['tbbusiness_type'] == 'B')
            {
                $this->__platform = 'tmall';
            }
        }
        
        $this->set_ver($this->__platform, self::$_shopInfo['api_version']);
        
        return true;
    }
    
    /**
     * 获取淘管对应版本
     * 
     * @param String $node_type 店铺类型
     * @param String $api_version 前端店铺版本
     **/
    private function set_ver($node_type, $api_version)
    {
        if(isset(self::$versionm[$node_type]))
        {
            $mapping = self::$versionm[$node_type];
            krsort($mapping);
            
            foreach ($mapping as $s_ver => $t_ver)
            {
                if (version_compare($api_version, $s_ver, '>=')) {
                    $this->__ver = $t_ver;
                    break;
                }
            }
        }
    }
    
    /**
     * 获取shop数据库表信息
     * 
     * @param array $filter
     * @return array
     */
    private function get_shop($filter)
    {
        $key = sprintf('%u', crc32(serialize($filter)));
        
        if (self::$_shops[$key]) return self::$_shops[$key];
        
        $field = 'shop_id,shop_bn,name,shop_type,node_id,node_type,api_version,tbbusiness_type';
        self::$_shopInfo = Db::name('ome_shop')->where($filter)->field($field)->find();
        
        self::$_shops[$key] = self::$_shopInfo;
        
        return self::$_shopInfo;
    }
}