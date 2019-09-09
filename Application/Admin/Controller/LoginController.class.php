<?php
namespace Admin\Controller;
use Common\Controller\PublicBaseController;
use Think\Verify;
use Common\Conf\Constants;

/**
 * 后台登录控制器
 *
 * @since: 2016年12月9日 下午4:49:34
 * @author: lyx
 * @version: V1.0.0
 */
class LoginController extends PublicBaseController {
    
    /**
    * 登录页面
    *
    * @since: 2016年12月9日 下午4:51:22
    * @author: lyx
    */
    public function index() {
        //显示title
        $this->assign('title', '管理员登录' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
        //显示seo关键字
        $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
        //显示seo描述
        $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);
        //显示网站logo
        if (trim($this->sys_config['WEB_LOGO'])) {
            $this->assign('logo', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_LOGO']);
        }
        //显示网站默认头像
        if (trim($this->sys_config['WEB_DEFAULT_AVATAR'])) {
            $this->assign('head_portrait', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_DEFAULT_AVATAR']);
        }
        $this->display();
    }
    
    /**
    * 生成验证码图片
    *
    * @since: 2016年12月9日 下午4:53:05
    * @author: lyx
    */
    public function verify() {
        ob_end_clean();
        $verify = new Verify();
        $verify->length = 4;
        $verify->codeSet = '0123456789';
        $verify->entry();
    }
    
    /**
    * 验证管理员登录
    *
    * @since: 2016年12月9日 下午4:53:26
    * @author: lyx
    */
    public function login() {
        /* $verify=new Verify();
        if(!$verify->check(I('post.verify_code'))){
            $result = array(
                    'status'  => false,
                    'message' => '验证码错误！请重新输入'
            );
            $this->ajaxReturn($result);
        } */
    
        $where = array(
                'username'  => I('post.username')
        );
        $admin = M('Admin')->where($where)->find();
        if ($admin && password_verify($_POST['password'], $admin['password'])) {
            if ($admin['status'] == Constants::NORMAL) {
                //保存管理员信息到session
                $_SESSION['admin'] = array(
                        'id'        => $admin['id'],
                        'username'  => $admin['username'],
                        'is_super'  => $admin['is_super']
                );
                
                $data = array(
                        'id'    => $admin['id'],
                        'login_time'  => curr_time()
                );
                
                //保存登录信息
                M('Admin')->save($data);
                
                $result = array(
                        'status'  => true,
                        'message' => '登录成功!'
                );
                
                //登录日志
                $this->addLog(Constants::LOG_ROLE_ADMIN, $admin['username'], $admin['username'] . "登录管理系统。登录的ip为：" . client_ip());
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '你的账号被禁止登陆，请与超极管理员联系'
                );
            }
        } else {
            $result = array(
                'status'  => false,
                'message' => '您输入的帐号或密码有误！请重新输入'
            );
        }
        $this->ajaxReturn($result);
    }
    
    /**
    * 退出登录
    *
    * @since: 2016年12月9日 下午4:54:28
    * @author: lyx
    */
    public function logout() {
        session('admin',null);
//         $this->success('退出成功，前往登录页面',U('Login/index'));
        $this->redirect('Login/index');
    }
}