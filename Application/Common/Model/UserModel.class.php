<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;
/**
 * 会员 Model
 *
 * @since: 2016年12月13日 下午2:51:53
 * @author: lyx
 * @version: V1.0.0
 */
class UserModel extends BaseModel{

    //定义自动验证
    protected $_validate = array(
    );
    
    //自动完成
    protected $_auto = array (
    );
    /**
     * 检测邀请人是否存在
     *
     * @param $inv
     * @return bool
     * @since: 2016年12月17日 上午8:40:50
     * @author: xielu
     */
    public function civ($inv)
    {
        $result=$this->where(array("id"=>$inv))->count('id');
        return $result>0;
    }
    /**
     *检测邀请人是否存在
     *
     * @param $recnumber
     * @return bool
     * @since: 2016年12月17日 上午8:40:50
     * @author: xielu
     */
    public function cciv($recnumber)
    {
        $result=$this->where(array("user_no"=>$recnumber))->count('id');
        return $result>0;
    }

    /**
    * 验证对应字段的值是否存在
    *
    * @param    string  $key    需要验证user表中的字段
    * @param    string  $value  需要验证字段对应的值
    * @param    int     $id     当前会员的id
    * @return   bool    存在（true）/不存在（false）
    *
    * @since: 2016年12月27日 下午3:13:20
    * @author: lyx
    */
    public function checkExist($key, $value, $id='')
    {
//         $result=$this->where(array($key=>$value))->count('id');
//         return $result>0;
        
        $user = $this->field('id')->where(array($key=>$value))->find();
        return ($id=='' && $user || $id!='' && $user && $user['id']!=$id) ? true : false;
    }

    /**
     *报单中心会员列表
     *
     * @param $where
     * @param $parameter
     * @param $page_number
     * @return array
     *
     * @since: 2016年12月29日 下午1:11:20
     * @author: xielu
     */
    public function getCenterList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
        return $data;
    }
    /**
     *报单中心会员列表
     *
     * @param $where
     * @param $parameter
     * @param $page_number
     * @return array
     *
     * @since: 2016年12月29日 下午1:11:20
     * @author: xielu
     */
    public function getMemberList($where,$parameter,$join,$page_number,$field){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPages($this, $where, $parameter, $order=$this->trueTableName.'.id desc',$join, $page_number,$field);
        $where['is_activated'] = Constants::OPERATE_STATUS_CONFIRM;
        $data['statistics'] = $this->where($where)
            ->field('SUM(investment) as sum_total')
            ->find();
        return $data;
    }
    
    /**
     * 会员报表
     *
     * @param    array   $where          查询的条件
     * @return   array
     *                   data       报表数据
     *                   statistics 统计对象
     *
     * @since: 2017年1月14日 下午3:53:51
     * @author: lyx
     */
    public function getReport($where){
    
        $data['report'] = $this->field("DATE_FORMAT(add_time,'%Y-%m-%d') AS date, count(id) AS count")->where($where)->group('date')->select();
    
        //统计数据
        $data['statistics'] = $this->where($where)
                                ->field('count(id) as sum_total')
                                ->find();
        return $data;
    }
    
    /**
    * 检测安置人位置状态
    *
    * @param    string  parent_no   安置人编号
    * @return   array
    *               status      是否可用
    *               location    空余位置
    *
    * @since: 2017年1月19日 下午5:42:12
    * @author: lyx
    */
    public function checkParentLocationStatus($parent_no) {
        $where = array(
                "user_no"    => $parent_no
        );
        $parent = $this->field('left_no,right_no')->where($where)->find();
        if ($parent) {
            if (!$parent['left_no'] && !$parent['right_no']) {
                $result = array(
                        'status'    => true,
                        'location'  => 'all'
                );
            } elseif (!$parent['left_no']) {
                $result = array(
                        'status'    => true,
                        'location'   => 'left'
                );
            } elseif (!$parent['right_no']) {
                $result = array(
                        'status'    => true,
                        'location'   => 'right'
                );
            } else {
                $result = array(
                        'status'    => false,
                        'message'   => '安置区域已满'
                );
            }
        } else {
            $result = array(
                    'status'    => false,
                    'message'   => '安置人不存在'
            );
        }        
        return $result;
    }
}