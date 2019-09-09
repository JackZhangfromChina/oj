<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 商品库存变更 Model
 *
 * @since: 2016年12月13日 下午3:08:47
 * @author: lyx
 * @version: V1.0.0
 */
class GoodsInventoryModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('quantity', 'number', '库存变更数量必须是数字', self::MODEL_BOTH),
            array('remark', 'require', '库存变更备注不能为空', self::MODEL_BOTH),
            array('remark', 'checklen', '库存变更备注必须在2~100个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,100)),
    );
    
    //自动完成
    protected $_auto = array (
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
}