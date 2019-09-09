<?php
return array(
        // 定义前台菜单
        'InActiveUserMenu' => array( 
                 array('title'=>'平台首页','icon'=>'icon-home','path'=>'Index/home','is_default'=>'true'),
                array('title'=>'团队管理','icon'=>'icon-group','children'=>array(
                        array('title'=>'注册会员','path'=>'Group/register'),
                        array('title'=>'安置网络','path'=>'Group/placeSystem'),
                        array('title'=>'推荐网络','path'=>'Group/recommendSystem'),
                        array('title'=>'团队会员列表','path'=>'Group/placeList'),
                        array('title'=>'推荐会员列表','path'=>'Group/recommendList'),
                        array('title'=>'下属会员列表','path'=>'Group/member'),
                )),
                array('title'=>'资料管理','icon'=>'icon-doc','children'=>array(
                        array('title'=>'个人信息','path'=>'User/info'),
                        array('title'=>'修改登录密码','path'=>'User/changePasswd'),
                        array('title'=>'修改安全码','path'=>'User/changeTrade'),
                        array('title'=>'账户信息','path'=>'User/account'),
                )),
        ),
);
			

