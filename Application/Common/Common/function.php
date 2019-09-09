<?php
    //获取IP
    function client_ip($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
            $ip=$_SERVER['HTTP_X_REAL_IP'];
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
        }else{
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     *获得随机数
     *
     * @param $length
     * @return string
     * @since: 2016年12月19日 上午9:26:15
     * @author: xielu
     */
    function getRandStr($length){
        //随机的字符串
        $str='abcdefghijklmnopqrstuvwxyz0123456789';
        //用户表中最大id
        $maxScore = D('User')->max('id');
        //最大用户id+1后转成十六进制，另外再加两位随机
        return dechex($maxScore+1) . substr(str_shuffle($str),0,$length);
    }

    /**
    * 格式化当前时间
    *
    * @since: 2016年12月13日 上午10:29:25
    * @author: lyx
    */
    function curr_time() {
        return date("Y-m-d H:i:s",time());
    }

    /**
    * 检查字符长度
    *
    * @param    string  $str    字符串
    * @param    int     $min    长度范围最小值
    * @param    int     $max    长度范围最大值
    * @return    boolean
    *
    * @since: 2016年12月16日 下午6:03:27
    * @author: lyx
    */
    function checklen($str,$min,$max) {
        if (mb_strlen($str,'utf-8')>$max || mb_strlen($str,'utf-8')<$min) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * 验证金额的合法性，两位小数
     *
     * @since: 2017年1月14日 上午10:06:41
     * @author: lyx
     */
    function check_money($money) {
        if (preg_match('/^([1-9][0-9]{0,7}|0)(\.[0-9]{1,2})?$/', $money)) {
            return true;
        }
        return false;
    }

    /**
    * 生成支付订单号算法
    *
    * @since: 2017年1月14日 上午10:35:41
    * @author: lyx
    */
    function pay_order_no(){
        return 'P-'.date('YmdHis').rand(10,99);
    }
    /**
     * 生成支付订单号算法
     *
     * @since: 2017年1月14日 上午10:35:41
     * @author: lyx
     */
    function token_no(){
        return date('YmdHis').rand(10,99);
    }
    /**
     * 生成商城订单号算法
     *
     * @since: 2017年1月14日 上午10:44:22
     * @author: lyx
     */
    function mall_order_no(){
        return 'M-'.date('YmdHis').rand(10,99);
    }

    /**
     * 判断是否为移动端
     * @return bool
     */
    function is_mobile_request()
    {
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser++;
        if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
        );
        if (in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser++;
        // Pre-final check to reset everything if the user is on Windows
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser = 0;
        // But WP7 is also Windows, with a slightly different characteristic
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
            $mobile_browser++;
        if ($mobile_browser > 0)
            return true;
        else
            return false;
    }
