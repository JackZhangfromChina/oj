<?php

namespace Common\Library;

/**
 * 封装海客短信接口（）
 */
class HeyskyMsgSend
{
	//定义app证书key
    private $appkey = null;
    private $secretKey = null;

    public function __construct($_appkey, $_secretKey)
    {
        $this->appkey = $_appkey;
        $this->secretKey = $_secretKey;
    }

    public function __call ($name, $arguments)
    {
        return "The function is not exist!";
    }

    /*
     * @cpid string Api 帐号
     * @cppwd string Api 密码
     * @to  number  目的地号码，国家代码+手机号码（国家号码、手机号码均不能带开头的0）
     * @content string 短信内容
     *
     * @Return string 消息ID，如果消息ID为空，或者代码抛出异常，则是发送未成功。
    */
   	/**
   	 * [sendMsg description]
   	 * @param  [type] $_RecNum [description]
   	 * @param  [type] $cppwd   [description]
   	 * @param  [type] $to      [description]
   	 * @param  [type] $content [description]
   	 * @return [type]          [description]
   	 */
    public function sendMsg($da, $content)
    {
    	if (empty($this->appkey)) {
            $result = [
                'status' => false,
                'msg' => 'app key不能为空'
            ];
        } else if (empty($this->secretKey)) {
           	$result = [
                'status' => false,
                'msg' => 'App Secret不能为空'
            ];
        } else if (empty($da)) {
           	$result = [
                'status' => false,
                'msg' => '手机号不能为空'
            ];
        } else if (empty($content)) {
            $result=array(
                'status' => false,
                'msg' => '短信内容不能为空'
            );
        }
        if (!empty($result)) {
           	return $result;
        }
        $result = $this->verifyPhoneNumber($da);
        if (!$result['status']) {
        	return $result;
        }
        $params = [
        	'command' => 'MT_REQUEST',
    		'cpid' => $this->appkey,
    		'cppwd' => $this->secretKey,
    		'da' => $da,
    		'sm' => $content,
    	];
        // http接口，支持 https 访问，如有安全方面需求，可以访问 https开头
        $api = 'http://api2.santo.cc/submit?'.http_build_query($params);
        try {
            $resp = file_get_contents($api);
            parse_str($resp, $resp);
            if (isset($resp['mterrcode'])) {
            	$result = $this->sendErrCode($resp['mterrcode']);
            	if (!empty($result)) {
		           return $result;
		        }
		        if ($resp['mtstat'] == 'ACCEPTD') {
		        	$result = [
		                'status' => true,
		                'msg' => '短信发送成功，mtmsgid：'.$resp['mtmsgid']
		            ];
		        } else {
		        	$result = [
		                'status' => false,
		                'msg' => '短信发送失败，mtmsgid：'.$resp['mtmsgid']
		            ];
		        }
            } else {
				$result = [
	                'status' => false,
	                'msg' => '网络错误'
	            ];
            }
	        return $result;
        } catch(Exception $e){
            $result = [
                'status' => false,
                'msg' => $e->getMessage()
            ];
            return $result;
        }
        // return self::extract_msgid($resp);
    }

    /**
     * 检查下发号码的有效性
     * @param  mixed $da  下发号码
     * @return [type]     [description]
     */
    public function verifyPhoneNumber($da)
    {
    	if (empty($this->appkey)) {
            $result = [
                'status' => false,
                'msg' => 'app key不能为空'
            ];
        } else if (empty($this->secretKey)) {
           	$result = [
                'status' => false,
                'msg' => 'App Secret不能为空'
            ];
        } else if (empty($da)) {
           	$result = [
                'status' => false,
                'msg' => '手机号不能为空'
            ];
        }
        if (!empty($result)) {
           	return $result;
        }
    	$params = [
    		'cpid' => $this->appkey,
    		'cppwd' => $this->secretKey,
    		'da' => $da,
    	];
    	$api = 'http://api2.santo.cc/verifyPhoneNumber?'.http_build_query($params);
    	try {
            $resp = file_get_contents($api);
            $resp = json_decode($resp, 1);
            if (isset($resp['errcode'])) {
            	$result = $this->sendErrCode($resp['errcode']);
            	if (!empty($result)) {
		           return $result;
		        }
		        if ($resp['isValid']) {
		        	$result = [
		                'status' => true,
		                'msg' => '下行号码有效'
		            ];
		        } else {
		        	$result = [
		                'status' => false,
		                'msg' => '下行号码无效，正确格式为【8613910825657】'
		            ];
		        }
            } else {
				$result = [
	                'status' => false,
	                'msg' => '网络错误'
	            ];
            }
	        return $result;
        } catch(Exception $e){
            $result = [
                'status' => false,
                'msg' => $e->getMessage()
            ];
            return $result;
        }
    }

	static function extract_msgid($resp)
    {
        preg_match('/mtmsgid=(.*?)&/', $resp, $re);
        if (!empty($re) && count($re) >= 2)
            return $re[1];

        return "";
    }

    private function sendErrCode($code)
    {
    	$result = [];
    	switch ($code) {
    		case '0101':
    			$result = [
    				'status' => false,
    				'msg' => '无效的 command 参数',
    			];
    			break;
    		case '0100':
    			$result = [
    				'status' => false,
    				'msg' => '请求参数错误',
    			];
    			break;
    		case '0104':
    			$result = [
    				'status' => false,
    				'msg' => '账号信息错误',
    			];
    			break;
    		case '0106':
    			$result = [
    				'status' => false,
    				'msg' => '账号密码错误',
    			];
    			break;
    		case '0107':
    			$result = [
    				'status' => false,
    				'msg' => '账号金额或信用额度不足',
    			];
    			break;
    		case '0108':
    			$result = [
    				'status' => false,
    				'msg' => 'IP 未在白名单中',
    			];
    			break;
    		case '0110':
    			$result = [
    				'status' => false,
    				'msg' => '目标号码格式错误或群发号码数量超过 100 个',
    			];
    			break;
    		case '0111':
    			$result = [
    				'status' => false,
    				'msg' => '发送内容不能为空',
    			];
    			break;
    		case '0112':
    			$result = [
    				'status' => false,
    				'msg' => '下发国内号码没有对应模板',
    			];
    			break;
    		case '0600':
    			$result = [
    				'status' => false,
    				'msg' => '未知错误',
    			];
    			break;
    	}

        return $result;
    }
}