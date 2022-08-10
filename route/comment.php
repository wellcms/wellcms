<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') and exit('Access Denied.');

// hook comment_start.php

$action = param(1);

// hook comment_before.php

switch ($action) {
    // hook comment_case_start.php
    case 'create':

        user_login_check();

        // hook comment_create_start.php

        $tid = param(2, 0);
        $thread = well_thread_read($tid);
        empty($thread) and message(-1, lang('thread_not_exists'));

        // hook comment_create_before.php

        $quotepid = param(3, 0);
        $fid = $thread['fid'];
        $forum = forum_read($fid);
        empty($forum) and message(1, lang('forum_not_exists'));
        1 != $forum['type'] and message(1, lang('user_group_insufficient_privilege'));

        // 附表数据在此处合并
        // hook comment_create_center.php

        // 用户组权限不足
        0 != $thread['status'] || !forum_access_user($fid, $gid, 'allowpost') and message(1, lang('user_group_insufficient_privilege'));

        // hook comment_create_after.php

        // 已关闭评论
        (($thread['closed'] || 0 == $forum['comment']) && (0 == $gid || $gid > 5)) and message(1, lang('thread_has_already_closed'));

        if ('GET' == $method) {

            2 != array_value($forum, 'comment', 0) and message(1, lang('user_group_insufficient_privilege'));

            $extra = array();
            
            // hook comment_create_get_start.php

            $safe_token = well_token_set($uid);
            $_SESSION['tmp_website_files'] = array();

            // 来源
            $referer = http_referer();
            $referer = $referer ? $referer : $thread['url'];

            // hook comment_create_get_before.php

            $header['title'] = lang('reply');
            $header['mobile_link'] = $referer;

            // hook comment_create_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['extra'] = $extra;
                $apilist['referer'] = $referer;
                $apilist['forum'] = $forum;
                $apilist['thread'] = well_thread_safe_info($thread);
                $apilist['safe_token'] = $safe_token;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('comment', $fid));
            }

        } elseif ('POST' == $method) {

            // 验证token
            if (1 == array_value($conf, 'comment_token', 0)) {
                $safe_token = param('safe_token');
                FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));
            }

            // hook comment_create_post_start.php

            $doctype = param('doctype', 0);
            $quotepid = param('quotepid', 0);
            $message = param('message', '', FALSE);

            empty($message) and message('message', lang('please_input_message'));

            if (2 == array_value($forum, 'comment', 0)) {
                // 过滤a标签
                $message = preg_replace("#<(\/?a.*?)>#si", '', $message);
            } else {
                $message = stripslashes(trim($message));
                $message = strip_tags($message);
            }

            $message = data_message_replace_url($tid, $message);

            // hook comment_create_post_before.php

            xn_strlen($message) > 524288 and message('message', lang('message_too_long'));

            $quotepost = comment_pid_read($quotepid);
            (empty($quotepost) || $quotepost['tid'] != $tid) and $quotepid = 0;

            // hook comment_create_post_center.php

            $post = array('tid' => $tid, 'uid' => $uid, 'fid' => $fid, 'create_date' => $time, 'userip' => $longip, 'doctype' => $doctype, 'quotepid' => $quotepid, 'message' => $message);
            // hook comment_create_post_middle.php
            $pid = comment_create($post);
            FALSE === $pid and message(-1, lang('create_post_failed'));

            // 清理主题缓存
            cache_delete('website_thread_' . $tid);

            $post = comment_read($pid);
            $post['floor'] = $thread['posts'] + 2;
            $postlist = array($post);

            // hook comment_create_post_after.php

            $allowpost = forum_access_user($fid, $gid, 'allowpost');
            $allowupdate = forum_access_mod($fid, $gid, 'allowupdate');
            $allowdelete = forum_access_mod($fid, $gid, 'allowdelete');

            // hook comment_create_post_end.php

            // 直接返回帖子的 html
            // return the html string to browser.
            $return_html = param('return_html', 0);
            if (1 == $return_html) {
                $filelist = array();
                ob_start();
                include _include(theme_load('comment_list.inc'));
                $s = ob_get_clean();
                message(0, $s);
            } elseif (2 == $return_html) {
                message(0, $post);
            } else {
                message(0, lang('create_post_successfully'));
            }
        }

        break;
    case 'update':

        user_login_check();

        // hook comment_update_start.php

        $pid = param(2, 0);
        $comment = comment_read($pid);
        empty($comment) and message(-1, lang('post_not_exists'));

        // hook comment_update_before.php

        $tid = $comment['tid'];
        $thread = well_thread_read_cache($tid);
        empty($thread) and message(-1, lang('thread_not_exists'));

        // hook comment_update_center.php

        $fid = $thread['fid'];
        $forum = forum_read($fid);
        empty($forum) and message(-1, lang('forum_not_exists'));
        1 != $forum['type'] and message(1, lang('user_group_insufficient_privilege'));

        // 高级回复编辑内容
        2 != array_value($forum, 'comment', 0) and message(1, lang('user_group_insufficient_privilege'));

        // hook comment_update_middle.php

        // 用户组权限不足
        forum_access_user($fid, $gid, 'allowpost') || message(1, lang('user_group_insufficient_privilege'));

        // 已关闭评论
        (($thread['closed'] || 0 == $forum['comment']) && (0 == $gid || $gid > 5)) and message(1, lang('thread_has_already_closed'));

        $allowupdate = forum_access_mod($fid, $gid, 'allowupdate');
        !$allowupdate && !$comment['allowupdate'] and message(-1, lang('have_no_privilege_to_update'));

        !$allowupdate && $thread['closed'] and message(-1, lang('thread_has_already_closed'));

        // hook comment_update_after.php

        if ('GET' == $method) {

            $extra = array();

            // hook comment_update_get_start.php

            $comment['message'] = htmlspecialchars($comment['message']);

            ($uid != $comment['uid']) and $post['message'] = xn_html_safe($comment['message']);

            // hook comment_update_get_before.php

            $attachlist = $imagelist = $filelist = array();
            $comment['files'] and list($attachlist, $imagelist, $filelist) = well_attach_find_by_pid($pid);

            // hook comment_update_get_center.php

            $_SESSION['tmp_website_files'] = array();

            $safe_token = well_token_set($uid);

            // hook comment_update_get_after.php

            // 来源
            $referer = http_referer();
            $referer = $referer ? $referer : $thread['url'];

            $header['title'] = lang('reply');
            $header['mobile_link'] = $thread['url'];

            // hook comment_update_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['extra'] = $extra;
                $apilist['referer'] = $referer;
                $apilist['forum'] = $forum;
                $apilist['thread'] = well_thread_safe_info($thread);
                $apilist['comment'] = comment_filter($comment);
                $apilist['safe_token'] = $safe_token;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('comment', $fid));
            }

        } elseif ('POST' == $method) {

            // 验证token
            if (1 == array_value($conf, 'comment_token', 0)) {
                $safe_token = param('safe_token');
                FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));
            }

            $message = param('message', '', FALSE);
            $doctype = param('doctype', 0);

            // hook comment_update_post_start.php

            if (2 == array_value($forum, 'comment', 0)) {
                // 过滤a标签
                $message = preg_replace("#<(\/?a.*?)>#si", '', $message);
            } else {
                $message = stripslashes(trim($message));
                $message = strip_tags($message);
            }

            // hook comment_update_post_before.php

            empty($message) and message('message', lang('please_input_message'));
            mb_strlen($message, 'UTF-8') > 2048000 and message('message', lang('message_too_long'));

            // hook comment_update_post_center.php

            $tmp_file = well_attach_assoc_type('post');
            if (md5($message) != md5($comment['message']) || !empty($tmp_file)) {
                // 云储存或使用图床需要把内容中的附件链接替换掉
                $message = data_message_replace_url($tid, $message);
                // hook comment_update_post_assoc_before.php
                // 关联附件
                $assoc = array('uid' => $uid, 'gid' => $gid, 'tid' => $tid, 'pid' => $comment['pid'], 'fid' => $comment['fid'], 'time' => $time, 'conf' => $conf, 'message' => $message, 'thumbnail' => 0, 'save_image' => 0, 'sess_file' => 1);
                $result = well_attach_assoc_handle($assoc);
                unset($assoc);
                $message = $result['message'];
                $images = $result['images'];
                $files = $result['files'];

                // hook comment_update_post_assoc_after.php

                $update = array('doctype' => $doctype, 'images' => $images, 'files' => $files, 'message' => $message);
                // hook comment_update_post_after.php
                FALSE === comment_update($pid, $update) and message(-1, lang('update_post_failed'));
            }

            // hook post_update_post_end.php

            message(0, lang('update_successfully'));
        }

        break;
    case 'delete':
        // 删除回复 type = 1支持批量删除，直接传pid一维数组pid = array(1,2,3)
        user_login_check();

        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

        $type = param('type', 0);
        if (0 == $type) {
            $pid = param(2, 0); 
        } else {
            $pid = _POST('pid');
            !is_int($pid) && !is_array($pid) && $pid = xn_json_decode($pid);
        }
        empty($pid) and message(-4, lang('data_is_empty'));

        // hook comment_delete_start.php

        if ('POST' != $method) message(1, lang('method_error'));

        include _include(APP_PATH . 'model/operate.func.php');

        $allowdelete = 1 == $gid || group_access($gid, 'allowdelete') || group_access($gid, 'allowuserdelete');

        empty($allowdelete) and message(-1, lang('user_group_insufficient_privilege'));

        if ($type) {

            // hook comment_delete_pids_start.php

            $pagesize = 25;
            $arrlist = comment_find($pid, $pagesize, FALSE);

            // hook comment_delete_pids_before.php

            $tidarr = array();
            $pidarr = array();
            $uidarr = array();
            $logarr = array();
            foreach ($arrlist as $key => &$val) {

                // hook comment_delete_pids_access_before.php

                if (!isset($forumlist[$val['fid']])) continue;
                $forum = $forumlist[$val['fid']];
                if (empty($forum['type'])) continue;

                // hook comment_delete_pids_access_center.php

                if (!$val['closed'] && $val['allowdelete'] && $forum['comment']) {

                    $pidarr[] = $val['pid'];

                    if ($val['tid']) {
                        $tidarr[$val['pid']] = $val['tid'];
                        $logarr[] = array('type' => 1, 'uid' => $uid, 'tid' => $val['tid'], 'pid' => $val['pid'], 'subject' => $val['subject'], 'comment' => '', 'create_date' => $time);
                    }

                    isset($uidarr[$val['uid']]) ? $uidarr[$val['uid']] += 1 : $uidarr[$val['uid']] = 1;

                    // hook comment_delete_pids_access_after.php
                }

                // hook comment_delete_pids_access_end.php
            }

            // hook comment_delete_pids_center.php

            operate_big_insert($logarr);

            empty($pidarr) and message(1, lang('data_malformation'));

            $r = comment_delete($pidarr);

            $uids = array();
            $update = array();
            foreach ($uidarr as $_uid => $n) {
                $uids[] = $_uid;
                $update[$_uid] = array('comments-' => $n);
                // hook comment_delete_pids_uidarr.php
            }

            // hook comment_delete_pids_user_update.php

            user_big_update(array('uid' => $uids), $update);

            // hook comment_delete_pids_after.php

            empty($tidarr) and message(1, lang('data_malformation'));

            $tidarr = array_count_values($tidarr);
            // 更新主题回复数
            $tids = array();
            $update = array();
            foreach ($tidarr as $tid => $n) {
                $tids[$tid] = $tid;
                $update[$tid] = array('posts-' => $n);
                // hook comment_delete_pids_tidarr.php
            }

            // hook comment_delete_pids_thread_update.php

            thread_big_update(array('tid' => $tids), $update);

            // hook comment_delete_pids_end.php

        } else {
            $post = comment_read($pid);
            empty($post) and message(-1, lang('post_not_exists'));

            // hook comment_delete_before.php

            $forum = isset($forumlist[$post['fid']]) ? $forumlist[$post['fid']] : NULL;
            empty($forum) and message(1, lang('forum_not_exists'));

            empty($forum['type']) and message(1, lang('user_group_insufficient_privilege'));

            // hook comment_delete_center.php

            $allowdelete = forum_access_mod($post['fid'], $gid, 'allowdelete');
            empty($allowdelete) && empty($post['allowdelete']) and message(1, lang('insufficient_delete_privilege'));

            empty($allowdelete) && ($post['closed'] or empty($forum['comment'])) and message(1, lang('thread_has_already_closed'));

            // hook comment_delete_allow_after.php

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
        break;
    // hook comment_case_end.php
}

// hook comment_end.php

?>