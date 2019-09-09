<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;
/**
 * 会员安置关系 Model
 *
 * @since: 2016年12月13日 下午3:17:03
 * @author: lyx
 * @version: V1.0.0
 */
class ParentNexusModel extends BaseModel{

    //定义自动验证
    protected $_validate = array(
    );
    
    //自动完成
    protected $_auto = array (
    );
    /**
     *会员列表
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
        $data =  $this->getPages($this, $where, $parameter, $order=$this->trueTableName.'.floor desc',$join, $page_number,$field);
        $where['is_activated'] = Constants::OPERATE_STATUS_CONFIRM;
        $data['statistics'] = $this->where($where)
            ->field('SUM(investment) as sum_total')
            ->join($join)
            ->find();
        return $data;
    }

}