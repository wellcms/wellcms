<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook admin_index_start.php

if ('login' == $action) {

    // hook admin_index_login_get_post.php

    if ('GET' == $method) {

        // hook admin_index_login_get_start.php

        $header['title'] = lang('admin_login');

        include _include(ADMIN_PATH . "view/htm/index_login.htm");

    } else if ('POST' == $method) {

        // hook admin_index_login_post_start.php

        $password = param('password');

        if (md5($password . $user['salt']) != $user['password']) {
            xn_log('password error. uid:' . $user['uid'] . ' - ******' . substr($password, -6), 'admin_login_error');
            message('password', lang('password_incorrect'));
        }

        admin_token_set();

        xn_log('login successed. uid:' . $user['uid'], 'admin_login');

        // hook admin_index_login_post_end.php

        message(0, jump(lang('login_successfully'), '.'));

    }

} elseif ('logout' == $action) {

    // hook admin_index_logout_start.php

    admin_token_clean();

    message(0, jump(lang('logout_successfully'), './'));

} elseif ('phpinfo' == $action) {

    unset($_SERVER['conf'], $_SERVER['db'], $_SERVER['cache']);
    phpinfo();
    exit;

} else {

    // hook admin_index_info_start.php

    $header['title'] = lang('admin_page');

    FALSE === group_access($gid, 'intoadmin') AND message(1, lang('user_group_insufficient_privilege'));

    $info = array();
    $info['disable_functions'] = ini_get('disable_functions');
    $info['allow_url_fopen'] = ini_get('allow_url_fopen') ? lang('yes') : lang('no');
    $info['safe_mode'] = ini_get('safe_mode') ? lang('yes') : lang('no');
    empty($info['disable_functions']) && $info['disable_functions'] = lang('none');
    $info['upload_max_filesize'] = ini_get('upload_max_filesize');
    $info['post_max_size'] = ini_get('post_max_size');
    $info['memory_limit'] = ini_get('memory_limit');
    $info['max_execution_time'] = ini_get('max_execution_time');
    $info['dbversion'] = $db->version();
    $info['SERVER_SOFTWARE'] = _SERVER('SERVER_SOFTWARE');
    $info['HTTP_X_FORWARDED_FOR'] = _SERVER('HTTP_X_FORWARDED_FOR');
    $info['REMOTE_ADDR'] = _SERVER('REMOTE_ADDR');

    // hook admin_index_info_before.php

    $stat = array();
    $stat['threads'] = function_exists('thread_count') ? thread_count() : 0;
    $stat['posts'] = function_exists('post_count') ? post_count() : 0;
    $stat['attachs'] = function_exists('attach_count') ? attach_count() : 0;
    $stat['articles'] = well_thread_count();
    $stat['comments'] = comment_count();
    $stat['website_attachs'] = well_attach_count();

    // hook admin_index_info_after.php

    $stat['users'] = isset($runtime['users']) ? $runtime['users'] : 0;
    $stat['disk_free_space'] = function_exists('disk_free_space') ? humansize(disk_free_space(APP_PATH)) : lang('unknown');

    // hook admin_index_info_end.php

    get_last_version($stat);

    include _include(ADMIN_PATH . 'view/htm/index.htm');
}

// hook admin_index_end.php

function get_last_version($stat)
{
    global $time, $conf, $config, $ip;

    $domain = _SERVER('HTTP_HOST');
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) || $time < array_value($config, 'last_version', 0)) return;

    $post = array('type' => 1, 'sitename' => xn_urlencode($conf['sitename']), 'domain' => xn_urlencode($domain), 'app_url' => xn_urlencode(http_url_path()), 'users' => $stat['users'], 'articles' => $stat['articles'], 'comments' => $stat['comments'], 'threads' => $stat['threads'], 'posts' => $stat['posts'], 'siteid' => plugin_siteid(), 'version' => array_value($config, 'version'), 'version_date' => array_value($config, 'version_date', 0));
    $json = https_request(OFFICIAL_URL . 'version.html?' . http_build_query($post), $post, '', 800, 1);
    if (isset($json) && !in_array($json, array('1', '2', 'fail'))) {
        $official = xn_json_decode($json);
        if (is_array($official)) {
            if (-1 == version_compare($config['official_version'], $official['version']) || $official['version_date'] > array_value($config, 'version_date', 0)) {
                $config['official_version'] = $official['version'];
                $config['version_date'] = array_value($official, 'version_date', 0);
                $config['upgrade'] = 1; // 有更新
            }
        }
    }

    //$config['last_version'] = clock_twenty_four();
    $config['last_version'] = $time + 7200;
    setting_set('conf', $config);
}

// hook admin_index_function.php

?>