<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model_flag_start.php

// ------------> 最原生的 CURD，无关联其他数据。
function flag__create($arr = array(), $d = NULL)
{
    // hook model_flag__create_start.php
    $r = db_insert('website_flag', $arr, $d);
    // hook model_flag__create_end.php
    return $r;
}

function flag__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_flag__update_start.php
    $r = db_update('website_flag', $cond, $update, $d);
    // hook model_flag__update_end.php
    return $r;
}

function flag__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_flag__read_start.php
    $r = db_find_one('website_flag', $cond, $orderby, $col, $d);
    // hook model_flag__read_end.php
    return $r;
}

function flag__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'flagid', $col = array(), $d = NULL)
{
    // hook model_flag__find_start.php
    $arr = db_find('website_flag', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_flag__find_end.php
    return $arr;
}

function flag__delete($flagid, $d = NULL)
{
    // hook model_flag__delete_start.php
    $r = db_delete('website_flag', array('flagid' => $flagid), $d);
    // hook model_flag__delete_end.php
    return $r;
}

function flag__count($cond = array(), $d = NULL)
{
    // hook model_flag_count_start.php
    $n = db_count('website_flag', $cond, $d);
    // hook model_flag_count_end.php
    return $n;
}

//--------------------------强相关--------------------------
function flag_create($arr)
{
    global $conf;
    if (empty($arr)) return FALSE;

    // hook model_flag_create_start.php
    $r = flag__create($arr);
    if ($r === FALSE) return FALSE;

    flag_delete_cache($arr['fid']);

    // hook model_flag_create_end.php
    return $r;
}

function flag_update($flagid, $update)
{
    global $conf;
    if (empty($flagid) || empty($update)) return FALSE;

    // hook model_flag__update_start.php

    $r = flag__update(array('flagid' => $flagid), $update);

    if (is_array($flagid)) {
        $arrlist = flag_find_by_flagid($flagid, 1, count($flagid));
        foreach ($arrlist as $val) {
            flag_delete_cache($val['fid']);
            cache_delete('flag_' . $val['flagid']);
        }
    } else {
        $read = flag_read_cache($flagid);
        flag_delete_cache($read['fid']);
        cache_delete('flag_' . $flagid);
    }

    // hook model_flag__update_end.php
    return $r;
}

// 主键读取flag
function flag_read($flagid)
{
    // hook model_flag_read_start.php
    $r = flag__read(array('flagid' => $flagid));
    $r AND flag_format($r);
    // hook model_flag_read_end.php
    return $r;
}

// 栏目所有属性
function flag_find($fid, $page = 1, $pagesize = 20)
{
    // hook model_flag_find_start.php
    $arrlist = flag__find(array('fid' => $fid), array('flagid' => -1), $page, $pagesize, 'flagid');

    if (empty($arrlist)) return NULL;

    // hook model_flag_find_before.php

    $i = 0;
    foreach ($arrlist as &$val) {
        ++$i;
        $val['i'] = $i;
        flag_format($val);
        // hook model_flag_find_after.php
    }

    // hook model_flag_find_end.php

    return $arrlist;
}

// 非主键 forum表有统计记录
function flag_count($fid)
{
    // hook model_flag_count_start.php
    $n = flag__count(array('fid' => $fid));
    // hook model_flag_count_end.php
    return $n;
}

// 删除数据同时清理缓存
function flag_delete($flagid)
{
    if (empty($flagid)) return FALSE;
    // hook model_flag_delete_start.php
    $read = flag_read($flagid);
    if (empty($read)) return FALSE;
    // hook model_flag_delete_before.php
    $r = flag__delete($flagid);
    if ($r === FALSE) return FALSE;
    // hook model_flag_delete_after.php
    flag_delete_cache_by_flagid($read['fid'], $flagid);
    // hook model_flag_delete_end.php
    return $r;
}

// 主键批量查询属性信息
function flag_find_by_flagid($flagids, $page, $pagesize)
{
    // hook model_flag_find_by_flagid_start.php

    $arrlist = flag__find(array('flagid' => $flagids), array('flagid' => -1), $page, $pagesize, 'flagid');
    if (empty($arrlist)) return NULL;

    // hook model_flag_find_by_flagid_before.php

    $i = 0;
    foreach ($arrlist as &$val) {
        ++$i;
        $val['i'] = $i;
        flag_format($val);
        // hook model_flag_find_by_flagid_after.php
    }

    // hook model_flag_find_by_flagid_end.php

    return $arrlist;
}

// 格式化
function flag_format(&$val)
{
    global $conf, $forumlist;

    if (empty($val)) return;
    empty($forumlist) AND $forumlist = forum_find();

    // hook model_flag_format_start.php

    $forum = array_value($forumlist, $val['fid']);

    $val['forum_name'] = $forum ? $forum['name'] : lang('index_page');
    $val['display_text'] = $val['display'] ? lang('yes') : lang('no');
    $val['forum_url'] = $forum ? forum_format_url($forum) : $conf['path'];
    $val['url'] = url('flag-' . $val['flagid']);
    $val['create_date_text'] = date('Y-m-d', $val['create_date']);
    // 主图只支持本地和云储存，不支持图床
    $val['icon_text'] = $val['icon'] ? file_path() . 'flag/' . $val['flagid'] . '.png?' . $val['icon'] : '';
    // hook model_flag_format_end.php
}

// 过滤数据
function flag_filter(&$arr)
{
    // hook flag_filter_start.php
    foreach ($arr as $key => &$val) {
        unset($val['fid']);
        unset($val['number']);
        unset($val['count']);
        unset($val['display']);
        unset($val['create_date']);
        unset($val['display_text']);
        unset($val['forum_name']);
        // hook flag_filter_center.php
    }
    // hook flag_filter_end.php
}

//--------------------------其他方法--------------------------
// 栏目下属性查询
function flag_read_by_name_and_fid($name, $fid)
{
    $arrlist = flag_forum_show($fid);
    if (empty($arrlist)) return NULL;
    foreach ($arrlist as $val) {
        if ($val['name'] == $name) {
            return $val;
        }
    }
    return FALSE;
}

//--------------------------cache--------------------------
// 主键读取flag缓存
function flag_read_cache($flagid)
{
    global $conf;
    // hook model_flag_read_cache_start.php
    $key = 'flag_' . $flagid;
    static $cache = array(); // 跨进程，需再加一层缓存： redis/memcached/xcache/apc/

    if (isset($cache[$key])) return $cache[$key];

    // hook model_flag_read_cache_before.php

    if ($conf['cache']['type'] == 'mysql') {
        $r = flag_read($flagid);
    } else {
        $r = cache_get($key);
        if ($r === NULL) {
            $r = flag_read($flagid);
            $r AND cache_set($key, $r, 300);
        }
    }

    // hook model_flag_read_cache_after.php

    $cache[$key] = $r ? $r : NULL;

    // hook model_flag_read_cache_end.php

    return $cache[$key];
}

// 获取版块展示的属性 0表示首页 已格式化
function flag_forum_show($fid = 0)
{
    $arrlist = flag_get($fid);
    if (empty($arrlist)) return NULL;

    foreach ($arrlist as $key => &$val) {
        if (empty($val['display'])) {
            unset($arrlist[$key]);
        }
    }

    return $arrlist;
}

//--------------------------kv + cache--------------------------
// 从缓存中获取版块下100个最新的flag 已格式化 $fid = 0 为首页 首页的都丢在$config['index_flags']和$config['index_flagstr']
$g_flag = FALSE;
function flag_get($fid)
{
    global $forumlist, $g_flag, $config;

    $g_flag === FALSE AND $g_flag = website_get('flag');
    if (isset($g_flag[$fid])) return $g_flag[$fid];

    empty($g_flag) AND $g_flag = array();

    if (empty($g_flag[$fid])) {
        if (empty($fid)) {
            // 首页
            $pagesize = $config['index_flags'];
        } else {
            // 版块
            $forumlist = empty($forumlist) ? forum_find() : $forumlist;
            $pagesize = $forumlist[$fid]['flags'];
        }

        if (empty($pagesize)) return NULL;

        $g_flag[$fid] = flag_find($fid, 1, $pagesize);

        $g_flag[$fid] AND flag_set($fid, $g_flag[$fid]);
    }

    return $g_flag[$fid];
}

// set kv cache
function flag_set($key, $val)
{
    global $g_flag;
    $g_flag === FALSE AND $g_flag = website_get('flag');
    empty($g_flag) AND $g_flag = array();
    $g_flag[$key] = $val;
    return website_set('flag', $g_flag);
}

// 删除版块下的flag缓存
function flag_delete_cache($fid)
{
    global $g_flag;
    $g_flag === FALSE AND $g_flag = website_get('flag');
    empty($g_flag) AND $g_flag = array();
    if (isset($g_flag[$fid])) {
        unset($g_flag[$fid]);
        website_set('flag', $g_flag);
    }
    return TRUE;
}

// 删除对应flagid的缓存
function flag_delete_cache_by_flagid($fid, $flagid)
{
    global $g_flag;
    $g_flag === FALSE AND $g_flag = website_get('flag');
    empty($g_flag) AND $g_flag = array();
    if (isset($g_flag[$fid][$flagid])) {
        unset($g_flag[$fid][$flagid]);
        website_set('flag', $g_flag);
    }
    return TRUE;
}

// hook model_flag_end.php

?>
