<?php
ini_set('date.timezone','Asia/Shanghai');
require_once("IpsPay_MD5.function.php");
require_once("IpsPayCode.funtion.php");
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("./logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class IpsGetOrderByMerBillNoSubmit
{
    var $ipspay_config;
     
    function __construct($ipspay_config){
        $this->ipspay_config = $ipspay_config;
    }
    function IpsGetOrderByMerBillNoSubmit($ipspay_config) {
        
        $this->__construct($ipspay_config);
    }
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    function buildRequestForm($para_temp) {
        // 创建请求资源
        $client = new SoapClient("https://newpay.ips.com.cn/psfp-entry/services/order?wsdl");
        // 待请求参数xml
        $param = $this->buildRequestPara($para_temp);
        // 接受响应
        $reuslt = $client->getOrderByMerBillNo($param);

        return $reuslt;
    }
    
    /**
     * 生成要请求给IPS的参数XMl
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数XMl
     */
    function buildRequestPara($para_temp) {
        $sReqXml = "<Ips>";
        $sReqXml .= "<OrderQueryReq>";
        $sReqXml .= $this->buildHead($para_temp);
        $sReqXml .= $this->buildBody($para_temp);
        $sReqXml .= "</OrderQueryReq>";
        $sReqXml .= "</Ips>";
        Log::DEBUG("请求给IPS的参数XMl:" . $sReqXml);
        return $sReqXml;
    }
    /**
     * 请求报文头
     * @param   $para_temp 请求前的参数数组
     * @return 要请求的报文头
     */
    function buildHead($para_temp){
        $sReqXmlHead = "<head>";
        $sReqXmlHead .= "<Version>".$para_temp["Version"]."</Version>";
        $sReqXmlHead .= "<MerCode>".$para_temp["MerCode"]."</MerCode>";
        $sReqXmlHead .= "<MerName>".$para_temp["MerName"]."</MerName>";
        $sReqXmlHead .= "<Account>".$para_temp["Account"]."</Account>";
        $sReqXmlHead .= "<ReqDate>".$para_temp["ReqDate"]."</ReqDate>";
        $sReqXmlHead .= "<Signature>".md5Sign($this->buildBody($para_temp),$para_temp["MerCode"],$this->ipspay_config['MerCert'])."</Signature>";
        $sReqXmlHead .= "</head>";
        return $sReqXmlHead;
    }
    /**
     *  请求报文体
     * @param  $para_temp 请求前的参数数组
     * @return 要请求的报文体
     */
    function buildBody($para_temp){
        $sReqXmlBody = "<body>";
        $sReqXmlBody .= "<MerBillNo>".$para_temp["MerBillNo"]."</MerBillNo>";
        $sReqXmlBody .= "<Date>".$para_temp["Date"]."</Date>";
        $sReqXmlBody .= "<Amount>".$para_temp["Amount"]."</Amount>";
        $sReqXmlBody .= "</body>";
        return $sReqXmlBody;
    }
}

?>