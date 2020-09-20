<?php 
namespace app\admin\model\erpapi;

use think\Db;
use think\Model;
use think\Cache;
use think\Config;

/**
 * 接口请求缓存
 */
class Rpcpoll extends Model
{
    public static $prefix;
    public static $dbschema;
    
    public function __construct()
    {
        self::$prefix = Config::get('database.prefix');
    }
    
    /**
     * 查询数据列表
     * 
     * @param string $cols
     * @param unknown $filter
     * @param number $offset
     * @param unknown $limit
     * @param string $orderby
     * @return unknown
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=null)
    {
        //当前模块名称
        $moduleName = request()->module();
        
        //当前控制名称
        $controllerName = request()->controller();
        
        //数据库表名
        $table_name = self::$prefix . $moduleName . $controllerName;
        
        //获取数据库表结构
        $this->get_schema();
        
        $cols = $cols ? $cols : '*';
        $offset = $offset >= 0 ? $offset : 0;
        
        //条件
        $where = ' WHERE '. $this->filter($filter);
        
        //排序
        $sql_sort = '';
        if ($orderby)
            $orderby .= ", calltime DESC";
        else
            $orderby = "calltime DESC";
        
        if($orderby)
        {
            $sql_sort = ' ORDER BY ' . (is_array($orderby) ? implode($orderby,' ') : $orderby);
        }
        
        //Limit
        $sql_limit = '';
        if ($offset >= 0 || $limit >= 0)
        {
            $offset = ($offset >= 0) ? $offset . "," : '';
            $limit = ($limit >= 0) ? $limit : '18446744073709551615';
            
            $sql_limit .= ' LIMIT ' . $offset . ' ' . $limit;
        }
        
        //sql
        $sql = 'SELECT '.$cols.' FROM `'. $table_name .'` '. $where . $sql_sort . $sql_limit;
        $rows = Db::query($sql);
        
        //格式化数据
        //$this->tidy_data($rows, $cols);
        
        return $rows;
    }
    
    //public公共类
    /**
     * 过滤不存在于数据库表dbschema里的filter查询字段名
     */
    public function filter($filter)
    {
        if(is_array($filter))
        {
            foreach($filter AS $k=>$v)
            {
                if(!isset(self::$dbschema['columns'][$k]))  unset($filter[$k]);
            }
        }
        
        return self::filter2sql($filter);
    }
    
    //public公共类
    static function filter2sql($filter)
    {
        $where = array('1');
        
        if($filter)
        {
            foreach($filter as $k=>$v)
            {
                if(is_array($v))
                {
                    foreach($v as $m)
                    {
                        if($m!=='_ANY_' && $m!=='' && $m!='_ALL_'){
                            $ac[] = $k.'=\''.$m.'\'';
                        }else{
                            $ac = array();
                            break;
                        }
                    }
                    
                    if(count($ac)>0){
                        $where[] = '('.implode($ac, ' or ') . ')';
                    }
                }else{
                    $where[] = '`'.$k.'` = "'.str_replace('"','\\"',$v).'"';
                }
            }
        }
        
        return implode(' AND ',$where);
    }
    
    /**
     * 获取数据库表结构
     * todo：后面要做成缓存存储
     */
    public function get_schema($sub=null)
    {
        self::$dbschema = $this->get_table_structure();
        
        return true;
    }
    
    public function _columns()
    {
        if(empty(self::$dbschema))
        {
            self::$dbschema = $this->get_table_structure();
        }
        
        return (array)self::$dbschema['columns'];
    }
    
    //public公共类
    /**
     * 获取数据库表结构和索引
     * 
     * @param string $tbname
     * @return bool || array
     */
    private function get_table_structure($tbname)
    {
        $define = Db::query("SHOW TABLES LIKE '".$tbname."'");
        if($define)
        {
            $rows = @Db::query('SHOW COLUMNS FROM '.$tbname);
            $columns = array();
            if($rows)
            {
                foreach($rows as $c)
                {
                    $columns[$c['Field']] = array(
                            'type'=>$c['Type'],
                            'default'=>$c['Default'],
                            'required'=>!($c['Null']=='YES'),
                    );
                }
            }
            
            $rows = @Db::query('SHOW INDEX FROM '.$tbname);
            $index = array();
            if($rows)
            {
                foreach($rows as $row)
                {
                    $index[$row['Key_name']] = array(
                            'Column_name'=>$row['Column_name'],
                            'Non_unique'=>$row['Non_unique'],
                            'Collation'=>$row['Collation'],
                            'Sub_part'=>$row['Sub_part'],
                            'Index_type'=>$row['Index_type'],
                    );
                }
            }
            
            return array('columns'=>$columns, 'index'=>$index);
        }else{
            return false;
        }
    }
    
    //public公共类
    /**
     * 格式化查询的数据
     */
    public function tidy_data(&$rows, $cols='*')
    {
        if( $rows )
        {
            $need_tidy = false;
            $tidy_type = array('serialize');
            $def_columns = $this->_columns();
            
            if(rtrim($cols) === '*'){
                $columns = $def_columns;
            }else{
                $tmp = explode(',', $cols);
                foreach($tmp AS $col){
                    $col = trim($col);
                    if(preg_match('/\S+ as \S+/i', $col)){
                        $array = preg_split('/ as /i', $col);
                        $ex_key = str_replace('`', '', trim($array[1]));
                        $ex_real = str_replace('`', '', trim($array[0]));
                        $columns[$ex_key] = $def_columns[$ex_real];
                    }else{
                        $ex_key = str_replace('`', '', $col);
                        $columns[$ex_key] = $def_columns[$ex_key];
                    }
                }
            }
            
            $curRow = current($rows);
            foreach( $columns as $k => $v ){
                if(in_array($v['type'], $tidy_type) && array_key_exists( $k, $curRow ) ){
                    $need_columns[] = $k;
                    $need_tidy = true;
                }
            }
            
            if($need_tidy){
                foreach( $rows as $key => $row ){
                    foreach( $need_columns as $column ){
                        switch(trim($columns[$column]['type'])){
                            case 'serialize':
                                $rows[$key][$column]=unserialize($row[$column]);
                            default:
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * 添加缓存
     * 
     * @param array $data
     * @param int $rpc_id
     * @param string $type
     */
    public function insertRpc($data, $rpc_id, $type='request')
    {
        $cache_key = sprintf("rpcpoll-%s-%s", $type, $rpc_id);
        
        //后面使用动态加载类文件,保存cache缓存内容
        //例如：$cache_save = cachecore::store($cache_key, serialize($data), 10800);
        $cache_save = Cache::set($cache_key, serialize($data), 10800);
        if($cache_save === false)
        {
            //保存数据到数据库
            //Db::name('erp_rpcpoll')->insert($data);
        }
    }
    
    /**
     * 更新缓存
     *
     * @param array $data
     * @param int $rpc_id
     * @param string $type
     */
    public function updateRpc($data, $rpc_id, $type='request')
    {
        list($id, $calltime) = explode('-', $rpc_id);
        
        $cache_key = sprintf("rpcpoll-%s-%s",$type,$rpc_id);
        
        //后面使用动态加载类文件,保存cache缓存内容
        //例如：$rpc_res_info = cachecore::fetch($cache_key);
        $rpc_res_info = Cache::get($cache_key);
        if ($rpc_res_info === false)
        {
            $filter = array('id'=>$id,'type'=>$type);
            if ($calltime) $filter['calltime'] = $calltime;
            
            //更新数据到数据库
            Db::name('erp_rpcpoll')->where($filter)->update($data);
        } else {
            $rpc_res_info = unserialize($rpc_res_info);
            $rpc_res_info = array_merge($rpc_res_info,$data);
            
            //后面使用动态加载类文件,保存cache缓存内容
            //例如：cachecore::store($cache_key,serialize($rpc_res_info),10800);
            Cache::set($cache_key, serialize($rpc_res_info), 10800);
        }
    }
    
    /**
     * 删除缓存
     * 
     * @param int $rpc_id
     * @param string $type
     */
    public function deleteRpc($rpc_id, $type='request')
    {
        list($id,$calltime) = explode('-', $rpc_id);
        
        $cache_key = sprintf("rpcpoll-%s-%s",$type,$rpc_id);
        
        //后面使用动态加载类文件,保存cache缓存内容
        //例如：$rpc_res_info = cachecore::fetch($cache_key);
        $rpc_res_info = Cache::get($cache_key);
        if($rpc_res_info === false)
        {
            $filter = array('id'=>$id,'type'=>$type);
            if ($calltime) $filter['calltime'] = $calltime;
            
            //删除缓存数据
            Db::name('erp_rpcpoll')->where($filter)->delete();
        }else{
            //后面使用动态加载类文件,保存cache缓存内容
            //例如：cachecore::store($cache_key,'',1);
            Cache::set($cache_key, '', 1);
        }
    }
    
    /**
     * 获取缓存
     * 
     * @param int $rpc_id
     * @param string $type
     */
    public function getRpc($rpc_id, $type='request')
    {
        $tmp = false;
        
        $cache_key = sprintf("rpcpoll-%s-%s", $type, $rpc_id);
        
        /***
        //后面使用动态加载类文件,保存cache缓存内容
        //例如：$rpc_res_info = cachecore::fetch($cache_key);
        $rpc_res_info = Cache::get($cache_key);
        if($rpc_res_info === false)
        {
            $filter = array('id'=>$id,'type'=>$type);
            if ($calltime) $filter['calltime'] = $calltime;
            
            //获取缓存数据
            $tmp = array();
            $tmp[0] = Db::name('erp_rpcpoll')->where($filter)->find();
        }else{
            $rpc_res_info = unserialize($rpc_res_info);
            $rpc_res_info['params'] = unserialize($rpc_res_info['params']);
            $tmp = array(0=>$rpc_res_info);
        }
        ***/
        
        return $tmp;
    }
}
