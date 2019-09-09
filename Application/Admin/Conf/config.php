<?php
return array(
        /* 页面跳转设置 */
        'TMPL_EXCEPTION_FILE'   => TMPL_PATH.'/Public/admin_404.html', // 默认异常对应的模板文件
        'EMPTY_PATH'            => TMPL_PATH.'/Public/admin_404.html', // 模块不存在对应的模板文件
        'TMPL_ACTION_ERROR'     => TMPL_PATH.'/Public/dispatch_jump.tpl', // 默认错误跳转对应的模板文件
        'TMPL_ACTION_SUCCESS'   => TMPL_PATH.'/Public/dispatch_jump.tpl', // 默认成功跳转对应的模板文件
        
        /* auth权限验证 设置 */
        'AUTH_CONFIG'           => array(
                'AUTH_USER'         => 'admin'  //用户信息表
        ),
        
        //不需要认证的规则
        'NO_AUTH_RULES'=>array(
                'Admin/Index/index',//后台框架
                'Admin/Index/home',//后台欢迎页
                'Admin/Data/backupDownload',//下载备份文件
                'Admin/Admin/info',//管理员个人信息
                'Admin/Admin/editPassword',//管理员修改密码
                'Admin/Index/unAuth',//无权限访问的提示页面
                'Admin/Mail/detail',//查看邮件
                'Admin/Mail/senderDel',//删除邮件（发件箱）
                'Admin/Mail/receiverDel',//删除邮件（收件箱）
                'Admin/Cash/exportExcel',//提现导出
                'Admin/Cash/exportRemitExcel',//汇款导出
                'Admin/Mall/saveSort',//拖拽排序
                'Admin/Mall/detail',//商品详情
                'Admin/Mall/orderDetail',//订单详情
                'Admin/Mall/exportExcel',//导出订单
                'Admin/Mall/upload',//图片上传
        ),
);
