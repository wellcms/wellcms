<?php
/*
	WellCMS 2.0 配置文件
	支持多台 DB，主从配置好以后，程序自动根据 SQL 读写分离。
	支持各种 cache，本机 apc/xcache, 网络: redis/memcached/mysql
	支持 CDN，如果前端开启了 CDN 请设置 cdn_on=>1, 否则获取 IP 会不准确 
	支持临时目录设置，独立 Linux 主机，可以设置为 /dev/shm 通过内存加速
*/
return array(
    'db' => array(
        'type' => 'mysql',
        'mysql' => array(
            'master' => array(
                'host' => 'localhost',
                'user' => 'root',
                'password' => 'root',
                'name' => 'test',
                'tablepre' => 'well_',
                'charset' => 'utf8',
                'engine' => 'innodb',
            ),
            'slaves' => array(),
        ),
        'pdo_mysql' => array(
            'master' => array(
                'host' => 'localhost',
                'user' => 'root',
                'password' => 'root',
                'name' => 'test',
                'tablepre' => 'well_',
                'charset' => 'utf8',
                'engine' => 'innodb',
            ),
            'slaves' => array(),
        ),
    ),
    'cache' => array(
        'enable' => true,
        'type' => 'mysql',
        'memcached' => array(
            'host' => 'localhost',
            'port' => '11211',
            'cachepre' => 'well_',
        ),
        'redis' => array(
            'host' => 'localhost',
            'port' => '6379',
            'cachepre' => 'well_',
        ),
        'xcache' => array(
            'cachepre' => 'well_',
        ),
        'yac' => array(
            'cachepre' => 'well_',
        ),
        'apc' => array(
            'cachepre' => 'well_',
        ),
        'mysql' => array(
            'cachepre' => 'well_',
        ),
    ),
    'tmp_path' => './tmp/',        // 可以配置为 linux 下的 /dev/shm ，通过内存缓存临时文件
    'log_path' => './log/',        // 日志目录

    // -------------------> 配置

    'view_url' => 'view/',        // 可以配置单独 CDN 域名：比如：http://static.domain.com/view/
    'upload_url' => 'upload/',    // 本地文件上传目录，
    'upload_path' => './upload/',    // 物理路径，可以用 NFS 存入到单独的文件服务器
    'upload_quick' => 0,  // 文件秒传 0关闭 1开启
    'attach_on' => 0,     // 0本地储存 1云储存 2图床
    'attach_delete' => 0, // 开启云储存后本地附件 0不删除 1删除
    'cloud_url' => '',    // 云储存域名或单独CDN域名，本地upload目录和目录下的文件上传至云储存，域名绑定的目录必须对应程序的upload，如http://upload.domain.com/upload/thumbnail/201912/***.jpg
    'path' => './', // 前台路径 "/" 结尾
    'logo_mobile_url' => 'img/logo.png',        // 手机 LOGO URL
    'logo_pc_url' => 'img/logo.png',            // PC  LOGO URL
    'logo_water_url' => 'img/water-small.png',    // 水印 LOGO URL
    'sitename' => 'WellCMS',
    'sitebrief' => 'Site Brief',
    'timezone' => 'Asia/Shanghai',  // 时区，默认中国
    'lang' => 'zh-cn',
    'runlevel' => 5,    // 0: 站点关闭; 1: 管理员可读写; 2: 会员可读;  3: 会员可读写; 4：所有人只读; 5: 所有人可读写
    'runlevel_reason' => 'The site is under maintenance, please visit later.',
    'cookie_pre' => 'well_', // cookie 前缀
    'cookie_domain' => '',   // cookie使用的域名，为空表示当前域名
    'cookie_path' => '',     // 为空则表示当前目录和子目录
    'cookie_lifetime' => '8640000', // cookie生命期8640000为100天
    'auth_key' => '',
    'pagesize' => 20,
    'postlist_pagesize' => 100,
    'listsize' => 100,  // 大数据量控制显示100页
    'linksize' => 20,   // 友情链接调用数量 排序数值最大的20个
    'tagsize' => 60,    // 显示tag的数量
    'comment_pagesize' => 20,  // 评论显示
    'cache_thread_list_pages' => 10,
    'online_update_span' => 120, // 在线更新频度，大站设置的长一些
    'online_hold_time' => 3600,  // 在线的时间
    'session_delay_update' => 0, // 开启 session 延迟更新，减轻压力，会导致不重要的数据(useragent,url)显示有些延迟，单位为秒。
    'upload_image_width' => 927,    // 上传图片自动缩略的最大宽度
    'upload_resize' => 'clip',      // 上传图片clip裁切 thumb缩略
    'order_default' => 'lastpid',   // 作为BBS时帖子排序
    'attach_dir_save_rule' => 'Ym', // 附件存放规则，附件多用：Ymd，附件少：Ym
    'update_views_on' => 1,
    'user_create_email_on' => 0,
    'user_create_on' => 1,
    'user_resetpw_on' => 0,
    'admin_bind_ip' => 1, // 后台是否绑定 IP 0关闭 1启用
    'cdn_on' => 0,
    'api_on' => 0, // 默认关闭，打开后前台部分页面可通过api获取数据
    /* 支持多种 URL 格式：
        0: ?read-create-1.html
        1: read-create-1.html
        2: /read/create/1.html
        3: /read/create/1
    */
    'url_rewrite_on' => 0,
    // 禁止插件
    'disabled_plugin' => 0,
    'version' => '2.0',
    'static_version' => '?2.0.05',
    'installed' => 0,
    'compress' => 1, // 代码压缩 0关闭 1仅压缩php、html代码(不压缩js代码) 2压缩全部代码 如果启用压缩出现错误，请关闭，删除html中的所有注释，并且js代码按照英文分号结束的地方加上分号;
    // token验证，开启后内容提交和上传都需要token，没有token无法操作，app建议开启，有效阻止抓包伪造提交等。开启后相当于单线程，仅限当前页有效。
    'upload_token' => 1, // 0关闭 1开启上传验证 token
    'message_token' => 0, // 0关闭 1开启发布内容验证 token
    'comment_token' => 0, // 0关闭 1开启评论验证 token
    'login_token' => 1, // 0关闭 1开启用户登陆 token
);
?>