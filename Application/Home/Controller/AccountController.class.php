<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Conf\Constants;

/**
 * 账号模块管理控制器
 *
 * @since: 2016年12月27日 下午4:47:56
 * @author: lyx
 * @version: V1.0.0
 */
class AccountController extends HomeBaseController {

    /**
     * 转账页面，包括转账操作处理
     *
     * @since: 2016年12月27日 下午4:50:51
     * @author: lyx
     */
    public function giro() {
    
        $account_type = I('account_type');
        
        switch ($account_type) {
            case Constants::ACCOUNT_TYPE_EB:
                $field = 'eb_account';
                $account_type_name = '注册币';
                break;
            case Constants::ACCOUNT_TYPE_TB:
                $field = 'tb_account';
                $account_type_name = '购物币';
                break;
            case Constants::ACCOUNT_TYPE_CB:
                $field = 'cb_account';
                $account_type_name = '现金';
                break;
            case Constants::ACCOUNT_TYPE_MB:
                $field = 'mb_account';
                $account_type_name = '奖金';
                break;
            case Constants::ACCOUNT_TYPE_RB:
                $field = 'rb_account';
                $account_type_name = '返利';
                break;
        }
        
        if (IS_POST) { //数据提交
    
            $to_user_no = I('post.to_user_no');
            $amount = I('post.amount');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '转账金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            
            $unit = $this->sys_config['SYSTEM_TRADE_MONEY_UNIT'];
            //验证金额的倍数
            if ($amount<=0 || $unit!=0 && ($amount*100)%($unit*100)!=0) {
                $result = array(
                        'status'  => false,
                        'message' => '转账金额必须是' . $unit . '的倍数！'
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
            
            //验证安全码
            if (!password_verify($_POST['password'], $this->user['two_password'])) {
                $result = array(
                        'status'  => false,
                        'message' => '安全码不正确！'
                );
                $this->ajaxReturn($result);
            }
            
            //判断是否是同一人
            if ($to_user_no == $this->user['user_no']) {
                $result = array(
                        'status'  => false,
                        'message' => '不能自己对自己转账！'
                );
                $this->ajaxReturn($result);
            }

            //判断余额
            if ($this->user[$field]<$amount) {
                $result = array(
                        'status'  => false,
                        'message' => '余额不足，转账失败！'
                );
                $this->ajaxReturn($result);
            }
            
            //获取转入的会员信息
            $to = M('User')->getByUserNo($to_user_no);
            if ($to) {
                //表示会员存在，但是两个会员不在同一区域并且系统不允许跨区域操作
                if ($this->sys_config['USER_CROSS_REGION'] == Constants::NO && strpos($to['path'], $this->user['path']) === false && strpos($this->user['path'], $to['path']) === false) {
                    $result = array(
                        'status'  => false,
                        'message' => '不在同一区域不能转账！'
                    );
                    $this->ajaxReturn($result);
                }
                
                $remark = I('post.remark');
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $this->user['user_no'],
                        'to_user_no'    => $to_user_no,
                        'type'          => Constants::GIRO_TYPE_USER,
                        'account_type'  => $account_type,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );     
                
                //封装账户变更记录数据
                $from_record = array(
                        'user_no'       => $this->user['user_no'],
                        'account_type'  => $account_type,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $this->user[$field] - $amount,
                        'remark'        => '给' . $to_user_no . '转账。转账备注：' . $remark,
                        'add_time'      => curr_time()
                );
                $to_record = array(
                        'user_no'       => $to_user_no,
                        'account_type'  => $account_type,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $amount,
                        'balance'       => $to[$field] + $amount,
                        'remark'        => $this->user['user_no'] . '转入。转账备注：' . $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user1 = M('User')
                            ->where(array('id'=>$this->user['id']))
                            ->setDec($field,$amount);
                $res_user2 = M('User')
                            ->where(array('id'=>$to['id']))
                            ->setInc($field,$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account1 = M('AccountRecord')->add($from_record);
                $res_account2 = M('AccountRecord')->add($to_record);
    
                //返回操作的状态
                if ($res_user1!==false && $res_user2!==false && $res_giro!==false && $res_account1!==false && $res_account2!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => $account_type_name . '转账成功。'
                    );
                    
                    //操作日志
                    $this->addLog('给' . $to_user_no . '进行' . $account_type_name . '转账，转账金额为：' . $amount . '。', $res_giro);
                    
                    //短信提醒(转出)
                    $msg_param = array(
                            'from'          => '您',
                            'to'            => $to_user_no,
                            'account_type'  => $account_type_name,
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $this->user['user_no'],
                            'phone'         => $this->user['phone']
                    );
                    $this->sendMessage($receiver, 'MESSAGE_ROLL_OUT', json_encode($msg_param), Constants::MESSAGE_TYPE_ROLL_OUT);
                    
                    //短信提醒(转入)
                    $msg_param = array(
                            'from'          => $this->user['user_no'],
                            'to'            => '您',
                            'account_type'  => $account_type_name,
                            'amount'        => $amount
                    );
                    $receiver2 = array(
                            'user_no'       => $to['user_no'],
                            'phone'         => $to['phone']
                    );
                    $this->sendMessage($receiver2, 'MESSAGE_ROLL_IN', json_encode($msg_param), Constants::MESSAGE_TYPE_ROLL_IN);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，' . $account_type_name . '转账失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，转账失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->assign('account_type',$account_type);
        $this->assign('field',$field);
        $this->assign('account_type_name',$account_type_name);
        $this->assign('unit', $this->sys_config['SYSTEM_TRADE_MONEY_UNIT']);
        $this->display();
    }
    
    /**
     * 转账列表页面
     *
     * @since: 2016年12月29日 上午10:07:17
     * @author: lyx
     */
    public function giroList() {
        $account_type = I('account_type');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
        
        //账号类型
        if ((isset($_GET['account_type']) || isset($_POST['account_type'])) && $account_type != '-1') {
            $where['account_type'] = $account_type;
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
        //关键词
        if ($keyword) {
            $where['to_user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //当前会员的
        $where['from_user_no'] = $this->user['user_no'];
        
        //查询数据
        $giros = D('Giro')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $giros['data']);
        $this->assign('page', $giros['page']);
        $this->assign('statistics', $giros['statistics']);
        $this->assign('account_type', $account_type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
    
    /**
     * 转入列表页面
     *
     * @since: 2016年12月29日 下午4:51:09
     * @author: lyx
     */
    public function intoList() {
        $account_type = I('account_type');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //账号类型
        if ((isset($_GET['account_type']) || isset($_POST['account_type'])) && $account_type != '-1') {
            $where['account_type'] = $account_type;
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
        //关键词
        if ($keyword) {
            $where['from_user_no'] = array('like', '%' . $keyword . '%') ;
        }
        //当前会员的
        $where['to_user_no'] = $this->user['user_no'];
    
        //查询数据
        $giros = D('Giro')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $giros['data']);
        $this->assign('page', $giros['page']);
        $this->assign('statistics', $giros['statistics']);
        $this->assign('account_type', $account_type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
    
    /**
     * 资金转换页面，包括转换操作处理
     *
     * @since: 2016年12月28日 下午5:36:34
     * @author: lyx
     */
    public function convert() {
    
        $type = I('type');
        
        switch ($type) {
            case Constants::ACCOUNT_CONVERT_EBTOCB:
                $from_field = 'eb_account';
                $to_field = 'cb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_EB;
                $account_type_to = Constants::ACCOUNT_TYPE_CB;
                $type_name = '注册币转现金';
                break;
            case Constants::ACCOUNT_CONVERT_TBTOCB:
                $from_field = 'tb_account';
                $to_field = 'cb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_TB;
                $account_type_to = Constants::ACCOUNT_TYPE_CB;
                $type_name = '购物币转现金';
                break;
            case Constants::ACCOUNT_CONVERT_MBTOCB:
                $from_field = 'mb_account';
                $to_field = 'cb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_MB;
                $account_type_to = Constants::ACCOUNT_TYPE_CB;
                $type_name = '奖金转现金';
                break;
            case Constants::ACCOUNT_CONVERT_RBTOCB:
                $from_field = 'rb_account';
                $to_field = 'cb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_RB;
                $account_type_to = Constants::ACCOUNT_TYPE_CB;
                $type_name = '返利转现金';
                break;
            case Constants::ACCOUNT_CONVERT_CBTOEB:
                $from_field = 'cb_account';
                $to_field = 'eb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_CB;
                $account_type_to = Constants::ACCOUNT_TYPE_EB;
                $type_name = '现金转注册币';
                break;
            case Constants::ACCOUNT_CONVERT_CBTOTB:
                $from_field = 'cb_account';
                $to_field = 'tb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_CB;
                $account_type_to = Constants::ACCOUNT_TYPE_TB;
                $type_name = '现金转购物币';
                break;
            case Constants::ACCOUNT_CONVERT_CBTOMB:
                $from_field = 'cb_account';
                $to_field = 'mb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_CB;
                $account_type_to = Constants::ACCOUNT_TYPE_MB;
                $type_name = '现金转奖金';
                break;
            case Constants::ACCOUNT_CONVERT_CBTORB:
                $from_field = 'cb_account';
                $to_field = 'rb_account';
                $account_type_from = Constants::ACCOUNT_TYPE_CB;
                $account_type_to = Constants::ACCOUNT_TYPE_RB;
                $type_name = '现金转返利';
                break;
        }
        
        if (IS_POST) { //数据提交
    
            $amount = I('post.amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '转换金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            
            $unit = $this->sys_config['SYSTEM_TRADE_MONEY_UNIT'];
            //验证金额的倍数
            if ($amount<=0 || $unit!=0 && ($amount*100)%($unit*100)!=0) {
                $result = array(
                        'status'  => false,
                        'message' => '转换金额必须是' . $unit . '的倍数！'
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
            
            //验证安全码
            if (!password_verify($_POST['password'], $this->user['two_password'])) {
                $result = array(
                        'status'  => false,
                        'message' => '安全码不正确！'
                );
                $this->ajaxReturn($result);
            }

            //判断余额
            if ($this->user[$from_field]<$amount) {
                $result = array(
                        'status'  => false,
                        'message' => '余额不足，资金转换失败！'
                );
                $this->ajaxReturn($result);
            }
    
            //封装账户变更记录数据
            $from_record = array(
                    'user_no'       => $this->user['user_no'],
                    'account_type'  => $account_type_from,
                    'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                    'amount'        => $amount,
                    'balance'       => $this->user[$from_field] - $amount,
                    'remark'        => $type_name . '。资金转换备注：' . $remark,
                    'add_time'      => curr_time()
            );
            $to_record = array(
                    'user_no'       => $this->user['user_no'],
                    'account_type'  => $account_type_to,
                    'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                    'amount'        => $amount,
                    'balance'       => $this->user[$to_field] + $amount,
                    'remark'        => $type_name . '。资金转换备注：' . $remark,
                    'add_time'      => curr_time()
            );
    
            //变更会员账号余额信息
            $where = array(
                    'id'  => $this->user['id']
            );
            $data = array(
                    $from_field     => array('exp',"$from_field - $amount"),
                    $to_field       => array('exp',"$to_field + $amount")
            );
            $res_user = M('User')->where($where)->save($data);
            
            //保存账号变更信息
            $res_account1 = M('AccountRecord')->add($from_record);
            $res_account2 = M('AccountRecord')->add($to_record);

            //返回操作的状态
            if ($res_user!==false && $res_account1!==false && $res_account2!==false) {
                $result = array(
                        'status'  => true,
                        'message' => $type_name . ',转换成功。'
                );
                
                //操作日志
                $this->addLog('进行' . $type_name . '操作，资金转换金额为：' . $amount . '。', $res_account1 . ',' . $res_account2);
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '数据库异常，' . $type_name . ',转换失败！'
                );
            }
                
            $this->ajaxReturn($result);
        }
    
        $this->assign('type_name',$type_name);
        $this->assign('type',$type);
        $this->assign('from_field',$from_field);
        $this->assign('unit', $this->sys_config['SYSTEM_TRADE_MONEY_UNIT']);
        $this->display();
    }
    
    /**
     * 流水账列表页面
     *
     * @since: 2016年12月29日 上午10:08:57
     * @author: lyx
     */
    public function index() {
        $account_type = I('account_type');
        $type = I('type');
        $start_date = I('start_date');
        $end_date = I('end_date');
        
        //账号类型
        if ((isset($_GET['account_type']) || isset($_POST['account_type'])) && $account_type != '-1') {
            $where['account_type'] = $account_type;
        }
        //账号类型
        if ((isset($_GET['type']) || isset($_POST['type'])) && $type != '-1') {
            $where['type'] = $type;
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
        //当前会员的
        $where['user_no'] = $this->user['user_no'];
        
        //查询数据
        $records = D('AccountRecord')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
        
        if ($records['statistics']) {
            $statistics = array(
                    'sum_plus' => $records['statistics'][0]['type'] == 0 ? $records['statistics'][0]['sum_total'] : 0,
                    'sum_minus' => $records['statistics'][0]['type'] == 1 ? $records['statistics'][0]['sum_total'] : $records['statistics'][1]['sum_total'],
            );
        } else {
            $statistics = array(
                    'sum_minus' => 0,
                    'sum_plus' => 0
            );
        }

        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $statistics);
        $this->assign('account_type', $account_type);
        $this->assign('type', $type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
    
    /**
     * 奖金明细页面
     *
     * @since: 2016年12月30日 上午10:03:10
     * @author: lyx
     */
    public function rewardList() {
        $type = I('type');
        $start_date = I('start_date');
        $end_date = I('end_date');
    
        //奖项类型
        if ((isset($_GET['type']) || isset($_POST['type'])) && $type != '-1') {
            $where['type'] = $type;
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
        //当前会员的
        $where['user_no'] = $this->user['user_no'];
    
        //查询数据
        $records = D('RewardRecord')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //封装页面需要的系统配置信息
        $config = array(
                'is_open_service_center'   => $this->sys_config['SYSTEM_OPEN_SERVICE_CENTER'],
                'is_open_touch_award'   => $this->sys_config['AWARD_OPEN_TOUCH'],
                'is_open_service_award'   => $this->sys_config['AWARD_OPEN_SERVICE'],
                'is_open_recommend_award'   => $this->sys_config['AWARD_OPEN_RECOMMEND'],
                'is_open_leader_award'   => $this->sys_config['AWARD_OPEN_LEADER'],
                'is_open_point_award'   => $this->sys_config['AWARD_OPEN_POINT'],
                'is_open_floor_award'   => $this->sys_config['AWARD_OPEN_FLOOR'],
                'is_open_layer_touch'   => $this->sys_config['AWARD_OPEN_LAYER_TOUCH'],
                'currency_symbol'   => $this->sys_config['SYSTEM_CURRENCY_SYMBOL']
        );
        $this->assign('config',$config);
        
        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('type', $type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->display();
    }
    
    /**
     * 返本明细页面
     *
     * @since: 2016年12月30日 上午10:03:10
     * @author: lyx
     */
    public function returnList() {
        
        //系统是否开启返本，没有开启返本不能进行任何操作
        if ($this->sys_config['SYSTEM_OPEN_RETURN'] == Constants::NO) {
//             exit('没有开启返本');
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启返本，不能进行操作！'));
        }
        
        $market_type = I('market_type');
        $start_date = I('start_date');
        $end_date = I('end_date');
    
        //奖项类型
        if ((isset($_GET['market_type']) || isset($_POST['market_type'])) && $market_type != '-1') {
            $where['market_type'] = $market_type;
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
        //当前会员的
        $where['user_no'] = $this->user['user_no'];
    
        //查询数据
        $records = D('ReturnRecord')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('market_type', $market_type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
}