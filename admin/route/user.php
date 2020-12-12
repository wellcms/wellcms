<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') and exit('Access Denied.');

3 != DEBUG && FALSE === group_access($gid, 'manageuser') and message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// hook admin_user_start.php

switch ($action) {
    // hook admin_user_case_start.php
    case 'list':
        $header['title'] = lang('user_admin');
        $header['mobile_title'] = lang('user_admin');

        $page = param('page', 1);
        $extra = array('page' => '{page}'); // 插件预留
        $pagesize = 20;

        $srchtype = param('srchtype');
        $keyword = trim(xn_urldecode(param('keyword')));
        $srchtype and $keyword and $extra += array('srchtype' => $srchtype, 'keyword' => urlencode($keyword));

        // hook admin_user_list_start.php

        $cond = array();
        $allowtype = array('uid', 'username', 'email', 'gid', 'create_ip');

        // hook admin_user_list_allow_type_after.php

        if ($keyword) {
            !in_array($srchtype, $allowtype, TRUE) and $srchtype = 'uid';
            $cond[$srchtype] = 'create_ip' == $srchtype ? sprintf('%u', ip2long($keyword)) : $keyword;
        }

        // hook admin_user_list_cond_after.php
        $n = user_count($cond);
        $userlist = user_find($cond, array('uid' => -1), $page, $pagesize);
        $pagination = pagination(url('user-list', $extra, TRUE), $n, $page, $pagesize);

        foreach ($userlist as &$_user) {
            $_user['group'] = array_value($grouplist, $_user['gid'], '');
        }

        $safe_token = well_token_set($uid);

        // hook admin_user_list_end.php

        include _include(ADMIN_PATH . 'view/htm/user_list.htm');
        break;
    case 'create':
        // hook admin_user_create_get_post.php

        if ('GET' == $method) {

            // hook admin_user_create_get_start.php

            $header['title'] = lang('admin_user_create');
            $header['mobile_title'] = lang('admin_user_create');

            $input['email'] = form_text('email', '');
            $input['username'] = form_text('username', '');
            $input['password'] = form_password('password', '');
            $grouparr = arrlist_key_values($grouplist, 'gid', 'name');
            $input['_gid'] = form_select('_gid', $grouparr, 0);

            $safe_token = well_token_set($uid);
            $input['safe_token'] = form_hidden('safe_token', $safe_token);

            // hook admin_user_create_get_end.php

            include _include(ADMIN_PATH . 'view/htm/user_create.htm');

        } elseif ('POST' == $method) {

            FALSE === group_access($gid, 'managecreateuser') and message(1, lang('user_group_insufficient_privilege'));

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            $email = param('email');
            $username = param('username');
            $password = param('password');
            $_gid = param('_gid');

            // hook admin_user_create_post_start.php

            empty($email) and message('email', lang('please_input_email'));
            $email && !is_email($email, $err) and message('email', $err);
            $username && !is_username($username, $err) and message('username', $err);

            $_user = user_read_by_email($email);
            $_user and message('email', lang('email_is_in_use'));

            $_user = user_read_by_username($username);
            $_user and message('username', lang('user_already_exists'));

            // hook admin_user_create_post_before.php

            $salt = xn_rand(16);
            $arr = array(
                'username' => $username,
                'password' => md5(md5($password) . $salt),
                'salt' => $salt,
                'gid' => $_gid,
                'email' => $email,
                'create_ip' => $longip,
                'create_date' => $time
            );
            // hook admin_user_create_post_after.php
            $r = user_create($arr);
            FALSE === $r and message(-1, lang('create_failed'));

            // hook admin_user_create_post_end.php

            message(0, lang('create_successfully'));

        }
        break;
    case 'update':
        $_uid = param('uid', 0);

        // hook admin_user_update_get_post.php

        if ('GET' == $method) {

            $extra = array('uid' => $_uid); // 插件预留

            // hook admin_user_update_get_start.php

            $header['title'] = lang('user_edit');
            $header['mobile_title'] = lang('user_edit');

            $_user = user_read($_uid);

            $input['email'] = form_text('email', $_user['email']);
            $input['username'] = form_text('username', $_user['username']);
            $input['password'] = form_password('password', '');
            $grouparr = arrlist_key_values($grouplist, 'gid', 'name');
            $input['_gid'] = form_select('_gid', $grouparr, $_user['gid']);

            $safe_token = well_token_set($uid);
            $input['safe_token'] = form_hidden('safe_token', $safe_token);

            // hook admin_user_update_get_end.php

            include _include(ADMIN_PATH . 'view/htm/user_update.htm');

        } elseif ('POST' == $method) {

            3 != DEBUG && FALSE === group_access($gid, 'manageupdateuser') and message(1, lang('user_group_insufficient_privilege'));

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            $email = param('email');
            $username = param('username');
            $password = param('password');
            $_gid = param('_gid');

            // hook admin_user_update_post_start.php

            $old = user_read($_uid);
            empty($old) and message('username', lang('uid_not_exists'));

            $email && !is_email($email, $err) and message(2, $err);
            if ($email and $old['email'] != $email) {
                $_user = user_read_by_email($email);
                $_user && $_user['uid'] != $_uid and message('email', lang('email_already_exists'));
            }
            if ($username and $old['username'] != $username) {
                $_user = user_read_by_username($username);
                $_user && $_user['uid'] != $_uid and message('username', lang('user_already_exists'));
            }

            $arr = array();
            $arr['email'] = $email;
            $arr['username'] = $username;
            $arr['gid'] = $_gid;

            if ($password) {
                $salt = xn_rand(16);
                $arr['password'] = md5(md5($password) . $salt);
                $arr['salt'] = $salt;
            }

            // hook admin_user_update_post_exec_before.php

            // 仅仅更新发生变化的部分 / only update changed field
            $update = array_diff_value($arr, $old);
            empty($update) and message(-1, lang('data_not_changed'));

            FALSE === user_update($_uid, $update) and message(-1, lang('update_failed'));

            // hook admin_user_update_post_end.php

            message(0, lang('update_successfully'));
        }
        break;
    case 'delete':
        if ('POST' != $method) message(-1, lang('method_error'));

        FALSE === group_access($gid, 'managedeleteuser') and message(1, lang('user_group_insufficient_privilege'));

        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

        $_uid = param('uid', 0);

        // hook admin_user_delete_start.php

        $_user = user_read($_uid);
        empty($_user) and message(-1, lang('user_not_exists'));
        (1 == $_user['gid']) and message(-1, 'admin_cant_be_deleted');

        FALSE === user_delete($_uid) and message(-1, lang('delete_failed'));

        // hook admin_user_delete_end.php

        message(0, lang('delete_successfully'));
        break;
    // hook admin_user_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_user_end.php

?>