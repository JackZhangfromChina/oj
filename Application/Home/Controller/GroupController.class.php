<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Conf\Constants;

/**
 * 会员信息管理控制器
 *
 * @since: 2016年12月14日 上午9:26:15
 * @author: lyx
 * @version: V1.0.0
 */
class GroupController extends HomeBaseController {
//    public function t()
//    {
//        $UserLevel =  D('UserLevel')->field('id,touch_max,touch_award')->select();
//        $Dao = M();
//        $re = $Dao->query("select sum(amount) as amounts  from mlmcms_reward_record where user_no='mlmcms_3ep' and `type`=0 and to_days(add_time) = to_days(now())");
//        $amount = $re[0]['amounts'];
//        foreach($UserLevel as $lv)
//        {
//            if($lv['id']==2)
//            {
//                $touch_max = $lv['touch_max'];
//                $touch_award = $lv['touch_award'];
//                break;
//            }
//        }
//        $touch_max = $touch_max;
//        if ($touch_max > $amount) {
//            echo 11;
//        }
//
//    }
    /**
     * 注册会员（包括注册逻辑）
     *
     * @since: 2017年1月19日 下午2:35:40
     * @author: lyx
     */
    public function register() {
        //注册会员信息保存
        if (IS_POST) {
            //会员登录信息变更
            if (I('post.curr_user') != $this->user['user_no']) {
                $result = array(
                    'status'  => false,
                    'message' => '会员登录信息变更，操作失败！'
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
            $data['user_no'] = $this->sys_config['USER_PREFIX'] . $data['user_no'];
            $user_model = D('User');

            //验证会员编号
            if ($user_model->checkExist('user_no',$data['user_no'])) {
                $result = array(
                    'status'  => false,
                    'message' => '会员编号已存在，注册失败！'
                );
                $this->ajaxReturn($result);
            }
            //验证会员手机号
            if ($user_model->checkExist('phone',$data['phone'])) {
                $result = array(
                        'status'  => false,
                        'message' => '会员手机号已存在，注册失败！'
                );
                $this->ajaxReturn($result);
            }
            //验证推荐人是否存在
            if (!$user_model->checkExist('user_no',$data['recommend_no'])) {
                $result = array(
                    'status'  => false,
                    'message' => '推荐人不存在，注册失败！'
                );
                $this->ajaxReturn($result);
            }

            //检测安置关系
            $parent = M('User')->field('left_no,right_no,path')->where(array("user_no" => I('post.parent_no')))->find();
            //安置人不存在或当前会员不可见
            if (!$parent || !$this->sys_config['USER_CROSS_REGION'] && strpos($parent['path'], $this->user['path']) === false) {
                $result = array(
                    'status'  => false,
                    'message' => '安置人不存在，注册失败！'
                );
                $this->ajaxReturn($result);
            }

            if (!$parent['left_no']) {
                //左区有空位，必须安置在左区
                $data['location'] = Constants::LOCATION_LEFT;
            } elseif ($data['location']==Constants::LOCATION_LEFT || $parent['right_no'] && $data['location']==Constants::LOCATION_RIGHT) {
                //开启自动滑落
                if ($this->sys_config['SYSTEM_AUTO_SLIDE']) {
                    //获取安置位置
                    $data['parent_no'] = $this->_autoSlide($data['parent_no']);
                    $data['location'] = Constants::LOCATION_LEFT;

                    //更新父节点对象
                    $parent = $user_model->field('left_no,right_no,path')->where(array('user_no'=>$data['parent_no']))->find();
                } else {
                    $result = array(
                        'status'  => false,
                        'message' => '安置区域被占用，注册失败！'
                    );
                    $this->ajaxReturn($result);
                }
            }

            //会员级别信息
            $level = D('UserLevel')->find($data['user_level_id']);

            //开启实注册并且注册币不足
            if($this->sys_config['USER_IS_REAL_REGISTER'] && $this->user['eb_account'] < $level['investment']) {
                $result = array(
                    'status'    => false,
                    'message'   => '注册币不足'
                );
                $this->ajaxReturn($result);
            }

            //推荐人对象
            $recommend = $user_model->field('rec_floor')->where(array('user_no'=>$data['recommend_no']))->find();
            //新会员位置路径
            $path = $parent['path'] . $data['location'];
            //新会员层数
            $floor = strlen($path);
            //新会员代数
            $rec_floor =$recommend['rec_floor']+1;//代数
            //封装新会员对象
            $userArr = array(
                'user_no'       => $data['user_no'],
                'realname'      => $data['realname'],
                'phone'         => $data['phone'],
                'password'      => password_hash($data['password'], PASSWORD_DEFAULT),
                'two_password'  => password_hash($data['two_password'], PASSWORD_DEFAULT),
                'parent_no'     => $data['parent_no'],
                'location'      => $data['location'],
                'path'          => $path,
                'user_level_id' => $data['user_level_id'],
                'recommend_no'  => $data['recommend_no'],
                'register_no'   => $this->user['user_no'],
                'floor'         => $floor,
                'rec_floor'     => $rec_floor,
                'investment'    => $level['investment'],
                'is_activated'  => Constants::NO,
                'add_time'      => curr_time(),
                'sms_code'      => $data['sms_code']
            );
            //报单中心写入
            if ($data['service_center_no']) {
                $userArr['service_center_no'] = $data['service_center_no'];
            }
            //奖励购物币
            if ($this->sys_config['SYSTEM_OPEN_MALL']) {
                $userArr['tb_account'] = $level['tb_reward'];
            }
            //会员信息存储
            $res_user = $user_model->add($userArr);
            //会员扩展信息存储
            $UserExtend->user_no = $data['user_no'];
            $res_user_extend = $UserExtend->add();

            //父节点保存新节点信息
            if ($data['location'] == Constants::LOCATION_LEFT) {
                $parent_data = array(
                    'left_no'=>$data['user_no']
                );
            } else {
                $parent_data = array(
                    'right_no'=>$data['user_no']
                );
            }
             D('User')->where(array('user_no'=>$data['parent_no']))->save($parent_data);

            //存储安置关系和推荐关系
            $res_recommend = $this->saveRecommendNexus($data['user_no'], $data['recommend_no']);//会员推荐关系
            $res_parent = $this->saveParentNexus($data['user_no'], $data['parent_no']);//会员安置关系

            //是否实注册
            if($this->sys_config['USER_IS_REAL_REGISTER']) {
                //激活会员
                $this->_userActivate($this->user['user_no'], $data, $level);
            }

            if ($res_user!==false && $res_user_extend!==false && $res_parent!==false && $res_recommend!==false && $res_parent!==false) {
                $result = array(
                    'status'  => true,
                    'message' => '恭喜你，注册成功！'
                );

                //操作日志
                $this->addLog('成功注册了会员' . $data['user_no'], $res_user);
            } else {
                $result = array(
                    'status'  => false,
                    'message' => '数据出现异常，注册失败！'
                );
            }

            $this->ajaxReturn($result);
        }
       $getField = I('get.');

        //会员级别
        $where = array(
            'status'    => Constants::NORMAL
        );
        $levels = M('UserLevel')
            ->field('id,title')
            ->where($where)
            ->order('id asc')
            ->select();
        //会员级别存在
        if ($levels) {
            //启用系统会员编号，生成系统编号
            if($this->sys_config['USER_OPEN_AUTO_NO']) {
                $auto_no = getRandStr(2);
            }

            //封装页面需要的系统配置信息
            $config = array(
                'user_prefix'   => $this->sys_config['USER_PREFIX'],
                'is_auto_slide'   => $this->sys_config['SYSTEM_AUTO_SLIDE'],
                'is_open_service_center'   => $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']
            );
            if($getField['parent_no']&&$getField['local'])
            {
                $config['parent_no'] = trim($getField['parent_no']);
                $config['local'] = $getField['local'];
            }

            $this->assign('levels',$levels);
            $this->assign('auto_no',$auto_no);
            $this->assign('config',$config);
            $this->assign('user_enroll_item',json_decode($this->sys_config['USER_ENROLL_ITEM'],true));

            $sms_codes = M('SmsCode')->select();
            $this->assign('smscodes', $sms_codes);
            $this->display();
        } else {
            //没有开放的会员级别
            $this->redirect('Index/unAuth', array('tips'=>'系统中没有开放的会员级别，不能注册会员，请与管理员联系！'));
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
            if (strcasecmp($v['user_no'],$pId['left_no']) ==0 ||strcasecmp($v['user_no'],$pId['right_no']) == 0) {
                if($v['left_no']||$v['right_no']) {
                    $v['children'] = $this->_getTree($data, $v);
                    if (!$v['left_no']) {
                        $children= array(
                                'user_no'       => '点击注册',
                                'left_no'       => '',
                                'right_no'      => '',
                                'left_market'   => 0,
                                'right_market'  => 0,
                                'touch_market'  => 0,
                                'investment'    => 0,
                                'local'         => Constants::LOCATION_LEFT,
                                'parent_no'     => $v['user_no']
                        );
                        array_push($v['children'], $children);
                    } elseif (!$v['right_no']) {
                        $children= array(
                                'user_no'       => '点击注册',
                                'left_no'       => '',
                                'right_no'      => '',
                                'left_market'   => 0,
                                'right_market'  => 0,
                                'touch_market'  => 0,
                                'investment'    => 0,
                                'local'         => Constants::LOCATION_RIGHT,
                                'parent_no'     => $v['user_no']
                        );
                        array_push($v['children'], $children);
                    }
                } else {
                    $childrens = array(
                            array(
                                    'user_no'       => '点击注册',
                                    'left_no'       => '',
                                    'right_no'      => '',
                                    'left_market'   => 0,
                                    'right_market'  => 0,
                                    'touch_market'  => 0,
                                    'investment'    => 0,
                                    'local'         => Constants::LOCATION_LEFT,
                                    'parent_no'     => $v['user_no']
                            ),
                            array(
                                    'user_no'       => '点击注册',
                                    'left_no'       => '',
                                    'right_no'      => '',
                                    'left_market'   => 0,
                                    'right_market'  => 0,
                                    'touch_market'  => 0,
                                    'investment'    => 0,
                                    'local'         => Constants::LOCATION_RIGHT,
                                    'parent_no'     => $v['user_no']
                            )
                    );
                    $v['children'] = $childrens;
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
     * @since: 2016年12月19日 上午9:26:15
     * @author: xielu
     */
    public function placeSystem() {
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
            ->where(array('user_no'=>$this->user['user_no']))
            ->find();
        }
        //获取子会员
        $parent_nexus = D('ParentNexus')->field('user_no')->where(array('parent_no'=>$parent['user_no'],'floor'=>array('LT',Constants::HOME_LEVEL)))->select();

        if ($parent_nexus) {
            //获取子会员详细信息
            $nexus_str = implode(",",array_column($parent_nexus, 'user_no'));
            $users =  D('User')->field('user_no,left_no,right_no,location,left_market,parent_no,right_market,touch_market,investment')
            ->where(array('user_no'=>array('in',$nexus_str)))
            ->order('location asc')
            ->select();

            //获取子会员数结构
            $tree = $this->_getTree($users,$parent);
            $data['children'] = $tree;
        }

        if (!$parent['left_no'] && !$parent['right_no']) {
            $childrens = array(
                    array(
                            'user_no'       => '点击注册',
                            'left_no'       => '',
                            'right_no'      => '',
                            'left_market'   => 0,
                            'right_market'  => 0,
                            'touch_market'  => 0,
                            'investment'    => 0,
                            'local'         => Constants::LOCATION_LEFT,
                            'parent_no'     => $parent['user_no']
                    ),
                    array(
                            'user_no'       => '点击注册',
                            'left_no'       => '',
                            'right_no'      => '',
                            'left_market'   => 0,
                            'right_market'  => 0,
                            'touch_market'  => 0,
                            'investment'    => 0,
                            'local'         => Constants::LOCATION_RIGHT,
                            'parent_no'     => $parent['user_no']
                    )
            );
            $data['children'] = $childrens;
        } else if (!$parent['left_no']) {
            $children= array(
                            'user_no'       => '点击注册',
                            'left_no'       => '',
                            'right_no'      => '',
                            'left_market'   => 0,
                            'right_market'  => 0,
                            'touch_market'  => 0,
                            'investment'    => 0,
                            'local'         => Constants::LOCATION_LEFT,
                            'parent_no'     => $parent['user_no']
                    );
            array_push($data['children'], $children);
        } elseif (!$parent['right_no']) {
            $children= array(
                            'user_no'       => '点击注册',
                            'left_no'       => '',
                            'right_no'      => '',
                            'left_market'   => 0,
                            'right_market'  => 0,
                            'touch_market'  => 0,
                            'investment'    => 0,
                            'local'         => Constants::LOCATION_RIGHT,
                            'parent_no'     => $parent['user_no']
                    );
            array_push($data['children'], $children);
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
     * @since: 2016年12月19日 上午9:26:15
     * @author: xielu
     *
     * @since: 2017年2月20日 上午11:33:49
     * @updater: lyx
     */
    public function recommendSystem() {
        //获取当前查看的顶点
        $user_no = I('get.no');
        if ($user_no) {
            $parent = D('User')->field('user_no,left_no,right_no,left_market,parent_no,right_market,touch_market,investment')
            ->where(array('user_no'=>$user_no))
            ->find();
        }
        //获取当前用户顶点
        if (!$parent) {
            $parent = D('User')->field('user_no,left_no,right_no,left_market,parent_no,right_market,touch_market,investment')
            ->where(array('user_no'=>$this->user['user_no']))
            ->find();
        }

        //获取子会员
        $recommend_nexus = D('RecommendNexus')->field('user_no')->where(array('recommend_no'=>$parent['user_no'],'rec_floor'=>array('LT',Constants::HOME_LEVEL)))->select();

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
     * 团队会员列表
     *
     * @since: 2016年12月19日 上午9:26:15
     * @author: xielu
     */
    public function placeList()
    {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        //关键词
        if ($keyword) {
            $where[C('DB_PREFIX').'parent_nexus.user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //是否激活
        if ((isset($_GET["status"]) || isset($_POST["status"])) &&$status!=-1) {
            $where['is_activated'] = $status;
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
        $parent_no = C('DB_PREFIX').'parent_nexus.parent_no';
        $where[$parent_no] = $this->user['user_no'];
        //查询数据
        $records = D('ParentNexus')->getMemberList($where, I(),C('DB_PREFIX').'user ON '.C('DB_PREFIX').'parent_nexus.user_no = '.C('DB_PREFIX').'user.user_no',
        $this->sys_config['SYSTEM_PAGE_NUMBER'],C('DB_PREFIX').'user.*,'.C('DB_PREFIX').'parent_nexus.user_no as puno,'.C('DB_PREFIX').'parent_nexus.parent_no as pno');
        $this->assign('statistics', $records['statistics']);
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 推荐会员列表
     *
     * @since: 2016年12月19日 上午9:26:15
     * @author: xielu
     *
     * @since: 2017年1月19日 下午12:00:50
     * @updater: lyx
     */
    public function recommendList() {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        //关键词
        if ($keyword) {
            $where[C('DB_PREFIX').'recommend_nexus.user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //是否激活
        if ((isset($_GET["status"]) || isset($_POST["status"])) &&$status!=-1) {
            $where['is_activated'] = $status;
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
        $parent_no = C('DB_PREFIX').'recommend_nexus.recommend_no';
        $where[$parent_no] = $this->user['user_no'];

        //查询数据
        $records = D('RecommendNexus')->getMemberList($where, I(),C('DB_PREFIX').'user ON '.C('DB_PREFIX').'recommend_nexus.user_no = '.C('DB_PREFIX').'user.user_no',
        $this->sys_config['SYSTEM_PAGE_NUMBER'],C('DB_PREFIX').'user.*,'.C('DB_PREFIX').'recommend_nexus.user_no as puno,'.C('DB_PREFIX').'recommend_nexus.recommend_no as pno');

        //数据渲染
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 下属会员
     *
     * @since: 2016年12月19日 上午9:26:15
     * @author: xielu
     *
     * @since: 2017年1月19日 上午11:57:08
     * @updater: lyx
     */
    public function member() {
        //系统是否开启报单中心，没有开启报单中心不能进行任何操作
        if ($this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'] == Constants::NO) {
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启报单中心，不能进行操作！'));
        }

        //会员报单中心信息
        $service_center = M('ServiceCenter')->find($this->user['user_no']);
        //不是报单中心不能进行操作
        if ($service_center['status'] != Constants::OPERATE_STATUS_CONFIRM) {
            $this->redirect('Index/unAuth', array('tips'=>'您还不是报单中心，不能进行此项操作！'));
        }

        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        //关键词
        if ($keyword) {
            $where['user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //是否激活
        if ((isset($_GET["status"]) || isset($_POST["status"])) &&$status!=-1) {
            $where['is_activated'] = $status;
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
        $where['service_center_no'] = $this->user['user_no'];

        //查询数据
        $records = D('User')->getCenterList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //数据渲染
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 会员激活
     *
     * @since: 2017年1月20日 下午2:04:18
     * @author: lyx
     */
    public function activated() {
        //获取级别谢谢
        $level = D('UserLevel')->find($this->user['user_level_id']);

        if ($this->user['is_activated'] == Constants::YES) {
            $result = array(
                'status'    => false,
                'message'   => '已经是激活状态，不需要再次激活'
            );
            $this->ajaxReturn($result);
        }
        //验证注册币
        if($this->user['eb_account'] < $level['investment']) {
            $result = array(
                'status'    => false,
                'message'   => '注册币不足，激活失败！'
            );
            $this->ajaxReturn($result);
        }

        //激活逻辑处理
        $result = $this->_userActivate($this->user['user_no'], $this->user, $level);

        $this->ajaxReturn($result);
    }

    /**
     * 激活会员
     *
     * @param    string user_no     激活人
     * @param    array  data        待激活的会员信息
     * @param    array  level       待激活级别信息
     * @return   array
     *                  status  操作结果状态
     *                  message 信息描述
     *
     * @since: 2017年1月19日 下午2:20:21
     * @updater: lyx
     */
    private function _userActivate($user_no,$data,$level) {
        //业绩记录
        $market = array(
            'user_no'       => $data['user_no'],
            'user_level_id' => $data['user_level_id'],
            'market_type'   => Constants::MARKET_TYPE_ENROLL,
            'amount'        => $level['investment'],
            'status'        => Constants::NO,
            'add_time'      => curr_time(),
            'return_number' => 0,
            'return_time'   => curr_time()
        );
        $marketId = D('MarketRecord')->add($market);

        if ($user_no == $data['user_no']) {
            $remark = '激活账户,扣减注册币：' . $level['investment'];
        } else {
            $remark = '激活会员' . $data['user_no'] .',扣减注册币：' . $level['investment'];
        }
        //记录会员账户变更信息
        $record = array(
            'user_no'       => $user_no,
            'account_type'  => Constants::ACCOUNT_TYPE_EB,
            'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
            'amount'        => $level['investment'],
            'balance'       => $this->user['eb_account'] - $level['investment'],
            'remark'        => $remark,
            'add_time'      => curr_time()
        );
        $res_account = M('AccountRecord')->add($record);

        //扣减注册币
        $res_user = M('User')->where(array('user_no'=>$user_no))->setDec('eb_account',$level['investment']);

        //激活信息
        $user_activate['activate_time'] = curr_time();
        $user_activate['is_activated'] = Constants::YES;
        $res_activate = M('User')->where(array('user_no'=>$data['user_no']))->save($user_activate);

        //结算方式为秒结，进行奖金结算
        if ($this->sys_config['SYSTEM_SETTLEMENT_METHOD'] == Constants::SETTLEMENT_METHOD_SECOND) {
            //奖金结算
            $market_record = D('MarketRecord')->find($marketId);
            A('Common/Service')->settlement($market_record);
        }

        if ($marketId!==false && $res_account!==false && $res_user!==false && $res_activate!==false) {
            //会员激活成功
            if ($user_no == $data['user_no']) {
                //操作日志
                $this->addLog('账户激活成功');
            }

            //发送短信
            $receiver = array(
                'user_no'       => $data['user_no']
            );
            $this->sendMessage($receiver, 'MESSAGE_ACTIVATE', '', Constants::MESSAGE_TYPE_ACTIVATE);

            return array(
                    'status'  => true,
                    'message' => '激活成功！'
            );
        } else {
            return array(
                'status'  => false,
                'message' => '激活失败！'
            );
        }
    }

    /**
     * 自动滑落
     *
     * @param    string  parent_no   开始滑落的节点
     * @return   array   安置人信息
     *               parent_no   安置人编号
     *               location    安置位置
     *
     * @since: 2017年1月20日 下午3:18:29
     * @author: lyx
     */
    public function _autoSlide($parent_no)
    {
        $left_no = M('User')->getFieldByUserNo($parent_no,'left_no');

        return !$left_no ? $parent_no : $this->_autoSlide($left_no);
    }
}