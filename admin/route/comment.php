<?php
/*
 * Copyright (C) www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

FALSE === group_access($gid, 'managecomment') AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// hook admin_comment_start.php

switch ($action) {
    // hook admin_comment_case_start.php
    case 'list':
        // hook admin_comment_list_start.php

        if ('GET' == $method) {

            // hook admin_comment_list_get_start.php
            // 0已验证 1待验证
            $verify = param('verify', 0);
            $page = param('page', 1);
            $pagesize = 25;
            // 插件预留
            $extra = array('page' => '{page}', 'verify' => $verify);

            // hook admin_comment_list_get_before.php

            // 所有审核过的回复
            if (0 == $verify) {

                // hook admin_comment_list_get_pid_before.php

                $n = comment_pid_count();

                // hook admin_comment_list_get_pid_after.php

                // 全站回复数据
                $n AND $postlist = comment_find_all($page, $pagesize);

                // hook admin_comment_list_get_postlist_after.php
            } elseif (1 == $verify) {

                // hook admin_comment_list_get_verify_start.php

                // hook admin_comment_list_get_verify_end.php
            }

            // hook admin_comment_list_get_middle.php

            $n = $n > ($pagesize * 2000) ? ($pagesize * 2000) : $n;
            // hook admin_comment_list_get_after.php
            $pagination = pagination(url('comment-list', $extra, TRUE), $n, $page, $pagesize);

            $safe_token = well_token_set($uid);

            $header['title'] = lang('comment');
            $header['mobile_title'] = lang('comment');
            $header['mobile_link'] = url('comment-list', '', TRUE);

            // hook admin_comment_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/comment_list.htm');
        }

        // hook admin_comment_list_end.php
        break;
    // hook admin_comment_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_comment_end.php

?>