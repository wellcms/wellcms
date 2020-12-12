<?php
/*
 * Copyright (C) www.wellcms.cn
 *
 * $arrlist = thread_tid__find(array('fid' => 1, 'tid' => array('>' => 40000000)), $orderby = array('tid' => 1), $page = 1, $pagesize = 20, $key = 'tid', $col = array('tid'));
 * total / page * pagesize
 * 100 / 101 + page * pagesize/ 201 + page * pagesize
 */
// hook model_thread_tid_start.php

// ------------> 原生CURD，无关联其他数据。
function thread_tid__create($arr = array(), $d = NULL)
{
    // hook model_thread_tid__create_start.php
    $r = db_replace('website_thread_tid', $arr, $d);
    // hook model_thread_tid__create_end.php
    return $r;
}

function thread_tid__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_thread_tid__update_start.php
    $r = db_update('website_thread_tid', $cond, $update, $d);
    // hook model_thread_tid__update_end.php
    return $r;
}

function thread_tid__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_thread_tid__read_start.php
    $r = db_find_one('website_thread_tid', $cond, $orderby, $col, $d);
    // hook model_thread_tid__read_end.php
    return $r;
}

function thread_tid__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tid', $col = array(), $d = NULL)
{
    // hook model_thread_tid__find_start.php
    $arr = db_find('website_thread_tid', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_thread_tid__find_end.php
    return $arr;
}

function thread_tid__delete($cond = array(), $d = NULL)
{
    // hook model_thread_tid__delete_start.php
    $r = db_delete('website_thread_tid', $cond, $d);
    // hook model_thread_tid__delete_end.php
    return $r;
}

function thread_tid__count($cond = array(), $d = NULL)
{
    // hook model_thread_tid__count_start.php
    $n = db_count('website_thread_tid', $cond, $d);
    // hook model_thread_tid__count_end.php
    return $n;
}

function thread_tid_big_insert($arr = array(), $d = NULL)
{
    // hook model_thread_tid_big_insert_start.php
    $r = db_big_insert('website_thread_tid', $arr, $d);
    // hook model_thread_tid_big_insert_end.php
    return $r;
}

function thread_tid_big_update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_thread_tid_big_update_start.php
    $r = db_big_update('website_thread_tid', $cond, $update, $d);
    // hook model_thread_tid_big_update_end.php
    return $r;
}
//--------------------------强相关--------------------------
function thread_tid_create($arr)
{
    if (empty($arr)) return FALSE;
    // hook model_thread_tid_create_start.php
    $r = thread_tid__create($arr);
    if (FALSE === $r) return FALSE;
    // hook model_thread_tid_create_end.php
    return $r;
}

// 单次查询 tid 正常直接单次查询主表
function thread_tid_read($tid)
{
    // hook model_thread_tid_read_start.php
    $r = thread_tid__read(array('tid' => $tid));
    // hook model_thread_tid_read_end.php
    return $r;
}

// 主键更新 若移动栏目 则需要更新此表fid
function thread_tid_update($tid, $fid)
{
    if (empty($tid) || empty($fid)) return FALSE;
    // hook model_thread_tid_update_start.php
    $r = thread_tid__update(array('tid' => $tid), array('fid' => $fid));
    // hook model_thread_tid_update_end.php
    return $r;
}

// 主键更新lastpid
function thread_tid_update_lastpid($tid, $lastpid)
{
    if (empty($tid) || empty($lastpid)) return FALSE;
    // hook model_thread_tid_update_start.php
    $r = thread_tid__update(array('tid' => $tid), array('lastpid' => $lastpid));
    // hook model_thread_tid_update_end.php
    return $r;
}

// 更新自定义主题排序
function thread_tid_update_rank($tid, $rank)
{
    if (empty($tid) || empty($rank)) return FALSE;
    // hook model_thread_tid_update_rank_start.php
    $r = thread_tid__update(array('tid' => $tid), array('rank' => $rank));
    // hook model_thread_tid_update_rank_end.php
    return $r;
}

// 遍历所有主题tid
function thread_tid_find($page = 1, $pagesize = 20, $desc = TRUE)
{
    // hook model_thread_tid_find_start.php
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = thread_tid__find($cond = array(), array('tid' => $orderby), $page, $pagesize, 'tid', array('tid', 'verify_date'));
    // hook model_thread_tid_find_end.php
    return $arr;
}

/* 遍历用户所有主题
 * @param $uid 用户ID
 * @param int $page 页数
 * @param int $pagesize 每页记录条数
 * @param bool $desc 排序方式 TRUE降序 FALSE升序
 * @param string $key 返回的数组用那一列的值作为 key
 * @param array $col 查询哪些列
 */
function thread_tid_find_by_uid($uid, $page = 1, $pagesize = 1000, $desc = TRUE, $key = 'tid', $col = array())
{
    if (empty($uid)) return array();
    // hook model_thread_tid_find_by_uid_start.php
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = thread_tid__find($cond = array('uid' => $uid), array('tid' => $orderby), $page, $pagesize, $key, $col);
    // hook model_thread_tid_find_by_uid_end.php
    return $arr;
}

// 遍历栏目下tid 支持数组 $fid = array(1,2,3)
function thread_tid_find_by_fid($fid, $page = 1, $pagesize = 1000, $desc = TRUE)
{
    if (empty($fid)) return array();
    // hook model_thread_tid_find_by_fid_start.php
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = thread_tid__find($cond = array('fid' => $fid), array('tid' => $orderby), $page, $pagesize, 'tid', array('tid', 'verify_date'));
    // hook model_thread_tid_find_by_fid_end.php
    return $arr;
}

function thread_tid_delete($tid)
{
    if (empty($tid)) return FALSE;
    // hook model_thread_tid_delete_start.php
    $r = thread_tid__delete(array('tid' => $tid));
    // hook model_thread_tid_delete_end.php
    return $r;
}

function thread_tid_count()
{
    // hook model_thread_tid_count_start.php
    $n = thread_tid__count();
    // hook model_thread_tid_count_end.php
    return $n;
}

// 统计用户主题数 大数量下严谨使用非主键统计
function thread_uid_count($uid)
{
    // hook model_thread_uid_count_start.php
    $n = thread_tid__count(array('uid' => $uid));
    // hook model_thread_uid_count_end.php
    return $n;
}

// 统计栏目主题数 大数量下严谨使用非主键统计
function thread_fid_count($fid)
{
    // hook model_thread_fid_count_start.php
    $n = thread_tid__count(array('fid' => $fid));
    // hook model_thread_fid_count_end.php
    return $n;
}

// hook model_thread_tid_end.php

?>