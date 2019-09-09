<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 规则 Model
 *
 * @since: 2016年12月13日 下午3:00:28
 * @author: lyx
 * @version: V1.0.0
 */
class AuthRuleModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('title', 'require', '规则名称必须填写', self::MUST_VALIDATE),
            array('title', 'checklen', '规则名称必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('name', 'checklen', '规则action必须在2~100个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,100)),
            array('name','checkUnique','规则已经存在！',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
        array('status',Constants::NORMAL,self::MODEL_INSERT),  // 新增时候status字段设置为1
        array('type',1,self::MODEL_INSERT),  // 新增时候type字段设置为1
        array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
        array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
    /**
    * 验证规则唯一性
    *
    * @return   bool    验证通过或失败
    *
    * @since: 2017年1月7日 上午10:45:44
    * @author: lyx
    */
    protected function checkUnique(){
        $item = $this->getByName(I('name'));
        if($item){
            return $item['id'] == I('id') ? true : false;
        }else{
            return true;
        }
    }
}