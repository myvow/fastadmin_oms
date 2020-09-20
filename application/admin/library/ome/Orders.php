<?php
/**
 * 订单数据公共类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome;

use think\Db;
use think\Exception;

use app\admin\model\ome\Orders as OrderObj;
use app\admin\library\ome\Common;

class Orders
{
    /**
     * 支付方式获取
     * 
     * @param String $pay_bn 支付方式编号
     * @param String $shop_type 店铺类型
     * @return array
     */
    final public function get_payment($pay_bn, $shop_type='')
    {
        $paymentInfo = Db::name('ome_payment_cfg')->where('pay_bn', $pay_bn)->find();
        if($paymentInfo){
            return $paymentInfo;
        }
        
        switch ($pay_bn)
        {
            case 'deposit':
                $paymentInfo = array(
                    'custom_name' => '预存款',
                    'pay_bn'      => 'deposit',
                    'pay_type'    => 'deposit',
                );
                break;
            default:
                $paymentInfo = array(
                    'custom_name' => '线上支付',
                    'pay_bn'      => 'online',
                    'pay_type'    => 'online',
                );
                break;
        }
        
        //支付方式不存在则保存
        Db::name('ome_payment_cfg')->save($paymentInfo);
        
        return $paymentInfo;
    }
    
    /**
     * 创建订单
     * 
     * @param array $sdf
     * @throws \Exception
     * @return boolean
     */
    function create_order(&$sdf)
    {
        $commonLib = new Common;
        $StockLib = new \app\admin\library\ome\inventory\Stock;
        $FreezeLib = new \app\admin\library\ome\inventory\Freeze;
        $operLogLib = new \app\admin\library\ome\log\Operation;
        
        $orderObj = new OrderObj;
        $shopObj = new \app\ome\model\Shop;
        
        //订单已存在则返回false
        $filter = array('order_bn'=>$sdf['order_bn'], 'shop_id'=>$sdf['shop_id']);
        $orderInfo = $orderObj->getRow('order_id', $filter);
        if($orderInfo){
            return false;
        }
        
        //开启事务
        //@todo：防止订单创建失败但是冻结却预占的问题
        Db::startTrans();
        
        try
        {
            //收货人/发货人地区转换
            $area = $sdf['consignee']['area'];
            $commonLib->region_validate($area);
            
            $sdf['consignee']['area'] = $area;
            
            $consigner_area = $sdf['consigner']['area'];
            $commonLib->region_validate($consigner_area);
            
            $sdf['consigner']['area'] = $consigner_area;
            
            //格式化订单明细
            foreach($sdf['order_objects'] as $key => $object)
            {
                $object['bn'] = trim($object['bn']);
                $item['delete'] = ($item['delete'] == 'true' ? 'true' : 'false');
                
                //item明细不存在
                if(empty($object['order_items']))
                {
                    $sdf['order_objects'][$key] = $object;
                    continue;
                }
                
                //item明细
                foreach($object['order_items'] as $k => $item)
                {
                    $item['bn'] = trim($item['bn']);
                    
                    //序列化货品的属性
                    $product_attr = $commonLib->_format_productattr($item['product_attr'], $item['product_id'],$item['original_str']);
                    $item['addon'] = $product_attr;
                    
                    //添加冻结
                    $num = intval($item['quantity']) - intval($item['sendnum']);
                    if($item['product_id'] && $item['delete']=='false' && $num > 0)
                    {
                        //修改预占库存
                        $res = $StockLib->freeze($item['product_id'], $num);
                        if(!$res){
                            throw new \Exception('插入订单冻结失败');
                        }
                        
                        //保存冻结明细日志
                        $freeze_type = $FreezeLib::__ORDER;
                        $bmsq_id = $FreezeLib::__SHARE_STORE;
                        $res = $FreezeLib->freeze($item['product_id'], $freeze_type, 0, $sdf['order_id'], $sdf['shop_id'], 0, $bmsq_id, $num);
                        if(!$res){
                            throw new \Exception('插入订单冻结日志失败');
                        }
                    }
                    
                    $object['order_items'][$k] = $item;
                }
                
                $sdf['order_objects'][$key] = $object;
            }
            
            //订单可合并标记生成hash值
            $combieHashIdxInfo = $this->genOrderCombieHashIdx($sdf);
            if($combieHashIdxInfo){
                $sdf['order_combine_hash'] = $combieHashIdxInfo['combine_hash'];
                $sdf['order_combine_idx'] = $combieHashIdxInfo['combine_idx'];
            }
            
            //根据店铺取运营组织
            $filter = array('shop_id'=>$sdf['shop_id']);
            $shopInfo = $shopObj->getRow('*', $filter);
            
            $sdf['org_id'] = $shopInfo['org_id'];
            
            //插入订单
            $res = $orderObj->insert($sdf);
            if(!$res)
            {
                throw new \Exception('插入订单失败');
            }
            
            //提交事务
            Db::commit();
        }
        catch (\Exception $e)
        {
            //回滚事务
            Db::rollback();
        }
        
        //记录操作日志
        $operLogLib->write_log('order_create@ome', $sdf['order_id'], '订单创建成功');
        
        return true;
    }
    
    /**
     * 订单可合并标记生成hash值
     * 
     * @param array $params
     */
    public function genOrderCombieHashIdx($params)
    {
        $order_combine_hash = '';
        $order_combine_idx = '';
        
        $member_id = $params['member_id'];
        $order_bn = $params['order_bn'];
        $shop_id = $params['shop_id'];
        $ship_name = $params['consignee']['name'];
        $ship_mobile = $params['consignee']['mobile'];
        $ship_area = $params['consignee']['area'];
        $ship_addr = $params['consignee']['addr'];
        $order_source = $params['order_source'];
        $ship_tel = $params['consignee']['telephone'];
        $is_cod = $params['shipping']['is_cod'];
        $self_delivery = $params['self_delivery'];
        $shop_type = $params['shop_type'];
        
        $uniqueness = $member_id ? $member_id : $order_bn;
        $uniqueness2 = $order_source == 'tbdx' ? $order_id : $order_source;
        $uniqueness3 = $is_cod == 'true' ? $order_id : $is_cod;
        $uniqueness4 = $self_delivery == 'false' ? $order_id : $self_delivery;
        
        switch($shop_type)
        {
            case 'taobao':
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$uniqueness2.'-'.$ship_tel.'-'.$shop_type;
                $combine_idx= $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
                break;
            case 'dangdang':
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$uniqueness3.'-'.$ship_tel.'-'.$shop_type;
                $combine_idx= $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
                break;
            case 'amazon':
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$uniqueness4.'-'.$ship_tel.'-'.$shop_type;
                $combine_idx = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
                break;
            case 'aikucun':
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$order_id.'-'.$ship_tel.'-'.$shop_type;
                $combine_idx = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
                break;
            case 'shopex_b2b':
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
                $combine_idx = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
                break;
            case '360buy':
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$uniqueness3.'-'.$ship_tel.'-'.$shop_type;
                $combine_idx = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod.'-'.$ship_tel.'-'.$shop_type;
            default:
                $combine_hash = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod;
                $combine_idx = $uniqueness.'-'.$shop_id.'-'.$ship_name.'-'.$ship_mobile.'-'.$ship_area.'-'.$ship_addr.'-'.$is_cod;
                break;
        }
        
        $result['combine_hash'] = MD5($combine_hash);
        $result['combine_idx'] = CRC32($combine_idx);
        
        return $result;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * erapi接口上获取订单--------需要重构逻辑，用到的地方，都需要重写
     * 
     * @param string $field
     * @param array $shopId
     * @param string $orderBn
     * @return Ambigous <boolean, string>
     */
    final protected function getOrder($field, $shopId, $orderBn)
    {
        //定义名为：getApiOrder
        
        //---------需要重构逻辑，用到的地方，都需要重写
        
        $orderModel = app::get('ome')->model('orders');
        $tgOrder = $orderModel->getList($field, array('order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
        $archiveOrder = app::get('archive')->model('orders')->getList('order_id,status,process_status,ship_status,pay_status,payed,pay_bn,member_id,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_email,ship_mobile,is_protect,is_cod,source,order_type', array('order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
        if ($archiveOrder){
            $archiveOrder[0]['tran_type'] = 'archive';
            $tgOrder = $archiveOrder;
        }
        if (!$tgOrder) {
            $orderRsp = kernel::single('erpapi_router_request')->set('shop',$shopId)->order_get_order_detial($orderBn);
            if ($orderRsp['rsp'] == 'succ') {
                $msg = '';
                $rs = kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'],$shopId,$msg);
                if ($rs) {
                    $tgOrder = $orderModel->getList($field, array('order_bn'=>$orderBn,'shop_id'=>$shopId), 0, 1);
                }
            }
        }
        return $tgOrder ? $tgOrder[0] : false;
    }
    
    //=未使用，使用的目录：app\erpapi\lib\shop\response\aftersalev2.php
    protected function _dealRefundNoOrder($sdf)
    {
        $filter = array(
                'order_bn' => $sdf['order_bn'],
                'shop_id' => $sdf['shop_id'],
                'refund_bn' => $sdf['refund_bn']
        );
        
        $rnoModel = app::get('ome')->model('refund_no_order');
        $rs = $rnoModel->getList('id', $filter);
        if(!$rs) {
            $refundNoOrder = array(
                    'order_bn' => $sdf['order_bn'],
                    'shop_id' => $sdf['shop_id'],
                    'refund_bn' => $sdf['refund_bn'],
                    'status' => $sdf['status'],
                    'sdf' => serialize($sdf)
            );
            $rnoModel->insert($refundNoOrder);
        }
    }
    
}