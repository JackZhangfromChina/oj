<?php
namespace Common\Controller;
use Think\Controller;
use Think\Think;
use Common\Library\Alidayu\AliMsgSend;
use Common\Library\HeyskyMsgSend;
use Common\Conf\Constants;
use PHPMailer;

/**
 * Base 基类控制器
 *
 * @since: 2016年12月9日 下午4:05:49
 * @author: lyx
 * @version: V1.0.0
 */
class BaseController extends Controller {

    protected $sys_config;  //数据库中配置的键值对
    protected $db_prefix;   //数据库表前缀
    private $userModel;
    /**
    * 初始化
    *
    * @since: 2016年12月9日 下午4:06:17
    * @author: lyx
    */
    public function _initialize() {

        //获取数据库中配置的code与value
        $configs=M('Config')->field('code,value')->select();
        foreach ($configs as $config) {
            $this->sys_config[$config['code']] = $config['value'];
        }

        //$this->sys_config['WEB_DOMAIN'] = "http://direct_soft.my";

        //获取数据库表前缀
        $this->db_prefix = C("DB_PREFIX");
        $this->userModel = M('User');
    }

   /**
    * 构造函数
    *
    * @since: 2016年12月9日 下午4:06:42
    * @author: lyx
    */
    public function __construct() {
        parent::__construct();
    }

   /**
    * 短信验证码
    *
    * @param $_RecNum string 手机号
    * @param $smsTemplateCode string 模板code
    * @param $_smsParam json 短信模板变量
    * @since: 2017年1月6日 上午9:28:20
    * @author: Wang Peng
    */
   public function verificationCode($userNo,$recNum, $smsTemplateCode, $smsParam){
        //获取code参数
        $template = json_decode($this->sys_config[$smsTemplateCode],true);
        $is_open = $template['is_open'];
        $template_id = $template['template_id'];

        //满足（用户名不为空&&开启短信发送&&对应的操作设置开启&&设置短信模板id）时才进行短信发送处理
        if(!empty($userNo) && $this->sys_config['MESSAGE_OPEN_SEND'] == Constants::YES && $is_open == Constants::YES && !empty($template_id)){

            //对短信接口传参
            if ($this->sys_config['MESSAGE_OPEN_SEND_ALI'] == Constants::YES) {
                //获取操作对应的模板设置参数
                $appKey = $this->sys_config['MESSAGE_APPKEY_ALI'];
                $secreKey = $this->sys_config['MESSAGE_SECRETKEY_ALI'];
                $smsFreeSignName = $this->sys_config['MESSAGE_SIGN_ALI'];
                $re = new AliMsgSend($appKey, $secreKey);
                $res = $re->sendMsg($recNum, $smsParam, $template_id, $smsFreeSignName);
            } elseif ($this->sys_config['MESSAGE_OPEN_SEND_HEYSKY'] == Constants::YES) {
                //获取操作对应的模板设置参数
                $appKey = $this->sys_config['MESSAGE_APPKEY_HEYSKY'];
                $secreKey = $this->sys_config['MESSAGE_SECRETKEY_HEYSKY'];
                $smsFreeSignName = $this->sys_config['MESSAGE_SIGN_HEYSKY'];
                $re = new HeyskyMsgSend($appKey, $secreKey);
                $_template = D('MessageTemplate')->where(['no'=>$template_id])->getField('msg');
                // $_content = "[$smsFreeSignName]" . $_template?:'';
                $_content = $_template?:'';
                $_content = $this->stringFetch($_content, $smsParam);
                $sms_param_arr = json_decode($smsParam,1);
                if (isset($sms_param_arr['sms_code'])) {
                    $recNum = $sms_param_arr['sms_code'].$recNum;
                } else {
                    $sms_code = D('User')->where(['user_no'=>$userNo])->getField('sms_code');
                    $recNum = $sms_code.$recNum;
                }
                $res = $re->sendMsg($recNum, $_content);
            } else {
                $res = [
                    'status'=>false,
                    'msg' => '未知的短信发送驱动器'
                ];
            }

            //验证返回状态
            $except = '';
            $status = Constants::YES;
            if(!$res['status']){
                $except = $res['msg'];
                $status = Constants::NO;
            }
            //写短信记录
            $result=array(
                    'user_no'   => $userNo,
                    'phone'     => $recNum,
                    'type'      => Constants::MESSAGE_TYPE_FORGET,
                    'exception' => $except,
                    'status'    => $status,
                    'send_time' => curr_time(),
                    'param'     => $smsParam,
                    'template'  => $template_id
            );
            $messagetable = M('Message')->add($result);

            //将json参数转成数组
            $codeparam = json_decode($smsParam,true);

            if(!empty($userNo)){
               $result=array(
                    'user_no' => $userNo,
                    'phone' => $recNum,
                    'code' => $codeparam['code'],
                    'is_validate' => Constants::NO,
                    'validate_time' => '',
                    'email' => '',
                    'send_time' => curr_time()
                    );
               $where = array(
                     'user_no' => $userNo,
                     'type' => Constants::MESSAGE_TYPE_FORGET
                );
               $authcode = M('AuthCode')->where($where)->find();
               if($authcode){
                $authcodetable = D('AuthCode')->where($where)->save($result);
               }else{
                $authcodetable = D('AuthCode')->add($result);
               }
            }

            if($status !== false && $messagetable !== false && $authcodetable !== false){
                return true;
            }else{
                return false;
            }
        }
   }

    /**
     * 发送短信
     * @param    array  receiver        短信接收者
     *                      user_no     会员编号
     *                      phone       手机号
     * @param    string template_code   系统设置中短信模板code
     * @param    string sms_param       短信模板变量(json串)
     * @param    int    type            短信发送类型
     *
     *
     * @since: 2017年1月6日 上午10:30:45
     * @author: Wang Peng
     *
     * @since: 2017年1月13日 上午10:45:04
     * @updater: lyx
     */
    public function sendMessage($receiver, $template_code, $sms_param, $type){
        //获取操作对应的模板设置参数
        $template = json_decode($this->sys_config[$template_code],true);
        $is_open = $template['is_open'];
        $template_id = $template['template_id'];

        //满足（短信接收者的会员编号不为空&&开启短信发送&&对应的操作设置开启&&设置短信模板id）时才进行短信发送处理
        if ($receiver['user_no'] !='' && $this->sys_config['MESSAGE_OPEN_SEND'] == Constants::YES && $is_open == Constants::YES && $template_id !='') {
            if (!$receiver['phone']) {
                $receiver['phone'] = M('User')->getFieldByUserNo($receiver['user_no'], 'phone');
            }

            if ($receiver['phone']) {

                //对短信接口传参
                if ($this->sys_config['MESSAGE_OPEN_SEND_ALI'] == Constants::YES) {
                    //获取操作对应的模板设置参数
                    $appKey = $this->sys_config['MESSAGE_APPKEY_ALI'];
                    $secreKey = $this->sys_config['MESSAGE_SECRETKEY_ALI'];
                    $smsFreeSignName = $this->sys_config['MESSAGE_SIGN_ALI'];
                    $re = new AliMsgSend($appKey, $secreKey);
                    $res = $re->sendMsg($receiver['phone'], $sms_param, $template_id, $smsFreeSignName);
                } elseif ($this->sys_config['MESSAGE_OPEN_SEND_HEYSKY'] == Constants::YES) {
                    //获取操作对应的模板设置参数
                    $appKey = $this->sys_config['MESSAGE_APPKEY_HEYSKY'];
                    $secreKey = $this->sys_config['MESSAGE_SECRETKEY_HEYSKY'];
                    $smsFreeSignName = $this->sys_config['MESSAGE_SIGN_HEYSKY'];
                    $re = new HeyskyMsgSend($appKey, $secreKey);
                    $_template = D('MessageTemplate')->where(['no'=>$template_id])->getField('msg');
                    // $_content = "[$smsFreeSignName]" . $_template?:'';
                    $_content = $_template?:'';
                    $_content = $this->stringFetch($_content, $sms_param);
                    $sms_param_arr = json_decode($sms_param,1);
                    if (isset($sms_param_arr['sms_code'])) {
                        $receiver['phone'] = $sms_param_arr['sms_code'].$receiver['phone'];
                    } else {
                        $sms_code = D('User')->where(['user_no'=>$receiver['user_no']])->getField('sms_code');
                        $receiver['phone'] = $sms_code.$receiver['phone'];
                    }
                    $res = $re->sendMsg($receiver['phone'], $_content);
                } else {
                    $res = [
                        'status'=>false,
                        'msg' => '未知的短信发送驱动器'
                    ];
                }

                //验证返回状态
                $except = '';
                $status = Constants::YES;
                if(!$res['status']){
                    $except = $res['msg'];
                    $status = Constants::NO;
                }
                //写短信记录
                $result=array(
                        'user_no'   => $receiver['user_no'],
                        'phone'     => $receiver['phone'],
                        'type'      => $type,
                        'exception' => $except,
                        'status'    => $status,
                        'send_time' => curr_time(),
                        'param'     => $sms_param,
                        'template'  => $template_id
                );
                M('Message')->add($result);
            }
        }
    }

    /**
     * 发送邮箱信息
     * @param    string  $userNo         用户编号
     * @param    string  $code           验证码
     * @param    string  $to             邮箱
     * @param    string title            邮件主题
     * @param    string content          邮箱内容
     *
     * @since: 2017年2月14日 下午2:20:30
     * @author: Wang Peng
     */
    public function sendMail($userNo, $code, $to, $title, $content) {
        //加载文件
        require './Application/Common/Library/PHPMailer/PHPMailerAutoload.php';

        //满足（邮件接收者的会员编号不为空&&开启邮件发送&&SMTP服务器不为空&&SMTP服务器用户名不为空&&SMTP服务器密码不为空&&SMTP服务器链接方式不为空）时才进行短信发送处理
        if(!empty($userNo) && $this->sys_config['MAIL_OPEN_SEND'] == Constants::YES && !empty($this->sys_config['MAIL_HOST']) && !empty($this->sys_config['MAIL_USERNAME']) && !empty($this->sys_config['MAIL_PASSWORD']) && !empty($this->sys_config['MAIL_SECURE'])){

            $mail = new PHPMailer; //实例化
            // 装配邮件服务器
            $mail->isSMTP();  //启动SMTP
            $mail->Host = $this->sys_config['MAIL_HOST']; //SMTP服务器地址
            $mail->SMTPAuth = true; //启用SMTP认证
            $mail->Username = $this->sys_config['MAIL_USERNAME'];//邮箱名称
            $mail->Password = $this->sys_config['MAIL_PASSWORD'];//邮箱密码
            $mail->SMTPSecure = $this->sys_config['MAIL_SECURE'];//发件人地址
            $mail->CharSet = 'utf-8';//邮件头部信息
            $mail->From = $this->sys_config['MAIL_USERNAME'];//发件人是谁
            $mail->AddAddress($to);
            $mail->FromName = $this->sys_config['MAIL_USERNAME'];
            $mail->isHTML(true);//是否是HTML字样
            $mail->Subject = $title;// 邮件标题信息
            $mail->Body = $content;//邮件内容
            // 发送邮件
            if (!$mail->send()) {
               return false;
            } else {
               $result=array(
                    'user_no' => $userNo,
                    'phone' => '',
                    'email' => $to,
                    'code' => $code,
                    'is_validate' => Constants::NO,
                    'validate_time' => '',
                    'send_time' => curr_time()
                    );

               $where = array(
                     'user_no' => $userNo,
                     'type' => Constants::MESSAGE_TYPE_FORGET
                );
               $authcode = M('AuthCode')->where($where)->find();
               if($authcode){
                 $res = D('AuthCode')->where($where)->save($result);
               }else{
                 $res = D('AuthCode')->add($result);
               }

              if($res){
                return true;
              }
            }
        }
    }

    /**
     * 会员推荐关系存储
     *
     * @param    string user_no     新节点会员编号
     * @param    string parent_no   父节点的会员编号（推荐人）
     * @return   bool   推荐关系保存是否成功
     *
     * @since: 2017年1月19日 下午2:33:02
     * @updater: lyx
     */
    public function saveRecommendNexus($user_no,$recommend_no) {
        //封装与当前父节点关系
        $dataList[] = array(
                'user_no'   => $user_no,
                'recommend_no' => $recommend_no,
                'rec_floor'     => 1
        );

        //查找父节点的推荐关系
        $where = array(
                'user_no'   => $recommend_no
        );
        $old_nexus = M('RecommendNexus')->where($where)->select();

        //封装与父节点上级节点的关系
        foreach ($old_nexus as $old) {
            $dataList[] = array(
                    'user_no'   => $user_no,
                    'recommend_no'  => $old['recommend_no'],
                    'rec_floor'     => $old['rec_floor'] + 1
            );
        }

        //保存推荐节点关系
        return M('RecommendNexus')->addAll($dataList);
    }

    /**
     * 会员安置关系
     *
     * @param    string user_no     新节点会员编号
     * @param    string parent_no   父节点的会员编号（安置人）
     * @return   bool   安置关系保存是否成功
     *
     * @since: 2017年1月19日 下午2:20:21
     * @updater: lyx
     */
    public function saveParentNexus($user_no,$parent_no) {

        //封装与当前父节点关系
        $dataList[] = array(
                'user_no'   => $user_no,
                'parent_no' => $parent_no,
                'floor'     => 1
        );

        //查找父节点的安置关系
        $where = array(
                'user_no'   => $parent_no
        );
        $old_nexus = M('ParentNexus')->where($where)->select();

        //封装与父节点上级节点的关系
        foreach ($old_nexus as $old) {
            $dataList[] = array(
                    'user_no'   => $user_no,
                    'parent_no' => $old['parent_no'],
                    'floor'     => $old['floor'] + 1
            );
        }

        //保存安置节点关系
        return M('ParentNexus')->addAll($dataList);
    }

    public function stringFetch($content, $var)
    {
        // $_content = Think::instance('Think\\Template')->parse($content);
        extract(json_decode($var,1), EXTR_OVERWRITE);
        @eval("\$_content = \"$content\";");
        return $_content;
    }
}
