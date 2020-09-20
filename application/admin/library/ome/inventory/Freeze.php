<?php
/**
 * 货品库存冻结明细日志Lib类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome\inventory;

use think\Db;

class Freeze
{
    //对象类型字段的常量定义
    //订单类型
    const __ORDER = 1;

    //仓库类型
    const __BRANCH = 2;

    //配额ID字段的常量定义
    //非配额即共享库存
    const __SHARE_STORE = -1;

    //门店确认库存
    const __STORE_CONFIRM = -2;
    
    //发货业务
    const __DELIVERY = 1;
    
    //售后业务
    const __RESHIP = 2;
    
    //采购退货业务
    const __RETURNED = 3;
    
    //调拨出库业务
    const __STOCKOUT = 4;
    
    //库内转储业务
    const __STOCKDUMP = 5;
    
    //唯品会出库业务
    const __VOPSTOCKOUT = 6;
    
    //人工库存预占业务
    const __ARTIFICIALFREEZE = 7;
    
     function __construct(){
        $this->_stockFreezeObj = app::get('material')->model('basic_material_stock_freeze');
        $this->_libBranchProduct = kernel::single('ome_branch_product');
     }

    /**
     * 增加基础物料冻结
     * 
     * @param Int $bm_id 基础物料ID
     * @param Int $obj_type 1 订单预占 2 仓库预占
     * @param Int $bill_type 业务类型  默认为0
     * @param Int $obj_id 关联对象ID
     * @param String $shop_id 店铺ID
     * @param String $branch_id 仓库ID
     * @param Int $bmsq_id 配额ID  -1代表非配额货品 -2代表门店确认库存的货品 -3代表门店非确认库存的货品
     * @param Int $num 预占数
     * @param string $log_type 日志类型  default ''
     * @return Boolean
     */
    public function freeze($bm_id, $obj_type, $bill_type, $obj_id, $shop_id, $branch_id, $bmsq_id, $num, $log_type=''){

        if(empty($bm_id) || empty($obj_type) || empty($bmsq_id)){
            return false;
        }

        $num = intval($num);

        switch($obj_type){
            //订单预占
            case 1:
                $filter = array('bm_id'=>$bm_id, 'obj_type'=>1, 'obj_id'=>$obj_id, 'bmsq_id'=>$bmsq_id);
                $insertExtData = array('shop_id'=>$shop_id, 'bill_type'=>$bill_type);
                break;
            //电商仓/门店仓预占
            case 2:
                $filter = array('bm_id'=>$bm_id, 'obj_type'=>2, 'obj_id'=>$obj_id, 'bmsq_id'=>$bmsq_id, 'bill_type'=>$bill_type);
                $insertExtData = array('shop_id'=>$shop_id, 'branch_id'=>$branch_id);
                break;
        }

        //仓库类型
        if($obj_type == 2)
        {
            //如果是门店确定性预占，总计字段也做更新
            if($bmsq_id == -2){
                $branchPrdLib        = kernel::single('o2o_branch_product');
                $rs = $branchPrdLib->changeStoreConfirmFreeze($branch_id,$bm_id,$num,'+');
                if($rs == false){
                    return false;
                }
            }
            elseif($bmsq_id == -1)
            {
                //仓库冻结库存
                $rs    = $this->_libBranchProduct->chg_product_store_freeze($branch_id, $bm_id, $num, '+', $log_type);
                if($rs == false){
                    return false;
                }
            }
        }
        
        $freezeRow = $this->_stockFreezeObj->getList('bmsf_id', $filter, 0, 1);
        if($freezeRow){
            $sql = "UPDATE sdb_material_basic_material_stock_freeze SET num=num+".$num.", last_modified=". time() ." WHERE bmsf_id=".$freezeRow[0]['bmsf_id'];
            if($this->_stockFreezeObj->db->exec($sql)){
                $rs = $this->_stockFreezeObj->db->affect_row();
                if(is_numeric($rs) && $rs > 0){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            $insertData = $filter;
            $insertData['num'] = $num;
            $insertData['create_time'] = time();
            $insertData['last_modified'] = time();
            
            if($insertExtData){
                $insertData = array_merge($insertData, $insertExtData);
            }

            return $this->_stockFreezeObj->insert($insertData);
        }
    }


    /**
     * 释放基础物料冻结
     * 
     * @param Int $bm_id 基础物料ID
     * @param Int $obj_type 1 订单预占 2 仓库预占
     * @param Int $bill_type 业务类型  默认为0
     * @param Int $obj_id 关联对象ID
     * @param String $branch_id 仓库ID
     * @param Int $bmsq_id 配额ID  -1代表非配额货品 -2代表门店确认库存的货品 -3代表门店非确认库存的货品
     * @param Int $num 预占数
     * @param string $log_type 日志类型  default ''
     * @return Boolean
     */
    public function unfreeze($bm_id, $obj_type, $bill_type, $obj_id, $branch_id, $bmsq_id, $num, $log_type=''){

        if(empty($bm_id) || empty($obj_type) || empty($bmsq_id)){
            return false;
        }

        $num = intval($num);

        switch($obj_type){
            case 1:
                $sql_where = "WHERE  bm_id =".$bm_id." and obj_type =".$obj_type." and obj_id =".$obj_id." and bmsq_id =".$bmsq_id;
                break;
            case 2:
                $sql_where = "WHERE  bm_id =".$bm_id." and obj_type =".$obj_type." and bill_type=". $bill_type ." and obj_id =".$obj_id." and bmsq_id =".$bmsq_id;
                break;
            default:
                return false;
                break;
        }

        //仓库类型
        if($obj_type == 2)
        {
            //如果是门店确定性预占，总计字段也做更新
            if($bmsq_id == -2){
                $branchPrdLib        = kernel::single('o2o_branch_product');
                $rs = $branchPrdLib->changeStoreConfirmFreeze($branch_id,$bm_id,$num,'-');
                if($rs == false){
                    return false;
                }
            }
            elseif($bmsq_id == -1)
            {
                //仓库冻结库存
                $rs    = $this->_libBranchProduct->chg_product_store_freeze($branch_id, $bm_id, $num, '-', $log_type);
                if($rs == false){
                    return false;
                }
            }
        }
        
        $sql = "UPDATE sdb_material_basic_material_stock_freeze SET num=IF((CAST(num AS SIGNED)-$num)>0,num-$num,0), last_modified=". time() ." ".$sql_where;
        if($this->_stockFreezeObj->db->exec($sql)){
            $rs = $this->_stockFreezeObj->db->affect_row();
            if(is_numeric($rs) && $rs > 0){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 订单全部发货后，调用该方法删除订单预占记录
     * 
     * @param Int $order_id 订单ID
     * @return Boolean
     */
    public function delOrderFreeze($order_id){

        if(empty($order_id)){
            return false;
        }

        $filter = array(
            'obj_id' => $order_id,
            'obj_type' => 1,
        );
        return $this->_stockFreezeObj->delete($filter);
    }

    /**
     * 发货单发货后，调用该方法删除仓库预占记录
     * 
     * @param Int $delivery_id 发货单ID
     * @return Boolean
     */
    public function delDeliveryFreeze($delivery_id){

        if(empty($delivery_id)){
            return false;
        }

        $filter = array(
            'obj_id' => $delivery_id,
            'obj_type' => 2,
            'bill_type' => self::__DELIVERY,
        );
        return $this->_stockFreezeObj->delete($filter);
    }

    /**
     * 根据订单号查询是否有该订单的预占
     * 
     * @param Int $order_id 订单ID
     * @return Boolean
     */
    public function hasOrderFreeze($order_id){

        if(empty($order_id)){
            return false;
        }

        $result = $this->_stockFreezeObj->getList('bmsf_id', array('obj_type'=>1,'obj_id'=>$order_id,'num|than'=>0), 0, 1);
        if($result){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 根据店铺ID、基础物料ID获取该物料店铺级的预占
     * 
     * @param Int $bm_id 基础物料ID
     * @param Int $shop_id 店铺ID
     * @return number
     */
    public function getShopFreeze($bm_id, $shop_id){

        if(empty($bm_id) || empty($shop_id)){
            return false;
        }

        $result = $this->_stockFreezeObj->db->selectrow("SELECT sum(num) as total FROM sdb_material_basic_material_stock_freeze WHERE bm_id=".$bm_id." AND obj_type=1 AND shop_id='".$shop_id."'");
        if($result){
            return $result['total'];
        }else{
            return 0;
        }
    }

    /**
     * 根据仓库ID、基础物料ID获取该物料仓库级的预占
     * 
     * @param Int $bm_id 基础物料ID
     * @param Int $branch_id 仓库ID
     * @return number
     */
    public function getBranchFreeze($bm_id, $branch_id){

        if(empty($bm_id) || empty($branch_id)){
            return false;
        }
        
        //冻结库存
        $result = $this->_stockFreezeObj->db->selectrow("SELECT store_freeze FROM sdb_ome_branch_product WHERE branch_id=".$branch_id." AND product_id=". $bm_id);
        
        if($result){
            return intval($result['store_freeze']);
        }else{
            return 0;
        }
    }
    
    /**
     * 根据基础物料ID获取关联仓库的冻结数量之和
     * 
     * @param Int $bm_id 基础物料ID
     * @return number
     */
    public function getBranchProductFreeze($bm_id){
        
        if(empty($bm_id)){
            return false;
        }
        
        $result = $this->_stockFreezeObj->db->selectrow("SELECT sum(store_freeze) AS total FROM sdb_ome_branch_product WHERE product_id=". $bm_id);
        if($result){
            return intval($result['total']);
        }else{
            return 0;
        }
    }
    
    /**
     * 删除仓库预占流水记录(除发货业务之外)
     * 
     * @param Int $obj_id 记录ID
     * @param Int $bill_type 业务类型
     * @return Boolean
     */
    public function delOtherFreeze($obj_id, $bill_type){
        
        if(empty($obj_id) || empty($bill_type)){
            return false;
        }
        
        $filter = array(
                'obj_id' => $obj_id,
                'obj_type' => 2,
                'bill_type' => $bill_type,
        );
        return $this->_stockFreezeObj->delete($filter);
    }
    
    /**
     * 根据基础物料ID获取对应的冻结库存
     * 
     * @param Int $bm_id 基础物料ID
     * @return number
     */
    public function getMaterialStockFreeze($bm_id){
        
        if(empty($bm_id)){
            return false;
        }
        
        //冻结库存
        $result = $this->_stockFreezeObj->db->selectrow("SELECT store_freeze FROM sdb_material_basic_material_stock WHERE bm_id=".$bm_id);
        
        if($result){
            return intval($result['store_freeze']);
        }else{
            return 0;
        }
    }
    
    /**
     * 根据门店仓库ID、基础物料ID获取该物料门店仓库级的预占
     *
     * @param Int $bm_id 基础物料ID
     * @param Int $branch_id 仓库ID
     * @return number
     */
    public function getO2oBranchFreeze($bm_id, $branch_id){
        
        if(empty($bm_id) || empty($branch_id)){
            return false;
        }
        
        //冻结库存
        $result = $this->_stockFreezeObj->db->selectrow("SELECT store_freeze FROM sdb_o2o_product_store WHERE bm_id=".$bm_id." AND branch_id=".$branch_id);
        
        if($result){
            return intval($result['store_freeze']);
        }else{
            return 0;
        }
    }
    
    /*
     * 删除人工库存预占流水记录
     * $obj_ids 记录主键数组
     */
    public function delArtificialFreeze($obj_ids){
        if(empty($obj_ids)){
            return false;
        }
        foreach($obj_ids as $var_obj_id){
            $filter = array(
                    "obj_id" => $var_obj_id,
                    "bill_type" => "7",
            );
            $this->_stockFreezeObj->delete($filter);
        }
    }
    
    /**
     * 根据基础物料bm_id获取该物料店铺级的预占
     *
     * @param Int $bm_id 基础物料ID
     * @return number
     */
    public function getShopFreezeByBmid($bm_id){
        
        if(empty($bm_id)){
            return false;
        }
        
        $result = $this->_stockFreezeObj->db->selectrow("SELECT sum(num) as total FROM sdb_material_basic_material_stock_freeze WHERE bm_id=".$bm_id." AND obj_type=1");
        if($result){
            return intval($result['total']);
        }else{
            return 0;
        }
    }
    
    /**
     * 根据基础物料bm_id获取该物料仓库级的预占
     *
     * @param Int $bm_id 基础物料ID
     * @param Array $branch_ids 仓库
     * @return number
     */
    public function getBranchFreezeByBmid($bm_id, $branch_ids=''){
        
        if(empty($bm_id)){
            return false;
        }
        
        $sql = "SELECT sum(num) as total FROM sdb_material_basic_material_stock_freeze WHERE bm_id=".$bm_id." AND obj_type=2";
        
        //仓库条件
        if($branch_ids && is_array($branch_ids)){
            $sql .= " AND branch_id IN(". implode(',', $branch_ids) .")";
        }
        
        //仓库冻结总数
        $result = $this->_stockFreezeObj->db->selectrow($sql);
        
        return intval($result['total']);
    }
    
    //根据基础物料bm_id获取在途库存
    public function getMaterialArriveStore($bm_id){
        if(empty($bm_id)){
            return false;
        }
        $sql = "SELECT SUM(arrive_store) AS 'total' FROM ".DB_PREFIX."ome_branch_product WHERE product_id=".$bm_id;
        $count = kernel::database()->selectrow($sql);
        if($count["total"]){
            return $count["total"];
        }else{
            return 0;
        }
    }
    
    //根据基础物料bm_id获取良品库存(除去残损仓)
    public function getMaterialGoodStore($bm_id){
        if(empty($bm_id)){
            return false;
        }
        $filter_str = "product_id=".$bm_id;
        $mdl_ome_branch = app::get('ome')->model('branch');
        $branchList = $mdl_ome_branch->db->select('SELECT branch_id FROM sdb_ome_branch WHERE type=\'damaged\'');
        if(!empty($branchList)){
            $damaged_branch_ids = array();
            foreach($branchList as $var_branch){
                $damaged_branch_ids[] = $var_branch["branch_id"];
            }
            $filter_str.= " and branch_id not in(".implode(",", $damaged_branch_ids).")";
        }
        $sql = "SELECT SUM(store) AS 'total' FROM ".DB_PREFIX."ome_branch_product WHERE ".$filter_str;
        $count = kernel::database()->selectrow($sql);
        if($count["total"]){
            return $count["total"];
        }else{
            return 0;
        }
    }
    
}
