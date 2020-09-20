<?php
/**
 * 订单接口处理入口类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response;

use think\Db;

use app\admin\library\erpapi\shop\response\Abstracts;

class Order extends Abstracts
{
    /**
     * ERP订单
     *
     * @var string
     **/
    public $_tgOrder = array();

    /**
     * 订单接收格式
     *
     * @var string
     **/
    public $_ordersdf = array();

    /**
     * 订单标准结构
     *
     * @var string
     **/
    public $_newOrder = array();

    /**
     * 可接收未付款订单
     *
     * @var string
     **/
    protected $_accept_unpayed_order = false;

    /**
     * 更新订单是否接收死单
     *
     * @var string
     **/
    protected $_update_accept_dead_order = false;

    /**
     * 订单obj明细唯一标识
     * todo：bn、shop、goods_id、obj_type
     *
     * @var string
     **/
    public $object_comp_key = 'bn-shop_goods_id-obj_type';

    /**
     * 订单item唯一标识
     * todo：bn、shop、product_id、item_type
     *
     * @var string
     **/
    public $item_comp_key = 'bn-shop_product_id-item_type';
    
    /**
     * 创建or更新 标识
     * 
     * @var string
     */
    public $_operationSel = '';
    
    /**
     * 防并发key
     *
     * @return void
     * @author 
     **/
    public function concurrentKey($sdf)
    {
        $this->__lastmodify = date2time($sdf['lastmodify']);
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        
        if ($sdf['method'] && $sdf['node_id'] && $sdf['order_bn'])
        {
            $key = $sdf['method'].$sdf['node_id'].$sdf['order_bn'];
        }
        
        return $key ? md5($key) : false;
    }
    
    /**
     * 处理接收的订单数据
     * 
     * @param array $sdf
     * @return array
     */
    public function add($sdf)
    {
        $this->_ordersdf = $sdf;
        $this->_tgOrder = $this->_newOrder = array();
        
        $this->__apilog['result']['data'] = array('tid'=>$this->_ordersdf['order_bn']);
        $this->__apilog['original_bn'] = $this->_ordersdf['order_bn'];
        $this->__apilog['title'] = '创建订单['.$this->_ordersdf['order_bn'].']';
        
        // 数据格式化
        $this->_analysis();
        
        //check
        if(empty($this->_ordersdf) || empty($this->_ordersdf['order_bn']) || empty($this->_ordersdf['order_objects']))
        {
            $this->__apilog['result']['msg'] = '接收数据不完整';
            return false;
        }
        
        //订单明细格式化
        $this->formatItemsSdf();
        
        //判断更新or新增
        $this->_operationSel();

        switch ($this->_operationSel)
        {
            case 'create':
                $rs = $this->_createOrder();
                if ($rs === false) return array();
                
                break;
            case 'update':
                $rs = $this->_updateOrder();

                if ($rs === false) return array();

                if (!$this->_newOrder && !$this->__apilog['result']['msg'])
                    $this->__apilog['result']['msg'] = '订单无结构变化，无需更新';
                
                if ($this->_newOrder) {
                    $this->_newOrder['order_id'] = $this->_tgOrder['order_id'];
                }
                
                break;
            default:
                $this->__apilog['title']         = '更新订单['.$this->_ordersdf['order_bn'].']';
                $this->__apilog['result']['msg'] = '更新时间没变，无需更新';
                
                return array();
                break;
        }
        
        return $this->_newOrder;
    }

    /**
     * 订单操作：创建 or 更新
     * 
     * @return bool
     **/
    protected function _operationSel()
    {
        $lastmodify = date2time($this->_ordersdf['lastmodify']);
        
        $this->_tgOrder = Db::name('ome_orders')->where('order_bn', $this->_ordersdf['order_bn'])->find();
        
        if(empty($this->_tgOrder))
        {
            $this->_operationSel = 'create'; //新增
        }
        elseif($lastmodify > $this->_tgOrder['outer_lastmodify'])
        {
            //更新前端店铺最后更新时间
            $filter = array('order_id'=>$this->_tgOrder['order_id']);
            $data = array('outer_lastmodify'=>$lastmodify);
            $isSave = Db::name('ome_orders')->where($filter)->update($data);
            
            if ($isSave !== false) $this->_operationSel = 'update'; //更新
        }
        
        return true;
    }

    /**
     * 创建订单
     *
     * @return void
     * @author 
     **/
    protected function _createOrder()
    {
        $this->__apilog['title']  = '创建订单['.$this->_ordersdf['order_bn'].']';
        
        //检查是否符合创建订单
        if (false === $this->_canCreate()) {
            return false;
        }
        
        //组件集合(订单数据转换)
        foreach ($this->get_convert_components() as $component)
        {
            $componentName = "\\app\\admin\\library\\erpapi\\shop\\response\\components\\". ucfirst($component);
            if (!class_exists($componentName))
            {
                continue;
            }
            
            $componentObj = new $componentName;
            if(!method_exists($componentObj, 'convert'))
            {
                continue;
            }
            
            //订单数据转换
            $componentObj->convert($this->_ordersdf);
        }
        $this->_newOrder = $componentObj->_newOrder; //获取最终转换的订单信息
        
        //插件数据处理
        foreach ($this->get_create_plugins() as $plugin)
        {
            $pluginName = "\\app\\admin\\library\\erpapi\\shop\\response\\plugins\\". ucfirst($plugin);
            if (!class_exists($pluginName))
            {
                continue;
            }
            
            $pluginObj = new $pluginName;
            if(!method_exists($pluginObj, 'convert'))
            {
                continue;
            }
            
            //订单插件处理
            $pluginsdf = $pluginObj->convert($this->_ordersdf);
            if($pluginsdf){
                $this->_newOrder['plugins'][$plugin] = $pluginsdf;
            }
        }
        
        return true;
    }

    /**
     * 订单组件
     *
     * @return void
     * @author 
     **/
    protected function get_convert_components()
    {
        $components = array('master','items','shipping','consignee','consigner','custommemo','markmemo','marktype','member','tax');
        return $components;
    }

    /**
     * 创建订单的插件
     *
     * @return void
     * @author 
     **/
    protected function get_create_plugins()
    {
        $plugins = array('payment','promotion','cod', 'ordertype','combine','service');
        return $plugins;
    }

    /**
     * 更新订单
     *
     * @return void
     * @author 
     **/
    protected function _updateOrder()
    {
        $this->__apilog['title'] = '更新订单['.$this->_ordersdf['order_bn'].']';

        if (false === $this->_canUpdate()) {
            return false;
        }

        
        
        
        
        
        
        
        // 组件集合
        $broker = kernel::single('erpapi_shop_response_components_order_broker');

        $broker->clearComponents();

        foreach ($this->get_update_components() as $component) {
            $broker->registerComponent($component);
        }

        $broker->setPlatform($this)->update();

        // 插件的SDF
        foreach ($this->get_update_plugins() as $plugin) {
            $pluginObj = kernel::single('erpapi_shop_response_plugins_order_'.$plugin);
            if (method_exists($pluginObj, 'convert')) {
                $pluginsdf = $pluginObj->convert($this);

                if ($pluginsdf) $this->_newOrder['plugins'][$plugin] = $pluginsdf;
            }
        }

        return true;
    }

    /**
     * 订单组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $components = array('master','items','shipping','consignee','consigner','custommemo','markmemo','marktype','member','tax');
        return $components;
    }

    /**
     * 更新插件
     *
     * @return void
     * @author 
     **/
    protected function get_update_plugins()
    {
        $plugins = array();

        return $plugins;
    }

    /**
     * 数据解析
     *
     * @return void
     * @author 
     **/
    protected function _analysis()
    {
        //店铺信息
        $this->_ordersdf['shop_id'] = $this->__channelObj->channel['shop_id'];
        $this->_ordersdf['shop_type'] = $this->__channelObj->channel['shop_type'];
        $this->_ordersdf['node_type'] = $this->__channelObj->channel['node_type'];
        $this->_ordersdf['shop_name'] = $this->__channelObj->channel['name'];
        
        // 配送信息
        if(is_string($this->_ordersdf['shipping']))
        $this->_ordersdf['shipping'] = json_decode($this->_ordersdf['shipping'],true);

        // 支付信息
        if(is_string($this->_ordersdf['payinfo']))
        $this->_ordersdf['payinfo'] = json_decode($this->_ordersdf['payinfo'],true);

        // 收货人信息
        if(is_string($this->_ordersdf['consignee']))
        $this->_ordersdf['consignee'] = json_decode($this->_ordersdf['consignee'],true);

        // 发货人信息
        if (is_string($this->_ordersdf['consigner'])) 
        $this->_ordersdf['consigner'] = json_decode($this->_ordersdf['consigner'],true);

        // 代销人信息
        if(is_string($this->_ordersdf['selling_agent']))
        $this->_ordersdf['selling_agent'] = json_decode($this->_ordersdf['selling_agent'],true);

        // 菜鸟直销订单
        if(is_string($this->_ordersdf['cn_info']))
        $this->_ordersdf['cn_info'] = json_decode($this->_ordersdf['cn_info'],true);

        // 买家会员信息
        if(is_string($this->_ordersdf['member_info']))
        $this->_ordersdf['member_info'] = json_decode($this->_ordersdf['member_info'],true);

        if($this->_ordersdf['member_info']['uname']){
            $this->_ordersdf['member_info']['uname'] = kernel::single('ome_order_func')->filterEmoji($this->_ordersdf['member_info']['uname']);
        }
         // 订单优惠方案
        if(is_string($this->_ordersdf['pmt_detail']))
        $this->_ordersdf['pmt_detail'] = json_decode($this->_ordersdf['pmt_detail'],true);

        // 订单商品
        if(is_string($this->_ordersdf['order_objects']))
        $this->_ordersdf['order_objects'] = json_decode($this->_ordersdf['order_objects'],true);

        // 支付单(兼容老版本)
        if(is_string($this->_ordersdf['payment_detail']))
        $this->_ordersdf['payment_detail'] = json_decode($this->_ordersdf['payment_detail'],true);
        
        if(is_string($this->_ordersdf['payments']))
        $this->_ordersdf['payments'] = $this->_ordersdf['payments'] ? json_decode($this->_ordersdf['payments'],true) : array();

        if(is_string($this->_ordersdf['other_list']))
        $this->_ordersdf['other_list'] = json_decode($this->_ordersdf['other_list'],true);

        // 去首尾空格
        self::trim($this->_ordersdf);

        // 如果是担保交易,订单支付状态修复成已支付
        if ($this->_ordersdf['pay_status'] == '2') {
            $this->_ordersdf['pay_status'] = '1';
        }

        // 如果是货到付款的，重置支付金额，支付单
        if ($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_ordersdf['payed']          = '0';
            $this->_ordersdf['payments']       = array();
            $this->_ordersdf['payment_detail'] = array();    
        }

        $this->_ordersdf['pmt_goods'] = abs($this->_ordersdf['pmt_goods']);
        $this->_ordersdf['pmt_order'] = abs($this->_ordersdf['pmt_order']);

        if ($this->_ordersdf['pay_status'] == '5') $this->_ordersdf['payed'] = 0;
        if (is_string($this->_ordersdf['service_order_objects']))
            $this->_ordersdf['service_order_objects'] = json_decode($this->_ordersdf['service_order_objects'],true);
        
        // 由于OBJ货号太长，导致更新的时候明细不一致
        foreach ((array) $this->_ordersdf['order_objects'] as $objkey => $object)
        {
            if ($object['bn'] && $object['bn']{40}) {
                $this->_ordersdf['order_objects'][$objkey]['bn'] = substr($object['bn'],0,40);
            }
        }

        if ($this->_ordersdf['end_time']){
            $this->_ordersdf['end_time'] = strtotime($this->_ordersdf['end_time']);
        }

        if(isset($this->_ordersdf['o2o_info'])){
            $this->_ordersdf['o2o_info']=json_decode($this->_ordersdf['o2o_info'],true);
        }
    }
    
    /**
     * 创建接收
     *
     * @return void
     * @author 
     **/
    protected function _canCreate()
    {
        if ($this->_ordersdf['ship_status'] != '0') {
            $this->__apilog['result']['msg'] = '已发货订单不接收';
            return false;
        }
        
        if ($this->_ordersdf['status'] != 'active') {
            $this->__apilog['result']['msg'] = ($this->_ordersdf['status'] == 'close' ? '取消订单不接收' : '完成订单不接收');

            return false;
        }
        
        //是否接收未付款的订单
        if ($this->_accept_unpayed_order !== true)
        {
            if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
                $this->__apilog['result']['msg'] = '未支付订单不接收';
                return false;
            }
        }
        
        if (in_array($this->_ordersdf['pay_status'], array('4','5','6','7','8'))) {
            $this->__apilog['result']['msg'] = '退款订单不接收';
            return false;
        }
        
        return true;
    }

    /**
     * 更新接收
     *
     * @return void
     * @author 
     **/
    protected function _canUpdate()
    {
        if (!in_array($this->_ordersdf['status'], array('active','finish','close','dead'))) {
            $this->__apilog['result']['msg'] = '不明订单状态不接收';
            return false;
        }

        if ($this->_ordersdf['status'] == 'close') {
            $this->__apilog['result']['msg'] = '关闭订单不接收';
            return false;
        }

        if ($this->_tgOrder['status'] == 'dead') {
            $this->__apilog['result']['msg'] = 'ERP取消订单，不做更新';
            return false;
        }

        if ($this->_update_accept_dead_order === false && $this->_ordersdf['status'] == 'dead') {
            $this->__apilog['result']['msg'] = '取消订单不接收';
            return false;
        }


        if ($this->_ordersdf['status'] == 'finish' && ($this->_ordersdf['end_time']=='' || $this->_tgOrder['end_time']>0)) {
            $this->__apilog['result']['msg'] = '完成订单不接收';
            return false;
        }

        if (in_array($this->_tgOrder['ship_status'],array('1','2')) || $this->_tgOrder['status']=='finish') {
            if ($this->_ordersdf['end_time']<=0 || $this->_tgOrder['end_time']>0){
                $this->__apilog['result']['msg'] = 'ERP发货订单，不做更新';
                return false;
            }
        }

        if (in_array($this->_tgOrder['ship_status'],array('3','4')) ) {
            $this->__apilog['result']['msg'] = 'ERP退货订单，不做更新';
            return false;
        }
    }



    public function status_update($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '修改订单状态['.$sdf['order_bn'].']';


        // 只接收作废订单
        if ($sdf['status'] == '') {
            $this->__apilog['result']['msg'] = '订单状态不能为空';
            return false;
        }

        if ($this->__channelObj->get_ver() > '1') {
            if ($sdf['status'] != 'dead') {
                $this->__apilog['result']['msg'] = '不接收除作废以外的其他状态';
                return false;
            }
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('pay_status,order_id,op_id,ship_status,status,process_status',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }


        if (in_array($tgOrder['pay_status'], array('1','2','3','4'))) {
            $this->__apilog['result']['msg'] = '订单已经支付，不更新';
            return false;
        }

        if ($tgOrder['ship_status'] != 0) {
            $this->__apilog['result']['msg'] = '订单未发货，不更新';
            return false;
        }

        if ($tgOrder['status'] != 'active' || $tgOrder['process_status'] == 'cancel') {
            $this->__apilog['result']['msg'] = '订单已取消，不更新';
            return false;
        }
            

        $updateOrder = array();

        if (!$tgOrder['op_id']) {
            $userModel = app::get('desktop')->model('users');
            $userinfo = $userModel->getList('user_id',array('super'=>'1'),0,1,'user_id asc');
            $updateOrder['op_id'] = $userinfo[0]['op_id'];
        }

        $updateOrder['status'] = $sdf['status'];

        if ($updateOrder) {    
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;
    }

    public function pay_status_update($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '修改订单支付状态['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text,cost_payment,total_amount,final_amount',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $updateOrder = array();

        $updateOrder['pay_status'] = $sdf['pay_status'];

        if ($updateOrder) {
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;

    }

    public function ship_status_update($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '修改订单发货状态['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $updateOrder = array();

        $updateOrder['ship_status'] = $sdf['ship_status'];

        if ($updateOrder) {
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;
    }

    public function custom_mark_add($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '添加订单买家备注['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id,custom_mark',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $updateOrder = array();
        if ($sdf['message']) {
            $custom_mark = $tgOrder['custom_mark'] ? unserialize($tgOrder['custom_mark']) : array();

            $custom_mark[] = array(
                'op_name'    => $sdf['sender'],
                'op_time'    => date2time($sdf['add_time']),
                'op_content' => htmlspecialchars($sdf['message']),
            );

            $updateOrder['custom_mark'] = serialize($custom_mark);
        }

        if ($updateOrder) {
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;
    }

    public function custom_mark_update($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '更新订单买家备注['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id,custom_mark',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $updateOrder = array();
        if ($sdf['message']) {
            $custom_mark = $tgOrder['custom_mark'] ? unserialize($tgOrder['custom_mark']) : array();

            $custom_mark[] = array(
                'op_name'    => $sdf['sender'],
                'op_time'    => date2time($sdf['add_time']),
                'op_content' => htmlspecialchars($sdf['message']),
            );

            $updateOrder['custom_mark'] = serialize($custom_mark);
        }

        if ($updateOrder) {
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;
    }

    public function memo_add($sdf)
    {
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '添加订单商家备注['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text,cost_payment,total_amount,final_amount',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $updateOrder = array(); 
        if ($sdf['memo']) {
            $mark_text =  $tgOrder['mark_text'] ? (array) unserialize($tgOrder['mark_text']) : array();

            $mark_text[] = array(
                'op_name'    => $sdf['sender'],
                'op_time'    => date2time($sdf['add_time']),
                'op_content' => htmlspecialchars($sdf['memo']),
            );

            $updateOrder['mark_text'] = serialize($mark_text);
        }

        if ($sdf['flag']) {
            $updateOrder['mark_type'] = $sdf['flag'];
        }

        if ($updateOrder) {
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;
    }

    public function memo_update($sdf)
    {
        // $this->__apilog['result']['data'] = array('tid'=>$this->_ordersdf['order_bn']);
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '更新订单商家备注['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text,cost_payment,total_amount,final_amount',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $updateOrder = array(); 
        if ($sdf['memo']) {
            $mark_text =  $tgOrder['mark_text'] ? (array) unserialize($tgOrder['mark_text']) : array();

            $mark_text[] = array(
                'op_name'    => $sdf['sender'],
                'op_time'    => date2time($sdf['add_time']),
                'op_content' => htmlspecialchars($sdf['memo']),
            );

            $updateOrder['mark_text'] = serialize($mark_text);
        }

        if ($sdf['flag']) {
            $updateOrder['mark_type'] = $sdf['flag'];
        }

        if ($updateOrder) {
            $updateOrder['order_id'] = $tgOrder['order_id'];
        }

        return $updateOrder;
    }

    public function payment_update($sdf)
    {
        // $this->__apilog['result']['data'] = array('tid'=>$this->_ordersdf['order_bn']);
        $this->__apilog['original_bn']    = $sdf['order_bn'];
        $this->__apilog['title']          = '更新订单支付方式['.$sdf['order_bn'].']';

        if ($this->__channelObj->get_ver() > '1') {
            $this->__apilog['result']['msg'] = '版本2不走此接口';
            return false;
        }

        // 读取订单
        $orderModel = app::get('ome')->model('orders');

        $filter = array('order_bn'=>$sdf['order_bn'],'shop_id'=>$this->__channelObj->channel['shop_id']);
        $tgOrder = $orderModel->getList('order_id,mark_text,cost_payment,total_amount,final_amount',$filter,0,1);
        $tgOrder = $tgOrder[0];

        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = 'ERP订单不存在';
            return false;
        }

        $total_amount = bcsub(bcadd($tgOrder['total_amount'], $sdf['cost_payment'],3), $tgOrder['cost_payment'],3);
        $updateOrder = array(
            'pay_bn' => $sdf['pay_bn'],
            'payinfo' => array(
                'pay_name'     => $sdf['payment'],
                'cost_payment' => $sdf['cost_payment'],
            ),
            'cur_amount'   => $total_amount,
            'total_amount' => $total_amount,
            'order_id'     => $tgOrder['order_id'],
        );
        return $updateOrder;
    }

    /**
     *
     * 订单明细格式化,该方法兼容订单打下来的明细结构，非常重要
     * @param void
     * @return void
     */
    protected function formatItemsSdf()
    {
        //非新的订单明细格式，老的两层结构做格式化
        if(!isset($this->_ordersdf['new_orderobj']))
        {
            $adjunctObj = array();//ec附件
            $giftObj = array();//ec商品促销指定商品赠品
            foreach($this->_ordersdf['order_objects'] as $k =>$object)
            {
                //如果1个item对1个obj认为是普通商品,item合并到obj层,item矩阵打过来的真实信息
                if(count($object['order_items']) == 1){
                    $tmp_obj_items = $object['order_items'][0];
                    unset($object['order_items']);
                    $tmp_obj = $object;
                    $tmp['order_object'][$k] = array_merge($tmp_obj, $tmp_obj_items);
                }else{
                    //如果是促销组合类，就直接以obj层为准
                    $adjunct_amount = 0;
                    foreach($object['order_items'] as $item)
                    {
                        $adjunct_flag = false;
                        $gift_flag = false;
                        if($item['item_type'] == 'adjunct'){
                            $adjunct_flag = true;
                        }
                        
                        if($item['item_type'] == 'gift'){
                            $gift_flag = true;
                        }
                        
                        if($item['status'] != 'close')
                        {
                            if($adjunct_flag){//配件对应amount pmt_price要从object里去除 另组一个object
                                $adjunct_amount += $item['amount'];
                                $adjunctObj[] = $item;
                            }elseif ($gift_flag){
                                $giftObj[] = $item;
                            }else{
                                $object_pmt += (float)$item['pmt_price'];
                            }
                        }else{
                            $is_delete = true;
                        }
                        
                    }
                    
                    $object_pmt += $object['pmt_price'];
                    if($adjunct_amount>0){
                        $object['amount'] = $object['amount']-$adjunct_amount;
                    }
                    unset($object['order_items']);
                    
                    $tmp_obj = $object;
                    $tmp_obj['status'] = $is_delete ? 'close' : 'active';
                    $tmp['order_object'][$k] = array_merge($tmp_obj, array('pmt_price'=>$object_pmt));
                }
            }
            
            //赋值重组后的数据
            $this->_ordersdf['order_objects'] = array_merge($tmp['order_object'],$adjunctObj,$giftObj);
        }
    }
}