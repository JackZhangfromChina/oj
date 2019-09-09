<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;
use Common\Library\PHPExcel;

/**
 * 订单 Model
 *
 * @since: 2016年12月13日 下午3:15:21
 * @author: lyx
 * @version: V1.0.0
 */
class OrderModel extends BaseModel{
	/**
     * 订单列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *
     * @since: 2016年12月29日 下午4:24:56
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
        
        return $data;
    }
    
}