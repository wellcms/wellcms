<?php

// hook model_forum_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function forum__create($arr)
{
    // hook model_forum__create_start.php
    $r = db_create('forum', $arr);
    // hook model_forum__create_end.php
    return $r;
}

function forum__update($fid, $arr)
{
    // hook model_forum__update_start.php
    $r = db_update('forum', array('fid' => $fid), $arr);
    // hook model_forum__update_end.php
    return $r;
}

function forum__read($fid)
{
    // hook model_forum__read_start.php
    $forum = db_find_one('forum', array('fid' => $fid));
    // hook model_forum__read_end.php
    return $forum;
}

function forum__delete($fid)
{
    // hook model_forum__delete_start.php
    $r = db_delete('forum', array('fid' => $fid));
    // hook model_forum__delete_end.php
    return $r;
}

function forum__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000)
{
    // hook model_forum__find_start.php
    $forumlist = db_find('forum', $cond, $orderby, $page, $pagesize, 'fid');
    // hook model_forum__find_end.php
    return $forumlist;
}

function forum_big_insert($arr = array(), $d = NULL)
{
    // hook model_forum_big_insert_start.php
    $r = db_big_insert('forum', $arr, $d);
    // hook model_forum_big_insert_end.php
    return $r;
}

function forum_big_update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_forum_big_update_start.php
    $r = db_big_update('forum', $cond, $update, $d);
    // hook model_forum_big_update_end.php
    return $r;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function forum_create($arr)
{
    // hook model_forum_create_start.php
    $r = forum__create($arr);
    forum_list_cache_delete();
    // hook model_forum_create_end.php
    return $r;
}

function forum_update($fid, $arr)
{
    // hook model_forum_update_start.php
    $r = forum__update($fid, $arr);
    forum_list_cache_delete();
    // hook model_forum_update_end.php
    return $r;
}

function forum_read($fid)
{
    global $conf, $forumlist;
    // hook model_forum_read_start.php
    if ($conf['cache']['enable']) {
        return empty($forumlist[$fid]) ? array() : $forumlist[$fid];
    } else {
        $forum = forum__read($fid);
        $forum and forum_format($forum);
        return $forum;
    }
    // hook model_forum_read_end.php
}

// 关联数据删除 把板块下所有的内容都查找出来，此处数据量大可能会超时，所以不要删除帖子特别多的板块
function forum_delete($fid)
{
    global $forumlist;

    if (empty($fid)) return FALSE;

    $forum = $forumlist[$fid];
    if (empty($forum)) return FALSE;

    // hook model_forum_delete_start.php

    $cond = array('fid' => $fid);
    // 分类 0论坛 1cms
    if (1 == $forum['type']) {
        $pagesize = 5000;
        if ($forum['threads'] > 5000) {
            $totalpage = ceil($forum['threads'] / $pagesize);
        } else {
            $totalpage = 1;
        }

        for ($i = 1; $i <= $totalpage; ++$i) {
            $threadlist = thread_tid__find($cond, array(), $i, $pagesize, 'tid', array('tid'));
            if ($threadlist) {
                $tids = array();
                foreach ($threadlist as $thread) $tids[] = $thread['tid'];
                !empty($tids) and well_thread_delete_all($tids);
            }
        }
    }

    // hook model_forum_delete_before.php

    $forum['fup'] and forum_update($forum['fup'], array('son-' => 1));

    $r = forum__delete($fid);

    forum_access_delete_by_fid($fid);

    forum_list_cache_delete();
    // hook model_forum_delete_end.php
    return $r;
}

// 未格式化
function forum_find($cond = array(), $orderby = array('rank' => -1), $page = 1, $pagesize = 1000)
{
    static $cache = array();
    $key = md5(xn_json_encode($cond));
    if (isset($cache[$key])) return $cache[$key];
    $cache[$key] = forum__find($cond, $orderby, $page, $pagesize);
    return $cache[$key];
}

// 统一调用格式化的数据
function forum_find_fmt($cond = array(), $orderby = array('rank' => -1), $page = 1, $pagesize = 1000)
{
    // hook model_forum_find_fmt_start.php
    $forumlist = forum_find($cond, $orderby, $page, $pagesize);
    if ($forumlist) {
        foreach ($forumlist as $key => &$forum) {
            forum_format($forum);
            // hook model_forum_find_fmt_format.php
        }
    }
    // hook model_forum_find_fmt_end.php
    return $forumlist;
}

// ------------> 其他方法

function forum_format(&$forum)
{
    global $conf;

    if (empty($forum)) return;

    // hook model_forum_format_start.php
    $forum['create_date_fmt'] = date('Y-n-j', $forum['create_date']);
    $forum['icon_url'] = $forum['icon'] ? file_path() . 'forum/' . $forum['fid'] . '.png' : view_path() . 'img/forum.png';
    $forum['accesslist'] = $forum['accesson'] ? forum_access_find_by_fid($forum['fid']) : array();

    $forum['modlist'] = array();
    if ($forum['moduids']) {
        $modlist = user_find_by_uids($forum['moduids']);
        foreach ($modlist as &$mod) $mod = user_safe_info($mod);
        $forum['modlist'] = $modlist;
    }

    // hook model_forum_format_before.php

    // type = 0BBS 1CMS
    if ($forum['type']) {
        // CMS需要格式化的
        if ($forum['flagstr']) {
            $flaglist = flag_forum_show($forum['fid']);
            if ($flaglist) {
                foreach ($flaglist as $key => $val) {
                    unset($val['fid'], $val['rank'], $val['count'], $val['number'], $val['display'], $val['create_date'], $val['create_date_fmt'], $val['display_fmt'], $val['forum_name'], $val['title'], $val['keywords'], $val['description'], $val['forum_url'], $val['i'], $val['tpl']);
                }
                $forum['flagstr_fmt'] = array_multisort_key($flaglist, 'rank', FALSE, 'flagid');
            }
        }

        $forum['thumbnail'] = $forum['thumbnail'] ? json_decode($forum['thumbnail'], true) : '';

        // hook model_forum_format_center.php

        // 可根据 model 区分 URL
        switch ($forum['model']) {
            /*case 1:
                $forum['url'] = url('category-' . $forum['fid'], '', FALSE);
                break;*/
            // hook model_forum_format_url_case.php
            default:
                switch ($forum['category']) {
                    case 1:
                        $forum['url'] = url('category-' . $forum['fid'], '', FALSE);
                        break;
                    case 2:
                        $forum['url'] = $forum['threads'] ? url('read-' . trim($forum['brief']), '', FALSE) : url('list-' . $forum['fid'], '', FALSE);
                        break;
                    case 3:
                        $forum['url'] = url('list-' . $forum['fid'], '', FALSE);
                        break;
                    // hook model_forum_format_default_url_case.php
                    default:
                        $forum['url'] = url('list-' . $forum['fid'], '', FALSE);
                        break;
                }
                break;
        }

        // hook model_forum_format_middle.php
    }

    // hook model_forum_format_end.php
}

function forum_count($cond = array())
{
    // hook model_forum_count_start.php
    $n = db_count('forum', $cond);
    // hook model_forum_count_end.php
    return $n;
}

function forum_maxid()
{
    // hook model_forum_maxid_start.php
    $n = db_maxid('forum', 'fid');
    // hook model_forum_maxid_end.php
    return $n;
}

// 从缓存中读取 forum_list 数据x
function forum_list_cache()
{
    global $conf, $forumlist;
    // hook model_forum_list_cache_start.php
    $key = 'forum-list';
    static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，跨进程需要再加一层缓存：redis/memcached/xcache/apc
    if (isset($cache[$key])) return $cache[$key];
    $forumlist = cache_get('forumlist');
    if (NULL === $forumlist) {
        $forumlist = forum_find_fmt();
        cache_set('forumlist', $forumlist, 7200);
    }
    $cache[$key] = $forumlist ? $forumlist : NULL;
    // hook model_forum_list_cache_end.php
    return $cache[$key];
}

// 更新 forumlist 缓存
function forum_list_cache_delete()
{
    global $conf;
    static $deleted = FALSE;
    if ($deleted) return;
    // hook model_forum_list_cache_delete_start.php
    cache_delete('forumlist');
    // 删除门户首页缓存
    cache_delete('portal_index_thread');
    $deleted = TRUE;
    // hook model_forum_list_cache_delete_end.php
}

// 对 $forumlist 权限过滤，查看权限没有，则隐藏
function forum_list_access_filter($forumlist, $gid, $allow = 'allowread')
{
    global $grouplist;

    // hook model_website_forum_list_access_filter_start.php

    if (empty($forumlist)) return array();

    // hook model_forum_list_access_filter_before.php

    if (1 == $gid) return $forumlist;

    $forumlist_filter = $forumlist;
    $group = $grouplist[$gid];

    // hook model_forum_list_access_filter_start.php

    foreach ($forumlist_filter as $fid => $forum) {

        // hook model_forum_list_access_filter_foreach_start.php

        if (empty($forum['accesson']) && empty($group[$allow]) || !empty($forum['accesson']) && empty($forum['accesslist'][$gid][$allow])) {

            // hook model_forum_list_access_filter_foreach_before.php

            unset($forumlist_filter[$fid]);
            
            // hook model_forum_list_access_filter_foreach_after.php
        }
        unset($forumlist_filter[$fid]['accesslist']);
        // hook model_forum_list_access_filter_foreach_end.php
    }
    // hook model_forum_list_access_filter_end.php
    return $forumlist_filter;
}

function forum_filter_moduid($moduids)
{
    $moduids = trim($moduids);
    if (empty($moduids)) return '';
    $arr = explode(',', $moduids);
    $r = array();
    foreach ($arr as $_uid) {
        $_uid = intval($_uid);
        $_user = user_read($_uid);
        if (empty($_user)) continue;
        if ($_user['gid'] > 4) continue;
        $r[] = $_uid;
    }
    return implode(',', $r);
}

function forum_safe_info($forum)
{
    // hook model_forum_safe_info_start.php
    //unset($forum['moduids']);
    // hook model_forum_safe_info_end.php
    return $forum;
}

function forum_filter($forumlist)
{
    // hook model_forum_filter_start.php
    foreach ($forumlist as &$val) {
        unset($val['brief'], $val['moduids'], $val['announcement'], $val['threads'], $val['tops'], $val['seo_title'], $val['seo_keywords'], $val['create_date_fmt'], $val['accesslist'], $val['icon_url'], $val['modlist'], $val['create_date_fmt']);
        // hook model_forum_filter_after.php
    }
    // hook model_forum_filter_end.php
    return $forumlist;
}

function forum_format_url($forum)
{
    global $conf;
    // hook model_forum_format_url_start.php
    if (0 == $forum['category']) {
        // 列表URL
        // hook model_forum_format_url_list_before.php
        $url = url('list-' . $forum['fid'], '', FALSE);
        // hook model_forum_format_url_list_after.php
    } elseif (1 == $forum['category']) {
        // 频道
        // hook model_forum_format_url_category_before.php
        $url = url('category-' . $forum['fid'], '', FALSE);
        // hook model_forum_format_url_category_after.php
    } elseif (2 == $forum['category']) {
        // 单页
        // hook model_forum_format_url_read_before.php
        $url = url('read-' . trim($forum['brief']), '', FALSE);
        // hook model_forum_format_url_read_after.php
    }
    // hook model_forum_format_url_end.php
    return $url;
}

// hook model_forum_end.php

?>