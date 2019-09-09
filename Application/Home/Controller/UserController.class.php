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
class UserController extends HomeBaseController {

    /**
    * 个人信息（包括信息保存）
    *
    * @since: 2017年1月18日 上午11:15:20
    * @author: lyx
    */
    public function info() {
        //信息提交
        if (IS_POST) {
            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
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
            /* $user_data = array(
                    'id'        => $this->user['id'],
                    'phone'     => $data['phone'],
                    'realname'  => $data['realname']
            );
            //保存会员信息及会员扩展信息
            $res_user = M('User')->save($user_data); */
            //获取会员扩展信息个数
            $user_extend_count = M('UserExtend')->where(array('user_no'=>$this->user['user_no']))->count();
            if ($user_extend_count > 0) {
                $result = $UserExtend->save($data);
            } else {
                $result = $UserExtend->add($data);
            }

            if (/* $res_user !== false &&  */$result !== false) {
                $res = array(
                    'status'  => true,
                    'message' => '个人资料修改成功!'
                );

                //操作日志
                $this->addLog('修改了个人资料');
            } else {
                $res = array(
                    'status'  => false,
                    'message' => '个人资料修改失败!'
                );
            }
            $this->ajaxReturn($res);
        }

        //会员扩展信息
        $user_extend = M('UserExtend')->find($this->user['user_no']);
        //会员报单中心信息
        $service_center = M('ServiceCenter')->find($this->user['user_no']);
        //会员级别
        $level  = M('UserLevel')->field('title')->find($this->user['user_level_id']);

        //会员升级判断
        $where = array(
                'id'        => array('gt',$this->user['user_level_id']),
                'status'    => Constants::NORMAL
        );
        $count = M('UserLevel')->where($where)->count('id');
        $is_upgrade = $count>0 ? Constants::YES : Constants::NO;

        //页面参数渲染
        $this->assign('user_extend',$user_extend);
        $this->assign('service_center',$service_center);
        $this->assign('level',$level);
        $this->assign('is_upgrade',$is_upgrade);
        $this->assign('user_enroll_item',json_decode($this->sys_config['USER_ENROLL_ITEM'],true));
        $this->assign('is_open_service_center',$this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']);
        $this->display();
    }

    /**
     * 修改登录密码（包括修改操作）
     *
     * @since: 2017年1月18日 下午2:15:20
     * @author: lyx
     */
    public function changePasswd() {
        if (IS_POST) {
            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
                $result = array(
                        'status'  => false,
                        'message' => '会员登录信息变更，操作失败！'
                );
                $this->ajaxReturn($result);
            }
            $data = I('post.');

            //判断新密码与确认密码是否一致
            if ($data['re_password'] != $data['password']) {

                $result = array(
                        'status'  => false,
                        'message' => '2次密码不一致，修改失败！'
                );
                $this->ajaxReturn($result);
            }
            //判断旧密码是否正确
            if (!password_verify($_POST['old_password'], $this->user['password'])) {
                $result = array(
                        'status'  => false,
                        'message' => '旧密码不正确，修改失败！'
                );
                $this->ajaxReturn($result);
            }

            //修改密码
            $user_data = array(
                    'id'        => $this->user['id'],
                    'password'  => password_hash($_POST['password'], PASSWORD_DEFAULT),
            );
            $result = M('User')->save($user_data);

            if ($result !== false) {
                $res = array(
                    'status'  => true,
                    'message' => '登录密码修改成功!'
                );
                //操作日志
                $this->addLog('修改了登录密码');
            } else {
                $res = array(
                    'status'  => false,
                    'message' => '登录密码修改失败!'
                );
            }
            $this->ajaxReturn($res);
        }

        $this->display();
    }

    /**
     * 修改安全码（包括修改操作）
     *
     * @since: 2017年1月18日 下午2:52:32
     * @author: lyx
     */
    public function changeTrade() {
        if (IS_POST) {
            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
                $result = array(
                        'status'  => false,
                        'message' => '会员登录信息变更，操作失败！'
                );
                $this->ajaxReturn($result);
            }
            $data = I('post.');
            //判断新密码与确认密码是否一致
            if ($data['password'] != $data['re_password']) {

                $result = array(
                        'status'  => false,
                        'message' => '2次密码不一致，修改失败！'
                );
                $this->ajaxReturn($result);
            }
            //判断旧密码是否正确
            if (!password_verify($_POST['old_password'], $this->user['two_password'])) {
                $result = array(
                        'status'  => false,
                        'message' => '旧密码不正确，修改失败！'
                );
                $this->ajaxReturn($result);
            }

            //修改密码
            $user_data = array(
                    'id'            => $this->user['id'],
                    'two_password'  => password_hash($_POST['password'], PASSWORD_DEFAULT),
            );
            $result = M('User')->save($user_data);

            if ($result !== false) {
                $res = array(
                        'status'  => true,
                        'message' => '安全码修改成功!'
                );

                //操作日志
                $this->addLog('修改了安全码');
            } else {
                $res = array(
                        'status'  => false,
                        'message' => '安全码修改失败!'
                );
            }
            $this->ajaxReturn($res);
        }

        $this->display();
    }

    /**
     * 账户信息
     *
     * @since: 2016年12月20日 下午15:26:00
     * @author: xielu
     *
     * @since: 2017年1月18日 上午11:10:45
     * @updater: lyx
     */
    public function account() {
        //会员会员级别
        $level =  M('UserLevel')->field('title')->find($this->user['user_level_id']);

        //已确认的总提现
        $where['status'] = Constants::OPERATE_STATUS_CONFIRM;
        $where['user_no'] = $this->user['user_no'];
        $withdraw = M('Withdraw')->where($where)
                    ->field('IFNULL(SUM(amount),0) as sum_total')
                    ->find();
        $remit= M('Remit')->where($where)
            ->field('IFNULL(SUM(amount),0) as sum_total')
            ->find();
        $this->assign('level',$level);
        $this->assign('withdraw',$withdraw);
        $this->assign('remit',$remit);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

    /**
     * 申请报单中心
     *
     * @since: 2017年1月18日 下午6:44:25
     * @author: lyx
     */
    public function serviceCenter() {
        if (IS_POST) {
            if (!$this->sys_config['SYSTEM_OPEN_SERVICE_CENTER']) {
                $result = array(
                        'status'  => false,
                        'message' => '系统没有开启报单中心，不能进行申请！'
                );
                $this->ajaxReturn($result);
            }
            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
                $result = array(
                        'status'  => false,
                        'message' => '会员登录信息变更，操作失败！'
                );
                $this->ajaxReturn($result);
            }

            $service_center =  M('ServiceCenter')->find($this->user['user_no']);
            //有申请记录
            if ($service_center) {
                if ($service_center['status'] == Constants::OPERATE_STATUS_INITIAL) {
                    //申请中
                    $result = array(
                            'status'  => false,
                            'message' => '已提交申请，尚未审核!'
                    );
                    $this->ajaxReturn($result);
                } else if ($service_center['status'] == Constants::OPERATE_STATUS_CONFIRM) {
                    //已是报单中心
                    $result = array(
                            'status'  => false,
                            'message' => '你已是报单中心!'
                    );
                    $this->ajaxReturn($result);
                } else {
                    //更新报单申请
                    $data = array(
                            'user_no'   => $this->user['user_no'],
                            'status'    => Constants::OPERATE_STATUS_INITIAL,
                            'update_time'  => curr_time()
                    );
                    $u_result = M('ServiceCenter')->save($data);
                }

            } else {
                //增加报单中心申请
                $data = array(
                        'user_no'   => $this->user['user_no'],
                        'status'    => Constants::OPERATE_STATUS_INITIAL,
                        'add_time'  => curr_time(),
                        'update_time'  => curr_time()
                );
                $u_result = M('ServiceCenter')->add($data);
            }

            if ($u_result !==false) {
                $result = array(
                        'status'  => true,
                        'message' => '报单中心申请成功!'
                );

                //操作日志
                $this->addLog('申请成为报单中心');
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '报单中心申请修改失败!'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 会员升级
     *
     * @since: 2017年1月18日 下午6:17:34
     * @updater: lyx
     */
    public function upgrade() {
        //升级操作逻辑处理
        if (IS_POST) {
            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
                $result = array(
                    'status'  => false,
                    'message' => '会员登录信息变更，操作失败！'
                );
                $this->ajaxReturn($result);
            }
            //参数验证
            if (!I('post.level_id')) {
                $res = array(
                    'status'  => false,
                    'message' => '参数错误!'
                );
                $this->ajaxReturn($res);
            }
            //升级的前置验证
            if ($this->user['user_level_id']>=I('post.level_id')) {
                $res = array(
                    'status'  => false,
                    'message' => '当前级别已经到达所需升级级别!'
                );
                $this->ajaxReturn($res);
            }

            //会员当前注册金额
            $curr_level  = M('UserLevel')->field('investment')->find($this->user['user_level_id']);
            $level  = M('UserLevel')->find(I('post.level_id'));
            //升级所需注册币
            if ($this->sys_config['SYSTEM_OPEN_RETURN']) {
                $money = $level['investment'];
            } else {
                $money = $level['investment']-$curr_level['investment'];
            }

            //会员账户余额验证
            if ($this->user['eb_account'] < $money) {
                $res = array(
                    'status'  => false,
                    'message' => '注册币不足，升级失败!'
                );
                $this->ajaxReturn($res);
            }

            //业绩记录
            $market = array(
                'user_no'       => $this->user['user_no'],
                'user_level_id' => I('post.level_id'),
                'market_type'   => Constants::MARKET_TYPE_UPGRADE,
                'amount'        => $money,
                'status'        => Constants::NO,
                'add_time'      => curr_time(),
                'return_number' => 0,
                'return_time'   => curr_time()
            );
            $marketId = D('MarketRecord')->add($market);

            //会员信息变更
            $user_data = array(
                'id'            => $this->user['id'],
                'user_level_id' => I('post.level_id'),
                'investment'    => $level['investment'],
                'eb_account'    => array('exp',"eb_account - $money")
            );
            $result_user = M('User')->save($user_data);

            //记录会员账户变更信息
            $record = array(
                'user_no'       => $this->user['user_no'],
                'account_type'  => Constants::ACCOUNT_TYPE_EB,
                'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                'amount'        => $money,
                'balance'       => $this->user['eb_account'] - $money,
                'remark'        => '会员升级至' . $level['title'] .',扣减注册币：' . $money,
                'add_time'      => curr_time()
            );
            $res_account = M('AccountRecord')->add($record);

            //结算方式为秒结，进行奖金结算
            if ($this->sys_config['SYSTEM_SETTLEMENT_METHOD'] == Constants::SETTLEMENT_METHOD_SECOND) {
                $market_record = D('MarketRecord')->find($marketId);
                A('Common/Service')->settlement($market_record);
            }

            //返回操作结果
            if ($marketId>0 && $result_user!==false && $res_account!==false) {
                $res = array(
                    'status'  => true,
                    'message' => '升级成功!'
                );

                //操作日志
                $this->addLog('成功升级至' . $level['title']);
            }else{
                $res = array(
                    'status'  => false,
                    'message' => '升级失败!'
                );
            }
            $this->ajaxReturn($res);
        }

        //会员当前注册金额
        $curr_level  = M('UserLevel')->field('investment')->find($this->user['user_level_id']);

        //会员可升级的级别
        $where = array(
            'id'        => array('gt',$this->user['user_level_id']),
            'status'    => Constants::NORMAL
        );
        $levels = M('UserLevel')->where($where)->select();
        //升级所需注册币及奖项设置解析
        foreach($levels as $k=>$level) {
            //升级所需注册币
            if ($this->sys_config['SYSTEM_OPEN_RETURN']) {
                $levels[$k]['money'] = $level['investment'];
            } else {
                $levels[$k]['money'] = $level['investment']-$curr_level['investment'];
            }
            //解析推荐奖设置
            if ($this->sys_config['AWARD_OPEN_RECOMMEND'] == Constants::YES) {
                $levels[$k]['recommend_award'] = $this->_parseAwardJson($level['recommend_award']);
            }
            //解析领导奖设置
            if ($this->sys_config['AWARD_OPEN_LEADER'] == Constants::YES) {
                $levels[$k]['leader_award'] = $this->_parseAwardJson($level['leader_award']);
            }
            //解析见点奖设置
            if ($this->sys_config['AWARD_OPEN_POINT'] == Constants::YES) {
                $levels[$k]['point_award'] = $this->_parseAwardJson($level['point_award']);
            }
            //解析层奖设置
            if ($this->sys_config['AWARD_OPEN_FLOOR'] == Constants::YES) {
                $levels[$k]['floor_award'] = $this->_parseAwardJson($level['floor_award']);
            }
        }

        //封装页面需要的系统配置信息
        $config = array(
            'is_open_touch_award'   => $this->sys_config['AWARD_OPEN_TOUCH'],
            'is_open_service_award'   => $this->sys_config['AWARD_OPEN_SERVICE'],
            'is_open_recommend_award'   => $this->sys_config['AWARD_OPEN_RECOMMEND'],
            'is_open_leader_award'   => $this->sys_config['AWARD_OPEN_LEADER'],
            'is_open_point_award'   => $this->sys_config['AWARD_OPEN_POINT'],
            'is_open_floor_award'   => $this->sys_config['AWARD_OPEN_FLOOR'],
            'is_open_service_center'   => $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'],
            'is_open_mall'   => $this->sys_config['SYSTEM_OPEN_MALL'],
            'is_open_return'   => $this->sys_config['SYSTEM_OPEN_RETURN']
        );

        $this->assign('levels',$levels);
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 解析奖项设置的json串
     *
     * @param    string  $award_json 需要解析的json串
     * @return   array   解析后的数组
     *               from    开始
     *               to      结束
     *               value   对应的值
     *
     * @since: 2016年12月22日 下午12:04:14
     * @author: lyx
     */
    private function _parseAwardJson($award_json){
        $award_arr = json_decode($award_json,true);
        $new_awards = array();
        foreach ($award_arr as $key => $award) {
            $key_arr=explode("-",$key);
            if ((count($key_arr)==1 || count($key_arr)==2 ) && $key_arr[0]!=0) {
                $new_awards[] = array(
                    'from'  => intval($key_arr[0]),
                    'to'    => intval($key_arr[1]) ? intval($key_arr[1]) : intval($key_arr[0]),
                    'value' => floatval($award)
                );
            }
        }
        return $new_awards;
    }
}