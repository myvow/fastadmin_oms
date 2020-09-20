<?php
/**
 * 会员数据公共类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\ome;

use think\Db;

use app\admin\library\ome\Common;

class Members
{
    /**
     * 更新订单会员信息
     * 
     * @param Array $member_info 会员信息
     * @param String $shop_id 店铺ID
     * @param Number $old_member_id 订单会员ID
     * @param Array $old_member 更新前的会员信息
     * 
     * @return int 会员ID
     */
    public function saveMember($member_info, $shop_id='', $old_member_id='', &$old_member=array())
    {
        //check
        if (empty($member_info)){
            return null;
        }
        
        $membersObj = Db::name('ome_members');
        $funcLib = new Common;
        
        if (!isset($member_info['area']))
        {
            if($member_info['area_state'])
            {
                $area = $member_info['area_state'] .'/'. $member_info['area_city'] .'/'. $member_info['area_district'];
                
                $funcLib->region_validate($area);
                
                $member_info['area'] = $area;
            }
        }
        
        //shopex前端店铺列表
        $shopex_shop_type = $funcLib->shopex_shop_type();
        
        //原会员信息
        if($old_member_id)
        {
            $md5_field = array('uname','name','area','addr','phone','mobile','telephone','email','zipcode');
            
            $old_member_info = $membersObj->where(array('member_id'=>$old_member_id))->find();
            
            //引用原会员信息
            $old_member = $old_member_info;
            
            $update_flag = false;
            foreach($md5_field as $sdf=>$field)
            {
                $compre_value = trim($member_info[$field]);
                
                if (empty($compre_value)) continue;
                
                if ($member_info[$field] != $old_member_info[$field])
                {
                    $update_flag = true;
                }
            }
            
            if ($update_flag == false)
            {
                return $old_member_id;
            }
        }
        
        if (empty($member_info['name'])) $member_info['name'] = $member_info['uname'];
        
        $member_detail = array();
        $member_id = null;
        if($member_info['uname'])
        {
            //判断是否存在该会员
            if(empty($member_info['member_id']))
            {
                if($member_info['shop_type'])
                {
                    if(in_array($member_info['shop_type'], $shopex_shop_type)){
                        $filter = array('uname'=>$member_info['uname'], 'shop_id'=>$shop_id, 'shop_type'=>$member_info['shop_type']);
                        $member_detail = $membersObj->where($filter)->field('member_id')->find();
                    }else{
                        $filter = array('uname'=>$member_info['uname'],'shop_type'=>$member_info['shop_type']);
                        $member_detail = $membersObj->where($filter)->field('member_id')->find();
                    }
                }
                else
                {
                    $member_detail = $membersObj->where(array('uname'=>$member_info['uname']))->field('member_id')->find();
                }
            }else{
                $member_detail['member_id'] = $member_info['member_id'];
            }
            
            $area = $member_info['area'];
            $area = str_replace('::', '', $area);
            $shop_area = '';
            
            if($member_info['consignee']['area_state'])
            {
                $shop_area = $member_info['consignee']['area_state'] .'/'. $member_info['consignee']['area_city'] .'/'. $member_info['consignee']['area_district'];
                
                $funcLib->region_validate($shop_area);
                
                $shop_area = str_replace('::', '', $shop_area);
            }
            
            $members_data = array(
                    'account' => array(
                            'uname' => $member_info['uname'],
                    ),
                    'contact' => array(
                            'name' => $member_info['name'],
                            'area' => $area,
                            'addr' => $member_info['addr'],
                            'phone' => array(
                                    'mobile' => $member_info['mobile'],
                                    'telephone' => $member_info['tel'],
                            ),
                            'email' => $member_info['email'],
                            'zipcode' => $member_info['zip'],
                    ),
                    'shop_type' => $member_info['shop_type'],
        
            );
            
            if(in_array($member_info['shop_type'], $shopex_shop_type))
            {
                $members_data['shop_id'] = $shop_id;
            }
            
            $shop_members_data = array(
                    'ship_name'     => $member_info['consignee']['name'] ? $member_info['consignee']['name'] : $member_info['name'],
                    'ship_area'     => $shop_area ? $shop_area : $area,
                    'ship_addr'     => $member_info['consignee']['addr'] ? $member_info['consignee']['addr'] : $member_info['addr'] ,
                    'ship_mobile'   => $member_info['consignee']['mobile'] ? $member_info['consignee']['mobile'] : $member_info['mobile'],
                    'ship_tel'      => $member_info['consignee']['telephone'] ? $member_info['consignee']['telephone'] : $member_info['tel'],
                    'ship_zip'      => $member_info['consignee']['zip'] ? $member_info['consignee']['zip'] : $member_info['zip'],
                    'ship_email'    => $member_info['consignee']['email'] ? $member_info['consignee']['email'] : $member_info['email'],
        
            );
            
            if(empty($member_detail['member_id']))
            {
                //插入
                $membersObj->insert($members_data);
                $member_detail['member_id'] = $membersObj->getLastInsID;
            }
            else
            {
                unset($members_data['shop_id'], $members_data['shop_type'], $members_data['account']['uname']);
                
                //更新
                $filter = array('member_id'=>$member_detail['member_id']);
                $membersObj->where($filter)->update($members_data);
            }
            
            $member_id = $member_detail['member_id'];
            if($member_id)
            {
                $shop_members_data['member_id'] = $member_id;
                
                $this->create_address($shop_members_data);
            }
        }
        
        return $member_id;
    }
    
    /**
     * 保存会员地址
     * 
     * @param array $data
     * @return bool
     */
    public function create_address($data)
    {
        $addressObj = Db::name('ome_member_address');
        $member_id = $data['member_id'];
        
        if(empty($member_id))
        {
            return false;
        }
        
        $address_hash = crc32($data['ship_name'].'-'.$data['ship_area'].$data['ship_addr'].'-'.$data['ship_mobile'].'-'.$data['ship_tel'].'-'.$data['ship_zip'].'-'.$data['ship_email']);
        $address_hash = sprintf('%u', $address_hash);
        $data['address_hash'] = $address_hash;
        
        //会员地址信息
        $address_detail = $addressObj->where(array('address_hash'=>$address_hash,'member_id'=>$member_id))->field('address_id')->find();
        
        //插入新会员地址
        if(empty($address_detail['address_id']))
        {
            $addressObj->insert($data);
            
            $data['address_id'] = $addressObj->getLastInsID;
        }
        
        if($data['is_default'] == '1' && $data['address_id'])
        {
            $sql = "UPDATE fa_ome_member_address SET is_default='0' WHERE member_id=". $data['member_id'] ." AND address_id !=". $data['address_id'];
            Db::execute($sql);
            
            $sql = "UPDATE sdb_ome_members ";
            $sql .= " SET area='".$data['ship_area']."',addr='".$data['ship_addr']."',mobile='".$data['ship_mobile']."',tel='".$data['ship_tel']."'";
            $sql .= ",email='".$data['ship_email']."', zip='".$data['ship_zip']."'";
            $sql .= " WHERE member_id=".$data['member_id'];
            Db::execute($sql);
        }
        
        return true;
    }
    
}