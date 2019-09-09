<?php
namespace Common\Model;
use Common\Model\BaseModel;
/**
 * 会员推荐关系 Model
 *
 * @since: 2016年12月13日 下午3:18:09
 * @author: lyx
 * @version: V1.0.0
 */
class RecommendNexusModel extends BaseModel{

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
        $data =  $this->getPages($this, $where, $parameter, $order=$this->trueTableName.'.rec_floor desc',$join, $page_number,$field);
        return $data;
    }

}