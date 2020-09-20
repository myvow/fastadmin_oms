<?php
/**
 * 订单明细数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;
use app\product\library\Common as ProductCommon;

class Items extends Abstracts
{
    private $_obj_alias = array(
                'goods' => '商品',
                'pkg' => '捆绑商品',
                'gift' => '赠品',
                'giftpackage' => '礼包',
                'lkb' => '福袋',
                'pko' => '多选一',
            );

    /**
     * 数据格式转换
     *
     * @return bool
     **/
    public function convert($ordersdf)
    {   
        $is_fail_order = false;
        $shop_id = $ordersdf['shop_id'];
        
        foreach ($ordersdf['order_objects'] as $object)
        {
            $order_items = $this->_format_items($object);
            if(!$order_items){
                $is_fail_order = true;
            }
            
            $this->_newOrder['order_objects'][] = array(
                'obj_type'      => $obj_type ? $obj_type : 'goods',
                'obj_alias'     => $object['obj_alias'] ? $object['obj_alias'] : $this->_obj_alias[$obj_type],
                'shop_goods_id' => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                'goods_id'      => $salesMInfo['sm_id'] ? $salesMInfo['sm_id'] : 0,
                'bn'            => $object['bn'] ? $object['bn'] : null,
                'name'          => $object['name'],
                'price'         => $object['price'] ? (float)$object['price'] : bcdiv($obj_amount,$object['quantity'],3),
                'amount'        => $obj_amount,
                'quantity'      => $object['quantity'],
                'weight'        => (float)$object['weight'],
                'score'         => (float)$object['score'],
                'pmt_price'     => (float)$object['pmt_price'],
                'sale_price'    => $obj_sale_price,
                'order_items'   => $order_items,
                'is_oversold'   => ($object['is_oversold'] == true) ? 1 : 0,
                'oid'           => $object['oid'],
                
                //flag可能从item上移到obj层上要保留的数据
                'delete'            => ($object['status'] == 'close') ? 'true' : 'false',
                'original_str'      => $object['original_str'],
                'product_attr'      => $object['product_attr'],
                'promotion_id'      => $object['promotion_id'],
                'divide_order_fee'  => $object['divide_order_fee'],
                'part_mjz_discount' => $object['part_mjz_discount'],
            );
            
            unset($order_items);
        }
        
        //设置为失败订单
        if($is_fail_order)
        {
            $this->_newOrder['is_fail']     = 'true';
            $this->_newOrder['edit_status'] = 'true';
            $this->_newOrder['archive']     = '1';
        }
        
        return true;
    }
    
    /**
     * 组织order_item订单明细数据
     */
    public function _format_items($object)
    {
        $productLib = new ProductCommon;
        
        $quantity = $object['quantity'] ? $object['quantity'] : 1;
        $obj_amount = $object['amount'] ? $object['amount'] : bcmul($quantity, $object['price'], 3);
        
        //订单明细上商品的销售总价
        if(isset($object['sale_price']) && is_numeric($object['sale_price']) && bccomp($object['sale_price'], 0, 3) != -1){
            $obj_sale_price = $object['sale_price'];
        }else{
            $obj_sale_price = bcsub($obj_amount,$object['pmt_price'],3);
        }
        
        //检查商品是否存在
        $salesMInfo = $productLib->getSalesMByBn($shop_id, $object['bn']);
        if(empty($salesMInfo)){
            return false;
        }
        
        //根据商品获取关联的产品列表
        $basicMInfos = $productLib->getBasicMBySalesMId($salesMInfo['sm_id']);
        if(empty($basicMInfos)){
            return false;
        }
        
        switch($salesMInfo['sales_material_type'])
        {
            case '2':
                //pkg捆绑商品,分摊产品金额
                $basicMInfos = $productLib->calProSaleMPriceByRate($obj_sale_price, $basicMInfos);
                $pmt_price_rate = $productLib->calpmtpriceByRate($object['pmt_price'], $basicMInfos);
                
                $obj_type = 'pkg'; break;
            case '3':
                $obj_type = 'gift'; break;
            default:
                $obj_type = 'goods';
        }
        
        //组织订单order_item明细数据
        $order_items = array();
        foreach($basicMInfos as $k => $basicMInfo)
        {
            if($obj_type == 'pkg')
            {
                $item_type = 'pkg';
                
                $cost = $basicMInfo['cost'];
                $pmt_price = $pmt_price_rate[$basicMInfo['material_bn']] ? $pmt_price_rate[$basicMInfo['material_bn']]['rate_price'] : 0.00;
                $sale_price = $basicMInfo['rate_price'];
                $amount = bcadd($pmt_price, $sale_price, 2);
                $price = bcdiv($amount, $basicMInfo['number'] * $object['quantity'], 2);
                
                $weight = $basicMInfo['weight'];
                $shop_product_id = 0;
                $divide_order_fee = 0;
                $part_mjz_discount = 0;
                
                $quantity = $basicMInfo['number'] * $object['quantity'];
            }
            else if($obj_type == 'gift')
            {
                $item_type ='gift';
                
                //如果是赠品重置相关的金额字段
                $cost = 0.00;
                $price = 0.00;
                $pmt_price = 0.00;
                $sale_price = 0.00;
                $amount = 0.00;
                $obj_amount = 0.00;
                $obj_sale_price = 0.00;
                
                $shop_product_id = $object['shop_product_id'] ? $object['shop_product_id'] : 0;
                $weight = (float)$object['weight'] ? $object['weight'] : ($basicMInfo['weight'] ? $basicMInfo['weight'] : 0.00);
                $divide_order_fee = 0;
                $part_mjz_discount = 0;
                $quantity = $basicMInfo['number'] * $object['quantity'];
            }
            else
            {
                $item_type = 'product';
                
                if(isset($object['sale_price']) && is_numeric($object['sale_price']) && bccomp($object['sale_price'], 0, 3) != -1){
                    $sale_price = $object['sale_price'];
                }else{
                    $sale_price = bcsub($obj_amount, (float)$object['pmt_price'],3);
                }
                
                $cost = (float)$object['cost'] ? $object['cost'] : $basicMInfo['cost'];
                $price = (float)$object['price'];
                $pmt_price = (float)$object['pmt_price'];
                $amount = $obj_amount;
                
                $weight = (float)$object['weight'] ? $object['weight'] : ($basicMInfo['weight'] ? $basicMInfo['weight'] : 0.00);
                $shop_product_id = $object['shop_product_id'] ? $object['shop_product_id'] : 0;
                
                $divide_order_fee = $object['divide_order_fee'];
                $part_mjz_discount = $object['part_mjz_discount'];
                $quantity = $basicMInfo['number']*$object['quantity'];
            }
            
            $order_items[] = array(
                    'item_type'         => $item_type,
                    'shop_goods_id'     => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                    'product_id'        => $basicMInfo['bm_id'] ? $basicMInfo['bm_id'] : 0,
                    'shop_product_id'   => $shop_product_id,
                    'bn'                => $basicMInfo['material_bn'],
                    'name'              => $basicMInfo['material_name'],
                    'cost'              => $cost ? $cost : 0.00,
                    'price'             => $price ? $price : 0.00,
                    'pmt_price'         => $pmt_price,
                    'sale_price'        => $sale_price ? $sale_price : 0.00,
                    'amount'            => $amount ? $amount : 0.00,
                    'weight'            => $weight ? $weight : 0.00,
                    'quantity'          => $quantity,
                    'addon'             => '',
                    'delete'            => ($object['status'] == 'close') ? 'true' : 'false',
                    'divide_order_fee'  => $divide_order_fee,
                    'part_mjz_discount' => $part_mjz_discount,
            );
        }
        
        return $order_items;
    }
    
    
    
    
    /**
     * 更新订单明细
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        // 后期修改
        if ($this->_platform->_tgOrder['ship_status'] == '0') {
        
            // 原单处理
            $tgOrder_object = array();
            foreach ((array)$this->_platform->_tgOrder['order_objects'] as $object) {
                $objkey = $this->_get_obj_key($object);

                $tgOrder_object[$objkey] = $object;

                $order_items = array();
                foreach ((array)$object['order_items'] as $item) {
                    $itemkey = $this->_get_item_key($item);

                    $order_items[$itemkey] = $item;
                }
                $tgOrder_object[$objkey]['order_items'] = $order_items;
            }

            $ordersdf = $ordersdf;
              
           //组织天下掉下来的新数据
            $sky_ordersdf_is_fail_order = false;
            $salesMLib = kernel::single('material_sales_material');
            $basicMStockLib = kernel::single('material_basic_material_stock');
            $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
            
            // 接收的参数
            $ordersdf_object = array();
            foreach ((array)$ordersdf['order_objects'] as $object) {
                
                //obj基础数据格式化
                
                $obj_amount = $object['amount'] ? $object['amount'] : bcmul($object['quantity'], $object['price'],3);
                $obj_sale_price = (isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3) ) ? $object['sale_price'] :  bcsub($obj_amount,$object['pmt_price'],3);
                $obj_type = $object['obj_type'] ? $object['obj_type'] : 'goods';

                $goods = array();$order_items = array();

                $salesMInfo = $salesMLib->getSalesMByBn($ordersdf['shop_id'],$object['bn']);
                if(!$salesMInfo){
                    $sky_ordersdf_is_fail_order = true;
                }
                if($salesMInfo){
                    if($salesMInfo['sales_material_type'] == 4){ //福袋
                        $basicMInfos = $salesMLib->get_order_luckybag_bminfo($salesMInfo['sm_id']);
                    }elseif($salesMInfo['sales_material_type'] == 5){ //多选一
                        $basicMInfos = $salesMLib->get_order_pickone_bminfo($salesMInfo['sm_id'],$object['quantity'],$ordersdf['shop_id']);
                    }else{
                        $basicMInfos = $salesMLib->getBasicMBySalesMId($salesMInfo['sm_id']);
                    }
                    if(!$basicMInfos){
                        $sky_ordersdf_is_fail_order = true;
                    }
                    if($basicMInfos){
                        switch($salesMInfo['sales_material_type']){
                            case "2":
                                $salesMLib->calProSaleMPriceByRate($obj_sale_price, $basicMInfos);
                                $price_rate = $salesMLib->calProPriceByRate($object['price'], $basicMInfos);
                                $pmt_price_rate = $salesMLib->calpmtpriceByRate($object['pmt_price'], $basicMInfos);
                                $obj_type = 'pkg'; break;
                            case "3":
                                $obj_type = 'gift'; break;
                            case "4":
                                $obj_type = 'lkb'; break;
                            case "5":
                                $obj_type = 'pko'; break;
                            default:
                                $obj_type = 'goods'; break;
                        }
                        
                        //组织item数据
                        foreach($basicMInfos as $k => $basicMInfo){
                            $cost       = (float)$object['cost'] ? $object['cost'] : $basicMInfo['cost'];
                            $price      = (float)$object['price'];
                            $pmt_price  = (float)$object['pmt_price'];
                            $sale_price = $obj_sale_price;
                            $amount     = $obj_amount;
                            $weight     = (float)$object['weight'] ? $object['weight'] : ($basicMInfo['weight'] ? $basicMInfo['weight'] : 0.00);
                            $quantity   = $basicMInfo['number']*$object['quantity'];
                            $shop_product_id = $object['shop_product_id'];
                            $divide_order_fee = $object['divide_order_fee'];
                            $part_mjz_discount = $object['part_mjz_discount'];
                            $item_type  = 'product';
                            if($obj_type == 'pkg'){
                                $cost            = $basicMInfo['cost'];
                                //$price           = $basicMInfo['rate_price'] ? bcdiv($basicMInfo['rate_price'], $quantity, 2) : 0.00;
                                $pmt_price  = $pmt_price_rate[$basicMInfo['material_bn']] ? $pmt_price_rate[$basicMInfo['material_bn']]['rate_price'] : 0.00;

                                $sale_price      = $basicMInfo['rate_price'];

                                $amount     = bcadd($pmt_price, $sale_price,2);
                                
                                $price = bcdiv($amount, $basicMInfo['number']*$object['quantity'], 2);
                                $weight          = $basicMInfo['weight'];
                                $divide_order_fee = 0;
                                $part_mjz_discount = 0;
                                $shop_product_id = 0;

                                $item_type  = 'pkg';
                            }else if($obj_type=='gift'){
                                //如果是赠品重置相关的金额字段
                                $cost = 0.00;
                                $price = 0.00;
                                $pmt_price = 0.00;
                                $sale_price = 0.00;
                                $amount =0.00;
                                $obj_amount = 0.00;
                                $obj_sale_price = 0.00;
                                $item_type  ='gift';
                                $divide_order_fee = 0;
                                $part_mjz_discount = 0;
                            }elseif($obj_type=='lkb'){
                                $cost = $basicMInfo['cost'];
                                $price = $basicMInfo['price'];
                                $pmt_price = 0;
                                $sale_price = $basicMInfo['price']*$quantity;
                                $amount = $basicMInfo['price']*$quantity;
                                $weight = $basicMInfo['weight']*$quantity;
                                $divide_order_fee = 0;
                                $part_mjz_discount = 0;
                                $shop_product_id = 0;
                                $item_type  = 'lkb';
                                $lbr_id = $basicMInfo["lbr_id"];
                            }elseif($obj_type == 'pko'){
                                $pmt_price = 0;
                                $sale_price = bcmul($obj_sale_price/$object['quantity'], $basicMInfo['number'], 3);
                                $amount = $sale_price;
                                $weight = $basicMInfo['weight'] ? $basicMInfo['weight']*$basicMInfo['number'] : 0.00;
                                $shop_product_id = 0;
                                $divide_order_fee = 0;
                                $part_mjz_discount = 0;
                                $item_type = 'pko';
                                $quantity = $basicMInfo['number'];
                            }
                            $itemtmp = array(
                                'shop_goods_id'     => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                                'product_id'        => $basicMInfo['bm_id'] ? $basicMInfo['bm_id'] : 0,
                                'shop_product_id'   => $shop_product_id,
                                'bn'                => $basicMInfo['material_bn'],
                                'name'              => $basicMInfo['material_name'],
                                'cost'              => $cost,
                                'price'             => $price,
                                'pmt_price'         => $pmt_price,
                                'sale_price'        => $sale_price,
                                'amount'            => $amount,
                                'weight'            => $weight,
                                'quantity'          => $quantity,
                                'addon'             => '',
                                'item_type'         => $item_type,
                                'delete'            => ($object['status'] == 'close') ? 'true' : 'false',
                                'order_id'          => $this->_platform->_tgOrder['order_id'],
                                'divide_order_fee'  =>  $divide_order_fee,
                                'part_mjz_discount' =>  $part_mjz_discount,
                                'lbr_id' => $lbr_id ? $lbr_id : "",
                            );
                            $itemkey = $this->_get_item_key($itemtmp);
                            $order_items[$itemkey] = $itemtmp;
                        }
                    }
                }


                $objecttmp = array(
                    'obj_type'      => $obj_type,
                    'obj_alias'     => $object['obj_alias'] ? $object['obj_alias'] : $this->_obj_alias[$obj_type],
                    'shop_goods_id' => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                    'goods_id'      => $salesMInfo['sm_id'] ? $salesMInfo['sm_id'] : 0,
                    'bn'            => $object['bn'] ? $object['bn'] : null,
                    'name'          => $object['name'],
                    'price'         => $object['price'] ? (float)$object['price'] : bcdiv($obj_amount,$object['quantity'],3),
                    'amount'        => $obj_amount,
                    'quantity'      => $object['quantity'],
                    'weight'        => (float)$object['weight'],
                    'score'         => (float)$object['score'],
                    'pmt_price'     => (float)$object['pmt_price'],
                    'sale_price'    => (float)$obj_sale_price,
                    'order_items'   => $order_items,
                    'is_oversold'   => ($object['is_oversold'] == true) ? 1 : 0,
                    'oid'           => $object['oid'],
                    'order_id'      => $this->_platform->_tgOrder['order_id'],
                    'divide_order_fee' => $object['divide_order_fee'],
                    'part_mjz_discount'=> $object['part_mjz_discount'],
                );
                unset($order_items);
                $objkey = $this->_get_obj_key($objecttmp);
                $ordersdf_object[$objkey] = $objecttmp;
            }

            // 判断ITEM有没有
            $need_del_info = array();
            foreach ($tgOrder_object as $objkey => $object) {
                $has_old_item_del = false;
                foreach ($object['order_items'] as $itemkey=>$item) {
                    // 如果已经被删除，则跳过
                    if($item['delete'] == 'true') continue;

                    // ITEM被删除
                    if (!$ordersdf_object[$objkey]['order_items'][$itemkey]) {
                        $this->_newOrder['order_objects'][$objkey]['obj_id'] = $object['obj_id'];
                        $this->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array('item_id'=>$item['item_id'],'delete'=>'true');
                        
                        //后续操作删除数据用
                        if($item["item_type"] == "pko" || $item["item_type"] == "lkb"){
                            if(!isset($need_del_info[$object['obj_id']])){
                                $need_del_info[$object['obj_id']] = array(
                                    "del_objkey" => $objkey,
                                );
                            }
                            $need_del_info[$object['obj_id']]["items"][] = array(
                                "item_id" => $item["item_id"],
                                "itemkey" => $itemkey,
                                "item_log_text" => "基础物料ID：".$item["product_id"]."；编码：".$item["bn"]."；单价：".$item["price"]."；数量：".$item["quantity"]."；类型：".$item["item_type"]."。",
                            );
                        }
                        
                        // 扣库存
                        if ($item['product_id']) {
                            $basicMStockLib->unfreeze($item['product_id'],$item['quantity']);
                            
                            //[扣减]基础物料店铺冻结
                            $basicMStockFreezeLib->unfreeze($item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $this->_platform->_tgOrder['order_id'], '', material_basic_material_stock_freeze::__SHARE_STORE, $item['quantity']);
                            
                            $has_old_item_del = true;
                            
                        }
                    }
                }
            }

            // 字段比较
            foreach ($ordersdf_object as $objkey => $object) {
                $obj_id = $tgOrder_object[$objkey]['obj_id'];
                $order_items = $object['order_items']; unset($object['order_items']);

                $object = array_filter($object,array($this,'filter_null'));
                // OBJECT比较
                $diff_obj = array_udiff_assoc((array)$object, (array)$tgOrder_object[$objkey],array($this,'comp_array_value'));
               
                if ($diff_obj) {
                    $diff_obj['obj_id'] = $obj_id;

                    $this->_newOrder['order_objects'][$objkey] = array_merge((array)$this->_newOrder['order_objects'][$objkey],(array)$diff_obj);
                    
                }

                foreach ($order_items as $itemkey => $item) {
                    $item = array_filter($item,array($this,'filter_null'));
                    // ITEM比较
                    $item_id = $tgOrder_object[$objkey]['order_items'][$itemkey]['item_id'];
                    $diff_item = array_udiff_assoc((array)$item, (array)$tgOrder_object[$objkey]['order_items'][$itemkey],array($this,'comp_array_value'));
                   
                    if ($diff_item) {
                        $diff_item['item_id'] = $item_id;

                        $this->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array_merge((array)$this->_newOrder['order_objects'][$objkey]['order_items'][$itemkey],(array)$diff_item);


                        if ($diff_item['delete'] == 'false' && $item['product_id']) {

                            $basicMStockLib->freeze($item['product_id'], $item['quantity']);
                            
                            //[增加]基础物料店铺冻结
                            $basicMStockFreezeLib->freeze($item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $this->_platform->_tgOrder['order_id'], $this->_platform->_tgOrder['shop_id'], 0, material_basic_material_stock_freeze::__SHARE_STORE, $item['quantity']);
                            
                        } elseif ($diff_item['delete'] == 'true' && $item['product_id']) {

                            $basicMStockLib->unfreeze($item['product_id'], $tgOrder_object[$objkey]['order_items'][$itemkey]['quantity']);
                            
                            //[扣减]基础物料店铺冻结
                            $basicMStockFreezeLib->unfreeze($item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $this->_platform->_tgOrder['order_id'], '', material_basic_material_stock_freeze::__SHARE_STORE, $tgOrder_object[$objkey]['order_items'][$itemkey]['quantity']);
                            
                        } elseif (isset($diff_item['quantity']) && $item['product_id']) {
                            // 如果库存发生变化，
                            $diff_quantity = bcsub($diff_item['quantity'], $tgOrder_object[$objkey]['order_items'][$itemkey]['quantity']);
                            
                            if($diff_quantity > 0){
                                $basicMStockLib->freeze($item['product_id'], abs($diff_quantity));
                                
                                //[增加]基础物料店铺冻结
                                $basicMStockFreezeLib->freeze($item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $this->_platform->_tgOrder['order_id'], $this->_platform->_tgOrder['shop_id'], 0, material_basic_material_stock_freeze::__SHARE_STORE, abs($diff_quantity));
                                
                            }elseif($diff_quantity < 0){
                                $basicMStockLib->unfreeze($item['product_id'], abs($diff_quantity));
                                
                                //[扣减]基础物料店铺冻结
                                $basicMStockFreezeLib->unfreeze($item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $this->_platform->_tgOrder['order_id'], '', material_basic_material_stock_freeze::__SHARE_STORE, abs($diff_quantity));
                                
                            }
                            
                        }
                    
                        $this->_newOrder['order_objects'][$objkey]['obj_id'] = $obj_id;
                    }

                    
                }
            }
         
            if($sky_ordersdf_is_fail_order){
                $this->_newOrder['is_fail']     = 'true';
                $this->_newOrder['edit_status'] = 'true';
                $this->_newOrder['archive']     = '1';  
            }
            if ($this->_newOrder['is_fail'] != 'true' && $this->_platform->_tgOrder['is_fail'] == 'true') {
                $this->_newOrder['is_fail']     = 'false';
                $this->_newOrder['edit_status'] = 'false';
                $this->_newOrder['archive']     = '0';        
            }
            
            if(!empty($need_del_info)){ //写删除日志并删除明细数据
                $mdl_ome_order_items = app::get('ome')->model('order_items');
                $write_log = array();
                foreach($need_del_info as $obj_id => $obj_info){
                    $current_memo = "";
                    foreach($obj_info["items"] as $var_item){
                        unset($this->_newOrder["order_objects"][$obj_info["del_objkey"]]["order_items"][$var_item["itemkey"]]);
                        $current_memo .= $var_item["item_log_text"];
                        $mdl_ome_order_items->delete(array("item_id"=>$var_item["item_id"]));
                    }
                    $write_log[] = array(
                        'obj_id'    => $this->_platform->_tgOrder['order_id'],
                        'obj_name'  => $this->_platform->_tgOrder['order_bn'],
                        'operation' => 'order_modify@ome',
                        'memo'      => $current_memo,
                    );
                }
                if(!empty($write_log)){
                    app::get('ome')->model('operation_log')->batch_write_log2($write_log);
                }
            }
            
        }
    }

    private function _get_obj_key($object)
    {
        $objkey = '';
        foreach (explode('-', $this->_platform->object_comp_key) as $field) {
            $objkey .= ($object[$field] ? trim($object[$field]) : '').'-';
        }
        
        return sprintf('%u',crc32(ltrim($objkey,'-')));
    }

    private function _get_item_key($item)
    {
        $itemkey = '';
        foreach (explode('-',$this->_platform->item_comp_key) as $field) {
            if ($field == 'unit_sale_price') {
                $itemkey .= bcdiv((float)$item['sale_price'], $item['quantity'],3).'-';
            } else {
                $itemkey .= ($item[$field] ? $item[$field] : '').'-'; 
            }
        }
        
        return sprintf('%u',crc32(ltrim($itemkey,'-')));
    }
}