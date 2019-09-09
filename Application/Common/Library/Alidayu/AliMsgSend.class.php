<?php
namespace Common\Library\Alidayu;
include('TopSdk.php');
use TopClient;
use AlibabaAliqinFcSmsNumSendRequest;

/**
 * 阿里大于短信封装类
 *
 * @since: 2017年1月4日 上午10:20:20
 * @author: Wang Peng
 * @version: V1.0.0
 */
class AliMsgSend{
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

    /**阿里大于验证码发送接口
     * @param $_RecNum string 手机号,
     * @param $_smsParam json 短信模板变量,
     * @param $_smsTemplateCode string 短信模板id,
     * @param $_extend string 公共回传参数,
     * @param $_smsFreeSignName string 短信签名,
     * @param $_smsType string 短信类型 default normal,
     * @return bool
     */
    public function sendMsg($_RecNum, $_smsParam, $_smsTemplateCode, $_smsFreeSignName, $_extend = "", $_smsType = "normal")
    {

        if(empty($this->appkey)){
          $result=array(
                'status' => false,
                'msg' => 'app key不能为空'
            );
        }else if(empty($this->secretKey)){
           $result=array(
                'status' => false,
                'msg' => 'App Secret不能为空'
            );
        }else if(empty($_RecNum)){
           $result=array(
                'status' => false,
                'msg' => '手机号不能为空'
            );
        }else if(empty($_smsParam)){
           /* $result=array(
                'status' => false,
                'msg' => '短信模板参数不能为空'
            ); */
        }else if(empty($_smsTemplateCode)){
            $result=array(
                'status' => false,
                'msg' => '短信模板id不能为空'
            );
        }else if(empty($_smsFreeSignName)){
            $result=array(
                'status' => false,
                'msg' => '短信签名不能为空'
            );
        }
        if(!empty($result)){
           return $result;
        }

        //淘宝接口文件
        $c = new TopClient;
        $c ->appkey = $this->appkey;
        $c ->secretKey = $this->secretKey;

        //短信发送接口
        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend( $_extend );
        $req->setSmsType( $_smsType );
        $req->setSmsFreeSignName( $_smsFreeSignName );
        $req->setSmsParam( $_smsParam );
        $req->setRecNum( $_RecNum );
        $req->setSmsTemplateCode( $_smsTemplateCode );
        $resp = $c->execute( $req );
        return $this->sendMsgResult($resp);
    }

    /**对接口返回值进行处理
     * @param null $_resp
     * @return bool
     */
    private function sendMsgResult($_resp = null)
    {

        if($_resp->sub_code == 'isv.appkey-not-exists'){
           $result=array(
                'status'=>false,
                'msg' => 'appkey错误'
            );
        }else if($_resp->msg == 'Invalid signature'){
           $result=array(
                'status'=>false,
                'msg' => 'secretkey错误'
            );
        }else if($_resp->sub_code == 'isv.OUT_OF_SERVICE'){
           $result=array(
                'status'=>false,
                'msg' => '业务停机'
            );
        }else if($_resp->sub_code == 'isv.PRODUCT_UNSUBSCRIBE'){
            $result=array(
                'status'=>false,
                'msg' => '产品服务未开通'
            );
        }else if($_resp->sub_code == 'isv.ACCOUNT_NOT_EXISTS'){
            $result=array(
                'status'=>false,
                'msg' => '账户信息不存在'
            );
        }else if($_resp->sub_code == 'isv.ACCOUNT_ABNORMAL'){
            $result=array(
                'status'=>false,
                'msg' => '账户信息异常'
            );
        }else if($_resp->sub_code == 'isv.SMS_TEMPLATE_ILLEGAL'){
            $result=array(
                'status'=>false,
                'msg' => '模板不合法'
            );
        }else if($_resp->sub_code == 'isv.SMS_SIGNATURE_ILLEGAL'){
            $result=array(
                'status'=>false,
                'msg' => '签名不合法'
            );
        }else if($_resp->sub_code == 'isv.MOBILE_NUMBER_ILLEGAL'){
            $result=array(
                'status'=>false,
                'msg' => '手机号码格式错误'
            );
        }else if($_resp->sub_code == 'isv.MOBILE_COUNT_OVER_LIMIT'){
            $result=array(
                'status'=>false,
                'msg' => '手机号码数量超过限制'
            );
        }else if($_resp->sub_code == 'isv.TEMPLATE_MISSING_PARAMETERS'){
            $result=array(
                'status'=>false,
                'msg' => '短信模板变量缺少参数'
            );
        }else if($_resp->sub_code == 'isv.INVALID_PARAMETERS'){
            $result=array(
                'status'=>false,
                'msg' => '参数异常'
            );
        }else if($_resp->msg == 'Invalid arguments:sms_param'){
            $result=array(
                'status'=>false,
                'msg' => '参数不合法'
            );
        }else if($_resp->sub_code == 'isv.BUSINESS_LIMIT_CONTROL'){
            $result=array(
                'status'=>false,
                'msg' => '触发业务流控限制'
            );
        }else if($_resp->sub_code == 'isv.INVALID_JSON_PARAM'){
             $result=array(
                'status'=>false,
                'msg' => 'JSON参数不合法'
            );
        }else if($_resp->sub_code == 'isv.BLACK_KEY_CONTROL_LIMIT'){
             $result=array(
                'status'=>false,
                'msg' => '模板变量中存在黑名单关键字'
            );
        }else if($_resp->sub_code == 'isv.PARAM_NOT_SUPPORT_URL'){
             $result=array(
                'status'=>false,
                'msg' => '不支持url为变量'
            );
        }else if($_resp->sub_code == 'isv.PARAM_LENGTH_LIMIT'){
            $result=array(
                'status'=>false,
                'msg' => '变量长度受限'
            );
        }else if($_resp->sub_code == 'isv.AMOUNT_NOT_ENOUGH'){
            $result=array(
                'status'=>false,
                'msg' => '余额不足'
            );
        }else if($_resp->result->success){
           $result=array(
                'status'=>true,
            );

        }else{
            $result=array(
                'status'=>false,
                'msg' => '未知异常'
            );
        }

        return $result;
    }
}
?>