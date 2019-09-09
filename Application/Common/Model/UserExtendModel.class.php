<?php
namespace Common\Model;
use Common\Model\BaseModel;

/**
 * 会员扩展信息 Model
 *
 * @since: 2016年12月13日 下午3:23:38
 * @author: lyx
 * @version: V1.0.0
 */
class UserExtendModel extends BaseModel{
    //定义自动验证
    protected $_validate = array(
            array('email', 'checklen', '邮箱必须在6~50个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(6,50)),
            array('qq', 'checklen', 'QQ必须在5~12个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(5,12)),
            array('telephone', 'checklen', '电话必须在4~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(4,20)),
            array('zip_code', 'checklen', '邮编必须在2~10个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,10)),
            array('alipay', 'checklen', '支付宝账号必须在6~50个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(6,50)),
            array('wechat', 'checklen', '微信账号必须在6~50个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(6,50)),
            array('birthday', 'checklen', '生日必须在2~10个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,10)),
            array('id_card', 'checklen', '身份证必须在4~20个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(4,20)),
            array('address', 'checklen', '地址必须在2~50个字符之间', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)),
            /* array('email', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)),
            array('qq', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('telephone', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('zip_code', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,10)),
            array('alipay', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)),
            array('wechat', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)),
            array('birthday', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,10)),
            array('id_card', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,20)),
            array('address', 'checklen', '注册项输入不合法', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH, array(2,50)), */
    );
    
    //自动完成
    protected $_auto = array (
            array('update_time','curr_time',self::MODEL_BOTH,'callback'), // 对update_time字段在新增/修改时写入当前时间
    );
}