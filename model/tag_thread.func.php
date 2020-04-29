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

function well_tag_thread_delete($tagid, $tid, $d = NULL)
{
    if (empty($tagid) || empty($tid)) return FALSE;
    // hook model_tag_thread_delete_start.php
    $r = db_delete('website_tag_thread', array('tagid' => $tagid, 'tid' => $tid), $d);
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

//--------------------------强相关--------------------------
function well_tag_thread_update($tagid, $tid, $update)
{
    if (empty($tagid) || empty($tid)) return FALSE;
    // hook model_tag_thread__update_start.php
    $r = well_tag_thread__update(array('tagid' => $tagid, 'tid' => $tid), $update);
    // hook model_tag_thread__update_end.php
    return $r;
}

function well_tag_thread_find($tagid, $page, $pagesize)
{
    // hook model_tag_thread_find_start.php
    $arr = well_tag_thread__find(array('tagid' => $tagid), array('tid' => -1), $page, $pagesize);
    // hook model_tag_thread_find_end.php
    return $arr;
}

function well_tag_thread_safe_info($arr)
{
    // hook model_tag_thread_safe_info_start.php

    // hook model_tag_thread_safe_info_end.php
    return $arr;
}

// hook model_tag_thread_end.php

?>
