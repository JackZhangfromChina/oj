<?php
namespace Common\Controller;
use Common\Controller\BaseController;
use Think\Auth;
use Common\Conf\Constants;

/**
 * Admin 基类控制器
 *
 * @since: 2016年12月9日 下午4:08:26
 * @author: lyx
 * @version: V1.0.0
 */
class AdminBaseController extends BaseController {
    /**
    * 初始化
    *
    * @since: 2016年12月9日 下午4:08:45
    * @author: lyx
    */
	public function _initialize(){
	    parent::_initialize();

	    //验证用户是否登录,未登录跳转到登录页面
	    if (empty($_SESSION['admin']['id'])) $this->redirect('Login/index');
	    
	    //如果不是超级管理员，验证权限
	    if ($_SESSION['admin']['is_super'] == Constants::NO) {
	        
	        $auth = new Auth();
	        $rule_name = MODULE_NAME  . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
	        //判断是否是需要验证的aciton
    	    if (in_array($rule_name, C('NO_AUTH_RULES'))) {
    	        //不需要验证
    	    } else {
    	        //进行权限验证
    	        $result = $auth->check($rule_name, $_SESSION['admin']['id']);
    	        if(!$result){
//     	            $this->error('您没有权限访问');
                    if (IS_AJAX) {
    	                $result = array(
                                'status'  => false,
                                'message' => '您没有权限操作！'
                        );
                        $this->ajaxReturn($result);
    	            } else {
    	                $this->redirect('Index/unAuth', array('tips'=>'您没有权限操作！'));
    	            }
    	        }
    	    }
	        
	    }
		
	    //获取管理员信息
	    $admin=M('Admin')->field("username,realname,password,phone,email,nickname")->find($_SESSION['admin']['id']);
		$this->assign('admin', $admin);
		$this->admin = $admin;
		//获取管理员菜单
		$admin_menu = C("AdminMenu");
		$this->assign("admin_menu", $admin_menu);
	}

    /**
    * 构造函数
    *
    * @since: 2016年12月9日 下午4:09:07
    * @author: lyx
    */
	public function __construct() {
	    parent::__construct();
	}
	
	/**
	 * 日志记录
	 *
	 * @param    string  remark  日志备注
	 * @param    string  extend  相关扩展信息
	 *
	 * @since: 2017年1月9日 下午6:02:33
	 * @author: lyx
	 */
	public function addLog($remark, $extend=''){
	    $log = array(
	            'role'         => Constants::LOG_ROLE_ADMIN,
	            'username'     => $_SESSION['admin']['username'],
	            'type'         => Constants::LOG_TYPE_OPERATION,
	            'remark'       => $remark,
	            'extend'       => $extend,
	            'operate_time' => curr_time(),
	    );
	    M('Log')->add($log);
	}
}

