<?php
namespace Admin\Controller;
use Common\Controller\AdminBaseController;
use Common\Conf\Constants;

/**
 * 现金模块管理控制器
 *
 * @since: 2017年1月3日 下午4:52:06
 * @author: lyx
 * @version: V1.0.0
 */
class CashController extends AdminBaseController {

    /**
     * 现金模板欢迎页
     *
     * @since: 2017年1月7日 下午3:04:03
     * @author: lyx
     */
    public function index() {

        $this->display();
    }

    /**
     * 提现列表页面
     *
     * @since: 2017年1月3日 下午4:56:07
     * @author: lyx
     */
    public function withdrawList() {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');
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
        $records = D('Withdraw')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
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
        $file_name = 'tixian'.date('YmdHis',time()).'.xls';
        $exceldata = D('Withdraw')->getExcelData($data);//获取所要显示的信息
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
        $this->addLog('导出提现记录成功。');
      }

    /**
     * 汇款列表页面
     *
     * @since: 2017年1月3日 下午5:20:05
     * @author: lyx
     */
    public function remitList() {
        $status = I('status');
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');

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
        $records = D('Remit')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $records['data']);
        $this->assign('page', $records['page']);
        $this->assign('statistics', $records['statistics']);
        $this->assign('status', $status);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->assign('currency_symbol', $this->sys_config['SYSTEM_CURRENCY_SYMBOL']);
        $this->display();
    }

      /**
       * 导出数据到excel
       *
       * @since: 2016年12月21日 上午11:44:22
       * @author: Wang Peng
       */
      public function exportRemitExcel(){
        import("Common.Library.PHPExcel.IOFactory");
        $data = I();
        $file_name = 'huikuan'.date('YmdHis',time()).'.xls';
        $exceldata = D('Remit')->getExcelData($data);//获取所要显示的信息
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
        $this->addLog('导出汇款记录成功。');
      }

    /**
     * 充值记录
     *
     * @since: 2017年1月13日 下午5:51:14
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
        //关键词
        if ($keyword) {
            $map['user_no'] = array('like', '%' . $keyword . '%') ;
            $map['order_no'] = array('like', '%' . $keyword . '%') ;
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }

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
     * 确认提现
     *
     * @since: 2017年1月3日 下午6:05:09
     * @author: lyx
     */
    public function confirmWithdraw() {
        if (IS_POST) { //数据提交

            $where = array(
                    'id'        => I('post.id')
            );
            $withdraw = M("Withdraw")->where($where)->find();
            //判断是否重复操作
            if ($withdraw['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }
            //保存操作设置
            $data['status'] = Constants::OPERATE_STATUS_CONFIRM;
            $data['operate_time'] = curr_time();

            $re = M("Withdraw")->where($where)->save($data);

            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );

                //操作日志
                $this->addLog('同意了' . $withdraw['user_no'] . '的提现。提现申请时间为：' . $withdraw['add_time'], I('post.id'));

                //短信提醒
                $msg_param = array(
                        'operate'   => '已确认',
                        'amount'    => $withdraw['amount']
                );

                $receiver = array(
                        'user_no'       => $withdraw['user_no']
                );
                $this->sendMessage($receiver, 'MESSAGE_WITHDRAW', json_encode($msg_param), Constants::MESSAGE_TYPE_WITHDRAW);
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
     * 驳回提现
     *
     * @since: 2017年1月3日 下午6:19:59
     * @author: lyx
     */
    public function rejectWithdraw() {
        if (IS_POST) { //数据提交

            $where = array(
                    'id'        => I('post.id')
            );
            $withdraw = M("Withdraw")->where($where)->find();
            //判断是否重复操作
            if ($withdraw['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }

            $amount = $withdraw['amount'];
            //获取会员信息
            $user = M('User')->getByUserNo($withdraw['user_no']);
            //变更会员账号信息
            $res_user = M('User')
                        ->where(array("user_no"=>$withdraw['user_no']))
                        ->setInc('cb_account',$amount);

            //记录会员账户变更信息
            $record = array(
                    'user_no'       => $withdraw['user_no'],
                    'account_type'  => Constants::ACCOUNT_TYPE_CB,
                    'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                    'amount'        => $amount,
                    'balance'       => $user['cb_account'] + $amount,
                    'remark'        => '会员提现被驳回，资金回滚',
                    'add_time'      => curr_time()
            );
            $res_account = M('AccountRecord')->add($record);

            //保存操作设置
            $data['status'] = Constants::OPERATE_STATUS_REJECT;
            $data['operate_time'] = curr_time();
            $re = M("Withdraw")->where($where)->save($data);

            if ($res_user !== false && $res_account !== false && $re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );

                //操作日志
                $this->addLog('驳回了' . $withdraw['user_no'] . '的提现。提现申请时间为：' . $withdraw['add_time'], I('post.id'));

                //短信提醒
                $msg_param = array(
                        'operate'   => '已驳回',
                        'amount'    => $withdraw['amount']
                );
                $receiver = array(
                        'user_no'       => $user['user_no'],
                        'phone'         => $user['phone']
                );
                $this->sendMessage($receiver, 'MESSAGE_WITHDRAW', json_encode($msg_param), Constants::MESSAGE_TYPE_WITHDRAW);
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
     * 确认汇款
     *
     * @since: 2017年1月3日 下午6:05:09
     * @author: lyx
     */
    public function confirmRemit() {
    if (IS_POST) { //数据提交

            $where = array(
                    'id'        => I('post.id')
            );
            $remit = M("Remit")->where($where)->find();
            //判断是否重复操作
            if ($remit['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }

            $amount = round($remit['amount'] / $this->sys_config['SYSTEM_EXRATE'] ,2);
            //获取会员信息
            $user = M('User')->getByUserNo($remit['user_no']);
            //变更会员账号信息
            $res_user = M('User')
                    ->where(array("user_no"=>$remit['user_no']))
                    ->setInc('cb_account',$amount);

            //记录会员账户变更信息
            $record = array(
                    'user_no'       => $remit['user_no'],
                    'account_type'  => Constants::ACCOUNT_TYPE_CB,
                    'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                    'amount'        => $remit['amount'],
                    'balance'       => $user['cb_account'] + $amount,
                    'remark'        => '会员汇款确认',
                    'add_time'      => curr_time()
            );
            $res_account = M('AccountRecord')->add($record);

            //保存操作设置
            $data['status'] = Constants::OPERATE_STATUS_CONFIRM;
            $data['operate_time'] = curr_time();
            $re = M("Remit")->where($where)->save($data);

            if ($res_user !== false && $res_account !== false && $re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );

                //操作日志
                $this->addLog('同意了' . $remit['user_no'] . '的汇款。汇款申请时间为：' . $remit['add_time'], I('post.id'));

                //短信提醒
                $msg_param = array(
                        'operate'   => '已确认',
                        'amount'    => $remit['amount']
                );
                $receiver = array(
                        'user_no'       => $user['user_no'],
                        'phone'         => $user['phone']
                );
                $this->sendMessage($receiver, 'MESSAGE_REMIT', json_encode($msg_param), Constants::MESSAGE_TYPE_REMIT);
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
     * 驳回汇款
     *
     * @since: 2017年1月3日 下午6:19:59
     * @author: lyx
     */
    public function rejectRemit() {
        if (IS_POST) { //数据提交

            $where = array(
                    'id'        => I('post.id')
            );
            $remit = M("Remit")->where($where)->find();
            //判断是否重复操作
            if ($remit['status'] != Constants::OPERATE_STATUS_INITIAL) {
                $result = array(
                        'status'  => false,
                        'message' => '已处理不能重复操作！'
                );
                $this->ajaxReturn($result);
            }
            //保存操作设置
            $data['status'] = Constants::OPERATE_STATUS_REJECT;
            $data['operate_time'] = curr_time();

            $re = M("Remit")->where($where)->save($data);

            if ($re !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '操作处理成功！'
                );

                //操作日志
                $this->addLog('驳回了' . $remit['user_no'] . '的汇款。汇款申请时间为：' . $remit['add_time'], I('post.id'));

                //短信提醒
                $msg_param = array(
                        'operate'   => '已驳回',
                        'amount'    => $remit['amount']
                );
                $receiver = array(
                        'user_no'       => $remit['user_no']
                );
                $this->sendMessage($receiver, 'MESSAGE_REMIT', json_encode($msg_param), Constants::MESSAGE_TYPE_REMIT);
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
     * 银行卡列表页面
     *
     * @since: 2017年1月11日 上午10:14:24
     * @author: lyx
     */
    public function bankList() {
        $start_date = I('start_date');
        $end_date = I('end_date');
        $keyword = I('keyword');

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
        $banks = D('Bank')->getList($where, I(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        //返回页面的数据
        $this->assign('list', $banks['data']);
        $this->assign('page', $banks['page']);
        $this->assign('start_date', $start_date);
        $this->assign('end_date', $end_date);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     * 修改银行卡页面（包括操作处理）
     *
     * @since: 2017年1月11日 上午10:29:16
     * @author: lyx
     */
    public function editBank() {
        if (IS_POST) { //数据提交

            $old_bank = M('Bank')->find(I('post.id'));

            $Bank = D("Bank"); // 实例化对象
            if (!$Bank->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $Bank->getError()
                );
            } else {
                // $userex = M("UserExtend")->where(array('user_no'=>$old_bank['user_no']))->find();
                // $user = M("User")->where(array('user_no'=>$old_bank['user_no']))->find();
                // if(!$userex || !$userex['id_card'] || !$user['realname'] || !$user['identification']){
                //     $result = array(
                //             'status'  => false,
                //             'message' => '请到个人信息中完善信息！'
                //     );
                //     $this->ajaxReturn($result);
                // }
                //数据保存
                $re = $Bank->save();
                if ($re !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '银行卡修改成功！'
                    );

                    //操作日志
                    $this->addLog('修改' . $old_bank['user_no'] . '的银行卡信息。原信息【卡号：' . $old_bank['bank_no'] . '；银行：' . $old_bank['bank'] . '; 支行：' . $old_bank['sub_bank']
                            . '】。新信息【卡号：' . I('post.bank_no') . '；银行：' . I('post.bank') . '; 支行：' . I('post.sub_bank') . '】。', I('post.id'));
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
        $realname = M('User')->getFieldByUserNo($bank['user_no'],'realname');
        $banks = M('BankType')->order('sort asc,id asc')->select();
        $this->assign('banks',$banks);
        $this->assign('bank', $bank);
        $this->assign('realname', $realname);
        $this->display();
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
                $this->addLog('删除' . $bank['user_no'] . '的卡号为' . $bank['bank_no'] . '的银行卡。', I('post.id'));
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
    * 订单支付结果查询
    *
    * @since: 2017年1月13日 下午6:02:11
    * @author: lyx
    * @author: xie lu
    */
    public function paymentQuery() {
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
                    'message' => $msg,
                ];
                $this->ajaxReturn($result);
            }
            $result = [
                'status'  => true,
                'message' => '支付状态已同步为最新',
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

    /**
     * 银行列表
     */
    public function bank() {
        $banks = D('BankType')->getList(Constants::YES, array(), $this->sys_config['SYSTEM_PAGE_NUMBER']);

        $this->assign('list', $banks['data']);
        $this->assign('page', $banks['page']);
        $this->display();
    }

    /**
     * 新增银行
     */
    public function addBankType()
    {
        if (IS_POST) { //数据提交
            $BankType = D("BankType"); // 实例化对象
            if (!$BankType->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $BankType->getError()
                );
            } else {
                $res = $BankType->add(); // 写入数据到数据库

                if ($res !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '添加银行成功'
                    );
                    //操作日志
                    $this->addLog('添加银行。银行名称为：' . I('post.title'), $res);
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '添加银行失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 修改银行
     */
    public function editBankType()
    {
        if (IS_POST) { //数据提交
            $BankType = D("BankType"); // 实例化对象
            if (!$BankType->create()) {
                $result = array(
                        'status'  => false,
                        'message' => $BankType->getError()
                );
            } else {
                $category = M('BankType')->find(I('post.id'));
                $res = $BankType->save(); // 写入数据到数据库

                if ($res !== false) {
                    $result = array(
                            'status'  => true,
                            'message' => '修改银行成功'
                    );
                    //操作日志
                    $this->addLog('修改名称为' . $category['title'] . '的银行。修改后的名称为' . I('post.title') . '。', I('post.id'));
                } else {
                    $result = array(
                            'status'  => false,
                            'message' => '修改银行失败！'
                    );
                }
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 删除银行
     */
    public function deleteBankType()
    {
        if (IS_POST) { //数据提交
            $bankType = M('BankType')->find(I('post.id'));

            //删除
            $res = M("BankType")->delete(I('post.id'));
            if ($res !== false) {
                $result = array(
                        'status'  => true,
                        'message' => '删除成功！'
                );
                //操作日志
                $this->addLog('删除名称为' . $bankType['title'] . '的银行。', I('post.id'));
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
     * 银行排序
     */
    public function saveSort()
    {
        if (IS_POST) { //数据提交
            $ids = I('post.ids');
            $rs = D('BankType')->getSort('BankType', 'id', $ids);

            if($rs===false){
                $result = array(
                        'status'  => false,
                        'message' => '排序失败！'
                );
            }else{
                $result = array(
                        'status'  => true,
                        'message' => '排序成功！'
                );
                //操作日志
                $this->addLog('对银行进行了排序');
            }
            $this->ajaxReturn($result);
        }
    }
}