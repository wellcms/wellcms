<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */

// hook model_misc_website_start.php

/*array(
    //list栏目已经rank排序
    'list' => array(
        'fid' => fid,
        'name' => name,
        'rank' => rank,
        'type' => type,
        'url' => url,
        'index_new' => index_new,
        'news' => 栏目下主题二数组,
    ),
    'flag' => array(
        'tid' => thread
    ),
    'sticky' => array(
        'tid' => thread
    )
);*/
function portal_index_thread_cache($forumlist)
{
    // hook model_portal_index_thread_cache_start.php
    $key = 'portal_index_thread';
    static $cache = array(); // 跨进程，需再加一层缓存： redis/memcached/xcache/apc/
    if (isset($cache[$key])) return $cache[$key];
    // hook model_portal_index_thread_cache_before.php
    $arr = cache_get($key);
    if ($arr === NULL) {
        $arr = portal_index_thread($forumlist);
        empty($arr) || cache_set($key, $arr);
    }
    // hook model_portal_index_thread_cache_after.php
    $cache[$key] = empty($arr) ? NULL : $arr;
    // hook model_portal_index_thread_cache_end.php
    return $cache[$key];
}

// 门户 获取需要在首页显示的栏目主题数据
function portal_index_thread($forumlist)
{
    if (empty($forumlist)) return NULL;

    // hook model_portal_index_thread_start.php

    $orderby = array('tid' => -1);
    $page = 1;

    // hook model_portal_index_thread_before.php

    // 遍历所有在首页显示内容的栏目
    //$index_forumlist = arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => 0, 'display' => 1), array('fid' => -1), 1, 1000);
    $index_forumlist = category_list($forumlist, 1);

    $arrlist = array();
    $forum_tids = array();
    $tidlist = array();

    // hook model_portal_index_thread_forumlist_start.php

    if ($index_forumlist) {
        foreach ($index_forumlist as &$_forum) {

            // hook model_portal_index_thread_forumlist_before.php

            // 首页显示数据
            $arrlist['list'][$_forum['fid']] = array(
                'fid' => $_forum['fid'],
                'name' => $_forum['name'],
                'rank' => $_forum['rank'],
                'type' => $_forum['type'],
                'url' => $_forum['url'],
                'index_new' => $_forum['index_new'],
                // hook model_portal_index_thread_forum_foreach.php
            );

            // hook model_portal_index_thread_forumlist_after.php

            $forum_thread = thread_tid__find(array('fid' => $_forum['fid']), $orderby, $page, $_forum['index_new'], 'tid', array('tid'));
            // 最新信息按栏目分组
            foreach ($forum_thread as $key => $_thread) {
                $forum_tids[$key] = $_thread;
            }

            // hook model_portal_index_thread_forumlist_after.php

            unset($forum_thread);
        }
        $tidlist += $forum_tids;
    }
    unset($index_forumlist);

    // hook model_portal_index_thread_forumlist_end.php

    unset($forumlist);

    // hook model_portal_index_thread_center.php

    // 获取属性对应的tid集合
    list($flaglist, $flagtids) = flag_thread_by_fid(0);
    empty($flagtids) || $tidlist += $forum_tids;

    // hook model_portal_index_thread_flag_after.php

    unset($flagtids);

    // hook model_portal_index_thread_sticky_before.php

    // 全局置顶
    $stickylist = sticky_index_thread();
    empty($stickylist) || $tidlist += $stickylist;

    // hook model_portal_index_thread_sticky_after.php

    // 在这之前合并所有二维数组 tid值为键/array('tid值' => tid值)
    $tidarr = arrlist_values($tidlist, 'tid');

    // 在这之前使用$tidarr = array_merge($tidarr, $arr)前合并所有一维数组 tid/array(1,2,3)
    // hook model_portal_index_thread_merge_after.php

    if (empty($tidarr)) {
        $arrlist['list'] = isset($arrlist['list']) ? array_multisort_key($arrlist['list'], 'rank', FALSE, 'fid') : array();
        return $arrlist;
    }

    // hook model_portal_index_thread_unique_before.php

    $tidarr = array_unique($tidarr);

    // hook model_portal_index_thread_unique_after.php

    $pagesize = count($tidarr);

    // hook model_portal_index_thread_find_before.php

    // 遍历获取的所有tid主题
    $threadlist = well_thread_find_asc($tidarr, $pagesize);

    // hook model_portal_index_thread_find_after.php

    // 遍历时为升序，翻转为降序
    $threadlist = array_reverse($threadlist);

    // hook model_portal_index_thread_reverse_after.php

    foreach ($threadlist as &$_thread) {

        // hook model_portal_index_thread_cate_before.php

        // 各栏目最新内容
        isset($forum_tids[$_thread['tid']]) AND $arrlist['list'][$_thread['fid']]['news'][$_thread['tid']] = $_thread;

        // 全站置顶内容
        isset($stickylist[$_thread['tid']]) AND $arrlist['sticky'][$_thread['tid']] = $_thread;

        // 首页属性主题
        if (!empty($flaglist)) {
            foreach ($flaglist as $key => $val) {

                if (in_array($_thread['tid'], $val['tids'])) {

                    $arrlist['flaglist'][$key][array_search($_thread['tid'], $val['tids'])] = $_thread;
                    ksort($arrlist['flaglist'][$key]);

                    $arrlist['flag'][$_thread['tid']] = $_thread;
                }
            }
        }

        // hook model_portal_index_thread_cate_after.php
    }

    unset($threadlist);

    if (isset($arrlist['sticky'])) {
        $i = 0;
        foreach ($arrlist['sticky'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }

    if (isset($arrlist['flag'])) {
        $i = 0;
        foreach ($arrlist['flag'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }

    if (isset($arrlist['flaglist'])) {
        foreach ($arrlist['flaglist'] as &$val) {
            $i = 0;
            foreach ($val as &$v) {
                ++$i;
                $v['i'] = $i;
            }
        }
    }

    // hook model_portal_index_thread_after.php

    isset($arrlist['list']) AND $arrlist['list'] = array_multisort_key($arrlist['list'], 'rank', FALSE, 'fid');

    // hook model_portal_index_thread_end.php

    return $arrlist;
}

//-------------------category--------------------
// 频道页缓存5分钟更新
function portal_channel_thread_cache($fid)
{
    // hook model_portal_channel_thread_cache_start.php
    $key = 'portal_channel_thread_' . $fid;
    static $cache = array(); // 跨进程，需再加一层缓存： redis/memcached/xcache/apc/
    if (isset($cache[$key])) return $cache[$key];
    // hook model_portal_channel_thread_cache_before.php
    $arr = cache_get($key);
    if ($arr === NULL) {
        $arr = portal_channel_thread($fid);
        empty($arr) || cache_set($key, $arr, 300);
    }
    // hook model_portal_index_thread_cache_after.php
    $cache[$key] = empty($arr) ? NULL : $arr;
    // hook model_portal_channel_thread_cache_end.php
    return $cache[$key];
}

// 门户 获取需要在频道显示的栏目主题数据
function portal_channel_thread($fid)
{
    global $forumlist;
    if (empty($fid)) return NULL;

    // hook model_portal_channel_thread_start.php

    $orderby = array('tid' => 1);
    $page = 1;

    // hook model_portal_channel_thread_before.php

    // 遍历所有在频道显示内容的栏目
    $category_forumlist = channel_category($fid);

    $arrlist = array();
    $forum_tids = array();
    $tidlist = array();

    // hook model_portal_channel_thread_forumlist_start.php

    if ($category_forumlist) {
        foreach ($category_forumlist as &$_forum) {

            // hook model_portal_channel_thread_forumlist_before.php

            // 频道显示数据
            $arrlist['list'][$_forum['fid']] = array(
                'fid' => $_forum['fid'],
                'name' => $_forum['name'],
                'rank' => $_forum['rank'],
                'type' => $_forum['type'],
                'url' => $_forum['url'],
                'index_new' => $_forum['index_new'],
                // hook model_portal_channel_thread_forum_foreach.php
            );

            // hook model_portal_channel_thread_forumlist_after.php

            $forum_thread = thread_tid__find(array('fid' => $_forum['fid']), $orderby, $page, $_forum['index_new'], 'tid', array('tid'));
            // 最新信息按栏目分组
            foreach ($forum_thread as $key => $_thread) {
                $forum_tids[$key] = $_thread;
            }

            // hook model_portal_channel_thread_forumlist_after.php

            unset($forum_thread);
        }
        $tidlist += $forum_tids;
    }

    // hook model_portal_channel_thread_forumlist_end.php

    unset($category_forumlist);

    // hook model_portal_channel_thread_center.php

    // 获取属性对应的tid集合
    list($flaglist, $flagtids) = flag_thread_by_fid($fid);
    empty($flagtids) || $tidlist += $forum_tids;

    // hook model_portal_channel_thread_flag_after.php

    unset($flagtids);

    // hook model_portal_channel_thread_sticky_before.php

    // 频道置顶
    $stickylist = sticky_list_thread($fid);
    empty($stickylist) || $tidlist += $stickylist;

    // hook model_portal_channel_thread_sticky_after.php

    // 在这之前合并所有二维数组 tid值为键/array('tid值' => tid值)
    $tidarr = arrlist_values($tidlist, 'tid');

    // 在这之前使用$tidarr = array_merge($tidarr, $arr)前合并所有一维数组 tid/array(1,2,3)
    // hook model_portal_channel_thread_merge_after.php

    if (empty($tidarr)) {
        $arrlist['list'] = isset($arrlist['list']) ? array_multisort_key($arrlist['list'], 'rank', FALSE, 'fid') : array();
        return $arrlist;
    }

    $tidarr = array_unique($tidarr);

    // hook model_portal_channel_thread_unique_after.php

    $pagesize = count($tidarr);

    // hook model_portal_channel_thread_find_before.php

    // 遍历获取的所有tid主题
    $threadlist = well_thread_find_asc($tidarr, $pagesize);

    // hook model_portal_channel_thread_find_after.php

    // 遍历时为升序，翻转为降序
    $threadlist = array_reverse($threadlist);

    // hook model_portal_channel_thread_reverse_after.php

    foreach ($threadlist as &$_thread) {

        // hook model_portal_channel_thread_cate_before.php

        // 各栏目最新内容
        isset($forum_tids[$_thread['tid']]) AND $arrlist['list'][$_thread['fid']]['news'][$_thread['tid']] = $_thread;

        // 全站置顶内容
        isset($stickylist[$_thread['tid']]) AND $arrlist['sticky'][$_thread['tid']] = $_thread;

        // 首页属性主题
        if (!empty($flaglist)) {
            foreach ($flaglist as $key => $val) {

                if (in_array($_thread['tid'], $val['tids'])) {

                    $arrlist['flaglist'][$key][array_search($_thread['tid'], $val['tids'])] = $_thread;
                    ksort($arrlist['flaglist'][$key]);

                    $arrlist['flag'][$_thread['tid']] = $_thread;
                }
            }
        }

        // hook model_portal_channel_thread_cate_after.php
    }

    unset($threadlist);

    if (isset($arrlist['sticky'])) {
        $i = 0;
        foreach ($arrlist['sticky'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }

    if (isset($arrlist['flag'])) {
        $i = 0;
        foreach ($arrlist['flag'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }

    if (isset($arrlist['flaglist'])) {
        foreach ($arrlist['flaglist'] as &$val) {
            $i = 0;
            foreach ($val as &$v) {
                ++$i;
                $v['i'] = $i;
            }
        }
    }

    // hook model_portal_channel_thread_after.php

    isset($arrlist['list']) AND $arrlist['list'] = array_multisort_key($arrlist['list'], 'rank', FALSE, 'fid');

    // hook model_portal_channel_thread_end.php

    return $arrlist;
}

// hook model_misc_website_end.php

?>