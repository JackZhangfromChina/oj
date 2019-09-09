<?php
/**
 * 支付宝支付回调
 */
namespace Api\Controller;

use Common\Controller\BaseController;
use Common\Conf\Constants;
use Payment\Common\PayException;
use Payment\Client\Notify;
use Payment\Client\Query;

class AliController extends BaseController
{
	protected $aliConfig;

	public function _initialize()
	{
		parent::_initialize();
		$this->aliConfig = [
			'use_sandbox' => false,
			'partner' => $this->sys_config['PAY_PARTNER_ALI'],
			'app_id' => $this->sys_config['PAY_APPID_ALI'],
			'sign_type' => 'RSA',
			'ali_public_key' => $this->sys_config['PAY_PUBLIC_KEY_ALI'],
			'rsa_private_key' => $this->sys_config['PAY_PRIVAT_EKEY_ALI'],
			'limit_pay' => [
			    // 'balance', // 余额
			    // 'moneyFund',
			    // 'debitCardExpress',
			    // 'creditCard',
			    // 'creditCardExpress',
			    'creditCardCartoon',
			    // 'credit_group',
			],
			'notify_url' => $this->sys_config['WEB_DOMAIN'] . '/Api/Ali/getNotify',
			'return_url' => $this->sys_config['WEB_DOMAIN'] . '/Api/Ali/getReturn',
			'return_raw' => true,
		];
	}

    public function getConfig()
    {
        return $this->aliConfig;
    }

	/**
	 * 异步通知
	 */
	public function getNotify()
	{
		$type = 'ali_charge';// xx_charge
        if (stripos($type, 'ali') !== false) {
            $config = $this->aliConfig;
        }

        try {
            $retData = Notify::getNotifyData($type, $config);// 获取第三方的原始数据
            $order =  D('Pay')->where(array('order_no'=>$retData['out_trade_no']))->find();
            if ($order) {
                if ($retData['trade_status'] == 'TRADE_SUCCESS') {
                    $verifyStatus = '支付成功';
                    if (!$order['trade_no'] && $order['status']!=1) {
                    	if (!$this->setOrder($order, $retData)) {
                    		$verifyStatus .= '，但系统订单更新失败';
                    	}
                    }
                }else{
                    if (!$order['trade_no'] && $order['status']!=1) {
                        $verifyStatus = '订单支付失败';
                        $payData = array(
                            'trade_no'=>$retData['trade_no'],
                            'success_time'=>curr_time(),
                            'status'=>2
                        );
                        D('Pay')->where(array('order_no'=>$order['order_no']))->save($payData);
                    }
                }
            }else{
                $verifyStatus = '订单不存在';
            }
            $this->addLog(($order['user_no']?:''), $verifyStatus);
                echo "success";
        } catch (PayException $e) {
            echo $e->errorMessage();
            exit;
        }
	}

	/**
	 * 同步通知
	 */
	public function getReturn()
	{
		$type = 'ali_charge';// xx_charge
        if (stripos($type, 'ali') !== false) {
            $config = $this->aliConfig;;
        }

        try {
            $retData = Notify::getNotifyData($type, $config);// 获取第三方的原始数据
            if($retData['trade_status']=='TRADE_SUCCESS'){
                redirect(U('Home/Cash/index',array('order_no'=>$retData['out_trade_no'])));
            }else{
                redirect(U('Home/Cash/payList'));
            }
        } catch (PayException $e) {
            echo $e->errorMessage();
            exit;
        }
	}

	/**
	 * 支付查询
	 */
	public function paymentQuery($order_no, &$msg)
	{
		if(empty($order_no)){
            $msg = '订单不存在';
            return false;
        }
        // 系统内查询
        $order = D('Pay')->getByOrderNo($order_no);
        if(empty($order)){
            $msg = '订单不存在';
            return false;
        }else if($order['status'] == Constants::PAY_STATUS_SUCCESS){
            $msg = '订单已支付完成！';
            return false;
        }else if($order['status'] == Constants::PAY_STATUS_CLOSE){
            $msg = '订单已取消';
            return false;
        }

        $data = [
            'out_trade_no' => $order['order_no'],
        ];
        $type = 'ali_charge';
        $config = $this->aliConfig;
        try {
            $ret = Query::run($type, $config, $data);

            if(isset($ret['trade_status'])){
                if($ret['trade_status']=='TRADE_SUCCESS'||$ret['trade_status']=='TRADE_FINISHED'){
                    $msg = '订单已支付成功';
                    if (!$this->setOrder($order, $ret)) {
                        $msg .= '，但系统订单更新失败！';
                        return true;
                    }
                    return false;
                }elseif($ret['trade_status']=='TRADE_CLOSED'){
                    $msg = '订单已取消';
                    $payData = array(
                        'trade_no'=>$ret['trade_no'],
                        'success_time'=>curr_time(),
                        'status'=>2
                    );
                    D('Pay')->where(array('order_no'=>$order['order_no']))->save($payData);
                    return false;
                }
            }
            $msg = '订单状态查询错误';
            return true;
        } catch (PayException $e) {
            $msg = $e->errorMessage();
            return true;
        }
	}

	/**
	 * 支付后续操作
	 * @param array $order   订单信息
	 * @param array $retData 支付信息
	 */
	public function setOrder($order, $retData)
	{
		// 更新支付单信息
        $flag = D('Pay')->where([
                'order_no' => $order['order_no'],
            ])->save([
                'trade_no' => $retData['trade_no'],
                'status' => 1,
                'success_time' => $retData['send_pay_date'],
            ]);
        $amount = isset($retData['total_fee'])?$retData['total_fee']:$retData['total_amount'];
        $amount = round($amount / ($this->sys_config['SYSTEM_EXRATE']) ,2);
        $user_data = [
                'cb_account' => ['exp', 'cb_account+'.round($amount, 2)],
            ];
        $user = D('User')->field('user_no,cb_account')->where(array('user_no'=>$order['user_no']))->find();
        // 变更会员账户信息
        if ($flag) {
            $flag = D('User')->where(['user_no'=>$user['user_no']])->save($user_data);
        }
        // 保存流水账记录
        if ($flag) {
            $flag = D('AccountRecord')->add([
                    'user_no'       => $user['user_no'],
                    'account_type'  => Constants::ACCOUNT_TYPE_CB,
                    'type'          => Constants::ACCOUNT_CHANGE_TYPE_INC,
                    'amount'        => $amount,
                    'balance'       => $user['cb_account'] + $amount,
                    'remark'        => '支付宝充值'.$order['order_no'].'，增加现金：'.$amount,
                    'add_time'      => curr_time()
                ]);
        }
        //短信提醒
        // $msg_param = array(
        //         'amount'        => strval($amount),
        // );
        // $receiver = array(
        //         'user_no'       => $user['user_no']
        // );
        // $this->sendMessage($receiver, 'MESSAGE_PAY', json_encode($msg_param), Constants::MESSAGE_TYPE_PAY);
        return $flag;
	}

    /**
     * 日志记录
     * @param string $user_no 用户编号
     * @param string $remark  备注
     */
    public function addLog($user_no, $remark){
        $log = array(
            'role'         => Constants::LOG_ROLE_USER,
            'username'     => $user_no,
            'type'         => Constants::LOG_TYPE_OPERATION,
            'remark'       => $remark,
            'operate_time' => curr_time(),
        );
        M('Log')->add($log);
    }
}