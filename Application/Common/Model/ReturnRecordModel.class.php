<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 返本记录 Model
 *
 * @since: 2016年12月13日 下午3:20:19
 * @author: lyx
 * @version: V1.0.0
 */
class ReturnRecordModel extends BaseModel{
    
    /**
     * 返本列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *                   statistics 统计对象
     *
     * @since: 2016年12月30日 下午1:57:32
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
    
        //统计数据
        $data['statistics'] = $this->where($where)
                                    ->field('SUM(amount) as sum_total')
                                    ->find();
        return $data;
    }
}