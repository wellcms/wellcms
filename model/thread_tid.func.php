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
    if (empty($tid) || empty($fid)) return FALSE;
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

//---------------- 其他方法 kv + cache ----------------

/*
// 热门不能看浏览数，以下方法会增加额外的负担，如果非要使用，可以剥离view字段 array_slice($arr, 0, 19); 取数组中前20个
// 获取热门主题tid array(1,2,3)
$g_hot = FALSE;
function thread_hot_get_tid($n = 20)
{
    global $config, $g_hot;

    if (0 == $config['setting']['hot_on']) return NULL;

    FALSE === $g_hot AND $g_hot = thread_hot_get();
    if (empty($g_hot)) return NULL;

    //$tids = array();
    //$i = 0;
    //foreach ($g_hot as $tid => $view) {
    //    ++$i;
    //    if ($i <= $n) $tids[$tid] = $tid; // 需要按照原tid顺序排序
    //}

    $keys = array_keys($g_hot);
    $tids = array_slice($keys, 0, $n-1); // array(1,2,3)

    return $tids;
}

// 热门主题 保存200个tid 按照浏览次数降序 array(tid => view)
function thread_hot_get()
{
    global $config, $g_hot;

    if (0 == $config['setting']['hot_on']) return NULL;

    FALSE === $g_hot AND $g_hot = website_get('hot');

    if (empty($g_hot)) {
        $g_hot = thread_hot_pull();
        website_set('hot', $g_hot);
    }

    return $g_hot;
}

// 详情页添加该函数
function thread_hot_set($thread)
{
    global $conf, $config, $g_hot;

    $hot_view = array_value($conf, 'hot_view', 10);
    if (0 == $config['setting']['hot_on'] || $thread['views'] < $hot_view) return FALSE;

    FALSE === $g_hot AND $g_hot = thread_hot_get();

    if (empty($g_hot)) {
        $g_hot = array($thread['tid'] => $thread['views']);
    } else {
        if (count($g_hot) < array_value($conf, 'hot_n', 1000)) {
            $g_hot[$thread['tid']] = $thread['views'];
        } else {
            if (isset($g_hot[$thread['tid']])) {
                $g_hot[$thread['tid']] = $thread['views'];
            } else {
                // 获取最后一个值比对
                $views = end($g_hot);
                if ($thread['views'] < $views) return FALSE;
                array_pop($g_hot); // 尾出栈
                $g_hot[$thread['tid']] = $thread['views'];
            }
        }
    }
    arsort($g_hot); // 键值降序
    return website_set('hot', $g_hot);
}

function thread_hot_pull($n = 0)
{
    global $conf;

    $threadlist = well_thread__find(array(), array('tid' => -1), 1, 5000, '', array('tid', 'views'));
    if (empty($threadlist)) return NULL;

    $threadlist = arrlist_multisort($threadlist, 'views', FALSE);

    $g_hot = array();
    $i = 0;
    $hot_n = $n ? $n : array_value($conf, 'hot_n', 200);
    $hot_view = array_value($conf, 'hot_view', 10);
    foreach ($threadlist as $_thread) {
        ++$i;
        if ($i <= $hot_n && $_thread['views'] >= $hot_view) $g_hot[$_thread['tid']] = $_thread['views'];
    }

    return $g_hot;
}

function thread_hot_delete($tid)
{
    global $config, $g_hot;

    if (0 == $config['setting']['hot_on']) return NULL;

    FALSE === $g_hot AND $g_hot = website_get('hot');
    if (empty($g_hot)) return NULL;
    unset($g_hot[$tid]);

    $n = count($g_hot);
    // 低于100则拉取补充到200
    $n < 100 AND $g_hot += thread_hot_pull(200 - $n);

    website_set('hot', $g_hot);

    return TRUE;
}*/

// hook model_thread_tid_end.php

?>