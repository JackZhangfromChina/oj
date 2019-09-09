<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 业绩记录 Model
 *
 * @since: 2016年12月13日 下午3:11:45
 * @author: lyx
 * @version: V1.0.0
 */
class MarketRecordModel extends BaseModel{

    /**
     * 业绩列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *                   statistics 统计对象
     *
     * @since: 2016年12月30日 下午3:30:16
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
    
    /**
     * 业绩报表
     *
     * @param    array   $where          查询的条件
     * @return   array
     *                   data       报表数据
     *                   statistics 统计对象
     *
     * @since: 2016年12月30日 下午3:30:16
     * @author: lyx
     */
    public function getReport($where){
        
        $data['report'] = $this->field("DATE_FORMAT(add_time,'%Y-%m-%d') AS date, SUM(amount) AS total")->where($where)->group('date')->select();
        
        //统计数据
        $data['statistics'] = $this->where($where)
                                    ->field('SUM(amount) as sum_total')
                                    ->find();
        return $data;
    }
}