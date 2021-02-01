<?php
/*
 * Copyright (C) www.wellcms.cn
*/

// hook model_tag_thread_start.php

// ------------> 最原生的 CURD，无关联其他数据。
function well_tag_thread_create($arr, $d = NULL)
{
    if (empty($arr)) return FALSE;
    // hook model_tag_thread_create_start.php
    $r = db_replace('website_tag_thread', $arr, $d);
    // hook model_tag_thread_create_end.php
    return $r;
}

function well_tag_thread__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_tag_thread__update_start.php
    $r = db_update('website_tag_thread', $cond, $update, $d);
    // hook model_tag_thread__update_end.php
    return $r;
}

function well_tag_thread_delete($id, $d = NULL)
{
    if (empty($id)) return FALSE;
    // hook model_tag_thread_delete_start.php
    $r = db_delete('website_tag_thread', array('id' => $id), $d);
    if (FALSE === $r) return FALSE;
    // hook model_tag_thread_delete_end.php
    return $r;
}

function well_tag_thread__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tid', $col = array(), $d = NULL)
{
    // hook model_tag_thread__find_start.php
    $arr = db_find('website_tag_thread', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_tag_thread__find_end.php
    return $arr;
}

function well_tag_thread__count($cond = array(), $d = NULL)
{
    // hook model_tag_thread__count_start.php
    $n = db_count('website_tag_thread', $cond, $d);
    // hook model_tag_thread__count_end.php
    return $n;
}

function tag_thread_max_id($col = 'id', $cond = array(), $d = NULL)
{
    // hook model_tag_thread_max_id_start.php
    $id = db_maxid('website_tag_thread', $col, $cond, $d);
    // hook model_tag_thread_max_id_end.php
    return $id;
}

function tag_thread_big_insert($arr = array(), $d = NULL)
{
    // hook model_tag_thread_big_insert_start.php
    $r = db_big_insert('website_tag_thread', $arr, $d);
    // hook model_tag_thread_big_insert_end.php
    return $r;
}

function tag_thread_big_update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_tag_thread_big_update_start.php
    $r = db_big_update('website_tag_thread', $cond, $update, $d);
    // hook model_tag_thread_big_update_end.php
    return $r;
}
//--------------------------强相关--------------------------

function well_tag_thread_update($id, $update)
{
    if (empty($id)) return FALSE;
    // hook model_tag_thread__update_start.php
    $r = well_tag_thread__update(array('id' => $id), $update);
    // hook model_tag_thread__update_end.php
    return $r;
}

function well_tag_thread_find($tagid, $page, $pagesize)
{
    // hook model_tag_thread_find_start.php
    $arr = well_tag_thread__find(array('tagid' => $tagid), array('id' => -1), $page, $pagesize);
    // hook model_tag_thread_find_end.php
    return $arr;
}

function well_tag_thread_find_by_tid($tid, $page, $pagesize)
{
    // hook model_tag_thread_find_by_tid_start.php
    $arr = well_tag_thread__find(array('tid' => $tid), array(), $page, $pagesize);
    // hook model_tag_thread_find_by_tid_end.php
    return $arr;
}

// hook model_tag_thread_end.php

?>