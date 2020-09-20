<?php
/**
 * 商家备注数据转换类
 *
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\library\erpapi\shop\response\components;

use app\admin\library\erpapi\shop\response\components\Abstracts;

class Markmemo extends Abstracts
{
    /**
     * 订单格式转换
     *
     * @return void
     **/
    public function convert($ordersdf)
    {
        $mark_text = $ordersdf['mark_text'];
        
        if (!is_null($mark_text) && $mark_text !== '') 
        {
            $markmemo[] = array(
                'op_name' => $ordersdf['shop_name'],
                'op_time' => date("Y-m-d H:i:s",time()),
                'op_content' => htmlspecialchars($mark_text),
            );
            
            $this->_newOrder['mark_text'] = serialize($markmemo);
        }
    }
    
    
    
    /**
     * 更新订单备注
     *
     * @return void
     **/
    public function update()
    {
        $old_mark_text = array();
        if ($this->_platform->_tgOrder['mark_text'] && is_string($this->_platform->_tgOrder['mark_text'])) {
            $old_mark_text = unserialize($this->_platform->_tgOrder['mark_text']);
        }

        $last_mark_text = array();
        foreach ((array) $old_mark_text as $key => $value) {
            if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

            if ( intval($value['op_time']) > intval($last_mark_text['op_time']) && ($value['op_name'] == $ordersdf['shop_name'] || in_array($ordersdf['node_type'],ome_shop_type::shopex_shop_type()) ) ) {
                $last_mark_text = $value;
            }
        }

        $mark_text = $ordersdf['mark_text'];
        if (!is_null($mark_text) && $mark_text !== '' && $last_mark_text['op_content'] != $mark_text) {
            $mark = (array) $old_mark_text;
            $mark[] = array(
                'op_name'    => $ordersdf['shop_name'],
                'op_content' => $mark_text,
                'op_time'    => date('Y-m-d H:i:s')
            );

            //备注去掉换行比
            $order_mark_text = preg_split("/[\n]--/",$mark_text);
            if (count($order_mark_text)>1) {
                $mark_data = preg_split('/[\n]/',$order_mark_text[0]);
                if ($mark_data[1] == $last_mark_text['op_content']) {
                    unset($mark);
                }
            }
        }
        
        if($mark){
            $this->_newOrder['mark_text'] = serialize($mark);
        }
    }
}