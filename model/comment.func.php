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

//--------------------------强相关--------------------------
// 评论回复不支持html标签，不支持附件和图片
// array('tid' => $tid, 'fid' => $fid, 'doctype' => $doctype, 'message' => $message);
function comment_create($post)
{
    global $time, $uid, $gid;
    if (empty($post)) return FALSE;

    // hook model_comment_create_start.php

    data_message_format($post);

    // 格式化后为空不入库
    if (empty($post['message'])) return FALSE;
    
    $pid = comment__create($post);
    if (FALSE === $pid) return FALSE;

    // hook model_comment_create_center.php

    $forum_update = array('todayposts+' => 1);
    // hook model_comment_create_forum_update_before.php
    forum_update($post['fid'], $forum_update);
    unset($forum_update);

    // hook model_comment_create_after.php

    // 我的回复 审核成功写入website_post_pid
    if (1 == $gid || !group_access($gid, 'commentverify')) {
        // 不需要审核 插入回复小表
        $arr = array('pid' => $pid, 'fid' => $post['fid'], 'tid' => $post['tid'], 'uid' => $uid);
        // hook model_comment_create_post_pid.php
        comment_pid_create($arr);

        // 更新最后回复lastuid
        $arr = array('posts+' => 1, 'last_date' => $time, 'lastuid' => $uid);
        // hook model_comment_create_thread_update.php

        well_thread_update($post['tid'], $arr);

        user_update($uid, array('comments+' => 1));

        // hook model_comment_create_update_lastpid.php
    } else {
        // 评论需要审核
        // hook model_comment_create_verify.php
    }

    // hook model_comment_create_end.php

    runtime_set('comments+', 1);
    runtime_set('todaycomments+', 1);

    // hook model_comment_create_end.php

    return $pid;
}

// 主键更新 $update = array('gid' => $gid, 'userip' => $longip, 'message' => $message, 'doctype' => $doctype);
function comment_update($pid, $update)
{
    global $gid;

    if (empty($pid) || empty($update)) return FALSE;

    // hook model_comment_update_start.php

    data_message_format($update);

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

    $postlist = comment_find($pidarr, $pagesize, FALSE);
    if ($postlist) {
        $i = 0;
        $floor = ($page - 1) * $pagesize + 2;
        foreach ($postlist as &$post) {
            ++$i;
            $post['i'] = $i;
            $post['floor'] = $floor++;
        }
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

    if (empty($postlist)) return NULL;

    $i = 0;
    foreach ($postlist as &$post) {
        ++$i;
        $post['i'] = $i;
        comment_format($post);
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
    $postlist = comment_find($pidarr, $pagesize);

    if (empty($postlist)) return NULL;

    // hook model_comment_find_all_after.php

    $i = 0;
    $floor = ($page - 1) * $pagesize + 2;
    foreach ($postlist as &$post) {
        ++$i;
        $post['i'] = $i;
        $post['floor'] = $floor++;
        // hook model_comment_find_all_foreach.php
    }

    // hook model_comment_find_all_end.php

    return $postlist;
}

function comment_read($pid)
{
    // hook model_comment_read_start.php
    $r = comment__read(array('pid' => $pid));
    $r AND comment_format($r);
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
    // hook model_comment_delete_end.php
    return $r;
}

// 通过删除主题 删除回复 同时更新用户评论数 此处也需要删除待验证和回收站回复数据
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

function comment_format(&$post)
{
    global $conf, $uid, $gid;
    // hook model_comment_format_start.php

    if (empty($post)) return;

    // hook model_comment_format_before.php

    $thread = well_thread_read_cache($post['tid']);
    //$post['fid'] = $thread['fid'];
    $post['closed'] = $thread['closed'];
    $post['subject'] = $thread['subject'];

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
    $post['allowupdate'] = ($uid == $post['uid']) || forum_access_mod($thread['fid'], $gid, 'allowupdate');
    $post['allowdelete'] = (group_access($gid, 'allowuserdelete') AND $uid == $post['uid']) || forum_access_mod($thread['fid'], $gid, 'allowdelete');

    $post['user_url'] = url('user-' . $post['uid'] . ($post['uid'] ? '' : '-' . $post['pid']));

    $post['classname'] = 'post';

    // hook model_comment_format_end.php
}

function comment_filter(&$val)
{
    // hook comment_filter_start.php
    unset($val['userip']);
    // hook comment_filter_end.php
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