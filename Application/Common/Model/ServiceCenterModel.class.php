<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;
/**
 * 服务中心 Model
 *
 * @since: 2016年12月13日 下午3:22:22
 * @author: lyx
 * @version: V1.0.0
 */
class ServiceCenterModel extends BaseModel{

	//查看会员是否是报单中心
    public function getServiceCenter($Mu_Number){
        $res = $this->where(array('user_no'=>$Mu_Number,'status'=>Constants::OPERATE_STATUS_CONFIRM))->count();
        return $res==1?true:false;
    }

    //定义自动验证
    protected $_validate = array(
    );
    
    //自动完成
    protected $_auto = array (
    );

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
    public function getCenterList($where,$parameter,$join,$page_number,$field){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPages($this, $where, $parameter, $order=$this->trueTableName.'.add_time desc',$join, $page_number,$field);
        $where['is_activated'] = Constants::OPERATE_STATUS_CONFIRM;
        return $data;
    }
}