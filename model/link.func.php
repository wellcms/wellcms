<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */
// hook model_link_start.php

// ------------> 原生CURD，无关联其他数据。
function link__create($arr = array(), $d = NULL)
{
    // hook model_link__create_start.php
    $r = db_replace('website_link', $arr, $d);
    // hook model_link__create_end.php
    return $r;
}

function link__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_link__update_start.php
    $r = db_update('website_link', $cond, $update, $d);
    // hook model_link__update_end.php
    return $r;
}

function link__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'id', $col = array(), $d = NULL)
{
    // hook model_link__find_start.php
    $arr = db_find('website_link', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_link__find_end.php
    return $arr;
}

function link__delete($cond = array(), $d = NULL)
{
    // hook model_link__delete_start.php
    $r = db_delete('website_link', $cond, $d);
    // hook model_link__delete_end.php
    return $r;
}

function link_count($cond = array(), $d = NULL)
{
    // hook model_link__count_start.php
    $n = db_count('website_link', $cond, $d);
    // hook model_link__count_end.php
    return $n;
}

//--------------------------强相关--------------------------
/**
 * @param $arr  创建数据的数组
 * @return bool 返回FALSE失败 返回id成功
 */
function link_create($arr)
{
    if (empty($arr)) return FALSE;
    // hook model_link_create_start.php
    $r = link__create($arr);
    link_delete_cache();
    // hook model_link_create_end.php
    return $r;
}

/**
 * @param $id 主键ID
 * @param $update 更新数组
 * @return bool 返回FALSE失败 TRUE成功
 */
function link_update($id, $update)
{
    if (empty($id) || empty($update)) return FALSE;
    // hook model_link_update_start.php
    $r = link__update(array('id' => $id), $update);
    link_delete_cache();
    // hook model_link_update_end.php
    return $r;
}

/**
 * @param int $page 页数
 * @param int $pagesize 每页显示数量
 * @return mixed
 */
function link_find($page = 1, $pagesize = 100)
{
    // hook model_link_find_by_uid_start.php
    $arr = link__find($cond = array(), array('rank' => -1), $page, $pagesize);
    // hook model_link_find_by_uid_end.php
    return $arr;
}

/**
 * @param $id
 * @return bool 返回FALSE失败 TRUE成功
 */
function link_delete($id)
{
    if (empty($id)) return FALSE;
    // hook model_link_delete_start.php
    $r = link__delete(array('id' => $id));
    link_delete_cache();
    // hook model_link_delete_end.php
    return $r;
}

//--------------------------kv + cache--------------------------
/**
 * @return mixed 返回全部友情链接
 */
function link_get($page = 1, $pagesize = 100)
{
    $g_link = website_get('friends_link');
    if (empty($g_link)) {
        $g_link = link_find($page, $pagesize);
        $g_link AND website_set('friends_link', $g_link);
    }
    return $g_link;
}

// delete kv and cache
function link_delete_cache()
{
    $g_link = link_find(1, 100);
    $g_link AND website_set('friends_link', $g_link);
    return TRUE;
}

// hook model_link_end.php

?>