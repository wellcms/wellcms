<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');

// 检查是否登录
user_login_check();

$action = param(1, 'index');

// hook home_start.php

// 从全局拉取$user
$header['mobile_title'] = $user['username'];
$header['mobile_linke'] = url('my');

switch ($action) {
    // hook home_case_start.php
    case 'index':
        $page = param(2, 1);
        $pagesize = $conf['pagesize'];
        $extra = array(); // 插件预留

        // hook home_index_start.php

        $threadlist = well_thread_find_by_uid($uid, $page, $pagesize);

        $allowdelete = group_access($gid, 'allowdelete') || group_access($gid, 'allowuserdelete') || 1 == $gid;

        $page_url = url('home-index-{page}', $extra);
        $num = $user['articles'];

        // hook home_index_pagination_before.php

        $pagination = pagination($page_url, $num, $page, $pagesize);

        $header['title'] = lang('my_index_page');

        // hook home_index_end.php

        include _include(theme_load(14));
        break;
    case 'comment':
        // hook home_comment_start.php

        if ('GET' == $method) {

            $page = param(2, 1);
            $pagesize = $conf['pagesize'];
            $extra = array(); // 插件预留

            // hook home_comment_before.php

            $postlist = comment_pid_find_by_uid($uid, $page, $pagesize);

            if ($postlist) {
                $pids = array();
                $tids = array();
                foreach ($postlist as &$_pid) {
                    $pids[] = $_pid['pid'];
                    $tids[] = $_pid['tid'];
                }

                // hook home_comment_center.php

                $threadlist = well_thread__find(array('tid' => $tids));

                $commentlist = comment_find_by_pid($pids, $pagesize);

                foreach ($commentlist as &$val) {
                    comment_filter($val);
                    $val['subject'] = $threadlist[$val['tid']]['subject'];
                    $val['url'] = url('read-' . $val['tid']);
                    $val['allowdelete'] = (group_access($gid, 'allowuserdelete') AND $uid == $val['uid']) || forum_access_mod($val['fid'], $gid, 'allowdelete');
                }
            }

            // hook home_comment_middle.php

            $allowdelete = group_access($gid, 'allowdelete') || group_access($gid, 'allowuserdelete') || 1 == $gid;

            $page_url = url('home-comment-{page}', $extra);
            $num = $user['comments'];

            // hook home_comment_pagination_before.php

            $pagination = pagination($page_url, $num, $page, $pagesize);

            $safe_token = well_token_set($uid);

            $header['title'] = lang('my') . lang('comment');

            // hook home_comment_after.php

            include _include(theme_load(16));
        }

        // hook home_comment_end.php
        break;
    // hook home_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook home_end.php

?>