<?php
namespace Common\Controller;
use Common\Controller\BaseController;
use Common\Conf\Constants;

/**
 * Home 基类控制器
 *
 * @since: 2016年12月9日 下午4:09:42
 * @author: lyx
 * @version: V1.0.0
 */
class HomeBaseController extends BaseController {

	protected $user;
	/**
	 * 初始化
	 *
	 * @since: 2016年12月9日 下午4:09:56
	 * @author: lyx
	 * @change xl
	 */
	public function _initialize() {
		parent::_initialize();

		//获取当前请求的路径
		$action_name = MODULE_NAME  . '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
		//网站状态为关闭，不能进行任何操作

		if ($this->sys_config['WEB_SYSTEM_STATE'] == Constants::NO) {
			if($action_name != 'Home/Index/close'){
				$this->redirect('Index/close');
			}
		} else {
			if($action_name == 'Home/Index/close'){
				$this->redirect('Login/index');
			}

			//验证用户是否登录,未登录跳转到登录页面
			if (empty($_SESSION['user']['id'])) $this->redirect('Login/index');

			//验证用户是否锁定,未登录跳转到登录页面
			$isLock = D('User')->field('is_locked')->find($_SESSION['user']['id']);
			if (!$isLock || $isLock['is_locked']==Constants::YES) {
				session('user',null);
				$this->redirect('Login/index');
			}
		}

		//获取会员信息
		$user = M('User')->find($_SESSION['user']['id']);
		$this->assign('user', $user);
		$this->user = $user;

		//未激活会员不能访问
		if ($user['is_activated'] == Constants::NO) {
		    $allow_conts = array("User", "Index", "Login");
		    if (!in_array(CONTROLLER_NAME, $allow_conts) && $action_name != 'Home/Group/activated') {
		        $this->redirect('Index/unAuth', array('tips'=>'会员尚未激活，不能进行操作！'));
		    }
		}
		
		//获取会员菜单
		/* if ($user['is_activated'] == Constants::YES) {
		    $user_menu = C("UserMenu");
		} else {
		    $user_menu = C("InActiveUserMenu");
		} */
		$user_menu = C("UserMenu");
		$this->assign("user_menu", $user_menu);
	}

	/**
	 * 构造函数
	 *
	 * @since: 2016年12月9日 下午4:10:48
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
	 * @since: 2017年1月9日 下午6:00:29
	 * @author: lyx
	 */
	public function addLog($remark, $extend=''){
		$log = array(
		        'role'         => Constants::LOG_ROLE_USER,
		        'username'     => $_SESSION['user']['user_no'],
		        'type'         => Constants::LOG_TYPE_OPERATION,
		        'remark'       => $remark,
		        'extend'       => $extend,
		        'operate_time' => curr_time(),
		);
		M('Log')->add($log);
	}
}
