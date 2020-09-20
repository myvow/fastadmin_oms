<?php 
/**
 * 订单Model类
 * 
 * @author wofeel<wofeel@126.com>
 * @license http://www.baidu.com
 * @version v1.0
 */
namespace app\admin\model\ome;

use think\Model;

class Abstracts extends Model
{
    //当前模型名称
    protected $name = ''; //系统会自动附加表前辍fa_
    
    //数据表名称
    //protected $table = 'fa_ome_orders'; //当指定某个数据库表时才会使用,并且要带表前辍
    
    //数据表主键
    protected $pk = '';
    
    //开启自动写入时间戳字段
    //protected $autoWriteTimestamp = 'int'; //默认为false
    //定义自动创建时间戳字段名
    //protected $createTime = 'createtime';
    //定义自动更新时间戳字段名
    //protected $updateTime = 'last_modified';
    
    //设置只读字段(只允许新建保存,不允许更新会自动过滤定义的字段)
    //protected $readonly = ['order_id', 'email'];
    
    //软删除
    //use SoftDelete;
    //protected $deleteTime = 'del_time'; //软删除时更新的时间字段名
    
    /****
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    /**
     * 获取追加属性status状态值
     * 
     * @param string $value 数据表status字段的值
     * @param array $data 数据表单条记录数组
     * @return string
     */
    /***
    public function getStatusTextAttr($value, $data)
    {
        $statsList = ['normal' => __('Normal'), 'hidden' => __('Hidden')];
        
        $value = $value ? $value : $data['status'];
        
        return isset($list[$value]) ? $list[$value] : '';
    }
    ***/
    
    //查询数据集对象(查询返回数组)
    protected $resultSetType = 'collection';
    
    /**
     * [自定义]默认排序方式
     */
    protected $_order_by = '';
    
    /**
     * [自定义]一对多关联查询
     */
    protected $_has_many = null;
    
    
    /**
     * 格式化查询条件
     * 
     * @param array $filter
     * @return array
     */
    public function _filter($filter)
    {
        if(empty($filter)){
            return [];
        }
        
        return $filter;
    }
    
    /**
     * 统计记录条数
     * 
     * @param array $filter
     * @return number
     */
    public function getCount($filter=null)
    {
        //filter条件
        $filter = $this->_filter($filter);
        
        if($filter){
            $count = $this->where($filter)->count();
        }else{
            $count = $this->count();
        }
        
        return $count;
    }
    
    /**
     * 查询单条记录
     * @todo：支持关联查询数据记录
     * 
     * @param string $field
     * @param array $filter
     * @param string $orderBy
     * @return array
     */
    public function getRow($field='*', $filter, $hasMany=null)
    {
        //filter条件
        $filter = $this->_filter($filter);
        
        //格式化查询字段名
        if($hasMany)
        {
            $pk = $this->pk ? $this->pk : $this->getPk(); //关联外键字段名
            $field = $this->_formatField($field, $pk);
        }
        
        //row
        $row = $this->where($filter)->field($field)->find()->toArray();
        if(empty($row)){
            return [];
        }
        
        //关联查询
        if($hasMany && is_array($hasMany))
        {
            $this->_rowDepends($row, $hasMany);
        }
        
        return $row;
        
    }
    
    /**
     * 查询记录列表
     * 
     * @param string $field
     * @param array $filter
     * @param number $offset
     * @param number $limit
     * @param string $orderBy
     * @return array
     */
    public function getList($field='*', $filter, $offset=0, $limit=-1, $orderBy=null)
    {
        //filter条件
        $filter = $this->_filter($filter);
        
        //orderby排序
        $orderBy = empty($orderBy) ? $this->_order_by : $orderBy;
        
        //limit查询条数
        $offset = intval($offset) ? intval($offset) : 0;
        $limit = intval($limit) ? intval($limit) : 0;
        
        //查询开始到末尾所有记录
        if($limit == -1)
        {
            $limit = $this->getCount($filter);
        }
        
        //list
        $dataList = $this->where($filter)->field($field)->order($orderBy)->limit($offset, $limit)->select()->toArray();
        
        return $dataList;
    }
    
    /**
     * 关联数据库表查询
     * @todo：关联表名必须在$_has_many里先声明,并且现只支持2级关联查询；
     * 
     * @param array $data
     * @param array $hasMany
     * @return bool
     */
    protected function _rowDepends(&$data, $hasMany)
    {
        if(empty($this->_has_many)){
            return false;
        }
        
        foreach ($hasMany as $tableName => $hasItem)
        {
            $model_name = $this->_has_many[$tableName]; //数据库表对象名
            if(empty($model_name))
            {
                continue; //_has_many里未声明,则跳过
            }
            
            $field = $hasItem['field']; //查询字段名
            $pk = $this->pk ? $this->pk : $this->getPk(); //关联外键字段名
            
            //格式化查询字段名
            $field = $this->_formatField($field, $pk);
            
            //获取类完整路径名
            $model_name = $this->parseModel($model_name);
            if(!class_exists($model_name)){
                continue;
            }
            
            //实例化对象
            $model = new $model_name;
            $fitler = array($pk=>$data[$pk]);
            $hasData = $model->where($fitler)->field($field)->select()->toArray();
            
            //[递归]二级关联查询
            if($hasItem['hasMany'])
            {
                $parentPk = ($model->pk ? $model->pk : $model->getPk());
                $parentInfo = array(
                        'parentFilter' => $fitler,
                        'parentPk' => $parentPk,
                );
                
                $data[$tableName] = $this->_rowDependsData($hasData, $hasItem['hasMany'], $parentInfo);
            }
            else 
            {
                $data[$tableName] = $hasData;
            }
            
            unset($model, $hasData, $fitler);
        }
        
        return true;
    }
    
    /**
     * 关联二级数据记录查询
     * 
     * @param obj $dataObj
     * @param array $hasMany
     * @param string $pk 关联外键字段名
     * @return array
     */
    private function _rowDependsData($data, $hasItem, $parentInfo)
    {
        $tableName = key($hasItem);
        $model_name = $this->_has_many[$tableName]; //数据库表对象名
        if(empty($model_name))
        {
            return $data;
        }
        
        $field = $hasItem[$tableName]['field'] ? $hasItem[$tableName]['field'] : '*'; //查询字段名
        $parentFilter = $parentInfo['parentFilter'];
        $parentPk = $parentInfo['parentPk'];
        
        //格式化查询字段名
        $field = $this->_formatField($field, $parentPk);
        
        //获取类完整路径名
        $model_name = $this->parseModel($model_name);
        if(!class_exists($model_name)){
            return $data;
        }
        
        //实例化对象(使用父条件获取所有关联数据)
        $model = new $model_name;
        $dataList = $model->where($parentFilter)->field($field)->select()->toArray();
        if(empty($dataList)){
            return $data;
        }
        
        //格式数组下标
        $data = array_column($data, null, $parentPk);
        
        //组织对应记录
        foreach ($dataList as $key => $val)
        {
            $primary_id = $val[$parentPk];
            
            $data[$primary_id][$tableName][] = $val;
        }
        
        //重新组织键名
        $data = array_values($data);
        
        unset($model, $dataList, $parentFilter);
        
        return $data;
    }
    
    /**
     * 格式化查询字段名,自动加入主键字段
     * @todo：防止指定查询字段时，没有传关联主键字段,导致一对多关联查询未匹配
     * 
     * @param string $field
     * @param string $pk
     * @return string
     */
    protected function _formatField($field, $pk)
    {
        $field = trim($field) ? trim($field) : '*';
        if($field == '*'){
            return $field;
        }
        
        if(!is_array($field)){
            $field = explode(',', $field);
        }
        
        $flag = false;
        foreach ($field as $key => $val)
        {
            $val = trim($val);
            
            if($val == $pk){
                $flag = true;
            }
            
            $field[$key] = $val;
        }
        
        if(!$flag){
            $field[] = $pk;
        }
        
        return implode(',', $field);
    }
}
