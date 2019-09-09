<?php
/**
 * 环迅支付回调
 */
namespace Api\Controller;

use Common\Controller\BaseController;
use Common\Conf\Constants;
use Payment\Common\PayException;

ini_set('date.timezone','Asia/Shanghai');

class IpsController extends BaseController
{
	protected $ipsConfig;

	public function _initialize()
	{
		parent::_initialize();
		$this->ipsConfig = [
            'Version' => 'v1.0.0',
            'InMerName' => $this->sys_config['PAY_MERNAME_IPS'],
            'MerCode' => $this->sys_config['PAY_MERCODE_IPS'],
            'Account' => $this->sys_config['PAY_ACCOUNT_IPS'],
            'MerCert' => $this->sys_config['PAY_MERCERT_IPS'],
            'IPSRSAPUB' => $this->sys_config['PAY_IPSRSAPUB_IPS'],
            'PostUrl' => 'https://newpay.ips.com.cn/psfp-entry/gateway/payment.do',
			'S2Snotify_url' => $this->sys_config['WEB_DOMAIN'] . '/Api/Ips/getNotify',
			'return_url' => $this->sys_config['WEB_DOMAIN'] . '/Api/Ips/getReturn',
			'Ccy' => '156',
            'Lang' => 'GB',
            'OrderEncodeType' => '5',
            'RetType' => '1',
            'MsgId' => '',
		];
	}

    public function getConfig()
    {
        return $this->ipsConfig;
    }

	/**
	 * 异步通知
	 */
	public function getNotify()
	{
        require_once(realpath(COMMON_PATH.'Library').'/Ipspay/lib/IpsPay_MD5.function.php');
        require_once(realpath(COMMON_PATH.'Library').'/Ipspay/lib/IpsPayCode.funtion.php');
        try {
            if(empty($_REQUEST)) {
                $verifyStatus = '订单支付失败';
            } else {
                $paymentResult = $_REQUEST['paymentResult'];
                $xmlResult = new \SimpleXMLElement($paymentResult);

                $strSignature = $xmlResult->GateWayRsp->head->Signature;
                $retEncodeType =$xmlResult->GateWayRsp->body->RetEncodeType;
                $strBody = subStrXml("<body>","</body>",$paymentResult);
                $retData = $xmlResult->GateWayRsp->body;

                if ($retEncodeType =="16" && !rsaVerify($strBody,$strSignature,$this->ipsConfig["IPSRSAPUB"])) {
                    // 公钥证书验证失败
                    $verifyStatus = '订单支付失败';
                } elseif (!md5Verify($strBody,$strSignature,$this->ipsConfig["MerCode"],$this->ipsConfig["MerCert"])) {
                    // 支付返回报文验签失败
                    $verifyStatus = '订单支付失败';
                } elseif ($order=D('Pay')->where(array('order_no'=>strval($retData->MerBillNo)))->find()) {
                    switch ($retData->Status) {
                        case 'Y':
                            $verifyStatus = '支付成功';
                            if (!$order['trade_no'] && $order['status']!=1) {
                                if (!$this->setOrder($order, $retData)) {
                                    $verifyStatus .= '，但系统订单更新失败';
                                }
                            }
                            break;
                        case 'P': // 交易处理中
                            $verifyStatus = '支付订单等待处理';
                            // nothing
                            break;
                        case 'N': // 交易失败
                        default:
                            if (!$order['trade_no'] && $order['status']!=1) {
                                $verifyStatus = '订单支付失败';
                                $payData = array(
                                    'trade_no'=>strval($retData->IpsBillNo),
                                    'success_time'=>curr_time(),
                                    'status'=>2
                                );
                                D('Pay')->where(array('order_no'=>$order['order_no']))->save($payData);
                            }
                            break;
                    }

                } else {
                    $verifyStatus = '订单不存在';
                }
                $this->addLog(($order['user_no']?:''), $verifyStatus);
                echo "success";
            }
        } catch (Exception $e) {
            echo $e->errorMessage();
            exit;
        }
	}

	/**
	 * 同步通知
	 */
	public function getReturn()
	{
		require_once(realpath(COMMON_PATH.'Library').'/Ipspay/lib/IpsPay_MD5.function.php');
        require_once(realpath(COMMON_PATH.'Library').'/Ipspay/lib/IpsPayCode.funtion.php');
        try {
            if(empty($_REQUEST)) {
                $verifyStatus = '订单支付失败';
            } else {
                $paymentResult = $_REQUEST['paymentResult'];
                $xmlResult = new \SimpleXMLElement($paymentResult);

                $strSignature = $xmlResult->GateWayRsp->head->Signature;
                $retEncodeType =$xmlResult->GateWayRsp->body->RetEncodeType;
                $strBody = subStrXml("<body>","</body>",$paymentResult);
                $retData = $xmlResult->GateWayRsp->body;

                if ($retEncodeType =="16" && !rsaVerify($strBody,$strSignature,$this->ipsConfig["IPSRSAPUB"])) {
                    // 公钥证书验证失败
                    redirect(U('Home/Cash/payList'));
                } elseif (!md5Verify($strBody,$strSignature,$this->ipsConfig["MerCode"],$this->ipsConfig["MerCert"])) {
                    // 支付返回报文验签失败
                    redirect(U('Home/Cash/payList'));
                } else {
                    switch ($retData->Status) {
                        case 'Y':
                            redirect(U('Home/Cash/index',array('order_no'=>$retData->MerBillNo)));
                            break;
                        case 'P': // 交易处理中
                        case 'N': // 交易失败
                        default:
                            redirect(U('Home/Cash/payList'));
                            break;
                    }
                }
            }
        } catch (Exception $e) {
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

        require_once(realpath(COMMON_PATH.'Library').'/Ipspay/lib/IpsPay_MD5.function.php');
        require_once(realpath(COMMON_PATH.'Library').'/Ipspay/lib/IpsPayCode.funtion.php');
        try {
            $parameter = $this->ipsConfig;
            $data = [
                "Version"       => $parameter['Version'],
                "MerCode"       => $parameter['MerCode'],
                "MerName"       => $parameter['InMerName'],
                "Account"       => $parameter['Account'],
                "ReqDate"       => date("YmdHis"),
                "MerCert"       => $parameter['MerCert'],
                "MerBillNo"     => $order['order_no'],
                "Date"          => date("Ymd",strtotime($order['add_time'])),
                "Amount"        => $order['amount'],
            ];
            vendor("IpsGetOrderByMerBillNoSubmit",realpath(COMMON_PATH.'Library\Ipspay\lib'), '.class.php');

            //建立请求
            $ipsGetOrderByMerBillNoSubmit = new \IpsGetOrderByMerBillNoSubmit($parameter);
            $ret = $ipsGetOrderByMerBillNoSubmit->buildRequestForm($data);

            // 返回数据处理
            $xmlResult = new \SimpleXMLElement($ret);

            $retData = $xmlResult->OrderQueryRsp->body;

            switch ($retData->Status) {
                case 'Y':
                    $msg = '订单已支付成功';
                    if (!$this->setOrder($order, $retData)) {
                        $msg .= '，但系统订单更新失败';
                    }
                    return false;
                case 'P': // 交易处理中
                    $msg = '订单支付等待处理';
                    // nothing
                    return false;
                case 'N': // 交易失败
                    $msg = '订单已取消';
                    $payData = array(
                        'trade_no'=>strval($retData->IpsBillNo),
                        'success_time'=>curr_time(),
                        'status'=>2
                    );
                    D('Pay')->where(array('order_no'=>$order['order_no']))->save($payData);
                    return false;
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
                'trade_no' => strval($retData->IpsBillNo),
                'status' => 1,
                'success_time' => date_format(date_create($retData->IpsBillTime), 'Y-m-d H:i:s'),
            ]);
        $amount = round($retData->Amount / ($this->sys_config['SYSTEM_EXRATE']) ,2);
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
                    'remark'        => '环迅充值'.$order['order_no'].'，增加现金：'.$amount,
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