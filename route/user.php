<?php
!defined('DEBUG') and exit('Access Denied.');

$action = param(1);

// hook user_start.php

switch ($action) {
    // hook user_case_start.php
    case 'comment':
        $_uid = param(2, 0);
        $_user = user_read_cache($_uid);
        empty($_user) and message(-1, lang('user_not_exists'));

        // hook user_comment_start.php

        if ('GET' == $method) {

            $page = param(3, 1);
            $pagesize = $conf['pagesize'];
            $extra = array(); // 插件预留
            $menulist = user_menu($_uid);

            // hook user_comment_before.php

            $postlist = comment_pid_find_by_uid($_user['uid'], $page, $pagesize);
            $pids = array();
            $tids = array();
            if ($postlist) {
                foreach ($postlist as &$_pid) {
                    $pids[] = $_pid['pid'];
                    $tids[] = $_pid['tid'];
                }

                // hook user_comment_center.php

                $threadlist = well_thread_find($tids);
                // 过滤没有权限访问的主题 / filter no permission thread
                well_thread_list_access_filter($threadlist, $gid);

                $arrlist = comment_find_by_pid($pids, $pagesize);
                foreach ($arrlist as $key => &$val) {
                    // 过滤没有权限访问的主题 / filter no permission thread
                    if (empty($threadlist[$val['tid']])) unset($arrlist[$key]);
                    comment_filter($val);
                    $val['subject'] = isset($threadlist[$val['tid']]['subject']) ? $threadlist[$val['tid']]['subject'] : '';
                    $val['url'] = isset($threadlist[$val['tid']]['url']) ? $threadlist[$val['tid']]['url'] : '';
                    $val['allowdelete'] = $val['fid'] ? forum_access_mod($val['fid'], $gid, 'allowdelete') : group_access($gid, 'allowdelete');
                }
            }

            // hook user_comment_middle.php

            $allowdelete = group_access($gid, 'allowdelete') || 1 == $gid;
            $access = array('allowdelete' => $allowdelete);

            $page_url = url('user-comment-' . $_user['uid'] . '-{page}', $extra);
            $num = $_user['comments'];

            // hook user_comment_pagination_before.php

            $pagination = pagination($page_url, $num, $page, $pagesize);

            // hook user_comment_after.php

            $header['title'] = $_user['username'] . ' - ' . lang('comment');
            $header['mobile_title'] = '';

            if ($ajax) {
                $apilist['header'] = $header;
                $apilist['extra'] = $extra;
                $apilist['menulist'] = $menulist;
                $apilist['num'] = $num;
                $apilist['page'] = $page;
                $apilist['pagesize'] = $pagesize;
                $apilist['page_url'] = $page_url;
                $apilist['arrlist'] = $arrlist;
                $apilist['access'] = $access;

                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('user_comment'));
            }
        }

        // hook user_comment_end.php
        break;
    case 'login':

        $uid and http_location($conf['path']);

        // hook user_login_get_post.php

        if ('GET' == $method) {

            // hook user_login_get_start.php

            $header['title'] = lang('user_login');
            empty($safe_token) and $safe_token = well_token_set(0);
            $referer = user_http_referer();
            $extra = array('safe_token' => $safe_token);

            // hook user_login_get_end.php

            $form_action = url('user-login', $extra);

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['referer'] = $referer;
                $apilist['action'] = $action;

                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('user_login'));
            }

        } else if ('POST' == $method) {

            // hook user_login_post_start.php

            $email = param('email'); // 邮箱或者手机号 / email or mobile
            $email = filter_all_html($email);
            $password = param('password');
            empty($email) and message('email', lang('email_is_empty'));
            if (is_email($email, $err)) {
                $_user = user_read_by_email($email);
                empty($_user) and message('email', lang('email_not_exists'));
            } else {
                $_user = user_read_by_username($email);
                empty($_user) and message('email', lang('username_not_exists'));
            }

            is_password($password, $err) || message('password', $err);
            $check = (md5($password . $_user['salt']) == $_user['password']);
            // hook user_login_post_password_check_after.php
            empty($check) and message('password', lang('password_incorrect'));

            // hook user_login_post_before.php

            // 更新登录时间和次数
            // update login times
            $update = array('login_ip' => $longip, 'login_date' => time(), 'logins+' => 1);

            // hook user_login_post_after.php

            user_update($_user['uid'], $update);

            // 全局变量 $uid 会在结束后，在函数 register_shutdown_function() 中存入 session (文件: model/session.func.php)
            // global variable $uid will save to session in register_shutdown_function() (file: model/session.func.php)

            $_SESSION['uid'] = $_user['uid'];
            user_token_set($_user['uid']); // 设置 token，下次自动登陆。

            // hook user_login_post_end.php

            message(0, lang('login_successfully'));
        }
        break;
    case 'create':

        $uid and http_location($conf['path']);

        // hook user_create_get_post.php

        empty($conf['user_create_on']) and message(-1, lang('user_create_not_on'));

        if ('GET' == $method) {

            // hook user_create_get_start.php

            $header['title'] = lang('create_user');
            $referer = user_http_referer();
            empty($safe_token) and $safe_token = well_token_set(0);
            $extra = array('safe_token' => $safe_token);

            $form_action = url('user-create', $extra);

            // hook user_create_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['referer'] = $referer;
                $apilist['action'] = $action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('user_create'));
            }

        } else if ('POST' == $method) {

            // hook user_create_post_start.php

            $email = param('email');
            $username = param('username');
            $password = param('password');
            $code = param('code');
            $email = filter_all_html($email);
            empty($email) and message('email', lang('please_input_email'));
            $username = filter_all_html($username);
            empty($username) and message('username', lang('please_input_username'));
            empty($password) and message('password', lang('please_input_password'));

            if ($conf['user_create_email_on']) {
                $sess_email = _SESSION('user_create_email');
                $sess_code = _SESSION('user_create_code');
                empty($sess_code) and message('code', lang('click_to_get_verify_code'));
                empty($sess_email) and message('code', lang('click_to_get_verify_code'));
                $email != $sess_email and message('code', lang('verify_code_incorrect'));
                $code != $sess_code and message('code', lang('verify_code_incorrect'));
            }

            is_email($email, $err) || message('email', $err);

            $_user = user_read_by_email($email);
            $_user and message('email', lang('email_is_in_use'));

            is_username($username, $err) || message('username', $err);
            $_user = user_read_by_username($username);
            $_user and message('username', lang('username_is_in_use'));

            is_password($password, $err) || message('password', $err);

            // hook user_create_post_before.php

            $salt = xn_rand(16);
            $_user = array(
                'username' => $username,
                'email' => $email,
                'password' => md5($password . $salt),
                'salt' => $salt,
                'gid' => 101,
                'create_ip' => $longip,
                'create_date' => $time,
                'logins' => 1,
                'login_date' => $time,
                'login_ip' => $longip,
            );

            // hook user_create_post_center.php

            $uid = user_create($_user);
            FALSE === $uid and message(-1, lang('user_create_failed'));
            $user = user_read($uid);

            // hook user_create_post_after.php

            // 更新 session

            unset($_SESSION['user_create_email'], $_SESSION['user_create_code']);
            $_SESSION['uid'] = $uid;
            user_token_set($uid);

            $extra = array('token' => user_token_gen($uid));

            // hook user_create_post_end.php

            message(0, lang('user_create_successfully'), $extra);
        }
        break;
    case 'logout':
        // hook user_logout_start.php

        $uid = 0;
        $_SESSION['uid'] = $uid;
        user_token_clear();

        // hook user_logout_end.php

        message(0, jump(lang('logout_successfully'), http_referer(), 1));
        break;
    case 'resetpw':
        // 重设密码第 1 步 | reset password first step
        // hook user_resetpw_get_post.php

        empty($conf['user_resetpw_on']) and message(-1, lang('closed'));

        if ('GET' == $method) {

            // hook user_resetpw_get_start.php

            $header['title'] = lang('resetpw');
            $form_action = url('user-resetpw');

            // hook user_resetpw_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['form_action'] = $form_action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('user_resetpw'));
            }

        } else if ('POST' == $method) {

            // hook user_resetpw_post_start.php

            $email = param('email');
            empty($email) and message('email', lang('please_input_email'));
            is_email($email, $err) || message('email', $err);

            $_user = user_read_by_email($email);
            empty($_user) and message('email', lang('email_is_not_in_use'));

            $code = param('code');
            empty($code) and message('code', lang('please_input_verify_code'));

            $sess_code = _SESSION('user_resetpw_code');
            empty($sess_code) and message('code', lang('click_to_get_verify_code'));
            $code != $sess_code and message('code', lang('verify_code_incorrect'));

            $sess_email = _SESSION('user_resetpw_email');
            (empty($sess_email) || $email != $sess_email) and message('email', lang('data_malformation'));

            $_SESSION['resetpw_verify_email'] = $sess_email;

            // hook user_resetpw_post_end.php

            message(0, lang('check_ok_to_next_step'));
        }
        break;
    case 'resetpw_complete':
        // 重设密码第 3 步 | reset password step 3

        // hook user_resetpw_get_post.php

        // 校验数据
        $email = _SESSION('user_resetpw_email');
        $resetpw_verify_email = _SESSION('resetpw_verify_email');
        (empty($email) || empty($resetpw_verify_email) || $resetpw_verify_email != $email) and message(-1, lang('data_empty_to_last_step'));

        $_user = user_read_by_email($email);
        empty($_user) and message(-1, lang('email_not_exists'));
        $_uid = $_user['uid'];

        if ('GET' == $method) {

            // hook user_resetpw_get_start.php

            $header['title'] = lang('resetpw');
            $form_action = url('user-resetpw_complete');

            // hook user_resetpw_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['form_action'] = $form_action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('user_resetpw_complete'));
            }

        } else if ('POST' == $method) {

            // hook user_resetpw_post_start.php

            $password = param('password');
            empty($password) and message('password', lang('please_input_password'));

            $salt = $_user['salt'];
            $password = md5($password . $salt);

            is_password($password, $err) || message('password', $err);

            user_update($_uid, array('password' => $password));

            unset($_SESSION['user_resetpw_email'], $_SESSION['user_resetpw_code']);

            // hook user_resetpw_post_end.php

            message(0, lang('modify_successfully'));
        }
        break;
    case 'send_code':
        include _include(XIUNOPHP_PATH . 'xn_send_mail.func.php');
        // 发送验证码
        'POST' != $method and message(-1, lang('method_error'));

        // hook user_send_code_start.php

        $action2 = param(2);

        if ('user_create' == $action2) {
            // 创建用户
            $email = param('email');

            empty($email) and message('email', lang('please_input_email'));
            is_email($email, $err) || message('email', $err);

            empty($conf['user_create_email_on']) and message(-1, lang('email_verify_not_on'));

            $_user = user_read_by_email($email);
            empty($_user) || message('email', lang('email_is_in_use'));

            $code = rand(100000, 999999);
            $_SESSION['user_create_email'] = $email;
            $_SESSION['user_create_code'] = $code;

        } elseif ('user_resetpw' == $action2) {
            // 重置密码，往老地址发送
            $email = param('email');

            empty($email) and message('email', lang('please_input_email'));
            is_email($email, $err) || message('email', $err);

            $_user = user_read_by_email($email);
            empty($_user) and message('email', lang('email_is_not_in_use'));

            empty($conf['user_resetpw_on']) and message(-1, lang('resetpw_not_on'));

            $code = rand(100000, 999999);
            $_SESSION['user_resetpw_email'] = $email;
            $_SESSION['user_resetpw_code'] = $code;

        } else {
            message(-1, 'action2 error');
        }

        $subject = lang('send_code_template', array('rand' => $code, 'sitename' => $conf['sitename']));
        $message = $subject;

        $smtplist = include _include(APP_PATH . 'conf/smtp.conf.php');
        $n = array_rand($smtplist);
        $smtp = $smtplist[$n];

        // hook user_send_code_before.php
        $r = xn_send_mail($smtp, $conf['sitename'], $email, $subject, $message);
        // hook user_send_code_after.php

        if (TRUE === $r) {
            message(0, lang('send_successfully'));
        } else {
            xn_log($errstr, 'send_mail_error');
            message(-1, $errstr);
        }
        break;
    case 'synlogin':
        // 简单的同步登陆实现：| sync login implement simply
        /* user-synlogin.html?token=token&return_url=url
           将用户信息通过 token 传递给其他系统 | send user information to other system by token
           两边系统将 auth_key 设置为一致，用 xn_encrypt() xn_decrypt() 加密解密。all subsystem set auth_key to correct by xn_encrypt() xn_decrypt()
        */
        // 检查过来的 token | check token
        $token = param('token');
        $return_url = param('return_url');

        $s = xn_decrypt($token);
        empty($s) and message(-1, lang('unauthorized_access'));

        list($_time, $_useragent) = explode("\t", $s);
        $useragent != $_useragent and message(-1, lang('authorized_get_failed'));

        empty($_SESSION['return_url']) and $_SESSION['return_url'] = $return_url;

        if (!$uid) http_location(url('user-login'));

        $return_url = _SESSION('return_url');

        empty($return_url) and message(-1, lang('request_synlogin_again'));
        unset($_SESSION['return_url']);

        $arr = array(
            'uid' => $user['uid'],
            'gid' => $user['gid'],
            'username' => $user['username'],
            'avatar_url' => $user['avatar_url'],
            'email' => $user['email'],
            'mobile' => $user['mobile'],
        );
        $s = xn_json_encode($arr);
        $s = xn_encrypt($s);

        // 将 token 附加到 URL，跳转回去 | add token into URL, jump back
        $url = xn_urldecode($return_url) . '?token=' . $s;
        http_location($url);
        break;
    // hook user_case_end.php
    default:
        // hook user_index_start.php

        $_uid = param(1, 0);
        $page = param(2, 1);
        $pagesize = $conf['pagesize'];
        $extra = array(); // 插件预留

        empty($_uid) and $_uid = $uid;
        $_user = user_read_cache($_uid);
        empty($_user) and message(-1, lang('user_not_exists'));

        $menulist = user_menu($_uid);

        // hook user_index_before.php

        $threadlist = well_thread_find_by_uid($_user['uid'], $page, $pagesize);
        well_thread_list_access_filter($threadlist, $gid);

        $allowdelete = group_access($gid, 'allowdelete');

        // hook user_index_center.php

        $page_url = url('user-' . $_user['uid'] . '-{page}', $extra);
        $num = $_user['articles'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $_user['articles'];

        // hook user_index_middle.php

        $pagination = pagination($page_url, $num, $page, $pagesize);

        // hook user_index_after.php

        $header['title'] = $_user['username'] . ' - ' . lang('thread');
        $header['mobile_title'] = '';

        // hook user_index_end.php

        if ($ajax) {
            if ($threadlist) {
                foreach ($threadlist as &$thread) $thread = well_thread_safe_info($thread);
            }

            $apilist['header'] = $header;
            $apilist['extra'] = $extra;
            $apilist['menulist'] = $menulist;
            $apilist['num'] = $num;
            $apilist['page'] = $page;
            $apilist['pagesize'] = $pagesize;
            $apilist['page_url'] = $page_url;
            $apilist['threadlist'] = $threadlist;
            $apilist['allowdelete'] = $allowdelete;

            $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
        } else {
            include _include(theme_load('user'));
        }
        break;
}

// hook user_end.php

?>