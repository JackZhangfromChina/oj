<?php
return array(
        /* 页面跳转设置 */
        'TMPL_EXCEPTION_FILE'   => TMPL_PATH.'/Public/404.html', // 默认异常对应的模板文件
        'EMPTY_PATH'            => TMPL_PATH.'/Public/404.html', // 模块不存在对应的模板文件
        'TMPL_ACTION_ERROR'     => TMPL_PATH.'/Public/dispatch_jump.tpl', // 默认错误跳转对应的模板文件
        'TMPL_ACTION_SUCCESS'   => TMPL_PATH.'/Public/dispatch_jump.tpl', // 默认成功跳转对应的模板文件
        
);
