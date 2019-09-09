<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 账号模块管理控制器
 *
 * @since: 2016年12月27日 上午10:04:52
 * @author: lyx
 * @version: V1.0.0
 */
class AccountController extends AdminBaseController {

    /**
    * 快捷操作页面（充值/扣款），包括快捷操作处理(废弃掉了)
    *
    * @since: 2016年12月27日 上午10:29:13
    * @author: lyx
    */
    public function giro() {
        $account_type = I('account_type');
        $type = I('type');
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
            
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
                
                //封装账户变更记录数据
                if ($type == Constants::ACCOUNT_CHANGE_TYPE_DEC) {
                    if ($user[$field]<$amount) {
                        $result = array(
                                'status'  => false,
                                'message' => '余额不足，扣款失败！'
                        );
                        $this->ajaxReturn($result);
                    }
                    $type_name = "扣款";
                    $balance = $user[$field] - $amount;
                    $from_user_no = $user_no;
                    $to_user_no = '';
                } else {
                    $type_name = "充值";
                    $balance = $user[$field] + $amount;
                    $from_user_no = '';
                    $to_user_no = $user_no;
                }
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => $account_type,
                        'type'          => $type,
                        'amount'        => $amount,
                        'balance'       => $balance,
                        'remark'        => '后台快捷' . $type_name .'。' . $type_name . '备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $from_user_no,
                        'to_user_no'    => $to_user_no,
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => $account_type,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
                
                //变更会员对应账号信息
                if ($type == Constants::ACCOUNT_CHANGE_TYPE_DEC) {
                    $res_user = M('User')
                                ->where(array("id"=>$user['id']))
                                ->setDec($field,$amount);
                } else {
                    $res_user = M('User')
                                ->where(array("id"=>$user['id']))
                                ->setInc($field,$amount);
                }
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
                
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => $account_type_name . '快捷' . $type_name .'成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员' . $account_type_name . '快捷' . $type_name . '。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => $account_type_name,
                            'operate'       => $type_name,
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    if ($type == Constants::ACCOUNT_CHANGE_TYPE_DEC) {
                        $this->sendMessage($receiver, 'MESSAGE_DEDUCT', json_encode($msg_param), Constants::MESSAGE_TYPE_DEDUCT);
                    } else {
                        $this->sendMessage($receiver, 'MESSAGE_RECHARGE', json_encode($msg_param), Constants::MESSAGE_TYPE_RECHARGE);
                    }
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
        
        $this->assign('type',$type);
        $this->assign('account_type',$account_type);
        $this->assign('account_type_name',$account_type_name);
        
        $this->display();
    }
    
    /**
    * 注册币快捷充值
    *
    * @since: 2017年1月9日 下午2:09:13
    * @author: lyx
    */
    public function ebRecharge() {
    
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
    
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_EB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $amount,
                        'balance'       => $user['eb_account'] + $amount,
                        'remark'        => '后台快捷充值。充值备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => '',
                        'to_user_no'    => $user_no,
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_EB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                            ->where(array("id"=>$user['id']))
                            ->setInc('eb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '注册币快捷充值成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员注册币快捷充值。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '注册币',
                            'operate'       => '充值',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_RECHARGE', json_encode($msg_param), Constants::MESSAGE_TYPE_RECHARGE);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 注册币快捷扣款
     *
     * @since: 2017年1月9日 下午2:14:38
     * @author: lyx
     */
    public function ebDeduct() {
        if (IS_POST) { //数据提交
        
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
                //验证余额
                if ($user['eb_account']<$amount) {
                    $result = array(
                            'status'  => false,
                            'message' => '余额不足，扣款失败！'
                    );
                    $this->ajaxReturn($result);
                }
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_EB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $user['eb_account'] - $amount,
                        'remark'        => '后台快捷扣款。扣款备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $user_no,
                        'to_user_no'    => '',
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_EB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
        
                //变更会员对应账号信息
                $res_user = M('User')
                    ->where(array("id"=>$user['id']))
                    ->setDec('eb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
        
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '注册币快捷扣款成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员注册币快捷扣款。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '注册币',
                            'operate'       => '扣款',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_DEDUCT', json_encode($msg_param), Constants::MESSAGE_TYPE_DEDUCT);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
        
        $this->display();
    }
    
    /**
     * 购物币快捷充值
     *
     * @since: 2017年1月9日 下午2:27:18
     * @author: lyx
     */
    public function tbRecharge() {
    
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
    
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_TB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $amount,
                        'balance'       => $user['tb_account'] + $amount,
                        'remark'        => '后台快捷充值。充值备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => '',
                        'to_user_no'    => $user_no,
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_TB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                ->where(array("id"=>$user['id']))
                ->setInc('tb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '购物币快捷充值成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员购物币快捷充值。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '购物币',
                            'operate'       => '充值',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_RECHARGE', json_encode($msg_param), Constants::MESSAGE_TYPE_RECHARGE);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 购物币快捷扣款
     *
     * @since: 2017年1月9日 下午2:35:55
     * @author: lyx
     */
    public function tbDeduct() {
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
                //验证余额
                if ($user['tb_account']<$amount) {
                    $result = array(
                            'status'  => false,
                            'message' => '余额不足，扣款失败！'
                    );
                    $this->ajaxReturn($result);
                }
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_TB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $user['tb_account'] - $amount,
                        'remark'        => '后台快捷扣款。扣款备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $user_no,
                        'to_user_no'    => '',
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_TB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                ->where(array("id"=>$user['id']))
                ->setDec('tb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '购物币快捷扣款成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员购物币快捷扣款。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '购物币',
                            'operate'       => '扣款',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_DEDUCT', json_encode($msg_param), Constants::MESSAGE_TYPE_DEDUCT);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 现金快捷充值
     *
     * @since: 2017年1月9日 下午2:42:41
     * @author: lyx
     */
    public function cbRecharge() {
    
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
    
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_CB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $amount,
                        'balance'       => $user['cb_account'] + $amount,
                        'remark'        => '后台快捷充值。充值备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => '',
                        'to_user_no'    => $user_no,
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_CB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                ->where(array("id"=>$user['id']))
                ->setInc('cb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '现金快捷充值成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员现金快捷充值。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '现金',
                            'operate'       => '充值',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_RECHARGE', json_encode($msg_param), Constants::MESSAGE_TYPE_RECHARGE);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 现金快捷扣款
     *
     * @since: 2017年1月9日 下午2:42:53
     * @author: lyx
     */
    public function cbDeduct() {
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
                //验证余额
                if ($user['cb_account']<$amount) {
                    $result = array(
                            'status'  => false,
                            'message' => '余额不足，扣款失败！'
                    );
                    $this->ajaxReturn($result);
                }
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_CB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $user['cb_account'] - $amount,
                        'remark'        => '后台快捷扣款。扣款备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $user_no,
                        'to_user_no'    => '',
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_CB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                            ->where(array("id"=>$user['id']))
                            ->setDec('cb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '现金快捷扣款成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员现金快捷扣款。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '现金',
                            'operate'       => '扣款',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_DEDUCT', json_encode($msg_param), Constants::MESSAGE_TYPE_DEDUCT);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 奖金快捷充值
     *
     * @since: 2017年1月9日 下午2:48:07
     * @author: lyx
     */
    public function mbRecharge() {
    
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
    
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_MB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $amount,
                        'balance'       => $user['mb_account'] + $amount,
                        'remark'        => '后台快捷充值。充值备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => '',
                        'to_user_no'    => $user_no,
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_MB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                            ->where(array("id"=>$user['id']))
                            ->setInc('mb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '奖金快捷充值成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员奖金快捷充值。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '奖金',
                            'operate'       => '充值',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_RECHARGE', json_encode($msg_param), Constants::MESSAGE_TYPE_RECHARGE);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 奖金快捷扣款
     *
     * @since: 2017年1月9日 下午2:45:29
     * @author: lyx
     */
    public function mbDeduct() {
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
                //验证余额
                if ($user['mb_account']<$amount) {
                    $result = array(
                            'status'  => false,
                            'message' => '余额不足，扣款失败！'
                    );
                    $this->ajaxReturn($result);
                }
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_MB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $user['mb_account'] - $amount,
                        'remark'        => '后台快捷扣款。扣款备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $user_no,
                        'to_user_no'    => '',
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_MB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                            ->where(array("id"=>$user['id']))
                            ->setDec('mb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '奖金快捷扣款成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员奖金快捷扣款。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '奖金',
                            'operate'       => '扣款',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_DEDUCT', json_encode($msg_param), Constants::MESSAGE_TYPE_DEDUCT);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 返利快捷充值
     *
     * @since: 2017年1月9日 下午2:49:45
     * @author: lyx
     */
    public function rbRecharge() {
    
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
    
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_RB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                        'amount'        => $amount,
                        'balance'       => $user['rb_account'] + $amount,
                        'remark'        => '后台快捷充值。充值备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => '',
                        'to_user_no'    => $user_no,
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_RB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                ->where(array("id"=>$user['id']))
                ->setInc('rb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '返利快捷充值成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员返利快捷充值。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '返利',
                            'operate'       => '充值',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_RECHARGE', json_encode($msg_param), Constants::MESSAGE_TYPE_RECHARGE);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 返利快捷扣款
     *
     * @since: 2017年1月9日 下午2:14:38
     * @author: lyx
     */
    public function rbDeduct() {
        if (IS_POST) { //数据提交
    
            $user_no = I('post.user_no');
            $amount = I('amount');
            $remark = I('post.remark');
            
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '金额不合法！'
                );
                $this->ajaxReturn($result);
            }
            //获取会员信息
            $user = M('User')->getByUserNo($user_no);
            if ($user) {
                //验证余额
                if ($user['rb_account']<$amount) {
                    $result = array(
                            'status'  => false,
                            'message' => '余额不足，扣款失败！'
                    );
                    $this->ajaxReturn($result);
                }
                //封装账户变更记录数据
                $record = array(
                        'user_no'       => $user_no,
                        'account_type'  => Constants::ACCOUNT_TYPE_RB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $user['rb_account'] - $amount,
                        'remark'        => '后台快捷扣款。扣款备注：' . $remark,
                        'add_time'      => curr_time()
                );
                //封装转账记录数据
                $giro = array(
                        'from_user_no'  => $user_no,
                        'to_user_no'    => '',
                        'type'          => Constants::GIRO_TYPE_SYSTEM,
                        'account_type'  => Constants::ACCOUNT_TYPE_RB,
                        'amount'        => $amount,
                        'remark'        => $remark,
                        'add_time'      => curr_time()
                );
    
                //变更会员对应账号信息
                $res_user = M('User')
                ->where(array("id"=>$user['id']))
                ->setDec('rb_account',$amount);
                //保存转账信息
                $res_giro = M('Giro')->add($giro);
                //保存账号变更信息
                $res_account = M('AccountRecord')->add($record);
    
                //返回操作的状态
                if ($res_user!==false && $res_giro!==false && $res_account!==false) {
                    $result = array(
                            'status'  => true,
                            'message' => '返利快捷扣款成功。'
                    );
                    //操作日志
                    $this->addLog('给编号为' . $user_no . '的会员返利快捷扣款。操作金额为：' . $amount, $res_giro);
                    
                    //短信提醒
                    $msg_param = array(
                            'account_type'  => '返利',
                            'operate'       => '扣款',
                            'amount'        => $amount
                    );
                    $receiver = array(
                            'user_no'       => $user_no
                    );
                    $this->sendMessage($receiver, 'MESSAGE_DEDUCT', json_encode($msg_param), Constants::MESSAGE_TYPE_DEDUCT);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '数据库异常，操作失败！'
                    );
                }
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '会员不存在，操作失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    
        $this->display();
    }
    
    /**
     * 转账列表页面
     *
     * @since: 2016年12月29日 下午3:58:27
     * @author: lyx
     */
    public function giroList() {
        $account_type = I('account_type');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //账号类型
        if ((isset($_GET["account_type"]) || isset($_POST["account_type"])) && $account_type != '-1') {
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
            $map['to_user_no'] = array('like', '%' . $keyword . '%') ;
            $map['from_user_no'] = array('like', '%' . $keyword . '%') ;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
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
        $keyword = I('keyword');
    
        //账号类型
        if ((isset($_GET["account_type"]) || isset($_POST["account_type"])) && $account_type != '-1') {
            $where['account_type'] = $account_type;
        }
        //账号类型
        if ((isset($_GET["type"]) || isset($_POST["type"])) && $type != '-1') {
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
        //关键词
        if ($keyword) {
            $where['user_no'] = array('like', '%' . $keyword . '%') ;
        }
    
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
        $this->assign('keyword', $keyword);
        $this->display();
    }
    
    /**
     * 奖金明细页面
     *
     * @since: 2016年12月30日 下午2:27:35
     * @author: lyx
     */
    public function rewardList() {
        $type = I('type');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //奖项类型
        if ((isset($_GET["type"]) || isset($_POST["type"])) && $type != '-1') {
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
        //关键词
        if ($keyword) {
            $where['user_no'] = array('like', '%' . $keyword . '%') ;
        }
    
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
        $this->assign('keyword', $keyword);
        $this->display();
    }

      /**
       * 导出数据到excel
       *
       * @since: 2016年12月21日 上午11:44:22
       * @author: Wang Peng
       */
      public function exportExcel(){
        import("Common.Library.PHPExcel.IOFactory");
        $data = I();  
        $file_name = 'jiangjin'.date('YmdHis',time()).'.xls';
        $exceldata = D('RewardRecord')->getExcelData($data);//获取所要显示的信息
        $excelwrite = new \PHPExcel_Writer_Excel5($exceldata);//将信息记录excel表格中
            
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$file_name.'"');
        header("Content-Transfer-Encoding:binary");
        $excelwrite->save('php://output'); 

        //操作日志
        $this->addLog('导出提现记录');
      }
    
    /**
     * 返本明细页面
     *
     * @since: 2016年12月30日 下午2:32:49
     * @author: lyx
     */
    public function returnList() {
    
        //系统是否开启返本，没有开启返本不能进行任何操作
        if ($this->sys_config['SYSTEM_OPEN_RETURN'] == Constants::NO) {
            //             exit("没有开启返本");
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启返本，不能进行操作！'));
        }
    
        $market_type = I('market_type');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //业绩类型
        if ((isset($_GET["market_type"]) || isset($_POST["market_type"])) && $market_type != '-1') {
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
        //关键词
        if ($keyword) {
            $where['user_no'] = array('like', '%' . $keyword . '%') ;
        }
    
        //查询数据
        $records = D('ReturnRecord')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('market_type', $market_type);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
    
    /**
     * 业绩列表页面
     *
     * @since: 2016年12月30日 下午3:13:42
     * @author: lyx
     */
    public function marketList() {
    
        $market_type = I('market_type');
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
    
        //业绩类型
        if ((isset($_GET["market_type"]) || isset($_POST["market_type"])) && $market_type != '-1') {
            $where['market_type'] = $market_type;
        }
        //状态
        if ((isset($_GET["status"]) || isset($_POST["status"])) && $status != '-1') {
            $where['status'] = $status;
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
            $where['user_no'] = array('like', '%' . $keyword . '%') ;
        }
    
        //查询数据
        $records = D('MarketRecord')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);
    
        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('market_type', $market_type);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }
}