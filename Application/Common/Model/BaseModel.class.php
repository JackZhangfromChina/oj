<?php
namespace Common\Model;
use Think\Model;
use Common\Common\PageUtil;
use Common\Common\Data;
use Common\Conf\Constants;

/**
 * 基础model
 *
 * @since: 2016年12月12日 下午1:30:22
 * @author: lyx
 * @version: V1.0.0
 */
class BaseModel extends Model{

    /**
    * 添加数据
    * 
    * @param  array $data  添加的数据
    * @return int          新增的数据id
    *
    * @since: 2016年12月12日 下午1:30:38
    * @author: lyx
    */
    public function addData($data){
        // 去除键值首尾的空格
        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        $id=$this->add($data);
        return $id;
    }

    /**
    * 修改数据
    * @param   array   $map    where语句数组形式
    * @param   array   $data   数据
    * @return  boolean         操作是否成功
    *
    * @since: 2016年12月12日 下午1:31:22
    * @author: lyx
    */
    public function editData($map,$data){
        // 去除键值首位空格
        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        $result=$this->where($map)->save($data);
        return $result;
    }

    /**
    * 删除数据
    * @param   array   $map    where语句数组形式
    * @return  boolean         操作是否成功
    *
    * @since: 2016年12月12日 下午1:32:09
    * @author: lyx
    */
    public function deleteData($map){
        if (empty($map)) {
            die('where为空的危险操作');
        }
        $result=$this->where($map)->delete();
        return $result;
    }

    /**
    * 数据排序
    * @param  array $data   数据源
    * @param  string $id    主键
    * @param  string $order 排序字段   
    * @return boolean       操作是否成功
    *
    * @since: 2016年12月12日 下午1:32:37
    * @author: lyx
    */
    public function orderData($data,$id='id',$order='order_number'){
        foreach ($data as $k => $v) {
            $v=empty($v) ? null : $v;
            $this->where(array($id=>$k))->save(array($order=>$v));
        }
        return true;
    }

    /**
    * 获取全部数据(无限级)
    * @param  string $type  tree获取树形结构 level获取层级结构
    * @param  string $order 排序方式   
    * @return array         结构数据
    *
    * @since: 2016年12月12日 下午1:33:28
    * @author: lyx
    */
    public function getTreeData($type='tree',$order='',$name='name',$child='id',$parent='pid'){
        // 判断是否需要排序
        if(empty($order)){
            $data=$this->select();
        }else{
            $data=$this->order($order.' is null,'.$order)->select();
        }
        // 获取树形或者结构数据
        if($type=='tree'){
            $data=Data::tree($data,$name,$child,$parent);
        }elseif($type=="level"){
            $data=Data::channelLevel($data,0,'&nbsp;',$child, $parent);
        }
        return $data;
    }

    /**
    * 获取分页数据
    * @param  subject  $model  model对象
    * @param  array    $map    where条件
    * @param  array    $parameter    分页的参数
    * @param  string   $order  排序规则
    * @param  integer  $limit  每页数量
    * @param  integer  $field  $field
    * @return array            分页数据
    *
    * @since: 2016年12月12日 下午1:34:49
    * @author: lyx
    */
    public function getPage($model, $map, $parameter, $order='',$limit=Constants::PAGE_NUMBER,$field=''){
        $count=$model
                ->where($map)
                ->count();
        $page= new PageUtil($count,$limit);
        //封装分页参数
        foreach($parameter as $key=>$val) {
            $page->parameter[$key] = urlencode($val);
        }
        
        // 获取分页数据
        if (empty($field)) {
            $list=$model
                ->where($map)
                ->order($order)
                ->limit($page->firstRow.','.$page->listRows)
                ->select();         
        }else{
            $list=$model
                ->field($field)
                ->where($map)
                ->order($order)
                ->limit($page->firstRow.','.$page->listRows)
                ->select();         
        }
        $data=array(
                'data'=>$list,
                'page'=>$page->show()
            );
        return $data;
    }
    /**
     * 获取分页数据
     * @param  subject  $model  model对象
     * @param  array    $map    where条件
     * @param  array    $parameter    分页的参数
     * @param  string   $order  排序规则
     * @param  integer  $limit  每页数量
     * @param  integer  $field  $field
     * @return array            分页数据
     *
     * @since: 2016年12月12日 下午1:34:49
     * @author: lyx
     */
    public function getPages($model, $map, $parameter, $order='',$join='',$limit=Constants::PAGE_NUMBER,$field=''){
        $count=$model
            ->where($map)
            ->join($join)
            ->count();
        $page= new PageUtil($count,$limit);
        //封装分页参数
        foreach($parameter as $key=>$val) {
            $page->parameter[$key] = urlencode($val);
        }

        // 获取分页数据
        if (empty($field)) {
            $list=$model
                ->where($map)
                ->order($order)
                ->join($join)
                ->limit($page->firstRow.','.$page->listRows)
                ->select();
        }else{
            $list=$model
                ->field($field)
                ->where($map)
                ->order($order)
                ->join($join)
                ->limit($page->firstRow.','.$page->listRows)
                ->select();
        }
        $data=array(
            'data'=>$list,
            'page'=>$page->show()
        );
        return $data;
    }
    /**
     * 检查字符长度
     *
     * @param    string  $str    字符串
     * @param    int     $min    长度范围最小值
     * @param    int     $max    长度范围最大值
     * @return    boolean
     *
     * @since: 2016年12月16日 下午6:03:27
     * @author: lyx
     */
    function checklen($str,$min,$max){
        if(mb_strlen($str,'utf-8')>$max || mb_strlen($str,'utf-8')<$min){
            return false;
        }else{
            return true;
        }
    }
    
    /**
     * 格式化当前时间
     *
     * @since: 2016年12月13日 上午10:29:25
     * @author: lyx
     */
    function curr_time(){
        return date("Y-m-d H:i:s",time());
    }
    
    /**
     * 对数据进行拖拽排序
     *
     * @param    object  $model          model对象
     * @param    object  $primary_key    主键名称
     * @param    object  $array          主键对应值的数组
     *
     * @return   boolean 是否保存成功
     *
     * @since: 2017年4月16日 上午11:10:17
     * @author: lyx
     */
    public static function getSort($model,$primary_key,$array){
        $count=count($array);
        for ($i=0; $i<$count; $i++) {
            $map[$primary_key] =$array[$i];
            $data['sort'] = $i+1;
            $rs = M($model)->where($map)->save($data);
            if ($rs===false) {
                return false;
                break;
            }
        }
        return true;
    }
}
