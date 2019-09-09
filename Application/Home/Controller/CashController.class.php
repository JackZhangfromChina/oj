<?php
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Conf\Constants;
use Payment\Client\Charge;

/**
 * 现金模块管理控制器
 *
 * @since: 2016年12月30日 下午3:37:43
 * @author: lyx
 * @version: V1.0.0
 */
class CashController extends HomeBaseController {

    /**
     * 回调显示支付信息
     */
    public function index(){
        //显示title
        $this->assign('title', '会员中心' . "-" . $this->sys_config['WEB_WEBSITE_NAME']);
        //显示seo关键字
        $this->assign('keywords', $this->sys_config['WEB_KEYWORD']);
        //显示seo描述
        $this->assign('description', $this->sys_config['WEB_DESCRIPTION']);
        //显示网站logo
        if (trim($this->sys_config['WEB_LOGO'])) {
            $this->assign('logo', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_LOGO']);
        }
        //显示网站默认头像
        if (trim($this->sys_config['WEB_DEFAULT_AVATAR'])) {
            $this->assign('head_portrait', $this->sys_config['WEB_DOMAIN'] . '/' . $this->sys_config['WEB_DEFAULT_AVATAR']);
        }
        //显示未读邮件数目
        $userno = $this->user['user_no'];
        $count = D('Mail')->where(array('receiver_no'=>$userno,'is_read'=>0))->count('id');
        $this->assign('unreadcount',$count);
        $this->assign('order_no',I('get.order_no'));
        $this->display();
    }

    /**
    * 充值记录
    *
    * @since: 2017年1月13日 下午5:49:34
    * @author: lyx
    */
    public function payList()
    {
        //系统是否开启支付，没有开启支付不能进行任何操作
        if ($this->sys_config['PAY_OPEN'] == Constants::NO) {
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启支付功能，不能进行操作！'));
        }
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');

        //状态
        if ((isset($_GET['status']) || isset($_POST['status'])) && $status != '-1') {
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
        //当前会员的
        $where['user_no'] = $this->user['user_no'];
        //关键词
        if ($keyword) {
            $where['order_no'] = array('like', '%' . $keyword . '%') ;
        }
        // $where['status'] = array('neq', '0') ;
        //查询数据
        $records = D('Pay')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 提现页面，包括提现操作处理
     *
     * @since: 2016年12月30日 下午3:39:20
     * @author: lyx
     */
    public function withdraw() {
        if (IS_POST) { //数据提交
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
                        'message' => '提现金额必须是' . $unit . '的倍数！'
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
            if ($this->user['cb_account']<$amount) {
                $result = array(
                        'status'  => false,
                        'message' => '余额不足，提现失败！'
                );
                $this->ajaxReturn($result);
            }

            $Withdraw = D("Withdraw"); // 实例化对象
            if (!$Withdraw->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Withdraw->getError()
                );
            } else {
                //数据保存
                $Withdraw->tax_amount = $this->sys_config['SYSTEM_WITHDRAW_FEE']/100*$Withdraw->amount;
                $Withdraw->actual_amount = (100-$this->sys_config['SYSTEM_WITHDRAW_FEE'])/100*$Withdraw->amount;
                $re = $Withdraw->add(); // 写入数据到数据库

                //变更会员账号信息
                $res_user = M('User')
                            ->where(array('id'=>$this->user['id']))
                            ->setDec('cb_account',$amount);
                //记录会员账户变更信息
                $record = array(
                        'user_no'       => $this->user['user_no'],
                        'account_type'  => Constants::ACCOUNT_TYPE_CB,
                        'type'          => Constants::ACCOUNT_CHANGE_TYPE_DEC,
                        'amount'        => $amount,
                        'balance'       => $this->user['cb_account'] - $amount,
                        'remark'        => '会员提现。提现备注：' . I('post.remark'),
                        'add_time'      => curr_time()
                );
                $res_account = M('AccountRecord')->add($record);

                if ($res_user !== false && $res_account !== false && $re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '提交成功！'
                    );
                    //操作日志
                    $this->addLog('提现申请，提现金额为：' . $amount . '。', $re);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '提交失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }

        $day = date('j',time());
        $day_arr = explode(';',$this->sys_config['SYSTEM_WITHDRAW_DAY']);
        if($this->sys_config['SYSTEM_WITHDRAW_DAY']!='' && !in_array($day,$day_arr)){
            $this->redirect('Index/unAuth', array('tips'=>'今天不是提现日，不能进行提现操作！'));
        }

        if ($this->sys_config['SYSTEM_MULTI_UNFINISHED_WITHDRAW'] == Constants::NO) {
            $where = array(
                    'user_no'       => $this->user['user_no'],
                    'status'        => Constants::OPERATE_STATUS_INITIAL
            );
            $initial_count = M('Withdraw')->where($where)->count('id');
            if ($initial_count) {
                $this->redirect('Index/unAuth', array('tips'=>'有未处理提现，不能进行提现操作！'));
            }
        }

        $where = array(
                'user_no'       => $this->user['user_no']
        );
        $bank = M('Bank')->where($where)->order('is_default desc,id desc')->find();
        if ($bank) {

            $this->assign('unit', $this->sys_config['SYSTEM_TRADE_MONEY_UNIT']);
            $this->assign('bank', $bank);
            $this->display();
        } else {
            $this->display('no_bank');
        }
    }

    /**
     * 提现列表页面
     *
     * @since: 2016年12月30日 下午3:39:45
     * @author: lyx
     */
    public function withdrawList() {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');

        //状态
        if ((isset($_GET['status']) || isset($_POST['status'])) && $status != '-1') {
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
        //当前会员的
        $where['user_no'] = $this->user['user_no'];

        //查询数据
        $records = D('Withdraw')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

    /**
     * 汇款页面，包括汇款操作处理
     *
     * @since: 2016年12月30日 下午3:40:01
     * @author: lyx
     */
    public function remit() {

        if (IS_POST) { //数据提交

            $amount = I('post.amount');
            //验证金额的输入合法性
            if (!check_money($amount)) {
                $result = array(
                        'status'  => false,
                        'message' => '转账金额不合法！'
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

            $Remit = D("Remit"); // 实例化对象
            if (!$Remit->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Remit->getError()
                );
            } else {
                //数据保存
                $re = $Remit->add(); // 写入数据到数据库
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '提交成功！'
                    );
                    //操作日志
                    $this->addLog('汇款单提交，汇款金额为：' . $amount . '。', $re);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '提交失败！'
                    );
                }
            }

            $this->ajaxReturn($result);
        }


        if ($this->sys_config['SYSTEM_MULTI_UNFINISHED_REMIT'] == Constants::NO) {
            $where = array(
                    'user_no'       => $this->user['user_no'],
                    'status'        => Constants::OPERATE_STATUS_INITIAL
            );
            $initial_count = M('Remit')->where($where)->count('id');
            if ($initial_count) {
                $this->redirect('Index/unAuth', array('tips'=>'有未处理汇款，不能进行汇款操作！'));
            }
        }

        $where = array(
                'user_no'       => $this->user['user_no']
        );
        $bank = M('Bank')->where($where)->order('is_default desc,id desc')->find();
        if ($bank) {
            $this->assign('bank', $bank);
            $this->display();
        } else {
            $this->display('no_bank');
        }
    }

    /**
     * 汇款列表页面
     *
     * @since: 2016年12月30日 下午3:40:17
     * @author: lyx
     */
    public function remitList() {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');

        //状态
        if ((isset($_GET['status']) || isset($_POST['status'])) && $status != '-1') {
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
        //当前会员的
        $where['user_no'] = $this->user['user_no'];

        //查询数据
        $records = D('Remit')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

    /**
     * 银行卡列表页面
     *
     * @since: 2017年1月3日 上午11:00:15
     * @author: lyx
     */
    public function bankList() {
        $where = array(
                'user_no'   => $this->user['user_no']
        );
        $banks = M('Bank')->where($where)->select();
        $this->assign('list', $banks);
        $this->display();
    }

    /**
     * 添加银行卡页面（包括操作处理）
     *
     * @since: 2017年1月3日 上午11:55:38
     * @author: lyx
     */
    public function addBank() {
    if (IS_POST) { //数据提交

            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
                $result = array(
                        'status'  => false,
                        'message' => '会员登录信息变更，操作失败！'
                );
                $this->ajaxReturn($result);
            }

            // $userex = M("UserExtend")->where(array('user_no'=>$this->user['user_no']))->find();
            // if(!$userex || !$userex['id_card'] || !$this->user['realname'] || !$this->user['identification']){
            //     $result = array(
            //             'status'  => false,
            //             'message' => '请到个人信息中完善信息！'
            //     );
            //     $this->ajaxReturn($result);
            // }

            $Bank = D("Bank"); // 实例化对象
            if (!$Bank->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Bank->getError()
                );
            } else {
                //数据保存
                $re = $Bank->add(); // 写入数据到数据库
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '银行卡添加成功！'
                    );

                    //操作日志
                    $this->addLog('新增银行卡信息。卡号：' . I('post.bank_no') . '；银行：' . I('post.bank') . '; 支行：' . I('post.sub_bank') . '。', $re);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '银行卡添加失败！'
                    );
                }
            }

            $this->ajaxReturn($result);
        }

        $banks = M('BankType')->order('sort asc,id asc')->select();
        $this->assign('banks',$banks);
        $this->display();
    }

    /**
     * 修改银行卡页面（包括操作处理）
     *
     * @since: 2017年1月3日 下午3:51:51
     * @author: lyx
     */
    public function editBank() {
        if (IS_POST) { //数据提交

            //会员登录信息变更
            if (I('post.user_no') != $this->user['user_no']) {
                $result = array(
                        'status'  => false,
                        'message' => '会员登录信息变更，操作失败！'
                );
                $this->ajaxReturn($result);
            }

            // $userex = M("UserExtend")->where(array('user_no'=>$this->user['user_no']))->find();
            // if(!$userex || !$userex['id_card'] || !$this->user['realname'] || !$this->user['identification']){
            //     $result = array(
            //             'status'  => false,
            //             'message' => '请到个人信息中完善信息！'
            //     );
            //     $this->ajaxReturn($result);
            // }

            $old_bank = M('Bank')->find(I('post.id'));

            $Bank = D("Bank"); // 实例化对象
            if (!$Bank->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Bank->getError()
                );
            } else {
                //数据保存
                $re = $Bank->save();
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '银行卡修改成功！'
                    );

                    //操作日志
                    $this->addLog('修改银行卡信息。原信息【卡号：' . $old_bank['bank_no'] . '；银行：' . $old_bank['bank'] . '; 支行：' . $old_bank['sub_bank']
                                        . '】。新信息【卡号：' . I('post.bank_no') . '；银行：' . I('post.bank') . '; 支行：' . I('post.sub_bank') . '】。', $old_bank['id']);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '银行卡修改失败！'
                    );
                }
            }

            $this->ajaxReturn($result);
        }

        $bank = M('Bank')->find(I('get.id'));
        if ($bank) {
            $banks = M('BankType')->order('sort asc,id asc')->select();
            $this->assign('banks',$banks);
            $this->assign('bank', $bank);
            $this->display();
        } else {
            $this->redirect('Cash/bankList');
        }
    }

    /**
     * 删除银行卡
     *
     * @since: 2017年1月3日 下午3:56:41
     * @author: lyx
     */
    public function delBank() {
        if (IS_POST) { //数据提交

            $bank = M('Bank')->find(I('post.id'));

            //数据删除
            $re = M('Bank')->delete(I('post.id'));
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );

                //操作日志
                $this->addLog('删除卡号为' . $bank['bank_no'] . '的银行卡。', I('post.id'));
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
     * 银行卡设置默认
     *
     * @since: 2017年1月3日 下午4:51:28
     * @author: lyx
     */
    public function setBank() {
        if (IS_POST) { //数据提交

            //清除默认设置
            $data['is_default'] = Constants::NO;
            $where = array(
                    'user_no'       => $this->user['user_no'],
                    'is_default'    => Constants::YES
            );
            $re_all = M('Bank')->where($where)->save($data);

            //设置默认
            $_POST['update_time'] = curr_time();
            $_POST['is_default'] = Constants::YES;
            $re = M('Bank')->save($_POST);
            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '设置成功！'
                );

                //操作日志
                $bank = M('Bank')->find(I('post.id'));
                $this->addLog('将卡号为：' . $bank['bank_no'] . '的银行卡设为默认。', I('post.id'));
            } else {
                $result = array(
                        'status'  => false,
                        'message' => '设置失败！'
                );
            }
            $this->ajaxReturn($result);
        }
    }

    /**
    * 生成支付订单号
    *
    * @since: 2017年1月14日 上午10:32:23
    * @author: lyx
    */
    private function _orderNo() {
        //生成订单号
        $order_no = pay_order_no();

        $count = M('Pay')->where(array('order_no'=>$order_no))->count('id');
        if ($count==0) {
            return $order_no;
        } else {
            $this->_orderNo();
        }
    }

    public function pay()
    {
        //系统是否开启支付，没有开启支付不能进行任何操作
        if ($this->sys_config['PAY_OPEN'] == Constants::NO) {
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启支付功能，不能进行操作！'));
        }
        if (IS_POST) {
            $userinfo = M('User')->where(array('user_no'=>$this->user['user_no']))->find();

            // if(!$userinfo['realname'] || !$userinfo['phone']){
            //     $result = array(
            //         'status'  => false,
            //         'message' => '请到个人信息中完善信息！'
            //     );
            // } else {
                $result = array(
                    'status'  => true,
                    'message' => ''
                );
            // }
            $this->ajaxReturn($result);
        } else {
            $this->assign('unit', $this->sys_config['SYSTEM_TRADE_MONEY_UNIT']);
            $this->assign('order_no', I('order_no'));
            $this->assign('amount', I('amount'));
            if ($order_info = D('Pay')->getByOrderNo(I('order_no'))) {
                $this->assign('pay_type', $order_info['pay_type']);
            }
            $this->display();
        }
    }

    // 第三方支付
    public function thirdPay()
    {
        //系统是否开启支付，没有开启支付不能进行任何操作
        if ($this->sys_config['PAY_OPEN'] == Constants::NO) {
            $this->redirect('Index/unAuth', array('tips'=>'系统未开启支付功能，不能进行操作！'));
        }
        $amount = I('amount');
        $pay_type = I('pay_type');

        if ($amount > 0) {
            $order_no = I('order_no');
            if ($order_no) {
                $order_info = D('Pay')->getByOrderNo($order_no);
                if (empty($order_info) || $order_info['user_no'] != $this->user['user_no']) {
                    $this->redirect('Index/unAuth', array('tips'=>'参数错误！'));
                } else {
                    $order_no = $order_info['order_no'];
                }
            } else {
                $order_no = $this->_orderNo();
                D('Pay')->add([
                    'user_no' => $this->user['user_no'],
                    'order_no' => $order_no,
                    'amount' => $amount,
                    'status' => Constants::PAY_STATUS_WAIT,
                    'title' => $this->sys_config['PAY_GOODS_TITLE'],
                    'body' => $this->sys_config['PAY_GOODS_BODY'],
                    'terminal_type' => 'web',
                    'member_ip' => client_ip(),
                    'add_time' => curr_time(),
                ]);
            }

            // 支付分支
            date_default_timezone_set('Asia/Shanghai');
            switch ($pay_type) {
                case 'ipspay':
                    D('Pay')->where(['order_no'=>$order_no])->save(['pay_type'=>'1']);
                    // 目前只支持web端
                    $parameter = A('Api/Ips')->getConfig();
                    $data = [
                        "Version"       => $parameter['Version'],
                        "MerCode"       => $parameter['MerCode'],
                        "Account"       => $parameter['Account'],
                        "MerCert"       => $parameter['MerCert'],
                        "PostUrl"       => $parameter['PostUrl'],
                        "S2Snotify_url" => $parameter['S2Snotify_url'],
                        "Return_url"    => $parameter['return_url'],
                        "CurrencyType"  => $parameter['Ccy'],
                        "Lang"          => $parameter['Lang'],
                        "OrderEncodeType"=>$parameter['OrderEncodeType'],
                        "RetType"       =>$parameter['RetType'],
                        "MerName"       => $parameter['inMerName'],
                        "MsgId"         => $parameter['MsgId'],
                        "MerBillNo" => $order_no,
                        "PayType"   => '01',
                        // "FailUrl"   => '',
                        "Date"      => date("Ymd",time()),
                        "ReqDate"   => date("YmdHis"),
                        "Amount"    => $amount,
                        // "Attach"    => '',
                        "RetEncodeType" => '17', // 交易返回接口加密方式 md5
                        "BillEXP"   => 1, // 过期时间（小时）
                        "GoodsName" => $this->sys_config['PAY_GOODS_TITLE'],
                        // "BankCode"  => '',
                        // "IsCredit"  => '',
                        // "ProductType"   => ''
                    ];
                    vendor("IpsPaySubmit",realpath(COMMON_PATH.'Library\Ipspay\lib'), '.class.php');

                    //建立请求
                    $ipspaySubmit = new \IpsPaySubmit($parameter);
                    $html_text = $ipspaySubmit->buildRequestForm($data);
                    echo $html_text;
                    exit;
                case 'alipay':
                default:
                    D('Pay')->where(['order_no'=>$order_no])->save(['pay_type'=>'0']);
                    // 判断支付终端类型
                    $type = 'ali_web';
                    $parameter = A('Api/Ali')->getConfig();
                    $data = [
                        'body' => $this->sys_config['PAY_GOODS_TITLE'],
                        'subject' => $this->sys_config['PAY_GOODS_BODY'],
                        'order_no' => $order_no,
                        'timeout_express' => time() + 600,// 表示必须 600s 内付款
                        'amount' => $amount,// 单位为元 ,最小为0.01
                        'return_param' => '123',
                    ];
                    try {
                        $ret = Charge::run($type, $parameter, $data);
                    } catch (PayException $e) {
                        $result = [
                            'status' => false,
                            'message' => $e->errorMessage(),
                        ];
                        $this->ajaxReturn($result);
                    }
                    redirect($ret);
                    exit;
            }
        }
    }
    /**
     * 订单支付状态检查
     * @return [type] [description]
     */
    public function paymentQuery()
    {
        if(IS_POST){
            $order_no = I('post.order_no');
            $order_info = D('Pay')->getByOrderNo($order_no);

            switch ($order_info['pay_type']) {
                case '1':
                    $control = A('Api/Ips');
                    break;
                case '0':
                default:
                    $control = A('Api/Ali');
                    break;
            }
            if(!$control->paymentQuery($order_no, $msg)){
                $result = [
                    'status'  => false,
                    'message' => $msg,
                ];
                $this->ajaxReturn($result);
            }
            $result = [
                'status'  => true,
                'message' => '订单可以操作',
            ];
            $this->ajaxReturn($result);
        }
    }

    /**
     * 订单取消
     * @return [type] [description]
     */
    public function orderCancel()
    {
        if(IS_POST){
            error_reporting(E_ALL || ~E_NOTICE);
            $order_no = I('post.order_no');
            $order_info = D('Pay')->getByOrderNo($order_no);

            switch ($order_info['pay_type']) {
                case '1':
                    $control = A('Api/Ips');
                    break;
                case '0':
                default:
                    $control = A('Api/Ali');
                    break;
            }
            if(!$control->paymentQuery($order_no, $msg)){
                $result = [
                    'status'  => false,
                    'message' => '订单不存在或已关闭或已经支付',
                ];
                $this->ajaxReturn($result);
            }
            if(!D('Pay')->orderCancel($order_no, $msg)){
                $result = [
                    'status'  => false,
                    'message' => '订单不存在或已关闭或已经支付',
                ];
                $this->ajaxReturn($result);
            }
            $result = [
                'status'  => true,
                'message' => '关闭成功',
            ];
            $this->ajaxReturn($result);
        }
    }
}