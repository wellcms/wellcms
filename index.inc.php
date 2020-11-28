<?php

!defined('DEBUG') AND exit('Access Denied.');

// hook index_inc_start.php

$sid = sess_start();

// 语言 / Language
$_SERVER['lang'] = $lang = include _include(APP_PATH . 'lang/' . $conf['lang'] . '/lang.php');

// hook index_inc_lang_after.php

// 用户组 / Group
$grouplist = group_list_cache();

// 支持 Token 接口（token 与 session 双重登陆机制，方便 REST 接口设计，也方便 $_SESSION 使用）
// Support Token interface (token and session dual match, to facilitate the design of the REST interface, but also to facilitate the use of $_SESSION)
$uid = intval(_SESSION('uid'));
empty($uid) AND $uid = user_token_get() AND $_SESSION['uid'] = $uid;
$user = user_read($uid);

// hook index_inc_before.php

$gid = empty($user) ? 0 : intval($user['gid']);
$group = isset($grouplist[$gid]) ? $grouplist[$gid] : $grouplist[0];

// hook index_inc_center.php

// 配置数据
$config = setting_get('conf');

// hook index_inc_config_after.php

// 频道 栏目 版块 / Category List Forum
$fid = 0;
$forumlist = forum_list_cache();
// 有权限查看的板块 / filter no permission forum
$forumlist_show = forum_list_access_filter($forumlist, $gid);
$forumarr = arrlist_key_values($forumlist_show, 'fid', 'name');

// hook index_inc_middle.php

// 头部 header.inc.htm 
$header = array(
    'title' => $conf['sitename'],
    'mobile_title' => '',
    'mobile_link' => $conf['path'],
    'keywords' => '',
    'description' => strip_tags($conf['sitebrief']),
    'navs' => array(),
);

// hook index_inc_header_after.php

// 运行时数据，存放于 cache_set() / runtime data
$runtime = runtime_init();

// hook index_inc_runtime_after.php

// 检测站点运行级别 / restricted access
check_runlevel();

// 全站的设置数据，站点名称，描述，关键词
// $setting = kv_get('setting');
$forum_nav = nav_list($forumlist_show);
// 二叉树导航 需要的时候自行启用
//$forum_nav = category_tree($forumlist_show);

// hook index_inc_after.php

$route = param(0, 'index');

// hook index_inc_route_before.php

if (!defined('SKIP_ROUTE')) {
    // 按照使用的频次排序，增加命中率，提高效率
    // According to the frequency of the use of sorting, increase the hit rate, improve efficiency
    switch ($route) {
        // hook index_route_case_start.php
        case 'index':
            include _include(APP_PATH . 'route/index.php');
            break;
        case 'read':
            include _include(APP_PATH . 'route/read.php');
            break;
        case 'list':
            include _include(APP_PATH . 'route/list.php');
            break;
        case 'category':
            include _include(APP_PATH . 'route/category.php');
            break;
        case 'tag':
            include _include(APP_PATH . 'route/tag.php');
            break;
        case 'flag':
            include _include(APP_PATH . 'route/flag.php');
            break;
        case 'user':
            include _include(APP_PATH . 'route/user.php');
            break;
        case 'home':
            include _include(APP_PATH . 'route/home.php');
            break;
        case 'my':
            include _include(APP_PATH . 'route/my.php');
            break;
        case 'attach':
            include _include(APP_PATH . 'route/attach.php');
            break;
        case 'comment':
            include _include(APP_PATH . 'route/comment.php');
            break;
        case 'operate':
            include _include(APP_PATH . 'route/operate.php');
            break;
        case 'intodb':
            include _include(APP_PATH . 'route/intodb.php');
            break;
        case 'browser':
            include _include(APP_PATH . 'route/browser.php');
            break;
        // hook index_route_case_end.php
        default:
            // hook index_route_case_default.php
            include _include(APP_PATH . 'route/index.php');
            break;
        //http_404();
        /*
        !is_word($route) AND http_404();
        $routefile = _include(APP_PATH."route/$route.php");
        !is_file($routefile) AND http_404();
        include $routefile;
        */
    }
}

// hook index_inc_end.php

?>