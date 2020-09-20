<?php
/**
 * 订单会员数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;
use app\admin\library\ome\Members;


class Member extends Abstracts
{
    public function convert($ordersdf)
    {
        $memberLib = new Members;
        
        $member_info = $ordersdf['member_info'];
        $shop_id = $ordersdf['shop_id'];
        
        unset($member_info['member_id']);
        
        if ($member_info)
        {
            $member_info['shop_type'] = $ordersdf['shop_type'];
            $member_info['consignee'] = $ordersdf['consignee'];
            
            //更新会员信息
            $member_id = $memberLib->saveMember($member_info, $shop_id);
            if ($member_id){
                $this->_newOrder['member_id'] = $member_id;
            }
        }
    }
    
    
    
    
    
    public function update()
    {
        $memberLib = new Members;
        
        $member_info = $ordersdf['member_info'];
        $shop_id = $ordersdf['shop_id'];

        unset($member_info['member_id']);

        if ($member_info)
        {
            $member_id = $memberLib->saveMember($member_info, $shop_id, $this->_platform->_tgOrder['member_id'], $old_member);
            
            if ($member_id != $this->_platform->_tgOrder['member_id'])
            {
                $this->_newOrder['member_id'] = $member_id;
            }
        }
    }
}