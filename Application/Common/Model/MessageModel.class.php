<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 短信 Model
 *
 * @since: 2016年12月13日 下午3:12:44
 * @author: lyx
 * @version: V1.0.0
 */
class MessageModel extends BaseModel{

    /**
     * 短信列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *
     * @since: 2017年1月13日 下午1:52:20
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
    
        return $data;
    }
}