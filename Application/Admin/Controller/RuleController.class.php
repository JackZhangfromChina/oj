<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;

/**
 * 权限模块管理控制器
 *
 * @since: 2017年1月5日 下午3:09:00
 * @author: lyx
 * @version: V1.0.0
 */
class RuleController extends AdminBaseController {
    
    /**
     * 权限列表页面
     *
     * @since: 2017年1月7日 上午10:06:21
     * @author: lyx
     */
    public function index(){
        $list=D('AuthRule')->getTreeData('tree','id','title','id','parent_id');
        $this->assign('list', $list);
        $this->display();
    }
    
    /**
     * 添加规则页面（包括添加操作）
     *
     * @since: 2017年1月7日 上午10:27:22
     * @author: lyx
     */
    public function add() {
    
        if (IS_POST) { //数据提交
            $AuthRule = D("AuthRule"); // 实例化对象
    
            if (!$AuthRule->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $AuthRule->getError()
                );
            } else {
                $re = $AuthRule->add(); // 写入数据到数据库
    
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '添加成功！'
                    );
                    //操作日志
                    $this->addLog('添加规则。规则名称为：' . I('post.title') . '，规则约束为：' . I('post.name') . '。', $re);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '添加失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    
        $this->assign('parent_id',I('get.parent_id'));
        $this->display();
    }
    
    /**
     * 修改规则页面（包括修改操作）
     *
     * @since: 2017年1月7日 上午10:23:14
     * @author: lyx
     */
    public function edit() {
        if (IS_POST) { //数据提交
            $AuthRule = D("AuthRule"); // 实例化对象
    
            $old_rule = M('AuthRule')->find(I('post.id'));
            if (!$AuthRule->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $AuthRule->getError()
                );
            } else {
                $re = $AuthRule->save(); // 写入数据到数据库
    
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '修改成功！'
                    );
                    //操作日志
                    $this->addLog('修改规则。原信息【规则名称：' . $old_rule['title'] . '，规则约束：' . $old_rule['name'] . '】。新信息【规则名称：' . I('post.title') . '，规则约束：' . I('post.name') . '】。', I('post.id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '修改失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    
        $rule = M("AuthRule")->find(I('get.id'));
        $this->assign('rule',$rule);
        $this->display();
    }
    
    /**
     * 删除规则
     *
     * @since: 2017年1月7日 上午10:26:02
     * @author: lyx
     */
    public function del() {
        if (IS_POST) { //数据提交
    
            //查询是否有角色包含此规则
            $sbu_count = M('AuthRule')
                    ->where(array('parent_id'  => I('post.id')))
                    ->count('id');
            if ($sbu_count > 0) {
                $result = array(
                        'status'  => false,
                        'message' => '此规则下有子规则，不能删除！'
                );
                $this->ajaxReturn($result);
            }
            
            //查询是否有角色包含此规则
            $count = M('AuthGroup')
                    ->where(array('rules'  => array('like','%"'.I('post.id').'"%')))
                    ->count('id');
            if ($count > 0) {
                $result = array(
                        'status'  => false,
                        'message' => '有角色包含此规则，不能删除！'
                );
                $this->ajaxReturn($result);
            }
    
            $rule = M('AuthRule')->find(I('post.id'));
            //数据删除
            $re = M("AuthRule")->delete(I('post.id'));
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                
                //操作日志
                $this->addLog('删除约束为' . $rule['name'] . '的规则。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '删除失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
    
    /**
     * 角色列表页面
     *
     * @since: 2017年1月5日 下午3:09:54
     * @author: lyx
     */
    public function roleList() {
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
            $where['title'] = array('like', '%' . $keyword . '%') ;
        }
    
        //查询数据
        $list = M('AuthGroup')
                ->where($where)
                ->order('id asc')
                ->select();
    
        //返回页面的数据
        $this->assign('list', $list);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
     * 添加角色页面（包括添加操作）
     *
     * @since: 2017年1月5日 下午4:11:03
     * @author: lyx
     */
    public function addRole() {
        
        if (IS_POST) { //数据提交
            $AuthGroup = D("AuthGroup"); // 实例化对象
            
            if (!$AuthGroup->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $AuthGroup->getError()
                );
            } else {
                $AuthGroup->rules = implode(',', $_POST['rule_ids']);
                $re = $AuthGroup->add(); // 写入数据到数据库
                
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '添加成功！'
                    );
                    //操作日志
                    $this->addLog('添加角色。角色名称为：' . I('post.title') . '。', $re);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '添加失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    
        // 获取规则数据
        $rules = D('AuthRule')->getTreeData('level', 'id', 'title', 'id', 'parent_id');
        $this->assign('rules',$rules);
        $this->display();
    }
    
    /**
     * 修改角色页面（包括修改操作）
     *
     * @since: 2017年1月5日 下午6:02:44
     * @author: lyx
     */
    public function editRole() {
        if (IS_POST) { //数据提交
            $AuthGroup = D("AuthGroup"); // 实例化对象
        
            if (!$AuthGroup->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $AuthGroup->getError()
                );
            } else {
                $role = M('AuthGroup')->find(I('post.id'));
                
                $AuthGroup->rules = implode(',', $_POST['rule_ids']);
                $re = $AuthGroup->save(); // 写入数据到数据库
        
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '修改成功！'
                    );
                    //操作日志
                    $this->addLog('修改名称为' . $role['title'] . '的角色。修改后的名称为' . I('post.title') . '。', I('post.id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '修改失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
        
        $group = M("AuthGroup")->find(I('get.id'));
        $group['rules_data'] = explode(',', $group['rules']);
        // 获取规则数据
        $rules = D('AuthRule')->getTreeData('level', 'id', 'title', 'id', 'parent_id');
        
        $this->assign('group',$group);
        $this->assign('rules',$rules);
        $this->display();
    }
    
    /**
     * 删除角色
     *
     * @since: 2017年1月7日 上午9:46:13
     * @author: lyx
     */
    public function delRole() {
        if (IS_POST) { //数据提交
    
            //查询角色下是否有管理员
            $count = M('AuthGroupAccess')
                    ->where(array('group_id'  => I('post.id')))
                    ->count();
            
            if ($count > 0) {
                $result = array(
                        'status'  => false,
                        'message' => '角色下存在管理员，不能删除！'
                );
                $this->ajaxReturn($result);
            }
            
            $role = M('AuthGroup')->find(I('post.id'));
            //角色删除
            $re = M("AuthGroup")->delete(I('post.id'));
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除名称为' . $role['title'] . '的角色。', I('post.id'));
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