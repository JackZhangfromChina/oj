<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 管理员模块管理控制器
 *
 * @since: 2016年12月15日 下午3:34:28
 * @author: lyx
 * @version: V1.0.0
 */
class AdminController extends AdminBaseController {
    
    /**
     * 管理员列表页面
     *
     * @since: 2017年1月4日 下午3:22:35
     * @author: lyx
     */
    public function index() {
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //时间
        if ($start_date && $end_date) {
            $where['update_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['update_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['update_time'] = array('ELT', $end_date . ' 23:59:59');
        }
        //关键词
        if ($keyword) {
            $where['username'] = array('like', '%' . $keyword . '%') ;
        }
    
        //查询数据
        $admins = D('Admin')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $admins['data']);
        $this->assign('page', $admins['page']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
    * 管理员个人信息页面（包括修改）
    *
    * @since: 2016年12月20日 上午11:24:09
    * @author: lyx
    */
    public function info() {
        if (IS_POST) { //数据提交
            $Admin = D("Admin"); // 实例化对象
    	    if (!$Admin->create()) {
    	        $result = array(
    	            'status'  => false,
    	            'message' => $Admin->getError()
    	        );
    	    } else {
    	        $Admin->id = $_SESSION['admin']['id']; // 获取当前登录对象的id
    	        $re = $Admin->save(); // 写入数据到数据库
    	        if ($re !== false) {
    	            $result = array(
    	                'status'  => true,
    	                'message' => '操作成功'
    	            );
    	            //操作日志
    	            $this->addLog('修改个人信息。');
    	        } else {
    	            $result = array(
    	                'status'  => false,
    	                'message' => '操作失败！'
    	            );
    	        }
    	    }
    	    $this->ajaxReturn($result);
        }
        
        $this->display();
    }
    
    /**
     * 管理员修改密码页面（包括修改密码的提交处理）
     *
     * @since: 2016年12月20日 上午11:24:45
     * @author: lyx
     */
    public function editPassword()
    {
        if (IS_POST) { //数据提交
            $where = array(
    	            'id'       => $_SESSION['admin']['id']
    	    );
    	    $admin=M('Admin')->where($where)->find();
    	    
    	    if ($admin && password_verify($_POST['old_password'], $admin['password'])) {
	            $data = array(
	                    'id'           => $_SESSION['admin']['id'],
	                    'password'     => password_hash($_POST['password'], PASSWORD_DEFAULT),
	                    'update_time'  => curr_time()
	            );
	            //保存密码
	            M('Admin')->save($data);
	    
	            $result = array(
	                    'status'  => true,
	                    'message' => '密码修改成功!'
	            );
	            
	            //操作日志
	            $this->addLog('修改密码。');
    	    } else {
    	        $result = array(
    	                'status'  => false,
    	                'message' => '旧密码不正确，密码修改失败!'
    	        );
    	    }
    	    $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 添加管理员页面（包括添加操作）
     *
     * @since: 2017年1月4日 下午4:15:38
     * @author: lyx
     */
    public function add() {
        
        if (IS_POST) { //数据提交
            $Admin = D("Admin"); // 实例化对象
            if (!$Admin->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Admin->getError()
                );
            } else {
                $Admin->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $re = $Admin->add(); // 写入数据到数据库
                
                if ($re !== false) {
                    
                    //保存管理员关系组信息
                    $groups = json_decode($_POST['auth_group'],true);
                    foreach ($groups as $group) {
                        $groupList[] = array(
                                'uid'       => $re,
                                'group_id'  => $group
                        );
                    }
                    $res_g = M('AuthGroupAccess')->addAll($groupList);
                    
                    if ($res_g !== false) {
                        $result = array(
                                'status'  => true,
                                'message' => '添加成功'
                        );
                        //操作日志
                        $this->addLog('添加用户名为：' . I('post.username') . '的管理员。', $re);
                    } else {
                        $result = array(
                                'status'  => false,
                                'message' => '添加失败！'
                        );
                    }
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '添加失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    
        $where = array(
                'status'  => Constants::NORMAL
        );
        $groups = M('AuthGroup')
                ->where($where)
                ->order('id asc')
                ->select();
        
        if ($groups) {
            $this->assign('groups',$groups);
            $this->display();
        } else {
            $this->redirect('Index/unAuth', array('tips'=>'系统中没有管理员角色，请先添加角色后再进行添加管理员！'));
        }
    }
    
    /**
     * 修改管理员页面（包括修改操作）
     *
     * @since: 2017年1月5日 下午2:02:35
     * @author: lyx
     */
    public function edit() {
    
        if (IS_POST) { //数据提交
            
            if (!empty($_POST['password'])) {
                $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
            } else {
                unset($_POST['password']);
            }
            $re = M('Admin')->save($_POST); // 写入数据到数据库

            if ($re !== false) {

                //保存管理员关系组信息
                $groups = json_decode($_POST['auth_group'],true);
                foreach ($groups as $group) {
                    $groupList[] = array(
                            'uid'       => I('post.id'),
                            'group_id'  => $group
                    );
                }
                M('AuthGroupAccess')->where(array('uid'  => I('post.id')))->delete();
                $res_g = M('AuthGroupAccess')->addAll($groupList);

                if ($res_g !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '修改成功'
                    );
                    //操作日志
                    $this->addLog('修改用户名为：' . I('post.username') . '的管理员信息。', I('post.id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '修改失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '修改失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $where = array(
                'status'  => Constants::NORMAL
        );
        $groups = M('AuthGroup')
                ->where($where)
                ->order('id asc')
                ->select();
        
        $admin = M("Admin")->find(I('get.id'));
        $admin_groups = M('AuthGroupAccess')
                    ->where(array('uid'  => I('get.id')))
                    ->select();
    
        $this->assign('groups',$groups);
        $this->assign('admin',$admin);
        $this->assign('admin_groups',array_column($admin_groups, 'group_id'));
        $this->display();
    }
    
    /**
     * 删除管理员
     *
     * @since: 2017年1月5日 下午3:02:58
     * @author: lyx
     */
    public function del() {
        if (IS_POST) { //数据提交
    
            $old_admin = M('Admin')->find(I('post.id'));
            
            //数据删除
            $re = M("Admin")->delete(I('post.id'));
            $re_a = M('AuthGroupAccess')->where(array('uid'  => I('post.id')))->delete();
            if ($re !== false && $re_a !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                
                //操作日志
                $this->addLog('删除用户名为：' . $old_admin['username'] . '的管理员。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '删除失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
}