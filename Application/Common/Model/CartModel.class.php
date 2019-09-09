<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Think\Page;
use Common\Conf\Constants;

/**
 * 购物车 Model
 *
 * @since: 2016年12月13日 下午3:01:26
 * @author: lyx
 * @version: V1.0.0
 */
class CartModel extends BaseModel{
	//自动完成
    protected $_auto = array (
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );

    /**
     * 购物车列表
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function getList($where){
        $list = M('Cart')
                    ->field('c.*,g.title,g.pic_url,g.new_price,g.status,g.inventory_number,g.limit_number')
                    ->alias('c')
                    ->join(C('DB_PREFIX') . 'goods g ON c.goods_id = g.id','LEFT')
                    ->where($where)
                    ->order(array('c.update_time'=>'desc'))
                    ->select();
        return $list;
    }
}