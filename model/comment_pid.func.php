<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */
// hook model_comment_pid_start.php

// ------------> 原生CURD，无关联其他数据。
function comment_pid_create($arr = array(), $d = NULL)
{
    if (empty($arr)) return FALSE;
    // hook model_comment_pid__create_start.php
    $r = db_replace('website_comment_pid', $arr, $d);
    // hook model_comment_pid__create_end.php
    return $r;
}

function comment_pid__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'pid', $col = array(), $d = NULL)
{
    // hook model_comment_pid__find_start.php
    $arr = db_find('website_comment_pid', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_comment_pid__find_end.php
    return $arr;
}

function comment_pid__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_comment_pid_read__start.php
    $r = db_find_one('website_comment_pid', $cond, $orderby, $col, $d);
    // hook model_comment_pid_read__end.php
    return $r;
}

function comment_pid__delete($cond = array(), $d = NULL)
{
    // hook model_comment_pid__delete_start.php
    $r = db_delete('website_comment_pid', $cond, $d);
    // hook model_comment_pid__delete_end.php
    return $r;
}

function comment_pid__count($cond = array(), $d = NULL)
{
    // hook model_comment_pid__count_start.php
    $n = db_count('website_comment_pid', $cond, $d);
    // hook model_comment_pid__count_end.php
    return $n;
}

//--------------------------强相关--------------------------

function comment_pid_read($pid)
{
    // hook model_comment_pid_read_start.php
    $r = comment_pid__read(array('pid' => $pid));
    // hook model_comment_pid_read_end.php
    return $r;
}

// 遍历主题下所有回复
function comment_pid_find($tid, $page = 1, $pagesize = 20, $desc = TRUE)
{
    // hook model_comment_pid_find_start.php
    $orderby = $desc == TRUE ? -1 : 1;
    $arr = comment_pid__find(array('tid' => $tid), array('pid' => $orderby), $page, $pagesize);
    // hook model_comment_pid_find_end.php
    return $arr;
}

// 遍历栏目下所有回复
function comment_pid_find_by_uid($uid, $page = 1, $pagesize = 20, $desc = TRUE)
{
    $orderby = $desc == TRUE ? -1 : 1;
    // hook model_comment_pid_find_by_uid_start.php
    $arr = comment_pid__find(array('uid' => $uid), array('pid' => $orderby), $page, $pagesize);
    // hook model_comment_pid_find_by_uid_end.php
    return $arr;
}

// 遍历栏目下所有回复
function comment_pid_find_all($page = 1, $pagesize = 20, $desc = TRUE)
{
    $orderby = $desc == TRUE ? -1 : 1;
    // hook model_comment_pid_find_by_fid_start.php
    $arr = comment_pid__find(array(), array('pid' => $orderby), $page, $pagesize);
    // hook model_comment_pid_find_by_fid_end.php
    return $arr;
}

// 彻底删除 pid
function comment_pid_delete($pid)
{
    if (empty($pid)) return FALSE;
    // hook model_comment_pid_delete_start.php
    $r = comment_pid__delete(array('pid' => $pid));
    // hook model_comment_pid_delete_end.php
    return $r;
}

function comment_pid_count()
{
    // hook model_comment_pid_count_start.php
    $n = comment_pid__count();
    // hook model_comment_pid_count_end.php
    return $n;
}

// 海量数据的情况下禁止使用非主键的统计函数
// 统计主题回复数 可直接调用website_thread表该主题回复数
function comment_pid_count_by_tid($tid)
{
    // hook model_comment_pid_count_by_tid_start.php
    $n = comment_pid__count(array('tid' => $tid));
    // hook model_comment_pid_count_by_tid_end.php
    return $n;
}

// 海量数据的情况下禁止使用非主键的统计函数
// 统计用户回复数 可直接调用user表该主题回复数posts
function comment_pid_count_by_uid($uid)
{
    // hook model_comment_pid_count_by_uid_start.php
    $n = comment_pid__count(array('uid' => $uid));
    // hook model_comment_pid_count_by_uid_end.php
    return $n;
}

// hook model_comment_pid_end.php
?>