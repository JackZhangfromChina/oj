<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 商品附属 Model
 *
 * @since: 2016年12月13日 下午3:07:19
 * @author: lyx
 * @version: V1.0.0
 */
class GoodsExtendModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
    	 array('detail', 'require', '商品详细不能为空', self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
    	array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
        array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
}