<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;
/**
 * 会员模块管理控制器
 *
 * @since: 2016年12月14日 上午9:20:36
 * @author: lyx
 * @version: V1.0.0
 */
class UserController extends AdminBaseController {

    /**
    * 会员列表页
    *
    * @since: 2017年1月19日 下午2:12:09
    * @author: lyx
    */
    public function index() {
        $level = I('level');
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        //关键词
        if ($keyword) {
            $where['user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //是否激活
        if ((isset($_GET["status"]) || isset($_POST["status"]))&&$status!=-1) {
            $where['is_activated'] = $status;
        }
        //会员级别
        if ((isset($_GET["level"]) || isset($_POST["level"]))&&$level!=-1) {
            $where['user_level_id'] = $level;
        }
        //时间
        if ($start_date && $end_date) {
            $where['add_time'] = array(
                    array('EGT', $start_date . ' 00:00:00'),
                    array('ELT', $end_date . ' 23:59:59')
            ) ;
        } elseif ($start_date) {
            $where['add_time'] = array('EGT', $start_date . ' 00:00:00');
        } elseif ($end_date) {
            $where['add_time'] = array('ELT', $end_date . ' 23:59:59');
        }
        //查询数据
        $records = D('User')->getMemberList($where, I(),C('DB_PREFIX').'user_level ON '.C('DB_PREFIX').'user.user_level_id = '.C('DB_PREFIX').'user_level.id',
                $this->sys_config['SYSTEM_PAGE_NUMBER'],C('DB_PREFIX').'user_level.id as lid,'.C('DB_PREFIX').'user.*,'.C('DB_PREFIX').'user_level.title');

        $levelList = D('UserLevel')->field('id,title')->select();

        //顶点会员
        $top = M('User')->field('id')->min('id');

        $this->assign('statistics', $records['statistics']);
        $this->assign('list', $records['data']);
        $this->assign('levelList', $levelList);
        $this->assign('page', $records['page']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('level', $level);
        $this->assign('top', $top);
        $this->assign('serviceCenter',  $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']);
        $this->display();
    }

    /**
    * 重置登录密码及安全码
    *
    * @since: 2017年1月20日 下午5:58:44
    * @author: lyx
    */
    public function resetPassword() {
        $user_no = I('post.user_no');
        //验证参数
        if (!$user_no) {
            $result = array(
                    'status'  => false,
                    'message' => '参数有误，操作失败！'
            );
            $this->ajaxReturn($result);
        }
        //获取会员信息
        $where = array(
                'user_no'   => $user_no
        );
        $user  = M('User')->field('id')->where($where)->find();
        //验证会员是否存在
        if (!$user) {
            $result = array(
                    'status'  => false,
                    'message' => '会员不存在，操作失败！'
            );
            $this->ajaxReturn($result);
        }

        //修改密码
        $data = array(
                'password'      => password_hash(sha1($this->sys_config['USER_INITIAL_PASSWORD']), PASSWORD_DEFAULT),
                'two_password'  => password_hash(sha1($this->sys_config['USER_INITIAL_PASSWORD']), PASSWORD_DEFAULT),
        );
        $res = D('user')->where($where)->save($data);

        if ($res !== false) {
            $result = array(
                    'status'  => true,
                    'message' => '密码重置成功！'
            );

            //操作日志
            $this->addLog('重置了会员' . $user_no . '的登录密码及安全码');
        } else {
            $result = array(
                    'status'  => false,
                    'message' => '密码重置失败！'
            );
        }

        $this->ajaxReturn($result);
    }

    /**
     * 锁定/解锁
     *
     * @since: 2017年1月20日 下午6:03:31
     * @author: lyx
     */
    public function setLock() {
        $user_no = I('post.user_no');
        $is_locked = I('post.is_locked');
        //验证参数
        if (!$user_no) {
            $result = array(
                    'status'  => false,
                    'message' => '参数有误，操作失败！'
            );
            $this->ajaxReturn($result);
        }
        //获取会员信息
        $where = array(
                'user_no'   => $user_no
        );
        $user  = M('User')->field('id')->where($where)->find();
        //验证会员是否存在
        if (!$user) {
            $result = array(
                    'status'  => false,
                    'message' => '会员不存在，操作失败！'
            );
            $this->ajaxReturn($result);
        }

        //修改锁定状态
        $data = array(
                'is_locked'      => $is_locked
        );
        $res = D('user')->where($where)->save($data);

        if ($res !== false) {
            $result = array(
                    'status'  => true,
                    'message' => '操作成功！'
            );

            if ($is_locked) {
                //操作日志
                $this->addLog('锁定了会员' . $user_no);
            } else {
                //操作日志
                $this->addLog('解锁了会员' . $user_no);
            }

        } else {
            $result = array(
                    'status'  => false,
                    'message' => '操作失败！'
            );
        }

        $this->ajaxReturn($result);
    }

    /**
    * 会员空激活/实激活
    *
    *
    * @since: 2017年1月21日 上午11:25:00
    * @author: lyx
    */
    public function userActivate()
    {
        $user_no = I('post.user_no');
        $is_activated = I('post.is_activated') ? I('post.is_activated') : 1;
        //验证参数
        if (!$user_no) {
            $result = array(
                    'status'  => false,
                    'message' => '参数有误，操作失败！'
            );
            $this->ajaxReturn($result);
        }
        //获取会员信息
        $where = array(
                'user_no'   => $user_no
        );
        $user  = M('User')->where($where)->find();
        //验证会员是否存在
        if (!$user) {
            $result = array(
                    'status'  => false,
                    'message' => '会员不存在，操作失败！'
            );
            $this->ajaxReturn($result);
        }
        if ($user['is_activated']) {
            $result = array(
                    'status'  => false,
                    'message' => '会员已是激活状态！'
            );
            $this->ajaxReturn($result);
        }

        //实激活
        if ($is_activated) {
            //会员级别信息
            $level = D('UserLevel')->find($user['user_level_id']);

            //业绩记录
            $market = array(
                    'user_no'       => $user['user_no'],
                    'user_level_id' => $user['user_level_id'],
                    'market_type'   => Constants::MARKET_TYPE_ENROLL,
                    'amount'        => $level['investment'],
                    'status'        => Constants::NO,
                    'add_time'      => curr_time(),
                    'return_number' => 0,
                    'return_time'   => curr_time()
            );
            $marketId = D('MarketRecord')->add($market);

            //激活信息
            $user_activate['activate_time'] = curr_time();
            $user_activate['is_activated'] = Constants::YES;
            $res_activate = M('User')->where(array('user_no'=>$user['user_no']))->save($user_activate);

            //结算方式为秒结，进行奖金结算
            if ($this->sys_config['SYSTEM_SETTLEMENT_METHOD'] == Constants::SETTLEMENT_METHOD_SECOND) {
                //奖金结算
                $market_record = D('MarketRecord')->find($marketId);
                A('Common/Service')->settlement($market_record);
            }

            $remark = '实激活了会员' . $user['user_no'];
        } else {
            //空激活
            $user_activate['activate_time'] = curr_time();
            $user_activate['is_activated'] = Constants::YES;
            $res_activate = M('User')->where(array('user_no'=>$user['user_no']))->save($user_activate);
            $marketId = true;
            $remark = '空激活了会员' . $user['user_no'];
        }

        if ($marketId!==false && $res_activate!==false) {
            $result = array(
                    'status'  => true,
                    'message' => '激活成功！'
            );

            //操作日志
            $this->addLog($remark);

            //发送短信
            $receiver = array(
                    'user_no'       => $user['user_no'],
                    'phone'         => $user['phone']
            );
            $this->sendMessage($receiver, 'MESSAGE_ACTIVATE', '', Constants::MESSAGE_TYPE_ACTIVATE);
        } else {
            $result = array(
                    'status'  => false,
                    'message' => '激活失败！'
            );
        }
        $this->ajaxReturn($result);
    }

    /**
    * 删除会员
    *
    * @since: 2017年1月21日 上午9:25:13
    * @author: lyx
    */
    public function userDelete()
    {
        $user_no = I('post.user_no');
        //验证参数
        if (!$user_no) {
            $result = array(
                    'status'  => false,
                    'message' => '参数有误，操作失败！'
            );
            $this->ajaxReturn($result);
        }
        //获取会员信息
        $where = array(
                'user_no'   => $user_no
        );
        $user = M('User')->where($where)->find();
        //验证会员是否存在
        if (!$user) {
            $result = array(
                    'status'  => false,
                    'message' => '会员不存在，操作失败！'
            );
            $this->ajaxReturn($result);
        }
        //顶点会员
        $top = M('User')->field('id')->min('id');
        if ($user['id'] == $top['id']) {
            $result = array(
                    'status'  => false,
                    'message' => '顶点会员，不允许删除！'
            );
            $this->ajaxReturn($result);
        }
        //验证会员是否激活
        if ($user['is_activated'] ==Constants::YES) {
            $result = array(
                    'status'  => false,
                    'message' => '该会员已激活，不允许删除！'
            );
            $this->ajaxReturn($result);
        }
        //验证是否是叶子会员
        if ($user['left_no'] || $user['right_no']) {
            $result = array(
                    'status'  => false,
                    'message' => '该会员下安置的有其他会员，不允许删除！'
            );
            $this->ajaxReturn($result);
        }
        //验证是否推荐了其他会员
        $recommend_count  = M('User')->where(array('recommend_no'=> $user_no))->count('id');
        if ($recommend_count>0) {
            $result = array(
                    'status'  => false,
                    'message' => '该会员已推荐了其他会员，不允许删除！'
            );
            $this->ajaxReturn($result);
        }
        /* //验证是否与其他会员进行转账
        $giro_where['_string'] = "(to_user_no = '$user_no' AND from_user_no != '')  OR (from_user_no = '$user_no' AND to_user_no != '')";
        $giro_cout = D('Giro')->where($giro_where)->count('id');
        if ($giro_cout>0) {
            $result = array(
                    'status'  => false,
                    'message' => '该会员与其他会员进行了转账操作，不允许删除！'
            );
            $this->ajaxReturn($result);
        } */

        //删除会员相关信息，日志信息、往来信件、短信发送、验证码保留。
        //删除会员关系
        $res[] = D('User')->where($where)->delete();
        $res[] = D('UserExtend')->where($where)->delete();
        $res[] = D('ParentNexus')->where($where)->delete();
        $res[] = D('RecommendNexus')->where($where)->delete();
        if ($user['location'] == Constants::LOCATION_LEFT) {
            $res[] = D('user')->where(array('user_no'=> $user['parent_no']))->save(array('left_no'=> ''));
        } else {
            $res[] = D('user')->where(array('user_no'=> $user['parent_no']))->save(array('right_no'=> ''));
        }

        $res[] = D('AccountRecord')->where($where)->delete();
        $res[] = D('Address')->where($where)->delete();
        $res[] = D('Bank')->where($where)->delete();
        $res[] = D('Cart')->where($where)->delete();

        $res[] = D('Remit')->where($where)->delete();
        $res[] = D('RewardRecord')->where($where)->delete();
        $res[] = D('ServiceCenter')->where($where)->delete();
        $res[] = D('Withdraw')->where($where)->delete();
        $res[] = D('Pay')->where($where)->delete();

        $orders = D('Order')->field('id')->where($where)->select();
        if ($orders){
            $order_where['order_id']  = array('in', implode(',',array_column($orders, 'id')));
            $res[] = D('OrderGoods')->where($order_where)->delete();
        }
        $res[] = D('Order')->where($where)->delete();

        $giro_map['_string'] = "to_user_no = '$user_no' OR from_user_no = '$user_no'";
        $res[] = D('Giro')->where($giro_map)->delete();

        //操作日志
        $this->addLog('删除了会员' . $user_no );

        foreach ($res as $value) {
            if ($value === false) {
                $result = array(
                        'status'  => false,
                        'message' => '数据出现异常，删除失败！'
                );
                $this->ajaxReturn($result);
            }
        }

        $result = array(
                'status'  => true,
                'message' => '删除成功！'
        );
        $this->ajaxReturn($result);
    }

    /**
     * 会员前台登录
     *
     * @since: 2017年1月20日 下午6:24:53
     * @author: lyx
     */
    public function login() {
        $user_no = I('user_no');
        //验证参数
        if ($user_no) {
            //获取会员信息
            $where = array(
                    'user_no'   => $user_no
            );
            $user  = M('User')->field('id')->where($where)->find();

            //验证会员是否存在
            if ($user) {
                session('user',null);

                $_SESSION['user']=array(
                    'id'        => $user['id'],
                    'user_no'   => $user_no
                );

                //操作日志
                $this->addLog('登录了' . $user_no . '的会员前台');

                redirect(U('Home/Index/index'));
            }
        }

        $this->redirect('Index/unAuth', array('tips'=>'系统数据出现异常，会员不存在，不能登录！'));
    }

    /**
     * 会员资料修改
     *
     * @since: 2017年1月21日 下午1:35:19
     * @author: lyx
     */
    public function edit() {
        //信息提交
        if (IS_POST) {
            //获取会员信息
            $where = array(
                    'user_no'   => I('post.user_no')
            );
            $user = M('User')->field('id')->where($where)->find();
            //验证会员是否存在
            if (!$user) {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
                $this->ajaxReturn($result);
            }

            $UserExtend = D("UserExtend"); // 实例化对象
            //自动验证
            if (!$UserExtend->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $UserExtend->getError()
                );
                $this->ajaxReturn($result);
            }

            $data = I('post.');
            //验证会员手机号
            if (D('User')->checkExist('phone', $data['phone'], $user['id'])) {
                $result = array(
                        'status'  => false,
                        'message' => '会员手机号已存在，资料修改失败！'
                );
                $this->ajaxReturn($result);
            }

            $user['phone'] = $data['phone'];
            $user['realname'] = $data['realname'];
            if ($data['password']) {
                $user['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if ($data['two_password']) {
                $user['two_password'] = password_hash($data['two_password'], PASSWORD_DEFAULT);
            }

            //保存会员信息及会员扩展信息
            $res_user = M('User')->save($user);

            //获取会员扩展信息个数
            $user_extend_count = M('UserExtend')->where($where)->count();
            if ($user_extend_count > 0) {
                $result = $UserExtend->save($data);
            } else {
                $result = $UserExtend->add($data);
            }

            if ($res_user !== false && $result !== false) {
                $res = array(
                        'status'  => true,
                        'message' => '会员资料修改成功!'
                );

                //操作日志
                $this->addLog('修改了会员'. $data['user_no'] .'的信息资料');
            } else {
                $res = array(
                        'status'  => false,
                        'message' => '会员资料修改失败!'
                );
            }
            $this->ajaxReturn($res);
        }

        $user_no = I('user_no');
        //验证参数
        if ($user_no) {
            //获取会员信息
            $where = array(
                    'user_no'   => $user_no
            );
            $user  = M('User')->where($where)->find();

            //验证会员是否存在
            if ($user) {
                //会员扩展信息
                $user_extend = M('UserExtend')->find($user_no);

                //页面参数渲染
                $this->assign('user',$user);
                $this->assign('user_extend',$user_extend);
                $this->assign('user_enroll_item',json_decode($this->sys_config['USER_ENROLL_ITEM'],true));
                $this->display();
            } else {
                $this->redirect('Index/unAuth', array('tips'=>'系统数据出现异常，会员不存在，不能进行修改！'));
            }
        } else {
            $this->redirect('Index/unAuth', array('tips'=>'系统数据出现异常，会员不存在，不能进行修改！'));
        }
    }

    /**
     * 安置树
     *
     * @param $data
     * @param $pId
     * @return array
     * @since: 2016年12月24日 上午9:26:15
     * @author: xielu
     */
    private function _getTree($data, $pId)
    {
        $tree = array();
        foreach($data as $k => $v)
        {
            if(strcasecmp($v['user_no'],$pId['left_no']) == 0 ||strcasecmp($v['user_no'],$pId['right_no']) == 0)
            {
                if($v['left_no']||$v['right_no'])
                {
                    $v['children'] = $this->_getTree($data, $v);
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }

    /**
     * 推荐树
     *
     * @param $data
     * @param $pId
     * @return array
     * @since: 2016年12月24日 上午9:26:15
     * @author: xielu
     */
    private function _recomTree($data, $pId)
    {
        $tree = array();

        foreach($data as $k => $v)
        {
            if(strcasecmp($v['recommend_no'],$pId) == 0)
            {
                $v['children'] = $this->_recomTree($data, $v['user_no']);
                if(!$v['children'])
                {
                    unset($v['children']);
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }

    /**
     * 安置网络
     *
     * @since: 2016年12月16日
     * @author: xielu
     *
     * @since: 2017年2月20日 上午11:01:22
     * @updater: lyx
     */
    public function placeSystem() {
        if (IS_POST) {
            $user = D('User')->field('user_no,left_no,right_no,location,left_market,parent_no,right_market,touch_market,investment')->where(array('user_no'=>I('post.userNo')))->find();
            if (!$user) {
                $result = array(
                    'status'  => false,
                    'message' => '无数据'
                );
            } else {
                $result = array(
                    'status'  => true,
                    'message' => ''
                );
            }
            $this->ajaxReturn($result);
        }

        //获取当前查看的顶点
        $user_no = I('get.no');
        if ($user_no) {
            $parent = D('User')->field('user_no,left_no,right_no,location,left_market,parent_no,right_market,touch_market,investment')
                ->where(array('user_no'=>$user_no))
                ->find();
        }
        //获取根顶点
        if (!$parent) {
            $parent = D('User')->field('user_no,left_no,right_no,location,left_market,parent_no,right_market,touch_market,investment')
                    ->order('id asc')
                    ->find();
        }
        //获取子会员
        $parent_nexus = D('ParentNexus')->field('user_no')->where(array('parent_no'=>$parent['user_no'],'floor'=>array('LT',Constants::ADMIN_LEVEL)))->select();

        if ($parent_nexus) {
            //获取子会员详细信息
            $nexus_str = implode(",",array_column($parent_nexus, 'user_no'));
            $users =  D('User')->field('user_no,left_no,right_no,location,left_market,parent_no,right_market,touch_market,investment')
                ->where(array('user_no'=>array('in',$nexus_str)))
                ->order('location asc')
                ->select();

            //获取子会员数结构
            $data = $this->_getTree($users,$parent);
            $data['children'] = $data;
        }

        $data['user_no'] = $parent['user_no'];
        $data['left_market'] = $parent['left_market'];
        $data['right_market'] = $parent['right_market'];
        $data['touch_market'] = $parent['touch_market'];
        $data['investment'] = $parent['investment'];
        $as = json_encode($data);
        $this->assign("as",$as);
        $this->display();
    }

    /**
     * 推荐网络
     *
     * @since: 2016年12月16日
     * @author: xielu
     *
     * @since: 2017年2月20日 上午11:29:37
     * @updater: lyx
     */
    public function recommendSystem() {
        if (IS_POST) {
            $user = D('User')->field('user_no,left_no,right_no,location,left_market,parent_no,right_market,touch_market,investment')->where(array('user_no'=>I('post.userNo')))->find();
            if (!$user) {
                $result = array(
                        'status'  => false,
                        'message' => '无数据'
                );
            } else {
                $result = array(
                        'status'  => true,
                        'message' => ''
                );
            }
            $this->ajaxReturn($result);
        }

        //获取当前查看的顶点
        $user_no = I('get.no');
        if ($user_no) {
            $parent = D('User')->field('user_no,left_no,right_no,left_market,parent_no,right_market,touch_market,investment')
            ->where(array('user_no'=>$user_no))
            ->find();
        }
        //获取根顶点
        if (!$parent) {
            $parent = D('User')->field('user_no,left_no,right_no,left_market,parent_no,right_market,touch_market,investment')
            ->order('id asc')
            ->find();
        }
        //获取子会员
        $recommend_nexus = D('RecommendNexus')->field('user_no')->where(array('recommend_no'=>$parent['user_no'],'rec_floor'=>array('LT',Constants::ADMIN_LEVEL)))->select();

        if ($recommend_nexus) {
            //获取子会员详细信息
            $nexus_str = implode(",",array_column($recommend_nexus, 'user_no'));
            $users =  D('User')->field('user_no,recommend_no,left_market,right_market,touch_market,investment')
            ->where(array('user_no'=>array('in',$nexus_str)))
            ->select();

            //获取子会员数结构
            $data = $this->_recomTree($users,$parent['user_no']);
            $data['children'] = $data;
        }

        $data['user_no'] = $parent['user_no'];
        $data['left_market'] = $parent['left_market'];
        $data['right_market'] = $parent['right_market'];
        $data['touch_market'] = $parent['touch_market'];
        $data['investment'] = $parent['investment'];
        $as = json_encode($data);
        $this->assign("as",$as);
        $this->display();
    }

    /**
     * 报单中心管理
     *
     * @since: 2016年12月16日
     * @author: xielu
     */
    public function center() {
        //系统是否开启报单中心，没有开启报单中心不能进行任何操作
        if ($this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'] == Constants::NO) {
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启报单中心，不能进行操作！'));
        }

        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        //关键词
        if ($keyword) {
            $where[C('DB_PREFIX').'service_center.user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //状态
        if ((isset($_GET["status"]) || isset($_POST["status"]))&&$status!=-1) {
            $where['status'] = $status;
        }

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

        //查询数据
        $records = D('ServiceCenter')->getCenterList($where, I(),C('DB_PREFIX').'user ON '.C('DB_PREFIX').'service_center.user_no = '.C('DB_PREFIX').'user.user_no',
                        $this->sys_config['SYSTEM_PAGE_NUMBER'],C('DB_PREFIX').'user.realname,'.C('DB_PREFIX').'service_center.*,'.C('DB_PREFIX').'user.phone');
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 确认申请
     *
     * @since: 2017年1月20日 下午5:17:12
     * @author: lyx
     */
    public function confirmServiceCenter() {
        if (IS_POST) { //数据提交

            $where = array(
                    'user_no'        => I('post.user_no')
            );
            $service_center = M("ServiceCenter")->where($where)->find();
            //判断是否重复操作
            if ($service_center['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }

            //保存操作设置
            $data['status'] = Constants::OPERATE_STATUS_CONFIRM;
            $res = M("ServiceCenter")->where($where)->save($data);

            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );

                //操作日志
                $this->addLog('同意了' . $service_center['user_no'] . '的成为报单中心的申请。申请时间为：' . $service_center['update_time']);
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '操作处理失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 驳回申请
     *
     * @since: 2017年1月20日 下午5:21:23
     * @author: lyx
     */
    public function rejectServiceCenter() {
        if (IS_POST) { //数据提交
            $where = array(
                    'user_no'        => I('post.user_no')
            );
            $service_center = M("ServiceCenter")->where($where)->find();
            //判断是否重复操作
            if ($service_center['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }

            //保存操作设置
            $data['status'] = Constants::OPERATE_STATUS_REJECT;
            $res = M("ServiceCenter")->where($where)->save($data);

            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );

                //操作日志
                $this->addLog('驳回了' . $service_center['user_no'] . '的成为报单中心的申请。申请时间为：' . $service_center['update_time']);
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '操作处理失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }
}