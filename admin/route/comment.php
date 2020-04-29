<?php
/*
 * Copyright (C) www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

FALSE === group_access($gid, 'managecomment') AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// hook website_admin_reply_start.php

if ('list' == $action) {

    // hook website_admin_reply_list_start.php

    if ('GET' == $method) {

        // hook website_admin_reply_list_get_start.php
        // 0已验证 1待验证
        $verify = param('verify', 0);
        $page = param(2, 1);
        $pagesize = 25;
        // 插件预留
        $extra = array('verify' => $verify);
        //$threadlist = NULL;

        // hook website_admin_reply_list_get_before.php

        // 所有审核过的回复
        if (0 == $verify) {

            // hook website_admin_reply_list_get_pid_before.php

            $n = comment_pid_count();

            // hook website_admin_reply_list_get_pid_after.php

            // 全站全部回复数据
            $n AND $postlist = comment_find_all($page, $pagesize);

            // hook website_admin_reply_list_get_postlist_after.php
        } elseif (1 == $verify) {

            // hook website_admin_reply_list_get_verify_start.php

            // hook website_admin_reply_list_get_verify_end.php
        }

        // hook website_admin_reply_list_get_middle.php

        $n = $n > ($pagesize * 2000) ? ($pagesize * 2000) : $n;
        // hook website_admin_reply_list_get_after.php
        $pagination = pagination(url('comment-list-{page}', $extra), $n, $page, $pagesize);

        $safe_token = well_token_set($uid);

        $header['title'] = lang('comment');
        $header['mobile_title'] = lang('comment');
        $header['mobile_link'] = url('comment-list');

        // hook website_admin_reply_list_get_end.php

        include _include(ADMIN_PATH . 'view/htm/comment_list.htm');
    }

    // hook website_admin_reply_list_end.php
}

// hook website_admin_reply_end.php

?>