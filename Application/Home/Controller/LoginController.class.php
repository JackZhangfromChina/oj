<?php
namespace Home\Controller;
use Common\Controller\PublicBaseController;
use Common\Conf\Constants;
use Think\Verify;

/**
 * 前台登录控制器
 *
 * @since: 2016年12月9日 下午4:49:34
 * @author: lyx
 * @version: V1.0.0
 */
class LoginController extends PublicBaseController {
    
    /**
     * 初始化
     *
     * @since: 2016年12月27日 下午5:21:44
     * @author: lyx
     * @change xl
     */
    public function _initialize() {
        parent::_initialize();

        //网站状态为关闭，不能登录
        if ($this->sys_config['WEB_SYSTEM_STATE'] == Constants::NO) {
            $this->redirect('Index/close');
        }
    }
    
    /**
    * 登录页面
    *
    * @since: 2016年12月9日 下午4:51:22
    * @author: lyx
    */
    public function index(){
        //显示title
        $this->assign('title', '会员登录' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
        //显示seo关键字
        $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
        //显示seo描述
        $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);
        //显示忘记密码
        $this->assign('isforget', $this->sys_config['SYSTEM_FORGET_PASSWORD']);
        //显示网站logo
        if (trim($this->sys_config['WEB_LOGO'])) {
            $this->assign('logo', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_LOGO']);
        }
        //显示网站默认头像
        if (trim($this->sys_config['WEB_DEFAULT_AVATAR'])) {
            $this->assign('head_portrait', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_DEFAULT_AVATAR']);
        }
        //验证是否记住
        if(!empty(cookie('username'))){
           $this->assign('remember', cookie('username'));
        }
        $this->display();
    }
    
    /**
    * 生成验证码图片
    *
    * @since: 2016年12月9日 下午4:53:05
    * @author: lyx
    */
    public function verify(){
         
        ob_end_clean();
        $verify = new Verify();
        $verify->length = 4;
        $verify->codeSet = '0123456789';
        $verify->entry();
    }
    
    /**
    * 验证会员登录
    *
    * @since: 2016年12月9日 下午4:53:26
    * @author: lyx
    */
    public function login(){
        echo 234;exit;
        /* $verify=new Verify();
        if(!$verify->check($_POST['verify_code'])){
            $result = array(
                    'status'  => false,
                    'message' => '验证码错误！请重新输入'
            );
            $this->ajaxReturn($result);
        } */
        
        //验证是否记住我
        if(!empty(I('remb'))){
            cookie('username',trim($_POST['username']),$_SERVER['REQUEST_TIME']+3600*24);
        }else{
            cookie('username',null);
        }

        $where['user_no'] = $_POST['username'];
        $user=M('User')->where($where)->find();

        if($user && password_verify($_POST['password'], $user['password'])){
            if($user['is_locked']){
                $result = array(
                        'status'  => false,
                        'message' => '账号已被锁定，请联系管理员解锁'
                );
                $this->ajaxReturn($result);
            }
            
            //保存会员信息到session
            $_SESSION['user']=array(
                'id'        => $user['id'],
                'user_no'   => $user['user_no']
            );
            
            $data = array(
                    'id'            => $user['id'],
                    'login_time'    => curr_time()
            );
            
            //保存登录信息
            M('User')->save($data);
            
            $result = array(
                    'status'  => true,
                    'message' => '登录成功!'
            );
            
            //登录日志
            $this->addLog(Constants::LOG_TYPE_LOGIN, $user['user_no'], $user['user_no'] . "登录会员系统。登录的ip为：" . client_ip());
        }else{
            $result = array(
                'status'  => false,
                'message' => '您输入的帐号或密码有误！请重新输入'
            );
        }

        $this->ajaxReturn($result);
    }
    
    /**
    * 忘记密码
    *
    * @since: 2017年2月13日 下午1:20:28
    * @author: Wang Peng
    */
    public function forgetPass(){
       //验证表单提交
       if(IS_POST){
          $verify = new Verify();
          $getcode = trim(I('code'));
          if(empty($getcode)){
             $result=array(
                   'status' => false,
                   'message' => '验证码不能为空'
                );
          }else{
               if (!$verify->check($getcode)) {
                  $result=array(
                       'status' => false,
                       'message' => '验证码不正确'
                    );
              }else{
                  $code = rand(1000,9999);
                  $user_no = trim(I('user_no'));
                  $phone = trim(I('phone'));
                  $email = trim(I('email'));

                  $userinfo = M('User')->where(array('user_no' => $user_no))->find();
                  if(!$userinfo){
                     $result=array(
                               'status' => false,
                               'message' => '用户不存在'
                            );
                     $this->ajaxReturn($result);
                  }

                  if(!empty($email) && $this->sys_config['SYSTEM_FORGET_PASSWORD'] == Constants::FORGET_PASSWORD_EMAIL){
                      $useremail = M('UserExtend')->where(array('user_no' => $user_no))->getField('email');
                      
                      if($useremail != $email){
                         $result=array(
                               'status' => false,
                               'message' => 'email不匹配'
                            );
                        $this->ajaxReturn($result);
                      }

                      $content = '您的验证码是'.$code.'，请在'.Constants::FORGET_PASSWORD_TIME.'分钟内使用';
                      $title = '验证码';
                      if(!$this->sendMail($user_no , $code , $email , $title , $content)){
                           $result=array(
                               'status' => false,
                               'message' => '邮件发送失败,请联系后台管理员！'
                            );
                      }else{
                            $result=array(
                                'status' => true,
                                'user_no' => $user_no
                            );
                             //日志
                            $this->addLog(Constants::LOG_TYPE_OPERATION, $user_no, '邮件发送成功');
                      }
                  }else if(!empty($phone) && $this->sys_config['SYSTEM_FORGET_PASSWORD'] == Constants::FORGET_PASSWORD_MESSAGE){
                     $userphone = M('User')->where(array('user_no' => $user_no))->getField('phone');
                     
                     if($userphone != $phone){
                        $result=array(
                               'status' => false,
                               'message' => '手机号不匹配'
                            );
                        $this->ajaxReturn($result);
                     }

                      $msg_param=array(
                       'code' => strval($code),
                       'time' => strval(Constants::FORGET_PASSWORD_TIME) 
                      );

                     if($this->verificationCode($user_no,$phone,'MESSAGE_FORGET',json_encode($msg_param))){
                        $result=array(
                            'status' => true,
                            'user_no' => $user_no
                        );
                         //日志
                         $this->addLog(Constants::LOG_TYPE_OPERATION, $user_no, '短信发送成功');
                    }else{
                        $result=array(
                            'status' => false,
                            'message' => '验证码发送失败，请联系后台管理员！'
                        );
                    }  
                  }  
              }
          }
          
          $this->ajaxReturn($result);
       }

        //验证信息发送方式
        if($this->sys_config['SYSTEM_FORGET_PASSWORD'] == 'ignore' ){
            $this->redirect('Login/index');
        }else{
            //显示title
            $this->assign('title', '忘记密码' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
            //显示seo关键字
            $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
            //显示seo描述
            $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);

            //验证忘记密码字段
            $this->assign('isforget', $this->sys_config['SYSTEM_FORGET_PASSWORD']);
            $this->display();
        } 
    }
    
   /**
    * 确认验证码
    *
    * @since: 2017年2月13日 下午1:20:28
    * @author: Wang Peng
    */
    public function confirmVerify(){
        if(IS_POST){
          $code = trim(I('code'));
          $authcode = M('AuthCode')->where(array('user_no'=>I('user_no')))->find();
          
          if($code != $authcode['code']){
             $result=array(
                   'status' => false,
                   'message' => '验证码不正确'
                );
          }else{
                $user=M('User')->where(array('user_no'=>I('user_no')))->find();
                if($user['is_locked']){
                   $result = array(
                       'status'  => false,
                       'message' => '账号已被锁定，请联系管理员解锁'
                   );
                }else{
                    if($user){
                        $sendtime = $authcode['send_time'];
                        $currdate = curr_time();

                        $minute=floor((strtotime($currdate)-strtotime($sendtime))%86400/60); 
                                
                        if($minute > 5 || $authcode['is_validate'] == Constants::YES){
                            $result = array(
                                'status'  => false,
                                'message' => '验证码已失效，请重新找回密码！'
                            );
                        }else{

                            //保存会员信息到session
                            $_SESSION['user']=array(
                                'id'        => $user['id'],
                                'user_no'   => $user['user_no']
                            );
                            
                            $data = array(
                                'id'            => $user['id'],
                                'login_time'    => curr_time()
                            );
                            
                            //保存登录信息
                            M('User')->save($data);
                            $codedata = array(
                                   'is_validate' => '1',
                                   'validate_time' => curr_time()
                                );
                           $res = M('AuthCode')->where(array('user_no'=>$user['user_no']))->save($codedata);

                           if($res !== false){
                               $result = array(
                                    'status'  => true,
                                    'user_no' => $user['user_no'],
                                    'message' => '安全验证通过!'
                                );
                                
                                //日志
                                $this->addLog(Constants::LOG_TYPE_OPERATION, $user['user_no'], '安全验证通过');
                               } 
                        } 
                    }else{
                        $result = array(
                            'status'  => false,
                            'message' => '您输入的帐号或密码有误！请重新输入'
                        );
                    }
                }
          }
          $this->ajaxReturn($result);
        }
      
      //验证信息发送方式
      if($this->sys_config['SYSTEM_FORGET_PASSWORD'] == 'ignore' ){
          $this->redirect('Login/index');
      }else{
          //显示title
          $this->assign('title', '安全验证' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
          //显示seo关键字
          $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
          //显示seo描述
          $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);
          $this->assign('user_no',I('user_no'));
          $this->display();
      }
    }
    
   /**
    * 重置密码
    *
    * @since: 2017年2月14日 上午10:18:28
    * @author: Wang Peng
    */
    public function resetPwd(){

        if(IS_POST){
            if(I('conpassword') != I('password')){
               $result = array(
                    'status'  => false,
                    'message' => '新密码和确认密码不一致！'
               );
            }else{
                $data=array(
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)    
                );
                $res = D('User')->where(array('user_no'=>I('user_no')))->save($data);

                if($res !== false){
                   $result = array(
                        'status'  => true,
                        'message' => '重置密码成功！'
                    );
                   //日志
                   $this->addLog(Constants::LOG_TYPE_OPERATION, I('user_no'), '重置密码成功');
                }
            } 
            $this->ajaxReturn($result);
        }

        //验证信息发送方式
        if($this->sys_config['SYSTEM_FORGET_PASSWORD'] == 'ignore' ){
             $this->redirect('Login/index');
        }else{
             //显示title
             $this->assign('title', '重置密码' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
             //显示seo关键字
             $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
             //显示seo描述
             $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);
             $this->assign('user_no',I('user_no'));
             $this->display();
        }  
    }
    
    /**
    * 退出登录
    *
    * @since: 2016年12月9日 下午4:54:28
    * @author: lyx
    */
    public function logout(){
        session('user',null);
//         $this->success('退出成功，前往登录页面',U('Login/index'));
        $this->redirect('Login/index');
    }
    
    /**
     * 语言选择
     */
    public function setLang(){
        $langArr = array('zh_cn','zh_tw','en');
        if (IS_POST) { //数据提交
            $lang_type = I('lang_type');
            if(in_array($lang_type, $langArr)){
                cookie('lang_type', $lang_type);
            }else{
                cookie('lang_type', $this->sys_config['SYSTEM_DEFAULT_LANGUAGE']);
            }
            
            $result = array(
                    'status'  => true,
                    'message' => '语言切换成功'
            );
            $this->ajaxReturn($result);
        }
    }
}