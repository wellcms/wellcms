<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');
// 主题评论

// hook comment_start.php

$action = param(1);

// hook comment_before.php

if ($action == 'create') {
    // 创建评论
    // hook comment_create_start.php

    $tid = param(2, 0);

    $thread = well_thread_read($tid);
    empty($thread) AND message(-1, lang('thread_not_exists'));

    // hook comment_create_before.php

    $fid = $thread['fid'];

    //$forum = forum_read($fid);
    $forum = isset($forumlist[$fid]) ? $forumlist[$fid] : NULL;
    empty($forum) AND message(1, lang('forum_not_exists'));

    empty($forum['type']) AND message(1, lang('user_group_insufficient_privilege'));

    // hook comment_create_center.php

    // 用户组权限不足
    forum_access_user($fid, $gid, 'allowpost') || message(1, lang('user_group_insufficient_privilege'));

    // hook comment_create_after.php

    // 已关闭评论
    (($thread['closed'] || empty($forum['comment'])) && ($gid == 0 || $gid > 5)) AND message(1, lang('thread_has_already_closed'));

    if ($method == 'GET') {
        // hook comment_create_get_start.php

        // hook comment_create_get_end.php
    } elseif ($method == 'POST') {
        // hook comment_create_post_start.php

        $message = param('message', '', FALSE);
        empty($message) AND message('message', lang('please_input_message'));
        $message = filter_all_html($message); // 过滤html标签
        xn_strlen($message) > 524288 AND message('message', lang('message_too_long'));

        $doctype = param('doctype', 0);

        $quotepid = param('quotepid', 0);
        $quotepost = comment_pid_read($quotepid);
        (empty($quotepost) || $quotepost['tid'] != $tid) AND $quotepid = 0;

        $post = array(
            'tid' => $tid,
            'uid' => $uid,
            'fid' => $fid,
            'create_date' => $time,
            'userip' => $longip,
            'doctype' => $doctype,
            'quotepid' => $quotepid,
            'message' => $message,
        );
        $pid = comment_create($post);
        $pid === FALSE AND message(-1, lang('create_post_failed'));

        // thread_sticky_create($fid, $tid);

        $post = comment_read($pid);
        $post['floor'] = $thread['posts'] + 2;
        $postlist = array($post);

        $allowpost = forum_access_user($fid, $gid, 'allowpost');
        $allowupdate = forum_access_mod($fid, $gid, 'allowupdate');
        $allowdelete = forum_access_mod($fid, $gid, 'allowdelete');

        // hook comment_create_post_end.php

        // 直接返回帖子的 html
        // return the html string to browser.
        $return_html = param('return_html', 0);
        if ($return_html) {
            $filelist = array();
            ob_start();
            include _include(APP_PATH . 'view/htm/comment_list.inc.htm');
            $s = ob_get_clean();

            message(0, $s);
        } else {
            message(0, lang('create_post_successfully'));
        }
    }
} elseif ($action == 'delete') {
    // 删除回复 type = 1支持批量删除，直接传pid一维数组pid = array(1,2,3)

    $type = param('type', 0);
    $pid = $type ? param('pid', array()) : param(2, 0);

    // hook comment_delete_start.php

    if ($method != 'POST') message(1, lang('method_error'));

    include _include(APP_PATH . 'model/operate.func.php');

    $allowdelete = group_access($gid, 'allowdelete') || group_access($gid, 'allowuserdelete') || $gid == 1;

    empty($allowdelete) AND message(-1, lang('user_group_insufficient_privilege'));

    if ($type) {

        // hook comment_delete_pids_start.php

        $pagesize = 25;

        $arrlist = comment_find($pid, $pagesize, FALSE);

        // hook comment_delete_pids_before.php

        $tidarr = array();
        $pidarr = array();
        $uidarr = array();
        foreach ($arrlist as $key => &$val) {

            // hook comment_delete_pids_access_after.php

            if (!isset($forumlist[$val['fid']])) continue;

            $forum = $forumlist[$val['fid']];

            if (empty($forum['type'])) continue;

            // hook comment_delete_pids_access_before.php

            if (!$val['closed'] && $val['allowdelete'] && $forum['comment']) {

                $pidarr[] = $val['pid'];
                $tidarr[$val['pid']] = $val['tid'];
                isset($uidarr[$val['uid']]) ? $uidarr[$val['uid']] += 1 : $uidarr[$val['uid']] = 1;

                $arr = array('type' => 1, 'uid' => $uid, 'tid' => $val['tid'], 'pid' => $val['pid'], 'subject' => $val['subject'], 'comment' => '', 'create_date' => $time);

                // 创建日志
                operate_create($arr);
                // hook comment_delete_pids_access_aftre.php
            }

            // hook comment_delete_pids_access_end.php
        }

        // hook comment_delete_pids_center.php
        
        empty($pidarr) AND message(1, lang('data_malformation'));

        $r = comment_delete($pidarr);

        foreach ($uidarr as $_uid => $n) {
            user_update($_uid, array('comments-' => $n));
        }

        // hook comment_delete_pids_safter.php

        empty($tidarr) AND message(1, lang('data_malformation'));

        // 更新主题回复数
        $tidarr = array_count_values($tidarr);
        foreach ($tidarr as $tid => $n) {
            well_thread_update($tid, array('posts-' => $n));
        }

        // hook comment_delete_pids_end.php

    } else {
        $post = comment_read($pid);
        empty($post) AND message(-1, lang('post_not_exists'));

        // hook comment_delete_before.php

        $forum = isset($forumlist[$post['fid']]) ? $forumlist[$post['fid']] : NULL;
        empty($forum) AND message(1, lang('forum_not_exists'));

        empty($forum['type']) AND message(1, lang('user_group_insufficient_privilege'));

        // hook comment_delete_center.php

        forum_access_user($post['fid'], $gid, 'allowpost') || message(1, lang('user_group_insufficient_privilege'));

        $allowdelete = forum_access_mod($post['fid'], $gid, 'allowdelete');
        empty($allowdelete) AND empty($post['allowdelete']) AND message(1, lang('insufficient_delete_privilege'));

        empty($allowdelete) AND ($post['closed'] OR empty($forum['comment'])) AND message(1, lang('thread_has_already_closed'));

        $r = comment_delete($pid);

        $update = array('comments-' => 1);
        // hook comment_delete_user_update.php
        user_update($post['uid'], $update);
        unset($update);
        // hook comment_delete_middle.php

        // 更新主题回复数
        $update = array('posts-' => 1);
        // hook comment_delete_thread_update.php
        $r = well_thread_update($post['tid'], $update);

        $arr = array('type' => 1, 'uid' => $uid, 'tid' => $post['tid'], 'pid' => $pid, 'subject' => $post['subject'], 'comment' => '', 'create_date' => $time);

        // hook comment_delete_after.php

        // 创建日志
        operate_create($arr);
    }

    // hook comment_delete_end.php

    message(0, lang('delete_successfully'));
}

// hook comment_end.php

?>