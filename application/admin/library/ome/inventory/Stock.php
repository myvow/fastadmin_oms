<?php
/**
 * 货品库存冻结Lib类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome\inventory;

use think\Db;

class Stock
{
     function __construct()
     {
        $this->_basicMaterialStockObj = Db::name('product_basic_material_stock');
     }
     
    /**
     *
     * 增加基础物料冻结数
     * 
     * @param Int $bm_id
     * @param Int $num
     * @return Boolean
     */
    public function freeze($bm_id, $num)
    {
        $dateline   = time();
        $storeFreeze = "store_freeze=IFNULL(store_freeze,0)+".$num;

        $sql = 'UPDATE sdb_material_basic_material_stock SET '.$storeFreeze.', last_modified='. $dateline .',max_store_lastmodify='. $dateline .' 
                WHERE bm_id='.$bm_id;
        if($this->_basicMaterialStockObj->db->exec($sql)){
            $rs = $this->_basicMaterialStockObj->db->affect_row();
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
     * 释放基础物料冻结数
     * 
     * @param Int $bm_id
     * @param int $num
     * @return Boolean
     */
    public function unfreeze($bm_id, $num)
    {
        $dateline   = time();
        $storeFreeze = " store_freeze=IF((CAST(store_freeze AS SIGNED)-$num)>0,store_freeze-$num,0)";

        $sql = 'UPDATE sdb_material_basic_material_stock SET '.$storeFreeze.', last_modified='. $dateline .',max_store_lastmodify='. $dateline .' 
                WHERE bm_id='.$bm_id;
        if($this->_basicMaterialStockObj->db->exec($sql)){
            $rs = $this->_basicMaterialStockObj->db->affect_row();
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
     * 更新基础物料[库存]
     * 
     * @param intval $product_id
     * @param intval $num
     * @param string $operator
     * @param type $log_type
     * @return Boolean
     */
    function change_store($bm_id, $num, $operator='=')
    {
        $dateline   = time();
        $store      = '';
        switch($operator)
        {
            case '+':
                $store    = "store=IFNULL(store, 0)+". $num . ',';
                break;
            case '-':
                $store    = " store=IF((CAST(store AS SIGNED)-". $num .")>0, store-". $num .",0), ";
                break;
            case '=':
            default:
                $store    = "store=".$num . ',';
                break;
        }
        
        $sql    = "UPDATE ". DB_PREFIX ."material_basic_material_stock SET ". $store .'last_modified='. $dateline .',max_store_lastmodify='. $dateline .' 
                   WHERE bm_id='.$bm_id;
        if($this->_basicMaterialStockObj->db->exec($sql)){
            $rs = $this->_basicMaterialStockObj->db->affect_row();
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
     *
     * 修改基础物料[冻结库存]
     * @param intval $product_id
     * @param intval $num
     * @param string $operator
     * @param type   $log_type
     * @return Boolean
     */
    function chg_product_store_freeze($bm_id, $num, $operator='=', $log_type='order')
    {
        $basicMaterialObj        = app::get('material')->model('basic_material');
        
        $dateline    = time();
        $store_freeze = '';
        $mark_no = uniqid();
        
        switch($operator)
        {
            case "+":
                $store_freeze = "store_freeze=IFNULL(store_freeze,0)+". $num .",";
                $action = '增加';
                break;
            case "-":
                $store_freeze = "store_freeze=IF((CAST(store_freeze AS SIGNED)-". $num .")>0,store_freeze-". $num .",0),";
                $action = '扣减';
                break;
            case "=":
            default:
                $store_freeze = "store_freeze=". $num .",";
                $action = '覆盖';
                break;
        }
        
        #修改库存
        $sql    = 'UPDATE '. DB_PREFIX .'material_basic_material_stock SET '.$store_freeze.'last_modified='.$dateline.',max_store_lastmodify='.$dateline.' 
                   WHERE bm_id='.$bm_id;
        if($this->_basicMaterialStockObj->db->exec($sql)){
            $rs = $this->_basicMaterialStockObj->db->affect_row();
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
     * 初始化基础物料[库存数量]
     */
    public function initNullStore($bm_id)
    {
        $dateline    = time();
        if($bm_id)
        {
            $sql = "UPDATE ". DB_PREFIX ."material_basic_material_stock SET store=0, last_modified='.$dateline.',max_store_lastmodify='.$dateline.' 
                    WHERE bm_id=" . $bm_id ." AND ISNULL(store) LIMIT 1";
            
            return $this->_basicMaterialStockObj->db->exec($sql);
        }
        else
        {
            return false;
        }
    }
}