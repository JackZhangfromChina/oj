<?php
namespace Common\Controller;
use Common\Controller\BaseController;
use Common\Conf\Constants;
/**
 * 通用基类控制器
 *
 * @since: 2016年12月9日 下午4:11:15
 * @author: lyx
 * @version: V1.0.0
 */
class PublicBaseController extends BaseController {
    
    /**
    * 初始化
    *
    * @since: 2016年12月9日 下午4:12:21
    * @author: lyx
    */
    public function _initialize() {

		parent::_initialize();
	}
	
    /**
    * 构造函数
    *
    * @since: 2016年12月9日 下午4:24:14
    * @author: lyx
    */
	public function __construct() {
	    parent::__construct();
	}
	
	/**
	 * 函数用途描述
	 *
	 * @param    int       role        用户角色:0表示会员，1表示管理员
	 * @param    string    username    用户名
	 * @param    string    remark      日志备注
	 *
	 * @since: 2017年1月9日 下午6:02:33
	 * @author: lyx
	 */
	public function addLog($role, $username, $remark){
	    $log = array(
	            'role'         => $role,
	            'username'     => $username,
	            'type'         => Constants::LOG_TYPE_LOGIN,
	            'remark'       => $remark,
	            'operate_time' => curr_time(),
	    );
	    M('Log')->add($log);
	}
}

