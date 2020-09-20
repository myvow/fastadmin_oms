<?php
/**
 * 公共函数库
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome;

use think\Db;
use think\Request;

class Common
{
    /**
     * 获取客户端IP地址
     */
    static function get_remote_addr()
    {
        $client_ip = request()->ip();
        
        return $client_ip;
    }
    
    /**
     * 地区字符串格式验证
     * @todo：正则匹配地区是否为本系统的标准地区格式，非标准格式需转换
     * 
     * @param string $area 待验证地区字符串
     * @return string 转换后的本系统标准格式地区
     */
    public function region_validate(&$area)
    {
        $is_correct_area = $this->is_correct_region($area);
        
        //非标准格式进行转换
        if (!$is_correct_area)
        {
            $this->local_region($area);
        }
        
        return true;
    }
    
    /**
     * 本地标准地区格式判断
     * 
     * @param string $area 地区字符串(如：malind:上海/徐汇区:22)
     * @return boolean
     */
    public function is_correct_region($area)
    {
        $pattrn = "/^([a-zA-Z]+)\:(\S+)\:(\d+)$/";
        if (preg_match($pattrn, $area)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 前端店铺三级地区本地临时转换
     * 
     * @param $area
     * @return bool
     */
    public function area_format($area)
    {
        $area_format = array(
                '内蒙古自治' => '内蒙古',
                '广西壮族自治' => '广西',
                '西藏自治' => '西藏',
                '宁夏回族自治' => '宁夏',
                '新疆维吾尔自治' => '新疆',
                '香港特别行政' => '香港',
                '澳门特别行政' => '澳门',
        );
        
        if ($area_format[$area]){
            return $area_format[$area];
        }else{
            return false;
        }
    }
    
    /**
     * 系统标准地区格式转换
     * @todo：正则匹配地区是否为本系统的标准地区格式
     * @todo：转换成功返回标准地区格式，转换失败原地区字符串返回
     * 
     * @param string $area 转换地区字符串
     * @return string 转换后的本系统标准格式地区
     */
    public function local_region(&$area)
    {
        $regionObj = Db::name('ome_regions');
        
        $tmp_area = explode("/",$area);
        
        //地区初始值临时存储
        $ini_first_name = trim($tmp_area[0]);
        $ini_second_name = trim($tmp_area[1]);
        $ini_third_name = trim($tmp_area[2]);
        
        $tmp_area2 = preg_replace('/省|市|县|区/', '', $tmp_area);
        $first_name = trim($tmp_area2[0]);
        
        //自治区兼容
        $tmp_first_name = $this->area_format($first_name);
        if($tmp_first_name) $first_name = $tmp_first_name;
        $second_name = trim($tmp_area2[1]);
        $third_name = trim($tmp_area2[2]);
        
        //获取省
        $region_first = $region_second = $region_third = "";
        if ($first_name)
        {
            //@todo：针对北京省份数据存在BOM头进行兼容
            if (strstr($first_name, '北京'))
            {
                $bom_first_name = chr(239).chr(187).chr(191).$first_name;
                
                $filter = array(
                        'local_name' => array('like', $bom_first_name.'%'),
                        'region_grade' => '1',
                );
                $region_first = $regionObj->where($filter)->field('package,region_id,local_name')->find();
                if(empty($region_first))
                {
                    $filter = array(
                            'local_name' => array('like', $first_name.'%'),
                            'region_grade' => '1',
                    );
                    $region_first = $regionObj->where($filter)->field('package,region_id,local_name')->find();
                }
            }
            else
            {
                $filter = array(
                        'local_name' => array('like', $first_name.'%'),
                        'region_grade' => '1',
                );
                $region_first = $regionObj->where($filter)->field('package,region_id,local_name')->find();
            }
            
            $first_name = $region_first['local_name'];
            
            //保存省信息
            if (empty($first_name))
            {
                $region_first = array(
                        'local_name' =>$ini_first_name,
                        'package' =>'mainland',
                        'region_grade' =>'1',
                );
                $regionObj->insert($region_first);
                $region_first['region_id'] = $regionObj->getLastInsID;
                
                $first_name = $region_first['local_name'];
                $region_path = ",". $region_first['region_id'] .",";
                
                //更新region_path字段
                $filter = array('region_id'=>$region_first['region_id']);
                $regionObj->where($filter)->update(array('region_path'=>$region_path));
            }
        }
        
        //获取市
        if ($second_name)
        {
            //精确查找
            $filter = array(
                    'local_name' => $ini_second_name,
                    'region_grade' => '2',
                    'p_region_id' => $region_first['region_id'],
            );
            $region_second = $regionObj->where($filter)->field('package,region_id,p_region_id,local_name')->find();
            
            if(empty($region_second['local_name']))
            {
                //模糊查找
                $filter = array(
                        'local_name' => array('like', $second_name.'%'),
                        'region_grade' => '2',
                        'p_region_id' => $region_first['region_id'],
                );
                $region_second = $regionObj->where($filter)->field('package,region_id,p_region_id,local_name')->find();
            }
            $second_name = $region_second['local_name'];
            
            //保存市信息
            if (empty($second_name))
            {
                $region_second = array(
                        'local_name' => $ini_second_name,
                        'p_region_id' => $region_first['region_id'],
                        'package' => 'mainland',
                        'region_grade' => '2',
                );
                $regionObj->insert($region_second);
                $region_second['region_id'] = $regionObj->getLastInsID;
                
                $second_name = $region_second['local_name'];
                $region_path = ",". $region_first['region_id'] .",". $region_second['region_id'] .",";
                
                //更新region_path字段
                $filter = array('region_id'=>$region_second['region_id']);
                $regionObj->where($filter)->update(array('region_path'=>$region_path));
                
                //添加二级地区后,更新一级地区的haschild
                $filter = array('region_id'=>$region_first['region_id']);
                $regionObj->where($filter)->update(array('haschild'=>1));
            }
        }
        
        //获取区、县
        if ($third_name)
        {
            //先根据第三级查出所有第二级
            if(empty($region_second['region_id']))
            {
                $filter = array(
                        'local_name' => array('like', $third_name.'%'),
                );
                $regions = $regionObj->where($filter)->field('p_region_id')->select();
                if ($regions)
                {
                    foreach ($regions as $k => $v)
                    {
                        $filter = array(
                                'region_id' => $v['p_region_id'],
                                'region_grade' => '2',
                        );
                        $region_second_tmp = $regionObj->where($filter)->field('region_path,package,region_id,p_region_id,local_name')->find();
                        
                        $tmp = explode(",", $region_second_tmp['region_path']);
                        if (in_array($region_first['region_id'], $tmp))
                        {
                            $region_second = $region_second_tmp;
                            $second_name = $region_second['local_name'];
                            break;
                        }
                    }
                }
            }
            
            //精确查找
            $filter = array(
                    'local_name' => $ini_third_name,
                    'p_region_id' => $region_second['region_id'],
                    'package' =>'mainland',
                    'region_grade' =>'3',
            );
            $region_third = $regionObj->where($filter)->field('package,region_id,p_region_id,local_name')->find();
            if (empty($region_third['local_name']))
            {
                //模糊查找
                $filter = array(
                        'local_name' => array('like', $third_name.'%'),
                        'p_region_id' => $region_second['region_id'],
                        'region_grade' =>'3',
                );
                $region_third = $regionObj->where($filter)->field('package,region_id,p_region_id,local_name')->find();
            }
            
            $third_name = $region_third['local_name'];
            if(empty($third_name))
            {
                if ($region_second['region_id'])
                {
                    $region_third = array(
                            'local_name' => $ini_third_name,
                            'p_region_id' => $region_second['region_id'],
                            'package' => 'mainland',
                            'region_grade' => '3',
                    );
                    $regionObj->insert($region_third);
                    $region_third['region_id'] = $regionObj->getLastInsID;
                    
                    $third_name = $region_third['local_name'];
                    $region_path = ",".$region_first['region_id'].",".$region_second['region_id'].",".$region_third['region_id'].",";
                    
                    //更新region_path字段
                    $filter = array('region_id'=>$region_third['region_id']);
                    $regionObj->where($filter)->update(array('region_path'=>$region_path));
                    
                    //添加三级地区后更新二级地区的haschild
                    $regionObj->where(array('region_id'=>$region_second['region_id']))->update(array('haschild'=>1));
                }
                else
                {
                    $filter = array(
                            'local_name' => array('like', $ini_third_name.'%'),
                            'p_region_id' => $region_first['region_id'],
                    );
                    $region_third = $regionObj->where($filter)->field('package,region_id,p_region_id,local_name')->find();
                    if ($region_third) {
                        $third_name = $ini_third_name;
                    }
                }
            }
        }
        
        $return = false;
        
        if ($region_third['region_id'])
        {
            $region_id = $region_third['region_id'];
            $package = $region_third['package'];
        }
        elseif ($region_second['region_id'])
        {
            $region_id = $region_second['region_id'];
            $package = $region_second['package'];
        }
        $region_area = array_filter(array($first_name, $second_name, $third_name));
        $region_area = implode("/", $region_area);
        
        if ($region_area || $region_id)
        {
            $area = $package .':'. $region_area .':'. $region_id;
            $return = true;
        }
        
        //去除多余分隔符“/”
        if ($return == false){
            $area = implode('/', array_filter($tmp_area));
        }
        
        return true;
    }
    
    /**
     * shopex前端店铺列表
     * 
     * @return array
     **/
    static function shopex_shop_type()
    {
        $shop = array(
                'shopex_b2b',
                'shopex_b2c',
                'ecos.b2c',
                'ecshop_b2c',
                'ecos.dzg',
                'bbc',
                'ecos.b2b2c.stdsrc',
                'shopex_fy',
                'shopex_penkrwd',
        );
        
        return $shop;
    }
    
    /**
     * 
     * 可以用函数全部替换掉
     * 
     * 将前端店铺过来的货品规格属性值序列化
     * 
     * @param array $productattr 货品属性值
     * @return serialize 货品属性值
     */
    public function _format_productattr($productattr='', $product_id='', $original_str='')
    {
        $addon = array('product_attr'=>$productattr);
        $addon = serialize($addon);
        
        return $addon;
    }
    
    
    
    
    
    
    
    
    
    
}