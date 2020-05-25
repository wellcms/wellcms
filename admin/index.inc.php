<?php
!defined('DEBUG') AND exit('Access Denied.');

// hook admin_index_inc_start.php

// 只允许管理员登陆后台
// Only allow administrators to log in the background

// 对于越权访问，可以默认为黑客企图，不用友好提示。
// For unauthorized access, can default to the hacking attempt, without a friendly reminder.
if (DEBUG < 3) {

    // hook admin_index_inc_before.php

    // 管理组检查 / check admin group
    if (FALSE === group_access($gid, 'intoadmin')) {
        setcookie($conf['cookie_pre'] . 'sid', '', $time - 86400);
        http_location(url(($conf['url_rewrite_on'] < 2 ? '../' : '') . 'user-login', '', 2));
    }

    // hook admin_index_inc_check_before.php

    // 管理员令牌检查 / check admin token
    admin_token_check();

    // hook admin_index_inc_check_after.php
}

// hook admin_index_inc_center.php

$route = param(0, 'index');

// hook admin_index_inc_after.php

switch ($route) {
    // hook admin_index_route_case_start.php
    case 'index':
        include _include(ADMIN_PATH . 'route/index.php');
        break;
    case 'content':
        include _include(ADMIN_PATH . 'route/content.php');
        break;
    case 'column':
        include _include(ADMIN_PATH . 'route/column.php');
        break;
    case 'flag':
        include _include(ADMIN_PATH . 'route/flag.php');
        break;
    case 'template':
        include _include(ADMIN_PATH . 'route/template.php');
        break;
    case 'comment':
        include _include(ADMIN_PATH . 'route/comment.php');
        break;
    case 'sticky':
        include _include(ADMIN_PATH . 'route/sticky.php');
        break;
    case 'page':
        include _include(ADMIN_PATH . 'route/page.php');
        break;
    case 'group':
        include _include(ADMIN_PATH . 'route/group.php');
        break;
    case 'setting':
        include _include(ADMIN_PATH . 'route/setting.php');
        break;
    case 'other':
        include _include(ADMIN_PATH . 'route/other.php');
        break;
    case 'user':
        include _include(ADMIN_PATH . 'route/user.php');
        break;
    case 'style':
        include _include(ADMIN_PATH . 'route/style.php');
        break;
    case 'plugin':
        include _include(ADMIN_PATH . 'route/plugin.php');
        break;
    // hook admin_index_route_case_end.php
    default:
        // hook admin_index_route_case_default.php
        include _include(ADMIN_PATH . 'route/index.php');
        break;
}

// hook admin_index_inc_end.php

?>