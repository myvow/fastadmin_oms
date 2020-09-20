<?php
/**
 * 管理者操作日志Lib库
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome\log;

use think\Db;

use app\admin\library\ome\Common;
use app\admin\library\ome\Users;

class Operation
{
    public $operations = array();
    
    /**
     * 写操作日志
     * 
     * @param int $operation 操作标识(例如：order_edit@ome)
     * @param int $obj_id 操作对象id(例如：订单的order_id主键字段)
     * @param string $memo 操作内容备注
     * @param int $operate_time 操作时间
     * @param string $operInfo 操作额外信息
     * @return bool
     */
    function write_log($operation, $obj_id, $memo=null, $operate_time=0, $operInfo=null)
    {
        $commonLib = new Common;
        
        $operate_time = ($operate_time ? $operate_time : time());
        $ip = $commonLib->get_remote_addr();
        
        //操作者
        $operInfo = $this->_get_op_info($operInfo);
        $op_id = $operInfo['op_id']; //操作者ID
        $op_name = $operInfo['op_name']; //操作者姓名
        
        //操作对象
        $temp = explode('@', $operation);
        if(empty($temp))
        {
            return false;
        }
        
        $model = $temp[1]; //应用名称,例如：ome
        $method = $temp[0]; //操作动作,例如：order_edit
        $operInfo = $this->get_operations($model, $method);
        $obj_type = $operInfo['type'];
        $obj_name = $operInfo['name'];
        
        //组织参数
        $data = array(
                'obj_id' => $obj_id,
                'obj_name' => $title_value, //201905071010002022
                'obj_type' => $obj_type, //orders@ome、inventory@taoguaninventory
                'operation' => $operation, //order_create@ome、inventory_modify@taoguaninventory
                'op_id' => $op_id, //操作人ID
                'op_name' => $op_name, //操作人账号
                'operate_time' => $operate_time, //操作时间
                'memo' => $obj_name, //操作动作的名称
                'ip' => $ip, //IP地址
        );
        
        $res = Db::name('ome_operation_log')->insert($data);
        if(!$res){
            return false;
        }
        
        return true;
    }
    
    /**
     * 获取操作者信息
     * 
     * @param array $operInfo 操作人信息
     * @return array
     */
    private function _get_op_info($operInfo=null)
    {
        $userLib = new Users;
        
        if ($operInfo){
            $_opinfo = $operInfo;
        }else{
            $_opinfo = $userLib->getDesktopUser();
        }
        
        return $_opinfo;
    }
    
    /**
     * 定义操作日志的动作名称列表
     * 
     * @param string $model
     * @param string $method
     * @return Array
     */
    function get_operations($model=null, $method=null)
    {
        $operations = array();
        $operations['ome'] = array(
                //订单
                'order_create' => array('name'=> '订单创建','type' => 'orders@ome'),
                'order_edit' => array('name'=> '订单编辑','type' => 'orders@ome'),
                'order_modify' => array('name'=> '订单修改','type' => 'orders@ome'),
                'order_back' => array('name'=> '订单的发货单打回','type' => 'orders@ome'),
                'order_dispatch' => array('name'=> '订单调度','type' => 'orders@ome'),
                'order_confirm' => array('name'=> '订单确认','type' => 'orders@ome'),
                'order_split' => array('name'=> '订单拆分','type' => 'orders@ome'),
                'order_payment' => array('name'=> '订单支付请求','type' => 'orders@ome'),
                'order_refund' => array('name'=> '订单退款请求','type' => 'orders@ome'),
                'order_pay' => array('name'=> '支付单添加','type' => 'orders@ome'),
                'order_refuse' => array('name'=> '订单发货拒收','type' => 'orders@ome'),
                'order_preprocess' => array('name'=> '订单预处理','type' => 'orders@ome'),
                'order_retrial' => array('name'=> '复审订单','type' => 'orders@ome'),
                
                //售后
                'return' =>array('name'=> '售后服务修改','type' => 'return_product@ome'),
                'reship' =>array('name'=>'售后服务修改','type' => 'reship@ome'),
                
                //退款
                'refund_apply' => array('name'=> '退款申请','type' => 'refund_apply@ome'),
                'refund_accept' => array('name'=> '退款成功','type' => 'refunds@ome'),
                'refund_refuse' => array('name'=> '退款拒绝','type' => 'refund_apply@ome'),
                'refund_verify' => array('name'=> '退款审核中','type' => 'refund_apply@ome'),
                'refund_pass' => array('name'=> '退款审核通过','type' => 'refund_apply@ome'),
                
                //支付
                'payment_create' => array('name'=> '生成支付单','type' => 'payments@ome'),
                
                //仓库
                'branch' => array('name'=> '库存导入','type' => 'branch@ome'),
                'branch_pos_del' => array('name'=> '删除货位','type' => 'branch_pos@ome'),
                
                //发货单
                'delivery_modify' => array('name'=> '发货单详情修改','type' => 'delivery@ome'),
                'delivery_position' => array('name'=> '发货单货位 录入','type' => 'delivery@ome'),
                'delivery_merge' => array('name'=> '发货单合并','type' => 'delivery@ome'),
                'delivery_split' => array('name'=> '发货单拆分','type' => 'delivery@ome'),
                'delivery_stock' => array('name'=> '发货单备货单打印','type' => 'delivery@ome'),
                'delivery_deliv' => array('name'=> '发货单商品信息打印','type' => 'delivery@ome'),
                'delivery_expre' => array('name'=> '发货单快递单打印','type' => 'delivery@ome'),
                'delivery_logi_no' => array('name'=> '发货单快递单 录入','type' => 'delivery@ome'),
                'delivery_check' => array('name'=> '发货单校验','type' => 'delivery@ome'),
                'delivery_process' => array('name'=> '发货单发货处理','type' => 'delivery@ome'),
                'delivery_back' => array('name'=> '发货单打回','type' => 'delivery@ome'),
                'delivery_logi' => array('name'=> '发货单物流公司修改','type' => 'delivery@ome'),
                'delivery_pick' => array('name'=> '发货单拣货','type' => 'delivery@ome'),
                'delivery_weightwarn' => array('name'=> '发货称重报警处理','type' => 'delivery@ome'),
                
                //子物流单操作日志
                'delivery_bill_print' => array('name'=> '多包裹物流单 打印','type' => 'delivery@ome'),
                'delivery_bill_delete' => array('name'=> '多包裹物流单 删除','type' => 'delivery@ome'),
                'delivery_bill_add' => array('name'=> '多包裹物流单 录入','type' => 'delivery@ome'),
                'delivery_bill_modify' => array('name'=> '多包裹物流单 修改','type' => 'delivery@ome'),
                'delivery_bill_express' => array('name'=> '多包裹物流单 发货','type' => 'delivery@ome'),
                'delivery_checkdelivery'=>array('name'=>'发货单发货处理','type' => 'delivery@ome'),
                
                //商品修改
                'goods_modify'=>array('name'=>'商品修改','type'=>'goods@ome'),
                'goods_add'=>array('name'=>'商品添加','type'=>'goods@ome'),
                'goods_hide'=>array('name'=>'商品隐藏','type'=>'goods@ome'),
                'goods_show'=>array('name'=>'商品显示','type'=>'goods@ome'),
                'goods_import'=>array('name'=>'商品导入','type'=>'goods@ome'),
                
                //跨境申报订单
                'customs_create' => array('name'=> '申报创建','type' => 'orders@customs'),
                'customs_edit' => array('name'=> '申报编辑','type' => 'orders@customs'),
                'customs_api' => array('name'=> '申报接口','type' => 'orders@customs'),
                'crm_on' => array('name'=> '开启CRM赠品应用','type' => 'gift@crm'),
                'crm_off' => array('name'=> '关闭CRM赠品应用','type' => 'gift@crm'),
                'crm_edit'=>array('name'=>'赠品规则修改','type'=>'gift_rule@ome'),
                
                //唯品会JIT
                'create_vopurchase' => array('name'=>'采购单创建', 'type' => 'order@purchase'),
                'update_vopurchase' => array('name'=>'采购单更新', 'type' => 'order@purchase'),
                'create_vopick' => array('name'=>'拣货单创建', 'type' => 'pick_bills@purchase'),
                'update_vopick' => array('name'=>'拣货单更新', 'type' => 'pick_bills@purchase'),
                'check_vopick' => array('name'=>'拣货单审核', 'type' => 'pick_bills@purchase'),
                'create_stockout_bills' => array('name'=>'出库单创建', 'type' => 'pick_stockout_bills@purchase'),
                'update_stockout_bills' => array('name'=>'出库单更新', 'type' => 'pick_stockout_bills@purchase'),
                'edit_stockout_bills' => array('name'=>'出库单编辑', 'type' => 'pick_stockout_bills@purchase'),
                'check_stockout_bills' => array('name'=>'出库单审核', 'type' => 'pick_stockout_bills@purchase'),
                
                //人工库存预占
                'import_artificial_freeze' => array('name'=>'人工库存预占导入', 'type' => 'basic_material_stock_artificial_freeze@console'),
                'add_artificial_freeze' => array('name'=>'人工库存预占新增', 'type' => 'basic_material_stock_artificial_freeze@console'),
                'delete_artificial_freeze' => array('name'=>'人工库存预占删除', 'type' => 'basic_material_stock_artificial_freeze@console'),
                'release_artificial_freeze' => array('name'=>'人工库存预占释放', 'type' => 'basic_material_stock_artificial_freeze@console'),
    
        );
        
        if($model && $method)
        {
            return $operations[$model][$method];
        }
        
        return $operations;
    }
}