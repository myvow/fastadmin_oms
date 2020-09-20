<?php
$db['order_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'obj_id' => 
    array (
      'type' => 'table:order_objects@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'shop_goods_id' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'shop_product_id' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'required' => true,
      'default' => 0,
    ),    
    'bn' => 
    array (
      'type' => 'varchar(40)',
      'editable' => false,
      'is_title' => true,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'cost' => 
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'price' => 
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'pmt_price' => 
    array (
      'type' => 'money',
      'default' => '0',
    'editable' => false,
    ),
    'sale_price' => 
    array (
      'type' => 'money',
      'default' => '0',
        'editable' => false,
    ),
    'amount' => 
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'weight' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'nums' => 
    array (
      'type' => 'number',
      'default' => 1,
      'required' => true,
      'editable' => false,
      'sdfpath' => 'quantity',
    ),
    'sendnum' => 
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'addon' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'item_type' => 
    array (
      'type' => 'varchar(50)',
      'default' => 'product',
      'required' => true,
      'editable' => false,
    ),
    'return_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'editable' => false,
      'label' => '已退货量',
    ),
  'divide_order_fee' =>
      array (
          'type' => 'money',
          'editable' => false,
          'label' => '分摊之后的实付金额',
      ),
  'part_mjz_discount' =>
      array (
          'type' => 'money',
          'editable' => false,
          'label' => '优惠分摊',
      ),
    'delete' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
    ),

  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);