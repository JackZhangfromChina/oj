<?php
return array(
        // 定义前台菜单
        'UserMenu' => array( 
                array('title'=>'平台首页','icon'=>'icon-home','path'=>'Index/home','is_default'=>'true'),
                array('title'=>'团队管理','icon'=>'icon-group','children'=>array(
                        array('title'=>'注册会员','path'=>'Group/register'),
                        array('title'=>'安置网络','path'=>'Group/placeSystem'),
                        array('title'=>'推荐网络','path'=>'Group/recommendSystem'),
                        array('title'=>'团队会员列表','path'=>'Group/placeList'),
                        array('title'=>'推荐会员列表','path'=>'Group/recommendList'),
                        array('title'=>'下属会员列表','path'=>'Group/member'),
                )),
                array('title'=>'订单管理','icon'=>'icon-basket','children'=>array(
                        array('title'=>'商品订购','path'=>'Mall/goods'),
                        array('title'=>'我的购物车','path'=>'Mall/cart'),
                        array('title'=>'订单查询','path'=>'Mall/orderList'),
                        array('title'=>'收货地址','path'=>'Mall/addrList'),
                )),
                array('title'=>'资金转换','icon'=>'icon-exchange','children'=>array(
                        array('title'=>'注册币转现金','path'=>'Account/convert','params'=>array('type'=>0)),
                        array('title'=>'购物币转现金','path'=>'Account/convert','params'=>array('type'=>1)),
                        array('title'=>'奖金转现金','path'=>'Account/convert','params'=>array('type'=>2)),
                        array('title'=>'返利转现金','path'=>'Account/convert','params'=>array('type'=>3)),
                        array('title'=>'现金转注册币','path'=>'Account/convert','params'=>array('type'=>4)),
                        array('title'=>'现金转购物币','path'=>'Account/convert','params'=>array('type'=>5)),
                        array('title'=>'现金转奖金','path'=>'Account/convert','params'=>array('type'=>6)),
                        array('title'=>'现金转返利','path'=>'Account/convert','params'=>array('type'=>7)),
                )),
                array('title'=>'资金转账','icon'=>'icon-arrow-curved','children'=>array(
                        array('title'=>'注册币转账','path'=>'Account/giro','params'=>array('account_type'=>0)),
                        array('title'=>'购物币转账','path'=>'Account/giro','params'=>array('account_type'=>1)),
                        array('title'=>'现金转账','path'=>'Account/giro','params'=>array('account_type'=>2)),
                        array('title'=>'奖金转账','path'=>'Account/giro','params'=>array('account_type'=>3)),
                        array('title'=>'返利转账','path'=>'Account/giro','params'=>array('account_type'=>4)),
                )),
                array('title'=>'现金管理','icon'=>'fa fa-money','children'=>array(
                        array('title'=>'银行卡管理','path'=>'Cash/bankList'),
                        array('title'=>'充值及明细','path'=>'Cash/payList'),
                        array('title'=>'提现及明细','path'=>'Cash/withdrawList'),
                        array('title'=>'汇款及明细','path'=>'Cash/remitList'),
                )),
                array('title'=>'财务明细','icon'=>'icon-suitcase','children'=>array(
                        array('title'=>'转账记录','path'=>'Account/giroList'),
                        array('title'=>'转入记录','path'=>'Account/intoList'),
                        array('title'=>'奖金明细','path'=>'Account/rewardList'),
                        array('title'=>'返利明细','path'=>'Account/returnList'),
                        array('title'=>'流水账记录','path'=>'Account/index'),
                )),
                array('title'=>'信息管理','icon'=>'icon-mail','children'=>array(
                        array('title'=>'系统公告','path'=>'News/index'),
                        array('title'=>'写邮件','path'=>'Mail/add'),
                        array('title'=>'发件箱','path'=>'Mail/outbox'),
                        array('title'=>'收件箱','path'=>'Mail/inbox'),
                        array('title'=>'未读邮件','path'=>'Mail/unread'),
                )),
                array('title'=>'资料管理','icon'=>'icon-doc','children'=>array(
                        array('title'=>'个人信息','path'=>'User/info'),
                        array('title'=>'修改登录密码','path'=>'User/changePasswd'),
                        array('title'=>'修改安全码','path'=>'User/changeTrade'),
                        array('title'=>'账户信息','path'=>'User/account'),
                )),
        ),
);
			

