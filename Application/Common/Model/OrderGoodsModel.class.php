<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 订单商品 Model
 *
 * @since: 2016年12月13日 下午3:16:10
 * @author: lyx
 * @version: V1.0.0
 */
class OrderGoodsModel extends BaseModel{
    /**
     * 订单商品列表
     *
     * @param   array   $where  查询的条件
     * @return  array   数据
     *
     * @since: 2017年4月24日 下午3:07:23
     * @author: lyx
     */
    public function getList($where){
        $list = M('OrderGoods')
                    ->field('aog.*,g.title,g.pic_url')
                    ->alias('aog')
                    ->join(C('DB_PREFIX') . 'goods g ON aog.goods_id = g.id','LEFT')
                    ->where($where)
                    ->order(array('g.id'=>'desc'))
                    ->select();
        return $list;
    }
    
}