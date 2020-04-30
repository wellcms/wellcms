<?php
/*
 * Copyright (C) www.wellcms.cn
 */

// hook model_misc_category_start.php

// 获取CMS全部栏目，包括频道的二叉树结构
function category_tree($forumlist)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    if (!empty($cache)) return $cache;
    $arrlist = arrlist_cond_orderby($forumlist, array('type' => 1), array(), 1, 1000);
    $arrlist = category_tree_format($arrlist);
    $cache = array_multisort_key($arrlist, 'rank', FALSE, 'fid');
    return $cache;
}

// 门户 获取需要在频道显示的栏目 fid name index_new最新显示数量
function channel_category($fid)
{
    global $forumlist_show;
    static $cache = array();
    if (isset($cache[$fid])) return $cache[$fid];
    // hook model_category_show_start.php
    if (empty($forumlist_show[$fid])) return NULL;
    // hook model_category_show_before.php
    $forum = $forumlist_show[$fid];
    // hook model_category_show_after.php
    $cache[$fid] = $forum['son'] ? arrlist_cond_orderby($forumlist_show, array('fup' => $fid, 'type' => 1, 'category' => 0), array('fid' => -1), 1, 1000) : NULL;
    // hook model_index_category_end.php
    return $cache[$fid];
}

// 返回网站所有频道
function all_channel($forumlist)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    if (!empty($cache)) return $cache;
    $channellist = arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => 1), array(), 1, 100);
    $fidarr = arrlist_key_values($channellist, 'fid', 'name');
    $cache = array('0' => lang('first_level_forum'));
    foreach ($fidarr as $key => $v) {
        $cache[$key] = $v;
    }
    return $cache;
}

/**
 * @param $forumlist    所有版块列表
 * @return mixed    返回二叉树结构的版块列表
 */
function category_tree_format($forumlist)
{
    // 格式化为树状结构 (会舍弃不合格的结构)
    foreach ($forumlist as &$v) {
        //$forumlist[$v['fid']]['son'] = array();
        // 按照上级fup格式化 归属子栏目到上级栏目
        if ($v['fup']) {
            $forumlist[$v['fup']]['sonlist'][$v['fid']] = $v;
            unset($forumlist[$v['fid']]);
        }
    }

    return $forumlist;
}

/**
 * @param $forumlist    所有版块列表
 * @return mixed    返回CMS全部栏目，包括频道(不包含单页和外链)
 */
function all_category($forumlist)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    if (!empty($cache)) return $cache;
    $cache = arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => array('<' => 2)), array(), 1, 1000);
    return $cache;
}

/**
 * @param $forumlist    所有版块列表
 * @param int $model 0文章
 * @param int $display 0全部CMS栏目 1在首页和频道显示内容的栏目
 * @param int $category 0列表 1频道 2单页 3外链
 * @return array
 */
function category_list($forumlist, $model = 0, $display = 0, $category = 0)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    $key = $model . '-' . $display . '-' . $category;
    if (isset($cache[$key])) return $cache[$key];
    // hook model_category_list_start.php
    if ($display) {
        foreach ($forumlist as $k => $val) {
            if (1 == $val['display'] && $val['model'] == $model && 1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
        // hook model_category_list_before.php
    } else {
        foreach ($forumlist as $k => $val) {
            if (1 == $val['type'] && $val['model'] == $model && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
        // hook model_category_list_after.php
    }
    // hook model_category_list_end.php
    return empty($cache[$key]) ? NULL : $cache[$key];
}

/**
 * @param $forumlist    所有版块列表 不分模型
 * @param int $display 0全部CMS栏目 1在首页和频道显示内容的栏目
 * @param int $category 0列表 1频道 2单页 3外链
 * @return array
 */
function category_list_show($forumlist, $display = 0, $category = 0)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    $key = $display . '-' . $category;
    if (isset($cache[$key])) return $cache[$key];
    // hook model_category_list_show_start.php
    if ($display) {
        foreach ($forumlist as $k => $val) {
            if (1 == $val['display'] && 1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
        // hook model_category_list_show_before.php
    } else {
        foreach ($forumlist as $k => $val) {
            if (1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
        // hook model_category_list_show_after.php
    }
    // hook model_category_list_show_end.php
    return empty($cache[$key]) ? NULL : $cache[$key];
}

/**
 * @param $forumlist    所有版块列表
 * @return mixed    BBS栏目数据(仅列表) 尚未开放bbs频道功能
 */
function forum_list($forumlist)
{
    // hook model_forum_list_start.php
    if (empty($forumlist)) return array();
    static $cache = array();
    if (!empty($cache)) return $cache;
    foreach ($forumlist as $_fid => $_forum) {
        if ($_forum['type']) continue;
        // hook model_forum_list_before.php
        $cache[$_fid] = $_forum;
        // hook model_forum_list_after.php
    }
    // hook model_forum_list_end.php
    return $cache;
}

// 导航显示的版块
function nav_list($forumlist)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    if (!empty($cache)) return $cache;
    // hook model_nav_list_start.php
    foreach ($forumlist as $fid => $forum) {
        if (0 == $forum['nav_display']) {
            unset($forumlist[$fid]);
        }
        // hook model_nav_list_before.php
    }
    // hook model_nav_list_end.php
    return $cache = $forumlist;
}

// hook model_misc_category_end.php
?>