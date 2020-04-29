<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model_page_start.php

// ------------> 原生CURD，无关联其他数据。
function page__create($arr = array(), $d = NULL)
{
    // hook model_page__create_start.php
    $r = db_replace('website_page', $arr, $d);
    // hook model_page__create_end.php
    return $r;
}

function page__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_page__update_start.php
    $r = db_update('website_page', $cond, $update, $d);
    // hook model_page__update_end.php
    return $r;
}

function page__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tid', $col = array(), $d = NULL)
{
    // hook model_page__find_start.php
    $arr = db_find('website_page', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_page__find_end.php
    return $arr;
}

function page__delete($cond = array(), $d = NULL)
{
    // hook model_page__delete_start.php
    $r = db_delete('website_page', $cond, $d);
    // hook model_page__delete_end.php
    return $r;
}

function page__count($cond = array(), $d = NULL)
{
    // hook model_page__count_start.php
    $n = db_count('website_page', $cond, $d);
    // hook model_page__count_end.php
    return $n;
}

//--------------------------强相关--------------------------
/*
 * @param $arr  创建数据的数组
 * @return bool 返回FALSE失败 返回tid成功
 */
function page_create($arr)
{
    if (empty($arr)) return FALSE;
    // hook model_page_create_start.php
    $r = page__create($arr);
    // hook model_page_create_end.php
    return $r;
}

/*
 * @param $tid  主题tid
 * @param $fid  版块fid
 * @return bool 返回FALSE失败 TRUE成功
 * 主键更新 若移动栏目 则需要更新此表fid
 */
function page_update($tid, $fid)
{
    if (empty($tid) || empty($fid)) return FALSE;
    // hook model_page_update_start.php
    $r = page__update(array('tid' => $tid), array('fid' => $fid));
    // hook model_page_update_end.php
    return $r;
}

/*
 * @param $tid  主题tid
 * @param $rank 排序
 * @return bool 返回FALSE失败 TRUE成功
 * 主题排序
 */
function page_update_rank($tid, $rank)
{
    if (empty($tid) || empty($rank)) return FALSE;
    // hook model_page_update_rank_start.php
    $r = page__update(array('tid' => $tid), array('rank' => $rank));
    // hook model_page_update_rank_end.php
    return $r;
}

/*
 * @param int $page 页数
 * @param int $pagesize 每页显示数量
 * @param bool $desc    排序TRUE倒序 FALSE升序
 * @return mixed
 * 遍历所有主题tid
 */
function page_find($page = 1, $pagesize = 20, $desc = TRUE)
{
    // hook model_page_find_by_uid_start.php
    $orderby = TRUE == $desc ? -1 : 1;
    $arrlist = page__find($cond = array(), array('tid' => $orderby), $page, $pagesize, 'tid', array('tid','rank'));
    // hook model_page_find_by_uid_end.php
    return $arrlist;
}

/*
 * @param $fid  版块fid 支持数组 $fid = array(1,2,3)
 * @param int $page 页数
 * @param int $pagesize 每页显示数量
 * @param bool $desc    排序TRUE降序 FALSE升序
 * @return mixed    返回遍历tid数据
 */
function page_find_by_fid($fid, $page = 1, $pagesize = 1000, $desc = TRUE)
{
    // hook model_page_find_by_fid_start.php
    $orderby = TRUE == $desc ? -1 : 1;
    $arrlist = page__find($cond = array('fid' => $fid), array('rank' => $orderby), $page, $pagesize, 'tid', array('tid','rank'));
    // hook model_page_find_by_fid_end.php
    return $arrlist;
}

/*
 * @param $tid  主题tid
 * @return bool 返回FALSE失败 TRUE成功
 */
function page_delete($tid)
{
    if (empty($tid)) return FALSE;
    // hook model_page_delete_start.php
    $r = page__delete(array('tid' => $tid));
    // hook model_page_delete_end.php
    return $r;
}

function page_count()
{
    // hook model_page_count_start.php
    $n = page__count();
    // hook model_page_count_end.php
    return $n;
}

/*
 * @param $fid  版块fid
 * @return mixed    返回版块下主题数量
 */
function page_fid_count($fid)
{
    // hook model_thread_fid_count_start.php
    $n = page__count(array('fid' => $fid));
    // hook model_thread_fid_count_end.php
    return $n;
}

// hook model_page_end.php

?>