<?php
namespace Common\Conf;

/**
* 基础常量
*
* @since: 2016年3月2日
* @author: 李永侠
* @version: V1.0.0
*/
class Constants {
    //正常/禁用
    const NORMAL = 1;
    const DISABLE = 0;
    
    //是/否
    const YES = 1;
    const NO = 0;
    
    //安置位置
    const LOCATION_LEFT = 1;
    const LOCATION_RIGHT = 2;
    
    //结算方式
    const SETTLEMENT_METHOD_DAY = 'day';    //日结
    const SETTLEMENT_METHOD_SECOND = 'second';  //秒结
    
    //忘记密码处理方式
    const FORGET_PASSWORD_IGNORE = 'ignore';    //忽略
    const FORGET_PASSWORD_MESSAGE = 'message';  //短信
    const FORGET_PASSWORD_EMAIL = 'email';  //email
    const FORGET_PASSWORD_TIME = 5; //设置5分钟找回
    
    //多语言
    const LANGUAGE_ZH_CN = 'zh_cn';  //中文简体
    const LANGUAGE_ZH_TW = 'zh_tw';  //中文繁体
    const LANGUAGE_EN = 'en';  //英文
    
    //多语言
    const SHIPPING_ALL_NO_FREE = -1;  //全部不包邮
    const SHIPPING_ALL_FREE = 0;  //全部包邮
    const SHIPPING_FREIGHT_COLLECT = -1;  //到付
    
    //默认顶级id值
    const DEFAULT_PARENT_ID = 0;
    
    
    //文件存储路径
    const UPLOAD_ROOT_PATH = "Upload/";   //根目录
    const UPLOAD_DATABASE_PATH = "data/"; //数据备份目录
    const UPLOAD_MALL_PATH = "mall/";     //商城相关保存目录
    const UPLOAD_EDITOR_PATH = "editor/"; //在线编辑器保存目录
    
    //转账类型
    const GIRO_TYPE_USER = 0; //会员
    const GIRO_TYPE_SYSTEM = 1; //系统
    
    //账号变更类型
    const ACCOUNT_CHANGE_TYPE_INC = 0; //增加
    const ACCOUNT_CHANGE_TYPE_DEC = 1; //减少
    
    //账号类型
    const ACCOUNT_TYPE_EB = 0;  //EB
    const ACCOUNT_TYPE_TB = 1;  //TB
    const ACCOUNT_TYPE_CB = 2;  //CB
    const ACCOUNT_TYPE_MB = 3;  //MB
    const ACCOUNT_TYPE_RB = 4;  //RB
    
    //账号转换类型
    const ACCOUNT_CONVERT_EBTOCB = 0;  //EB转CB
    const ACCOUNT_CONVERT_TBTOCB = 1;  //TB转CB
    const ACCOUNT_CONVERT_MBTOCB = 2;  //MB转CB
    const ACCOUNT_CONVERT_RBTOCB = 3;  //RB转CB
    const ACCOUNT_CONVERT_CBTOEB = 4;  //CB转EB
    const ACCOUNT_CONVERT_CBTOTB = 5;  //CB转TB
    const ACCOUNT_CONVERT_CBTOMB = 6;  //CB转MB
    const ACCOUNT_CONVERT_CBTORB = 7;  //CB转RB
    
    //操作状态
    const OPERATE_STATUS_INITIAL = 0;  //未处理
    const OPERATE_STATUS_CONFIRM = 1;  //已完成
    const OPERATE_STATUS_REJECT = 2;  //已驳回
    
    //日志用户角色
    const LOG_ROLE_USER = 0; //会员
    const LOG_ROLE_ADMIN = 1; //管理员
    
    //日志类型
    const LOG_TYPE_LOGIN = 0; //登录
    const LOG_TYPE_OPERATION = 1; //操作
    
    //短信类型
    const MESSAGE_TYPE_FORGET = 0; //忘记密码
    const MESSAGE_TYPE_ACTIVATE = 1; //会员激活
    const MESSAGE_TYPE_WITHDRAW = 2; //提现审核
    const MESSAGE_TYPE_DEDUCT = 3; //后台扣款
    const MESSAGE_TYPE_RECHARGE = 4; //后台充值
    const MESSAGE_TYPE_ROLL_IN = 5; //资金转入
    const MESSAGE_TYPE_ROLL_OUT = 6; //资金转出
    const MESSAGE_TYPE_REMIT = 7; //汇款审核
    const MESSAGE_TYPE_BUY = 8; //商城下单
    const MESSAGE_TYPE_ORDER = 9; //订单审核
    
    //支付回调配置
    const PAY_CALLBACK_SYNC = 'Api/Back/returnNow'; //同步回调请求地址（相对项目）
    const PAY_CALLBACK_ASYNC = 'Api/Back/returnPage'; //异步回调请求地址（相对项目）
    //支付状态
    const PAY_STATUS_WAIT = 0;  //等待支付
    const PAY_STATUS_SUCCESS = 1;  //支付成功
    const PAY_STATUS_CLOSE = 2;  //已关闭
    
    //业绩类型
    const MARKET_TYPE_ENROLL = 0; //注册
    const MARKET_TYPE_UPGRADE = 1; //升级
    
    //允许购物角色
    const MALL_ALLOW_ROLE_ALL = 'all'; //所有会员
    const MALL_ALLOW_ROLE_SERVICE_CENTER = 'service_center'; //报单中心
    
    //分页数设定
    const PAGE_NUMBER = 10;
    const PAGE_NUMBER_MALL = 12;
    //网络图显示层数
    const HOME_LEVEL = 3;
    const ADMIN_LEVEL = 5;

    //商城设置
    const ORDER_SHIPPING_TO_PAY = 1; //1:到付
    const ORDER_SHIPPING_WITHHOLD = 2; //2:运费代扣
    const GOODS_STOCK_DECLINE = 1; //库存损耗
    const GOODS_STOCK_ADD = 0; //库存追加
    const GOODS_UPPER = 1; //上架
    const GOOD_LOWER = 2; //下架
    const REWARD_TYPE_TOUCH = 0; //对碰奖
    const REWARD_TYPE_RECOMMEND = 1; //推荐奖
    const REWARD_TYPE_LEADER = 2; //领导奖
    const REWARD_TYPE_POINT = 3; //见点奖
    const REWARD_TYPE_DECLARATION = 4; //报单奖
    const REWARD_TYPE_LAYER = 5; //层奖
    const REWARD_TYPE_TOUCHLAYER = 6; //层碰奖
}