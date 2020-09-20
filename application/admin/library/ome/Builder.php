<?php
/**
 * Body列表内容基础Lib类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome;

use think\Config;


abstract class Builder
{
    protected $model = null;
    protected $app = null;
    protected $tablename = null;
    
    public $page = 0;
    public $pagelimit = 10;
    
    public function __construct($obj)
    {
        $this->model = $obj;
        
        //自动设置表名
        $this->getTablename();
        
        //访问页码
        if(isSet($_GET['page'])){
            $this->page = intval($_GET['page']);
        }
        
        $this->page = $this->page ? $this->page : 1;
        
        //设置每页显示条数
        $this->setPagesize();
    }
    
    /**
     * 根据model类名,自动设置表名
     * @todo：未加表前辍,例如：ome_orders
     */
    protected function getTablename()
    {
        $class_name = get_class($this->model);
        
        $temp = substr($class_name, strripos($class_name, 'model')+6);
        $temp = explode('\\', $temp);
        
        //app目录名
        $this->app = $temp[0];
        unset($temp[0]);
        
        $this->tablename = strtolower(implode('_', $temp));
    }
    
    /**
     * 手工设置数据库表名
     * @todo：未加表前辍,例如：ome_orders
     */
    protected function setTablename($tablename)
    {
        $temp = explode('_', $tablename);
        
        //app目录名
        $this->app = $temp[0];
        unset($temp[0]);
        
        $this->tablename = strtolower(implode('_', $temp));
    }
    
    /**
     * 设置每页显示条数
     */
    protected function setPagesize()
    {
        //先读取缓存设置的条数
        //@todo：后面修改为读取缓存,比如列表页手工选择了显示条数,会记录到缓存中;
        $pagesize = 0;
        
        //读取配置的条数
        if(empty($pagesize))
        {
            $paginate = Config::get('paginate');
            $pagesize = intval($paginate['list_rows']) ? intval($paginate['list_rows']) : $this->pagelimit;
        }
        
        $this->pagelimit = $pagesize;
    }
}