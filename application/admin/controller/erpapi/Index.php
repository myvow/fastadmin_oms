<?php
namespace app\admin\controller\erpapi;

use app\common\controller\Frontend;
use think\Db;

class Index extends Frontend
{
    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证是否拥有应用的权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    
    //无需登录的接口,*表示全部
    protected $noNeedLogin = '*';
    
    //无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';
    
    //是否开启转义HTML标签(关闭)
    protected $layout = '';
    
    public function index()
    {
        /**
         * 获取前端登录的用户信息
         * 
         * 必须extends Frontend继承前台控制器基类
         */
        //获取Auth对象
        $auth = \app\common\library\Auth::instance();
        
        //获取会员模型
        $user = $auth->getUser();
        
        echo($user->id.'======='.$user->username);
        
        echo('<pre>==aa=');
        print_r($auth);
        exit();
        
        
        
        
        
        $data = Db::name('ome_orders')->where(array('order_id'=>1))->field('*')->find();
        
        echo('<pre>===');
        print_r($data);
        exit();
        
        
        
        $salesMaterialInfo = array();
        
        $shop_id = '123456';
        $bn = 'innisfreaadsfdsaeml005';
        
        $filter = array(
                'sales_material_bn' => $bn,
                'shop_id' => array('IN', array($shop_id, '_ALL_')),
        );
        $salesMaterialInfo = Db::name('product_sales_material')->where($filter)->find();
        
        echo('<pre>===');
        print_r($salesMaterialInfo);
        exit();
        
        
        
        
        
        
        $sellingagent = array(
                'area_state' => '安徽省',
                'area_city' => '淮南市',
                'area_district' => '中关村s',
        );
        
        
        $area = $sellingagent['area_state'] . '/' . $sellingagent['area_city'] . '/'.$sellingagent['area_district'];
        
        //$funLib = new Common;
        //$funLib->region_validate($area);
        
        
        echo('<pre>===');
        print_r($area);
        exit();
        
        
        
        die('abc...');
    }

}
