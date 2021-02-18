<?php
/*
 * Copyright (C) www.wellcms.cn
 * 主题回复(评论)
 */

// hook model_comment_start.php

// ------------> 原生CURD，无关联其他数据。
function comment__create($arr = array(), $d = NULL)
{
    // hook model_comment__create_start.php
    $r = db_insert('website_comment', $arr, $d);
    // hook model_comment__create_end.php
    return $r;
}

function comment__update($pid, $update = array(), $d = NULL)
{
    // hook model_comment__update_start.php
    $r = db_update('website_comment', array('pid' => $pid), $update, $d);
    // hook model_comment__update_end.php
    return $r;
}

function comment__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_comment__read_start.php
    $r = db_find_one('website_comment', $cond, $orderby, $col, $d);
    // hook model_comment__read_end.php
    return $r;
}

function comment__find($cond = array(), $orderby = array('pid' => -1), $page = 1, $pagesize = 20, $key = 'pid', $col = array(), $d = NULL)
{
    // hook model_comment__find_start.php
    $threadlist = db_find('website_comment', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_comment__find_end.php
    return $threadlist;
}

function comment__delete($cond = array(), $d = NULL)
{
    // hook model_comment__delete_start.php
    $r = db_delete('website_comment', $cond, $d);
    // hook model_comment__delete_end.php
    return $r;
}

function comment_count($cond = array(), $d = NULL)
{
    // hook model_comment_count_start.php
    $n = db_count('website_comment', $cond, $d);
    // hook model_comment_count_end.php
    return $n;
}

function comment_max_pid($col = 'pid', $cond = array(), $d = NULL)
{
    // hook model_comment_max_pid_start.php
    $pid = db_maxid('website_comment', $col, $cond, $d);
    // hook model_comment_max_pid_end.php
    return $pid;
}

function comment_big_insert($arr = array(), $d = NULL)
{
    // hook model_comment_big_insert_start.php
    $r = db_big_insert('website_comment', $arr, $d);
    // hook model_comment_big_insert_end.php
    return $r;
}

function comment_big_update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_comment_big_update_start.php
    $r = db_big_update('website_comment', $cond, $update, $d);
    // hook model_comment_big_update_end.php
    return $r;
}

//--------------------------强相关--------------------------
// 评论回复不支持html标签，不支持附件和图片
// array('tid' => $tid, 'fid' => $fid, 'doctype' => $doctype, 'message' => $message);
function comment_create($post)
{
    global $time, $uid, $gid;
    if (empty($post)) return FALSE;

    // 是否审核 TRUE无需审核 FALSE需要审核
    $commentverify = group_access($gid, 'allowdelete') || !group_access($gid, 'commentverify');

    // hook model_comment_create_start.php

    data_message_format($post);

    // 格式化后为空不入库
    if (empty($post['message'])) return FALSE;

    // hook model_comment_create_before.php

    $pid = comment__create($post);
    if (FALSE === $pid) return FALSE;

    // 关联附件
    well_attach_assoc_post(array('tid' => $post['tid'], 'pid' => $pid, 'uid' => $uid, 'assoc' => 'post', 'post_create' => 1, 'images' => 0, 'files' => 0, 'message' => $post['message']));

    // hook model_comment_create_center.php

    // 我的回复 审核成功写入website_post_pid
    if (TRUE === $commentverify) {

        $forum_update = array('todayposts+' => 1);
        // hook model_comment_create_forum_update_before.php
        forum_update($post['fid'], $forum_update);
        unset($forum_update);

        // hook model_comment_create_middle.php

        // 不需要审核 插入回复小表
        $arr = array('pid' => $pid, 'fid' => $post['fid'], 'tid' => $post['tid'], 'uid' => $uid);
        // hook model_comment_create_post.php
        comment_pid_create($arr);

        // 更新最后回复lastuid
        $arr = array('posts+' => 1, 'last_date' => $time, 'lastuid' => $uid);
        // hook model_comment_create_thread_update.php
        well_thread_update($post['tid'], $arr);

        $user_update = array('comments+' => 1);
        // hook model_comment_create_user_update.php
        user_update($uid, $user_update);

        // hook model_comment_create_after.php

        runtime_set('comments+', 1);
        runtime_set('todaycomments+', 1);

        // hook model_comment_create_runtime_after.php

    } else {
        // 评论需要审核
        // hook model_comment_create_verify.php
    }

    // hook model_comment_create_end.php

    return $pid;
}

// 更新时关联附件在route中完成，此处只做已变更内容更新 $update = array('gid' => $gid, 'images' => $images, 'files' => $files, 'userip' => $longip, 'message' => $message, 'doctype' => $doctype);
function comment_update($pid, $update)
{
    global $gid;

    if (empty($pid) || empty($update)) return FALSE;

    // hook model_comment_update_start.php

    if (isset($update['doctype'], $update['message'])) {
        data_message_format($update);
    }

    // hook model_comment_update_before.php

    // 我的回复 审核成功写入website_post_pid
    if (1 == $gid || !group_access($gid, 'commentverify')) {
        $update['status'] = 0;
    } else {
        // hook model_comment_update_verify_start.php
        $update['status'] = 1;
        $read = comment__read(array('pid' => $pid));
        if ($read) {
            comment_pid__delete(array('pid' => $pid));
            // hook model_comment_update_verify_before.php
            $r = comment__read(array('tid' => $read['tid']), array('pid' => -1));
            // 更新最后回复
            $arr = array('posts-' => 1, 'lastuid' => $r['uid']);
            // hook model_comment_update_verify_center.php
            $r AND well_thread_update($read['tid'], $arr);
            // hook model_comment_update_verify_after.php
            // 待审核
        }
        // hook model_comment_update_verify_end.php
    }

    // hook model_comment_update_after.php

    $r = comment__update($pid, $update);

    // hook model_comment_update_end.php

    return $r;
}

// 主题下 所有回复数据详情
function comment_find_by_tid($tid, $page = 1, $pagesize = 20)
{
    // hook model_comment_find_by_tid_start.php

    $arr = comment_pid_find($tid, $page, $pagesize, FALSE);

    if (empty($arr)) return NULL;

    // hook model_comment_find_by_tid_before.php

    $pidarr = arrlist_values($arr, 'pid');

    $postlist = comment__find(array('pid' => $pidarr), array('pid' => 1), 1, $pagesize);

    // hook model_comment_find_by_tid_center.php

    $i = 0;
    $floor = ($page - 1) * $pagesize + 2;
    foreach ($postlist as &$post) {
        ++$i;
        $post['i'] = $i;
        $post['floor'] = $floor++;
        data_format($post);
        comment_format($post);
        comment_format_message($post); // 云储存
        // hook model_comment_find_by_tid_after.php
    }

    // hook model_comment_find_by_tid_end.php

    return $postlist;
}

// 遍历所有回复
function comment_find($pidarr, $pagesize = 20, $desc = TRUE)
{
    if (empty($pidarr)) return NULL;

    // hook model_comment_find_start.php

    $orderby = TRUE == $desc ? -1 : 1;
    $postlist = comment__find(array('pid' => $pidarr), array('pid' => $orderby), 1, $pagesize);

    $i = 0;
    foreach ($postlist as &$post) {
        ++$i;
        $post['i'] = $i;
        data_format($post);
        comment_format($post);
        comment_format_message($post); // 云储存
    }

    // hook model_comment_find_end.php

    return $postlist;
}

// 未格式化
function comment_find_by_pid($pidarr, $pagesize = 20, $desc = TRUE)
{
    if (empty($pidarr)) return NULL;

    // hook model_comment_find_by_pid_start.php

    $orderby = TRUE == $desc ? -1 : 1;
    $postlist = comment__find(array('pid' => $pidarr), array('pid' => $orderby), 1, $pagesize);

    // hook model_comment_find_by_pid_end.php

    return $postlist;
}

// 遍历所有回复
function comment_find_all($page = 1, $pagesize = 20)
{
    // hook model_comment_find_all_start.php

    $arr = comment_pid_find_all($page, $pagesize);

    if (empty($arr)) return NULL;

    $pidarr = arrlist_values($arr, 'pid');

    // hook model_comment_find_all_before.php

    // 遍历主题和回复
    $postlist = comment__find(array('pid' => $pidarr), array('pid' => -1), 1, $pagesize);

    // hook model_comment_find_all_after.php

    $i = 0;
    $floor = ($page - 1) * $pagesize + 2;
    foreach ($postlist as &$post) {
        ++$i;
        $post['i'] = $i;
        $post['floor'] = $floor++;
        data_format($post);
        comment_format($post);
        comment_format_message($post); // 云储存
        // hook model_comment_find_all_foreach.php
    }

    // hook model_comment_find_all_end.php

    return $postlist;
}

function comment_read($pid)
{
    // hook model_comment_read_start.php
    $r = comment__read(array('pid' => $pid));
    if ($r) {
        data_format($r);
        comment_format($r);
        comment_format_message($r); // 云储存
    }
    // hook model_comment_read_end.php
    return $r;
}

// 直接删除回复 彻底删除
function comment_delete($pid)
{
    if (empty($pid)) return FALSE;
    // hook model_comment_delete_start.php
    $r = comment__delete(array('pid' => $pid));
    if (FALSE === $r) return FALSE;
    // 删除小表
    $r = comment_pid_delete($pid);
    // 删除附件
    well_attach_delete_by_pid($pid);
    // hook model_comment_delete_end.php
    return $r;
}

// 删除主题和回复 同时更新用户评论数 此处也需要删除待验证和回收站回复数据
function comment_delete_by_tid($tid)
{
    $thread = well_thread_read_cache($tid);
    if (empty($thread)) return FALSE;

    // hook model_comment_delete_by_tid_start.php

    $posts = $thread['posts'];
    if (0 == $posts) return FALSE;

    $size = 1000;
    if ($posts > $size) {
        $n = ceil($posts / $size);
    } else {
        $n = $posts;
    }

    for ($i = 0; $i <= $n; ++$i) {
        // 查询回复小表 该主题回复 pid
        $arr = comment_pid__find(array('tid' => $tid), array('pid' => -1), 1, $size, 'pid', array('pid', 'uid'));

        // hook model_comment_delete_by_tid_before.php

        if (empty($arr)) return FALSE;

        $pidarr = array();
        $uidarr = array();
        foreach ($arr as $val) {
            $pidarr[] = $val['pid'];
            isset($uidarr[$val['uid']]) ? $uidarr[$val['uid']] += 1 : $uidarr[$val['uid']] = 1;
        }

        foreach ($uidarr as $_uid => $n) {
            user_update($_uid, array('comments-' => $n));
        }

        // 删除所有回复和小表
        comment_delete($pidarr);

        // hook model_comment_delete_by_tid_after.php
    }

    // hook model_comment_delete_by_tid_end.php

    return $posts;
}

/*
 * @param $tids 数组array(1,2,3)
 * @param $n 删除的总评论数量
 * @return int 返回删除的数量
 */
function comment_delete_by_tids($tids, $n)
{
    $arrlist = comment_pid__find(array('tid' => $tids), array('pid' => 1), 1, $n);
    if (!$arrlist) return 0;

    $pids = array();
    $uidarr = array();
    foreach ($arrlist as $val) {
        $pids[] = $val['pid'];
        isset($uidarr[$val['uid']]) ? $uidarr[$val['uid']] += 1 : $uidarr[$val['uid']] = 1;
    }

    comment_pid_delete($pids);

    comment__delete(array('pid' => $pids));

    // 删除附件
    well_attach_delete_by_pid($pids);

    $uids = array();
    $update = array();
    foreach ($uidarr as $_uid => $n) {
        $uids[] = $_uid;
        $update[$_uid] = array('comments-' => $n);
    }

    // 更新用户评论数
    user_big_update(array('uid' => $uids), $update);

    return count($pids);
}

function comment_delete_by_pids($pids)
{
    $commentlist = comment__find(array('pid' => $pids), array('pid' => -1), 1, count($pids));

    $pidarr = array();
    $uidarr = array();
    $tidarr = array();
    foreach ($commentlist as $comment) {
        // 每个栏目下的回复数
        $pidarr[] = $comment['pid'];

        // uid
        isset($uidarr[$comment['uid']]) ? $uidarr[$comment['uid']] += 1 : $uidarr[$comment['uid']] = 1;

        // tid
        isset($tidarr[$comment['tid']]) ? $tidarr[$comment['tid']] += 1 : $tidarr[$comment['tid']] = 1;
    }
    unset($postist);

    if (!empty($pidarr)) {
        // 删除附件
        well_attach_delete_by_pid($pidarr);

        // 删除主表
        comment__delete(array('pid' => $pidarr));

        // 删除小表
        comment_pid_delete($pidarr);

        runtime_set('comments-', count($pidarr));
    }

    // 更新用户评论数
    if (!empty($uidarr)) {
        $uids = array();
        $update = array();
        foreach ($uidarr as $_uid => $n) {
            $uids[] = $_uid;
            $update[$_uid] = array('credits-' => $n);
        }

        user_big_update(array('uid' => $uids), $update);
    }

    // 更新主题回复数
    if (!empty($tidarr)) {
        $tids = array();
        $update = array();
        foreach ($tidarr as $_tid => $n) {
            $tids[] = $_tid;
            $update[$_tid] = array('posts-' => $n);
        }

        thread_big_update(array('tid' => $tids), $update);
    }
}

function comment_format_by_tid(&$post)
{
    global $conf, $uid, $gid;
    // hook model_comment_format_start.php

    if (empty($post)) return;

    // hook model_comment_format_before.php

    $post['create_date_fmt'] = humandate($post['create_date']);
    //$post['message'] = stripslashes(htmlspecialchars_decode($post['message']));

    // hook model_comment_format_center.php

    $user = user_read_cache($post['uid']);

    $post['username'] = array_value($user, 'username');
    $post['user_avatar_url'] = array_value($user, 'avatar_url');
    $post['user'] = $user ? $user : user_guest();
    isset($post['floor']) || $post['floor'] = 0;

    // hook model_comment_format_after.php

    // 权限判断
    $post['allowupdate'] = ($uid == $post['uid']) || forum_access_mod($post['fid'], $gid, 'allowupdate');
    $post['allowdelete'] = (group_access($gid, 'allowuserdelete') AND $uid == $post['uid']) || forum_access_mod($post['fid'], $gid, 'allowdelete');

    $post['user_url'] = url('user-' . $post['uid'] . ($post['uid'] ? '' : '-' . $post['pid']));

    if ($post['files'] > 0) {
        list($attachlist, $imagelist, $post['filelist']) = well_attach_find_by_pid($post['pid']);

        // 使用图床 评论使用图床，mysql会过多，写死链接到内容是减轻mysql的过多的方法
        if (2 == $conf['attach_on']) {
            foreach ($imagelist as $key => $attach) {
                $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];
                // 替换成图床
                $post['message'] = FALSE !== strpos($post['message'], $url) && $attach['image_url'] ? str_replace($url, $attach['image_url'], $post['message']) : $post['message'];
            }
        }

    } else {
        $post['filelist'] = array();
    }

    $post['classname'] = 'post';

    // hook model_comment_format_end.php
}

function comment_format(&$post)
{
    global $conf, $uid, $gid, $forumlist;
    // hook model_comment_format_start.php

    if (empty($post)) return;

    // hook model_comment_format_before.php
    $forum = $post['fid'] ? forum_read($post['fid']) : '';
    $thread = well_thread_read_cache($post['tid']);
    //$post['fid'] = $thread['fid'];
    $post['closed'] = $thread['closed'];
    $post['subject'] = $thread['subject'];
    $post['url'] = $thread['url'];

    $post['create_date_fmt'] = humandate($post['create_date']);
    //$post['message'] = stripslashes(htmlspecialchars_decode($post['message']));

    // hook model_comment_format_center.php

    $user = user_read_cache($post['uid']);

    $post['username'] = array_value($user, 'username');
    $post['user_avatar_url'] = array_value($user, 'avatar_url');
    $post['user'] = $user ? user_safe_info($user) : user_guest();
    isset($post['floor']) || $post['floor'] = 0;

    // hook model_comment_format_after.php

    // 权限判断
    $post['allowupdate'] = 2 == array_value($forum, 'comment', 0) && ($uid == $post['uid'] || forum_access_mod($thread['fid'], $gid, 'allowupdate'));
    $post['allowdelete'] = (group_access($gid, 'allowuserdelete') AND $uid == $post['uid']) || forum_access_mod($thread['fid'], $gid, 'allowdelete');

    $post['user_url'] = url('user-' . $post['uid'] . ($post['uid'] ? '' : '-' . $post['pid']));

    if ($post['files'] > 0) {
        list($attachlist, $imagelist, $filelist) = well_attach_find_by_pid($post['pid']);

        // 使用图床 评论使用图床，mysql会过多，写死链接到内容是减轻mysql的过多的方法
        if (2 == $conf['attach_on']) {
            foreach ($imagelist as $key => $attach) {
                $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];
                // 替换成图床
                $post['message'] = FALSE !== strpos($post['message'], $url) && $attach['image_url'] ? str_replace($url, $attach['image_url'], $post['message']) : $post['message'];
            }
        }

        $post['filelist'] = $filelist;
    } else {
        $post['filelist'] = array();
    }

    $post['classname'] = 'post';

    // hook model_comment_format_end.php
}

function comment_format_message(&$val)
{
    global $conf;
    // hook model_comment_format_message_start.php

    if (empty($val)) return;

    // 使用云储存
    1 == $conf['attach_on'] || 0 == $conf['attach_on'] AND $val['message'] = str_replace('="upload/', '="' . file_path(), $val['message']);

    //$val['message'] = stripslashes(htmlspecialchars_decode($val['message']));

    // hook model_comment_format_message_end.php
}

// 把内容中使用了云储存的附件链接替换掉
function comment_message_replace_url($pid, $message)
{
    global $conf;

    // hook model_comment_message_replace_url_start.php

    if (0 == $conf['attach_on']) {
        $message = FALSE !== strpos($message, '="../upload/') ? str_replace('="../upload/', '="upload/', $message) : $message;
        $message = FALSE !== strpos($message, '="/upload/') ? str_replace('="/upload/', '="upload/', $message) : $message;
    } elseif (1 == $conf['attach_on']) {
        // 使用云储存
        $message = str_replace('="' . $conf['cloud_url'] . 'upload/', '="upload/', $message);
    } elseif (2 == $conf['attach_on']) {

        // 使用图床 评论使用图床，mysql会过多，写死链接到内容是减轻mysql的过多的方法
        list($attachlist, $imagelist, $filelist) = well_attach_find_by_pid($pid);

        foreach ($imagelist as $key => $attach) {
            $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];
            // 替换回相对链接
            $message = $attach['image_url'] && FALSE !== strpos($message, $attach['image_url']) ? str_replace($attach['image_url'], $url, $message) : $message;
        }
    }

    // hook model_comment_message_replace_url_end.php

    return $message;
}

function comment_filter($val)
{
    // hook comment_filter_start.php
    unset($val['userip']);
    // hook comment_filter_end.php
    return $val;
}

function comment_highlight_keyword($str, $k)
{
    // hook model_comment_highlight_keyword_start.php
    $r = str_ireplace($k, '<span class=red>' . $k . '</span>', $str);
    // hook model_comment_highlight_keyword_end.php
    return $r;
}

// <img src="/view/img/face/1.gif"/>
// <blockquote class="blockquote">
function comment_message_format(&$s)
{
    if (xn_strlen($s) < 100) return;
    $s = preg_replace('#<blockquote\s+class="blockquote">.*?</blockquote>#is', '', $s);
    $s = str_ireplace(array('<br>', '<br />', '<br/>', '</p>', '</tr>', '</div>', '</li>', '</dd>' . '</dt>'), "\r\n", $s);
    $s = str_ireplace(array('&nbsp;'), " ", $s);
    $s = strip_tags($s);
    $s = preg_replace('#[\r\n]+#', "\n", $s);
    $s = xn_substr(trim($s), 0, 100);
    $s = str_replace("\n", '<br>', $s);
}

// 对内容进行引用
function comment_quote($quotepid)
{
    // hook model_comment_quote_start.php

    $quotepost = comment_read($quotepid);
    if (empty($quotepost)) return '';
    $uid = $quotepost['uid'];
    $s = $quotepost['message'];

    // hook model_comment_quote_before.php

    $s = comment_brief($s, 100);
    $userhref = url('user-' . $uid);
    $user = user_read_cache($uid);

    // hook model_comment_quote_after.php

    $r = '<blockquote class="blockquote">
		<a href="' . $userhref . '" class="text-small text-muted user">
			<img class="avatar-1" src="' . $user['avatar_url'] . '">
			' . $user['username'] . '
		</a>
		' . $s . '
		</blockquote>';

    // hook model_comment_quote_end.php

    return $r;
}

// 获取内容的简介 0: html, 1: txt; 2: markdown; 3: ubb
function comment_brief($s, $len = 100)
{
    // hook model_comment_brief_start.php
    $s = strip_tags($s);
    $s = htmlspecialchars($s);
    $more = xn_strlen($s) > $len ? ' ... ' : '';
    $s = xn_substr($s, 0, $len) . $more;
    // hook model_comment_brief_end.php
    return $s;
}

// hook model_comment_end.php

?>