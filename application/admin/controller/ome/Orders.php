<?php 
/**
 * 订单管理
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\controller\ome;

use app\common\controller\Backend;

use app\admin\model\ome\Orders as OrderObj;
use app\admin\library\ome\View;

class Orders extends Backend
{
    protected $model = null;
    public $_action = '';
    public $_filter = [];
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->model = new OrderObj;
    }
    
    protected function _view()
    {
        $menus = array();
        
        //filter
        $actKey = intval($this->request->get('act'));
        
        //Tab菜单
        switch($this->_action)
        {
            case 'active':
                $menus = $this->_view_active();
            break;
            case 'confirm':
                $menus = $this->_view_confirm();
            break;
        }
        
        if(empty($menus)){
            return [];
        }
        
        foreach ($menus as $key => $val)
        {
            $menus[$key]['count'] = $this->model->getCount($val['filter']);
            $menus[$key]['href'] = url('ome/orders/'. $this->_action, ['act'=>$key]);
            
            if($actKey == $key){
                $this->_filter = $val['filter'];
            }
        }
        
        //act
        $this->view->assign('act', $actKey);
        
        return $menus;
    }
    
    protected function _view_active()
    {
        $filter = array('status'=>'active', 'process_status'=>'unconfirmed');
        $tabs = array(
                0 => array('name'=>__('全部'), 'filter'=>array()),
                1 => array('name'=>__('货到付款'), 'filter'=>array('is_cod'=>'true')),
                2 => array('name'=>__('已支付'), 'filter'=>array('pay_status'=>'1')),
                3 => array('name'=>__('待支付'), 'filter'=>array('pay_status'=>array('IN', array('0','3')))),
        );
        
        foreach ($tabs as $key => $val)
        {
            $tabs[$key]['filter'] = array_merge($filter, $val['filter']);
        }
        
        return $tabs;
    }
    
    protected function _view_confirm()
    {
        $filter = array('status'=>'active', 'process_status'=>'unconfirmed');
        
        $filter = array();
        
        $tabs = array(
                0 => array('name'=>__('全部'), 'filter'=>array()),
                1 => array('name'=>__('货到付款'), 'filter'=>array('is_cod'=>'true')),
                2 => array('name'=>__('已支付'), 'filter'=>array('pay_status'=>'1')),
                3 => array('name'=>__('待支付'), 'filter'=>array('pay_status'=>array('IN', array('0','3')))),
        );
        
        foreach ($tabs as $key => $val)
        {
            $tabs[$key]['filter'] = array_merge($filter, $val['filter']);
        }
        
        return $tabs;
    }
    
    
    public function index()
    {
        
        die('ome_order_index...');
    }
    
    /**
     * 待处理订单栏目
     * 
     * @return string
     */
    public function confirm()
    {
        //Tab菜单
        $this->_action = 'confirm';
        $this->tabs = $this->_view();
        $this->view->assign('tabs', $this->tabs);
        
        //params
        $params = array(
                'title' => '订单列表',
                //'tablename' => 'ome_order_objects', //手工设置读取数据的表名
                'field' => '*',
                'filter' => $this->_filter,
                'show_detail' => true, //显示详情查看按钮
                'show_export' => true, //导出按钮
                'show_import' => true, //导入按钮
        );
        
        //列表所有数据
        $viewObj = new View($this->model);
        $viewObj->main($params);
        
        
        //error_log(var_export($viewObj, 1)."===\n\n", 3, 'F:/logs/view.log');
        //echo('<pre>');
        //print_r($viewObj->data);
        //exit();
        
        
        
        /***
        //所需数据
        1、列表标题
        2、标题栏
        3、是否有查看按钮
        4、字段列表显示选择
        5、搜索项
        6、列表数据
        7、允许排序的字段名
        8、分页
        ***/
        
        $this->view->assign('title', $viewObj->_title); //标题
        $this->view->assign('show_detail', $viewObj->_show_detail); //查看详情按钮
        $this->view->assign('columns', $viewObj->columns['default_in_list']); //默认显示的字段
        $this->view->assign('select_columns', $viewObj->columns['in_list']); //可选择是否显示的字段
        $this->view->assign('search_columns', $viewObj->columns['search']); //允许搜索的字段
        $this->view->assign('sort_columns', $viewObj->columns['in_sort']); //标题列是否允许手工点击排序
        $this->view->assign('datalist', $viewObj->data); //列表数据
        
        return $this->view->fetch();
    }
    
    /**
     * 当前订单栏目
     * 
     * @return string
     */
    public function active()
    {
        //设置过滤方法(去除字符串中的 HTML 标签)
        $this->request->filter(['strip_tags']);
        
        //ajax获取数据
        if ($this->request->isAjax())
        {
            //是否是关联查询
            $this->relationSearch = false;
            
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            //生成查询所需要的条件,排序方式
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            
            //总记录数
            $total = $this->model->where($where)->order($sort, $order)->count();
            
            //记录列表
            $list = $this->model->where($where)->order($sort, $order)->limit($offset, $limit)->select();
            
            /***
            foreach ($list as $item)
            {
                $item->user->visible(['id', 'username', 'nickname', 'avatar']);
                $item->archives->visible(['id', 'title', 'diyname']);
            }
            ***/
            
            //error_log(var_export($total, 1)."===". var_export($list, 1) ."===\n\n", 3, 'F:/logs/active.log');
            
            $result = array('total'=>$total, 'rows'=>$list);
            
            return json($result);
        }
        
        
        
        //Tab菜单
        $this->_action = 'active';
        $this->tabs = $this->_view();
        $this->view->assign('tabs', $this->tabs);
        
        
        return $this->view->fetch();
    } 
    
    
    
}