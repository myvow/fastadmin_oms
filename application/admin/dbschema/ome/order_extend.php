<?php
$db['order_extend']=array(
  'columns' => array(
        'order_id' => array(
            'type'     => 'table:orders@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'pkey' => true,
            'comment'  => '订单号',
        ),
        'receivable' =>
        array (
            'type' => 'money',
            'default' => '0',
            'label' => '应收费用',
            'editable' => false,
        ),
        'sellermemberid' => array(
            'type' => 'varchar(255)',
            'label' => '卖家会员登录名',
        ),
        'extend_status' =>
          array (
                  'type' => 'varchar(30)',
                  'default' => '0',
                  'comment' => '订单扩展状态(比如收货人信息发生变更)',
                  'editable' => false
          ),
          'bool_extendstatus' => array (
            'type'      => 'bigint(20)',
            'default'   => '0',
            'comment'   => '订单扩展是与否二进制状态',
            'editable'  => false
        ),
          'presale_auto_paid'=>array (
              'type' => 'money',
              'default' => '0',
              'label' => '预售补全尾款金额',

              'editable' => false,
          ),
          'presale_pay_status'=>array (
              'type' => 'tinyint unsigned',

              'default' => 0,
              'editable' => false,
          ),
        'store_dly_type' => array(
            'type' => 'tinyint(1)',
            'default' => '0',
            'comment' => '门店发货模式',
            'editable' => false,
        ),
        'store_bn' => array(
            'type' => 'varchar(20)',
            'comment' => '门店编码',
            'editable' => false,
        ),
        'store_process_status' => array(
            'type' => 'tinyint(1)',
            'default' => '0',
            'comment' => '门店处理状态',
            'editable' => false,
        ),
	'orig_reship_id' =>
        array (
          'type' => 'int',
          'default'=>0,
          'editable' => false,
          'label' => '原始换货单号ID',
        ),
        'push_time' => array(
            'type'      => 'time',
            'label'     => '推单时间',
            'default'   => '0',
        ),
        'assign_express_code' =>
        array(
            'type' => 'varchar(20)',
            'editable' => false,
            'label' => '指定快递编码',
            'width' => 100,
            'in_list' => false,
            'default_in_list' => true,
        ),
        'platform_logi_no' => array(
            'type' => 'varchar(255)',
            'label' => '平台运单号',
        ),
        'o2o_store_bn'=>array(
          'type' => 'varchar(20)',
            'comment' => '店铺门店编码',
            'editable' => false,
        ),
        'o2o_store_name'=>array(
          'type' => 'varchar(20)',
            'comment' => '店铺门店名称',
            'editable' => false,
        ),
  ),
  'engine'  => 'innodb',
  'version' => '$Rev: 40912 $',
  'comment' => '订单扩展表',
);