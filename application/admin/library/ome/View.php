<?php
/**
 * Body列表内容Lib类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome;

use app\admin\library\ome\Builder;
use app\admin\model\ome\Orders;

class View extends Builder
{
    public $dbschema = null;
    public $columns = null;
    
    public $_title = ''; //列表标题
    public $_field = ''; //查询字段
    public $_filter = array(); //查询条件
    public $_show_detail = false; //详情查看按钮
    public $_show_export = false; //导出按钮
    public $_show_import = false; //导入按钮
    
    /**
     * 构建列表数据
     * 
     * @param array $params
     */
    public function main($params)
    {
        $orderObj = new Orders;
        
        //列表标题文字
        $this->_title = $params['title'] ? $params['title'] : '列表';
        
        //详细信息查看
        $this->_show_detail = $params['show_detail'] ? true : false;
        
        //导出按钮
        $this->_show_export = $params['show_export'] ? true : false;
        
        //导入按钮
        $this->_show_import = $params['show_import'] ? true : false;
        
        //手工设置数据库表名
        if(isset($params['tablename']) && $params['tablename'])
        {
            $this->setTablename($params['tablename']);
        }
        
        //加载dbschema
        $this->getDbschema();
        
        //Post搜索
        if($_POST){
            $params['filter'] = array_merge($params['filter'], $_POST);
        }
        
        //条件
        if($params['filter']){
            $this->formatSearchFilter($params['filter']);
            //$this->_filter = $params['filter'];
        }
        
        //查询字段名
        if($params['field']){
            $this->_field = $params['field'];
        }else{
            $this->_field = implode(',', array_keys($this->columns['in_list']));
        }
        
        //条数
        $offsize = ($this->page - 1) * $this->pagelimit;
        
        //列表数据
        $this->data = $orderObj->getList($this->_field, $this->_filter, $offsize, $this->pagelimit);
        
        return true;
    }
    
    /**
     * 加载dbschema数据字段
     */
    public function getDbschema()
    {
        $is_cache = true;
        
        //读取缓存dbschema
        //@todo：后面修改为读取缓存;
        $this->dbschema = null;
        
        if(empty($this->dbschema))
        {
            $is_cache = false;
            
            require_once APP_PATH.'admin/dbschema/'. $this->app .'/'. $this->tablename .'.php';
            
            $this->dbschema = $dbschema;
        }
        
        //格式化
        $sorts = array();
        foreach ($this->dbschema['columns'] as $key => $val)
        {
            //字段显示顺序
            if(isset($val['sort']) && $val['sort']){
                $val['sort'] = intval($val['sort']);
            }elseif(isset($val['pkey']) && $val['pkey']){
                $val['sort'] = 1;
            }else{
                $val['sort'] = 100;
            }
            
            //主键
            if(isset($val['pkey']) && $val['pkey'])
            {
                $val['label'] = (isset($val['label']) ? $val['label'] : 'ID');
                
                $this->columns['in_list'][$key] = $val;
                $this->columns['default_in_list'][$key] = $val;
                
                $sorts[] = $val['sort'];
            }
            
            //是否显示
            if(isset($val['in_list']) && $val['in_list'])
            {
                if(isset($val['default_in_list']) && $val['default_in_list']){
                    $this->columns['default_in_list'][$key] = $val;
                }
                
                $this->columns['in_list'][$key] = $val;
                $sorts[] = $val['sort'];
            }
            
            //是否搜索
            if(isset($val['searchtype']) && $val['searchtype'])
            {
                $this->columns['search'][$key] = $val;
            }
            
            //标题行是否允许排序
            if(isset($val['in_sort']) && $val['in_sort'])
            {
                $this->columns['in_sort'][$key] = $key;
            }
        }
        
        //字段显示顺序
        if($sorts){
            array_multisort($sorts, SORT_ASC, $this->columns['in_list']);
        }
        
        //存储到缓存中
        if(!$is_cache){
            //$this->dbschema
        }
        
        return true;
    }
    
    public function formatSearchFilter($filter)
    {
        foreach ($filter as $key => $v)
        {
            if (!is_array($v) && $v !== false){
                $v = trim($v);
            }
            
            if ($v === '') {
                continue;
            }
            
            if(empty($this->dbschema['columns'][$key])){
                continue;
            }
            
            $this->_filter[$key] = $v;
        }
    }
}