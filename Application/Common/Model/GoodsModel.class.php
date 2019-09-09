<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 商品 Model
 *
 * @since: 2016年12月13日 下午3:05:20
 * @author: Wang Peng
 * @version: V1.0.0
 */
class GoodsModel extends BaseModel{

	//定义自动验证
    protected $_validate = array(
            array('title', 'require', '商品名称不能为空', self::MODEL_BOTH),
            array('title', 'checklen', '商品名称必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('goods_category_id', 'require', '商品分类不能为空', self::MODEL_BOTH),
            array('old_price', 'require', '商品原价不能为空', self::MODEL_BOTH),
            array('total_number', 'require', '商品数量不能为空', self::MODEL_BOTH),
            array('inventory_number', 'require', '库存量不能为空', self::MODEL_BOTH),
            array('limit_number', 'require', '限购数量不能为空', self::MODEL_BOTH),
            array('warn_number', 'require', '预警数量不能为空', self::MODEL_BOTH),
    );
    //自动完成
    protected $_auto = array (
            array('status',Constants::NO,self::MODEL_INSERT),  // 新增时候status字段设置为0
            array('sell_number',0,self::MODEL_INSERT),  // 新增时候sell_number字段设置为0
            array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
    /**
     * 商品列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *
     * @since: 2017年4月16日 下午3:08:00
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
    
        return $data;
    }
    
}