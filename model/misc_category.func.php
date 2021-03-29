<?php
/*
 * Copyright (C) www.wellcms.cn
 */

// hook model_misc_category_start.php

function nav_member()
{
    $route = param(0);
    static $cache = array();
    $key = 'nav_member_'.$route;
    if (isset($cache[$key])) return $cache[$key];

    // hook model_misc_nav_member_start.php

    $navs = array(
        // hook model_misc_nav_member_navs_start.php
        'home' => array('url' => url('home'), 'name' => lang('my_index_page'), 'active' => 'menu-home'),
        // hook model_misc_nav_member_home_after.php
        'my' => array('url' => url('my'), 'name' => lang('my_setting'), 'active' => 'menu-my'),
        // hook model_misc_nav_member_navs_end.php
    );

    // hook model_misc_nav_member_before.php

    $menus = array();

    // hook model_misc_nav_member_center.php

    switch ($route) {
        // hook model_misc_nav_member_case_start.php
        case 'my':
            // hook model_misc_nav_member_my_start.php
            $menus += array(
                // hook model_misc_nav_member_my_profile_before.php
                'my' => array('url' => url('my'), 'name' => lang('my_basic_profile'), 'active' => 'my-profile'),
                // hook model_misc_nav_member_my_password_before.php
                'my-password' => array('url' => url('my-password'), 'name' => lang('modify_password'), 'active' => 'my-password'),
                // hook model_misc_nav_member_my_avatar_before.php
                'my-avatar' => array('url' => url('my-avatar'), 'name' => lang('modify_avatar'), 'active' => 'my-avatar'),
                // hook model_misc_nav_member_my_avatar_after.php
            );
            // hook model_misc_nav_member_my_end.php
            break;
        case 'home':
            // hook model_misc_nav_member_home_start.php
            $menus += array(
                // hook model_misc_nav_member_home_article_before.php
                'home-article' => array('url' => url('home-article'), 'name' => lang('thread'), 'active' => 'my-article'),
                // hook model_misc_nav_member_home_comment_before.php
                'home-comment' => array('url' => url('home-comment'), 'name' => lang('comment'), 'active' => 'my-comment'),
                // hook model_misc_nav_member_home_comment_after.php
            );
            // hook model_misc_nav_member_home_end.php
            break;
        // hook model_misc_nav_member_case_end.php
    }

    // hook model_misc_nav_member_end.php

    return array($navs, $menus);
}

// 获取CMS全部栏目，包括频道的二叉树结构
function category_tree($forumlist)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    if (isset($cache['forumlist'])) return $cache['forumlist'];
    $arrlist = arrlist_cond_orderby($forumlist, array('type' => 1), array(), 1, 1000);
    $arrlist = category_tree_format($arrlist);
    $cache['forumlist'] = array_multisort_key($arrlist, 'rank', FALSE, 'fid');
    return $cache['forumlist'];
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
    if (isset($cache['all_channel'])) return $cache['all_channel'];
    $channellist = arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => 1), array(), 1, 100);
    $fidarr = arrlist_key_values($channellist, 'fid', 'name');
    $cache['all_channel'] = array('0' => lang('first_level_forum'));
    foreach ($fidarr as $key => $v) {
        $cache['all_channel'][$key] = $v;
    }
    return $cache['all_channel'];
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
    if (isset($cache['all_category'])) return $cache['all_category'];
    $cache['all_category'] = arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => array('<' => 2)), array(), 1, 1000);
    return $cache['all_category'];
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
            if (1 == $val['display'] && 1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
        // hook model_category_list_before.php
    } else {
        foreach ($forumlist as $k => $val) {
            if (1 == $val['type'] && $val['category'] == $category) {
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
    if (isset($cache['bbs_forum_list'])) return $cache['bbs_forum_list'];
    $cache['bbs_forum_list'] = array();
    foreach ($forumlist as $_fid => $_forum) {
        if ($_forum['type']) continue;
        // hook model_forum_list_before.php
        $cache['bbs_forum_list'][$_fid] = $_forum;
        // hook model_forum_list_after.php
    }
    // hook model_forum_list_end.php
    return $cache['bbs_forum_list'];
}

// 导航显示的版块
function nav_list($forumlist)
{
    if (empty($forumlist)) return NULL;
    static $cache = array();
    if (isset($cache['nav_list'])) return $cache['nav_list'];
    // hook model_nav_list_start.php
    foreach ($forumlist as $fid => $forum) {
        if (0 == $forum['nav_display']) {
            unset($forumlist[$fid]);
        }
        // hook model_nav_list_before.php
    }
    // hook model_nav_list_end.php
    return $cache['nav_list'] = $forumlist;
}

// hook model_misc_category_end.php
?>