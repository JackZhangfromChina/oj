<?php
return array(
        /* URL设置 */
        'MODULE_ALLOW_LIST'     => array('Home','Admin','Api','Test'),   //允许访问列表
        'URL_HTML_SUFFIX'       => '',  // URL伪静态后缀设置
        'URL_MODEL'             => 0,   //启用rewrite
        'URL_CASE_INSENSITIVE'  => false,   // url区分大小写
        
        /* 开发设置 */
        'SHOW_PAGE_TRACE'       => true,   // 是否显示调试面板
        'APP_DEBUG'             => true,   //调试模式
        
//      'TAGLIB_BUILD_IN'       => 'Cx,Common\Tag\My', // 加载自定义标签
//      'LOAD_EXT_CONFIG'       => 'db,alipay,oauth',  // 加载网站设置文件
        'LOAD_EXT_CONFIG'       => 'db,menu,part_menu',   // 加载网站设置文件
        'TMPL_PARSE_STRING'     => array(   // 定义常用路径
                '__PUBLIC__'        => __ROOT__.'/Public',
                '__HOME_CSS__'      => __ROOT__.trim(TMPL_PATH,'.').'Home/Public/css',
                '__HOME_JS__'       => __ROOT__.trim(TMPL_PATH,'.').'Home/Public/js',
                '__HOME_IMAGES__'   => __ROOT__.trim(TMPL_PATH,'.').'Home/Public/images',
                '__ADMIN_CSS__'     => __ROOT__.trim(TMPL_PATH,'.').'Admin/Public/css',
                '__ADMIN_JS__'      => __ROOT__.trim(TMPL_PATH,'.').'Admin/Public/js',
                '__ADMIN_IMAGES__'  => __ROOT__.trim(TMPL_PATH,'.').'Admin/Public/images',
                '__PUBLIC_CSS__'    => __ROOT__.trim(TMPL_PATH,'.').'Public/css',
                '__PUBLIC_JS__'     => __ROOT__.trim(TMPL_PATH,'.').'Public/js',
                '__PUBLIC_IMAGES__' => __ROOT__.trim(TMPL_PATH,'.').'Public/images',
        ),
        
        /* 缓存设置 */
        'DATA_CACHE_TIME'       => 1800,    // 数据缓存有效期s
        'DATA_CACHE_PREFIX'     => 'mem_',  // 缓存前缀
        'DATA_CACHE_TYPE'       => 'Memcached',     // 数据缓存类型,
        'MEMCACHED_SERVER'      => '127.0.0.1',     // 服务器ip
        
        /* session 设置 */
        'SESSION_OPTIONS'           => array(
                array(
                        'name'              => 'admin',//设置session名
                        'expire'            => 24*3600*15, //SESSION保存15天
                        'use_trans_sid'     => 1,//跨页传递
                        'use_only_cookies'  => 0,//是否只开启基于cookies的session的会话方式
                ),
                array(
                        'name'              => 'user',//设置session名
                        'expire'            => 60, //SESSION保存15天
                        'use_trans_sid'     => 1,//跨页传递
                        'use_only_cookies'  => 0,//是否只开启基于cookies的session的会话方式
                ),
        ),
        
        /* 模板主题 */
//         'DEFAULT_THEME'  	=> 	'default',
//         'THEME_LIST'		=>	'default,think',
//         'TMPL_DETECT_THEME' => 	true, // 自动侦测模板主题
        
        /* 页面跳转设置 */
        'TMPL_EXCEPTION_FILE'   => THINK_PATH.'Tpl/think_exception.tpl',    // 默认异常对应的模板文件
        'TMPL_ACTION_ERROR'     => THINK_PATH.'Tpl/dispatch_jump.tpl',  // 默认错误跳转对应的模板文件
        'TMPL_ACTION_SUCCESS'   => THINK_PATH.'Tpl/dispatch_jump.tpl',  // 默认成功跳转对应的模板文件
//         'EMPTY_PATH'            => THINK_PATH.'Tpl/think_exception.tpl',     // 模块不存在对应的模板文件
);
