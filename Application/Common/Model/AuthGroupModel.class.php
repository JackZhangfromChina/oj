<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;
/**
 * 管理员用户组 Model
 *
 * @since: 2016年12月13日 下午2:57:44
 * @author: lyx
 * @version: V1.0.0
 */
class AuthGroupModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('title', 'require', '管理员真实姓名必须填写', self::MUST_VALIDATE),
            array('title','checkUnique','角色已经存在！',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
        array('status',Constants::NORMAL,self::MODEL_INSERT),  // 新增时候status字段设置为1
        array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
        array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
    /**
    * 验证名称唯一性
    *
    * @return   bool    验证通过或失败
    *
    * @since: 2017年1月7日 上午10:44:34
    * @author: lyx
    */
    protected function checkUnique(){
        $item = $this->getByTitle(I('title'));
        if($item){
            return $item['id'] == I('id') ? true : false;
        }else{
            return true;
        }
    }
}