<?php
namespace Common\Model;
use Common\Model\BaseModel;
use Common\Conf\Constants;

/**
 * 银行卡 Model
 *
 * @since: 2017年1月10日 上午10:55:13
 * @author: lyx
 * @version: V1.0.0
 */
class BankModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('user_no', 'require', '会员编号必须填写', self::MUST_VALIDATE),
            array('user_no', 'checklen', '会员编号必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('bank', 'require', '银行名称必须填写', self::MUST_VALIDATE),
            array('bank', 'checklen', '银行名称必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('sub_bank', 'require', '开户支行必须填写', self::MUST_VALIDATE),
            array('sub_bank', 'checklen', '开户支行必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('bank_no', 'require', '银行卡号必须填写', self::MUST_VALIDATE),
            array('bank_no', 'checklen', '银行卡号必须在2~20个字符之间', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('bank_no','checkUnique','银行卡号已经存在！',self::MUST_VALIDATE,'callback',self::MODEL_BOTH),
    );
    
    //自动完成
    protected $_auto = array (
        array('is_default',Constants::NO,self::MODEL_INSERT),  // 新增时候is_default字段设置为0
        array('add_time','curr_time',self::MODEL_INSERT,'callback'), // 对create_time字段在新增时写入当前时间
        array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
    
    /**
     * 银行卡列表
     *
     * @param    array   $where          查询的条件
     * @param    array   $parameter      分页参数
     * @param    int     $page_number    分页数
     * @return   array
     *                   data       数据
     *                   page       分页对象
     *
     * @since: 2017年1月11日 上午10:22:02
     * @author: lyx
     */
    public function getList($where,$parameter,$page_number){
        //查询分页数据并返回数据和分页对象
        $data =  $this->getPage($this, $where, $parameter, $order='id desc', $page_number);
    
        return $data;
    }
    
    /**
    * 验证银行卡号唯一性
    *
    * @return   bool    验证通过或失败
    *
    * @since: 2017年1月10日 上午11:04:45
    * @author: lyx
    */
    protected function checkUnique(){
        
        $where = array(
                'user_no'   => I('user_no'),
                'bank_no'   => I('bank_no')
        );
        $item = M('Bank')->where($where)->find();
        if($item){
            return $item['id'] == I('id') ? true : false;
        }else{
            return true;
        }
    }
}