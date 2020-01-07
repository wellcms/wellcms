<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */
// hook model_sticky_start.php

// ------------> 最原生的 CURD，无关联其他数据

function sticky_thread_create($arr, $d = NULL)
{
    // hook model_sticky_create_start.php
    $r = db_replace('website_thread_sticky', $arr, $d);
    cache_delete('sticky_thread_list');
    // hook model_sticky_create_end.php
    return $r;
}

function sticky_thread__update($tid, $arr, $d = NULL)
{
    // hook model_sticky__update_start.php
    $r = db_update('website_thread_sticky', array('tid' => $tid), $arr, $d);
    // hook model_sticky__update_end.php
    return $r;
}

function sticky_thread__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tid', $col = array(), $d = NULL)
{
    // hook model_sticky__find_start.php
    $threadlist = db_find('website_thread_sticky', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_sticky__find_end.php
    return $threadlist;
}

function sticky_thread__delete($tid, $d = NULL)
{
    // hook model__sticky__delete_start.php
    $r = db_delete('website_thread_sticky', array('tid' => $tid), $d);
    // hook model__sticky__delete_end.php
    return $r;
}

function sticky_thread__count($cond = array(), $d = NULL)
{
    // hook model_sticky__count_start.php
    $n = db_count('website_thread_sticky', $cond, $d);
    // hook model_sticky__count_end.php
    return $n;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理
// 更改置顶 更新主题和栏目
function sticky_thread_change($tid, $sticky, $thread)
{
    global $time;
    // hook model_sticky_change_start.php
    if (empty($thread)) return FALSE;
    well_thread_update($tid, array('sticky' => $sticky));
    $r = sticky_thread_create(array('fid' => $thread['fid'], 'tid' => $thread['tid'], 'sticky' => $sticky, 'create_date' => $time));
    // hook model_sticky_change_end.php
    return $r;
}

function sticky_thread_update_by_tid($tid, $newfid)
{
    // hook model_sticky_update_by_tid_start.php
    $r = sticky_thread__update($tid, array('fid' => $newfid));
    cache_delete('sticky_thread_list');
    // hook model_sticky_update_by_tid_end.php
    return $r;
}

function sticky_thread_delete($tid)
{
    // hook model_sticky_delete_start.php
    $thread = well_thread__read($tid);
    if (empty($thread)) return FALSE;
    if ($thread['sticky']) {
        well_thread_update($tid, array('sticky' => 0));
        cache_delete('sticky_thread_list');
    }
    $r = sticky_thread__delete($tid);
    // hook model_sticky_delete_end.php
    return $r;
}

function sticky_thread_count()
{
    // hook model_sticky_count_start.php
    $n = sticky_thread__count();
    // hook model_sticky_count_end.php
    return $n;
}

function sticky_thread_count_by_sticky($sticky)
{
    // hook model_sticky_count_by_sticky_start.php
    $n = sticky_thread__count(array('sticky' => $sticky));
    // hook model_sticky_count_by_sticky_end.php
    return $n;
}

function sticky_thread_count_by_fid($fid)
{
    // hook model_sticky_count_by_fid_start.php
    $n = sticky_thread__count(array('fid' => $fid));
    // hook model_sticky_count_by_fid_end.php
    return $n;
}

// 全局置顶主题tid
function sticky_index_thread()
{
    // hook model_index_sticky_thread_start.php
    $arrlist = sticky_thread_find_cache();
    if (empty($arrlist)) return NULL;
    // hook model_index_sticky_thread_before.php
    $arr = array();
    foreach ($arrlist as $val) {
        if ($val['sticky'] == 3) {
            $arr[$val['tid']] = $val;
        }
    }
    // 按照置顶时间排序
    $arr = array_multisort_key($arr, 'create_date', FALSE, 'tid');
    // hook model_index_sticky_thread_end.php
    return $arr;
}

// 频道 列表置顶主题tid(包含首页和频道)
function sticky_list_thread($fid)
{
    global $forumlist_show;

    // hook model_index_sticky_thread_start.php

    $forum = isset($forumlist_show[$fid]) ? $forumlist_show[$fid] : NULL;
    if (empty($forum)) return NULL;

    // hook model_index_sticky_thread_before.php

    $arrlist = sticky_thread_find_cache();
    if (empty($arrlist)) return NULL;

    // hook model_index_sticky_thread_center.php

    // 区分频道和频道下栏目
    $fids = array();
    foreach ($forumlist_show as $val) {
        if ($val['type'] && $val['display']) {
            if ($forum['category'] == 1) {
                if ($val['fup'] == $fid) {
                    $fids[] = $val['fid'];
                }
            } else {
                // 频道下子栏目
                if ($val['fup'] == $forum['fup']) {
                    $fids[] = $val['fid'];
                }
            }
        }
    }

    // 区分栏目和频道 栏目需要查询本身和上级频道置顶，频道只需查询本身
    $sticky1 = array();
    $sticky2 = array();
    $sticky3 = array();
    if ($forum['category'] == 1) {
        // 频道和全局置顶主题
        foreach ($arrlist as $val) {
            if (in_array($val['fid'], $fids) && $val['sticky'] == 2) {
                $sticky2[$val['tid']] = $val;
            } elseif ($val['sticky'] == 3) {
                $sticky3[$val['tid']] = $val;
            }
        }
    } else {
        // 栏目/上级频道/全局置顶
        foreach ($arrlist as $val) {
            if ($forum['fid'] == $val['fid'] && $val['sticky'] == 1) {
                $sticky1[$val['tid']] = $val;
            } elseif (in_array($val['fid'], $fids) && $val['sticky'] == 2) {
                $sticky2[$val['tid']] = $val;
            } elseif ($val['sticky'] == 3) {
                $sticky3[$val['tid']] = $val;
            }
        }
    }

    $sticky3 = empty($sticky3) ? array() : array_multisort_key($sticky3, 'create_date', FALSE, 'tid');
    $sticky2 = empty($sticky2) ? array() : array_multisort_key($sticky2, 'create_date', FALSE, 'tid');
    $sticky1 = empty($sticky1) ? array() : array_multisort_key($sticky1, 'create_date', FALSE, 'tid');

    $arr = $sticky3 + $sticky2 + $sticky1;

    // hook model_index_sticky_thread_end.php

    return $arr;
}

// 全部置顶缓存
function sticky_thread_find_cache()
{
    global $conf;

    // hook model_sticky_thread_find_cache_start.php

    $key = 'sticky_thread_list';
    static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，跨进程需要再加一层缓存：redis/memcached/xcache/apc
    if (isset($cache[$key])) return $cache[$key];

    // hook model_sticky_thread_find_cache_before.php

    if ($conf['cache']['type'] == 'mysql') {
        $arr = sticky_thread__find(array(), array('tid' => -1), 1, 5000);
    } else {
        $arr = cache_get($key);
        if ($arr === NULL) {
            $arr = sticky_thread__find(array(), array('tid' => -1), 1, 5000);
            $arr AND cache_set($key, $arr, 1800);
        }
    }

    // hook model_sticky_thread_find_cache_after.php

    $cache[$key] = $arr ? $arr : NULL;

    // hook model_sticky_thread_find_cache_end.php

    return $cache[$key];
}

// hook model_sticky_end.php

?>