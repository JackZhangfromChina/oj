<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 账户变更记录 Model
 *
 * @since: 2016年12月13日 下午2:54:18
 * @author: lyx
 * @version: V1.0.0
 */
class AccountRecordModel extends BaseModel{
    
    /**
     * 流水账列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *                   statistics 统计对象
     *
     * @since: 2016年12月29日 下午4:24:56
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
        
        //统计数据
        $data['statistics'] = $this->where($where)
                                ->field('type,SUM(amount) as sum_total')
                                ->group('type')
                                ->order('type asc')
                                ->select();
        return $data;
    }
}