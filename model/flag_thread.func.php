<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model_flag_thread_start.php

function flag_thread__create($arr = array(), $d = NULL)
{
    if (empty($arr)) return FALSE;
    // hook model_flag_thread__create_start.php
    $r = db_insert('website_flag_thread', $arr, $d);
    // hook model_flag_thread__create_end.php
    return $r;
}

function flag_thread__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_flag_thread__update_start.php
    $r = db_update('website_flag_thread', $cond, $update, $d);
    // hook model_flag_thread__update_end.php
    return $r;
}

function flag_thread__read($id, $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_flag_thread__read_start.php
    $thread = db_find_one('website_flag_thread', array('id' => $id), $orderby, $col, $d);
    // hook model_flag_thread__read_end.php
    return $thread;
}

function flag_thread__delete($cond = array(), $d = NULL)
{
    // hook model_flag_thread__delete_start.php
    $r = db_delete('website_flag_thread', $cond, $d);
    // hook model_flag_thread__delete_end.php
    return $r;
}

function flag_thread__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = '')
{
    // hook model_flag_thread__find_start.php
    $arr = db_find('website_flag_thread', $cond, $orderby, $page, $pagesize, $key);
    // hook model_flag_thread__find_end.php
    return $arr;
}

function flag_thread__count($cond = array())
{
    // hook model_flag_thread__count_start.php
    $n = db_count('website_flag_thread', $cond);
    // hook model_flag_thread__count_end.php
    return $n;
}

//--------------------------强相关--------------------------
// 传入flagid数组 创建属性主题$type 1首页 2频道 3版块
function flag_create_thread($fid, $type, $tid, $flagids)
{
    global $time;

    if (empty($tid) || empty($flagids)) return FALSE;
    // hook model_flag_create_thread_start.php

    foreach ($flagids as $_flagid) {
        // hook model_flag_create_thread_before.php
        flag_update($_flagid, array('count+' => 1));
        $arr = array('flagid' => $_flagid, 'fid' => $fid, 'tid' => $tid, 'type' => $type, 'create_date' => $time);
        // hook model_flag_create_thread_center.php
        $r = flag_thread__create($arr);
        if (FALSE === $r) return FALSE;
        // hook model_flag_create_thread_after.php
        flag_thread_delete_cache($_flagid);
    }

    // hook model_flag_create_thread_end.php

    return TRUE;
}

// 通过tid更新版块，审核主题时可使用
function flag_thread_update_by_tid($tid, $update)
{
    if (empty($tid) || empty($update)) return FALSE;
    // hook model_flag_thread_update_by_tid_start.php
    $r = flag_thread__update(array('tid' => $tid), $update);
    // hook model_flag_thread_update_by_tid_end.php
    return $r;
}

// Primary key find / update or delete / Support for arrays:array(1,2,3,4,5)
function flag_thread_find_by_id($id, $page, $pagesize)
{
    // hook model_flag_thread_find_by_id_start.php
    $threadlist = flag_thread__find(array('id' => $id), array(), $page, $pagesize, 'id');
    // hook model_flag_thread_find_by_id_end.php
    return $threadlist;
}

// Query flag and ID
function flag_thread_find($tid, $page, $pagesize)
{
    // hook model_flag_thread_find_start.php
    $threadlist = flag_thread__find(array('tid' => $tid), array(), $page, $pagesize, 'id');
    // hook model_flag_thread_find_end.php
    return $threadlist;
}

// Tid under query flag
function flag_thread_find_by_flagid($flagid, $page, $pagesize, $key = 'id')
{
    // hook model_flag_thread_find_by_flagid_fid_start.php
    $threadlist = flag_thread__find(array('flagid' => $flagid), array('id' => -1), $page, $pagesize, $key);
    // hook model_flag_thread_find_by_flagid_fid_end.php
    return $threadlist;
}

// Primary key delete
function flag_thread_delete($id)
{
    if (empty($id)) return FALSE;
    // hook model_flag_thread_delete_start.php
    $r = flag_thread__delete(array('id' => $id));
    // hook model_flag_thread_delete_end.php
    return $r;
}

/*
 * @param $tids 删除的tid集合 array(1,2,3)
 * @param $n 删除总数量
 * @return int 返回删除数量
 */
function flag_thread_delete_by_tids($tids, $n)
{
    $arrlist = flag_thread__find(array('tid' => $tids), array('id' => 1), 1, $n);
    if (!$arrlist) return 0;

    $idarr = array();
    $flagarr = array();
    foreach ($arrlist as $val) {
        $idarr[] = $val['id']; // 删除
        isset($flagarr[$val['flagid']]) ? $flagarr[$val['flagid']] += 1 : $flagarr[$val['flagid']] = 1; // 更新
    }

    flag_thread_delete($idarr);

    $flagids = array();
    $update = array();
    foreach ($flagarr as $flagid => $n) {
        $flagids[] = $flagid;
        $update[$flagid] = array('count-' => $n);
    }

    flag_big_update(array('flagid' => $flagids), $update);

    return count($idarr);
}

//--------------------------其他方法--------------------------
// 获取版块下属性设置为显示的主题tid $fid = 0 为首页
function flag_thread_by_fid($fid)
{
    // hook model_flag_thread_by_fid_start.php

    $flaglist = flag_forum_show($fid);

    if (empty($flaglist)) return array(NULL, NULL);

    // hook model_flag_thread_by_fid_before.php

    // 自定义排序
    $flaglist = array_multisort_key($flaglist, 'rank', FALSE, 'flagid');

    // hook model_flag_thread_by_fid_center.php

    $flagtids = array();
    foreach ($flaglist as $key => $val) {
        if ($val['count']) {
            $flaglist[$key]['tids'] = arrlist_values(flag_thread_get($val['flagid']), 'tid');
            $flagtids += flag_thread_get($val['flagid']);
        }
        // hook model_flag_thread_by_fid_after.php
    }

    // hook model_flag_thread_by_fid_end.php

    return array($flaglist, $flagtids);
}

// 根据tid返回各版块属性flagid
function flag_forum_by_tid($tid)
{
    // hook model_flag_forum_by_tid_start.php

    $forumarr = array();
    $catearr = array();
    $indexarr = array();
    $flagarr = array();

    $thread = well_thread_read_cache($tid);
    if ($thread['flags']) {
        $arrlist = flag_thread_find($tid, 1, $thread['flags']);

        // hook model_flag_forum_by_tid_before.php

        foreach ($arrlist as &$val) {

            // hook model_flag_forum_by_tid_center.php

            1 == $val['type'] and $indexarr[] = $val['flagid'];
            2 == $val['type'] and $catearr[] = $val['flagid'];
            3 == $val['type'] and $forumarr[] = $val['flagid'];
            $flagarr[$val['flagid']] = $val['id'];
            // hook model_flag_forum_by_tid_after.php
        }
    }

    // hook model_flag_forum_by_tid_end.php

    return array($indexarr, $catearr, $forumarr, $flagarr);
}

// $oldids取消绑定flagid更新flag使用 / $ids array('flagid主键' => 'id主键')
function flag_thread_delete_by_ids($flagids, $ids)
{
    if (empty($flagids) || empty($ids)) return FALSE;

    // hook model_flag_thread_delete_by_ids_start.php

    $idarr = array();
    foreach ($flagids as $_flagid) {
        // hook model_flag_thread_delete_by_ids_before.php
        $r = flag_update($_flagid, array('count-' => 1));
        if (FALSE === $r) return FALSE;
        $idarr[] = $ids[$_flagid];
        flag_thread_delete_cache($_flagid);
        // hook model_flag_thread_delete_by_ids_after.php
    }

    $r = flag_thread_delete($idarr);
    if (FALSE === $r) return FALSE;

    // hook model_flag_thread_delete_by_ids_end.php

    return $r;
}

function flag_thread_filter(&$val)
{
    // hook flag_thread_filter_start.php
    unset($val['id'], $val['fid'], $val['flagid'], $val['type'], $val['create_date']);
    // hook flag_thread_filter_end.php
}

//--------------------------kv + cache--------------------------
// 从缓存中获取flag下tid 按照设置显示的数量获取
$g_flag_thread = FALSE;
function flag_thread_get($flagid)
{
    global $g_flag_thread;
    FALSE === $g_flag_thread and $g_flag_thread = website_get('flag_thread');
    if (isset($g_flag_thread[$flagid])) return $g_flag_thread[$flagid];

    empty($g_flag_thread) and $g_flag_thread = array();

    if (empty($g_flag_thread[$flagid])) {
        $read = flag_read_cache($flagid);
        $g_flag_thread[$flagid] = flag_thread_find_by_flagid($flagid, 1, $read['number'], 'tid');
        if (!empty($g_flag_thread[$flagid])) {
            foreach ($g_flag_thread[$flagid] as &$val) {
                flag_thread_filter($val);
            }
            flag_thread_set($flagid, $g_flag_thread[$flagid]);
        }
    }
    return $g_flag_thread[$flagid];
}

// 设置缓存 $key = flagid / $val = flagid下tid数组
function flag_thread_set($key, $val)
{
    global $g_flag_thread;
    FALSE === $g_flag_thread and $g_flag_thread = website_get('flag_thread');
    empty($g_flag_thread) and $g_flag_thread = array();
    $g_flag_thread[$key] = $val;
    return website_set('flag_thread', $g_flag_thread);
}

// 删除flag下tid缓存
function flag_thread_delete_cache($flagid)
{
    global $g_flag_thread;
    FALSE === $g_flag_thread and $g_flag_thread = website_get('flag_thread');
    empty($g_flag_thread) and $g_flag_thread = array();
    if (isset($g_flag_thread[$flagid])) {
        unset($g_flag_thread[$flagid]);
        website_set('flag_thread', $g_flag_thread);
    }
    return TRUE;
}

// Delete by tid / 通过tid删除flag下的主题和对应flagid缓存
function flag_thread_delete_by_tid($tid)
{
    global $g_flag_thread;
    FALSE === $g_flag_thread and $g_flag_thread = website_get('flag_thread');

    if (empty($g_flag_thread)) {
        $g_flag_thread = array();
    } else {

        $thread = well_thread_read_cache($tid);
        $arrlist = flag_thread_find($tid, 1, $thread['flags']);

        $flagarr = $ids = array();
        foreach ($arrlist as $val) {
            $flagarr[] = $val['flagid'];
            $ids[] = $val['id'];

            if (isset($g_flag_thread[$val['flagid']])) unset($g_flag_thread[$val['flagid']]);
        }

        website_set('flag_thread', $g_flag_thread);

        // 主键更新
        flag_update($flagarr, array('count-' => 1));

        // 主键删除
        flag_thread_delete($ids);
    }

    return TRUE;
}

// 主键删除 通过$flagid删除flag下的主题和对应flagid缓存
function flag_thread_delete_by_flagid($flagid)
{
    global $g_flag_thread;

    // hook model_flag_thread_delete_by_flagid_start.php

    FALSE === $g_flag_thread and $g_flag_thread = website_get('flag_thread');

    // hook model_flag_thread_delete_by_flagid_before.php

    $read = flag_read_cache($flagid);
    if (empty($read)) return TRUE;

    $arrlist = flag_thread_find_by_flagid($flagid, 1, $read['count']);
    if (empty($arrlist)) return TRUE;

    // hook model_flag_thread_delete_by_flagid_center.php

    $flagarr = $ids = array();
    $n = 0;
    foreach ($arrlist as $val) {
        ++$n;
        $flagarr[] = $val['flagid'];
        $ids[] = $val['id'];

        if (isset($g_flag_thread[$flagid])) unset($g_flag_thread[$flagid]);
        // hook model_flag_thread_delete_by_flagid_middle.php
    }

    website_set('flag_thread', $g_flag_thread);

    // 主键更新
    flag_update($flagarr, array('count-' => $n));

    // hook model_flag_thread_delete_by_flagid_after.php

    // 主键删除
    $r = flag_thread_delete($ids);

    // hook model_flag_thread_delete_by_flagid_end.php
    return $r;
}

// hook model_flag_thread_end.php

?>