<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 * 栏目管理扩展
 */

// hook model_misc_category_start.php

// 获取CMS全部栏目，包括频道的二叉树结构
function category_tree($forumlist)
{
    $forumlist = arrlist_cond_orderby($forumlist, array('type' => 1), array(), 1, 1000);
    $forumlist = category_tree_format($forumlist);
    $forumlist = array_multisort_key($forumlist, 'rank', FALSE, 'fid');
    return $forumlist;
}

// 门户 获取需要在频道显示的栏目 fid name index_new最新显示数量
function channel_category($fid)
{
    global $forumlist_show;
    // hook model_category_show_start.php
    if (empty($forumlist_show[$fid])) return NULL;
    // hook model_category_show_before.php
    $forum = $forumlist_show[$fid];
    // hook model_category_show_after.php
    $forum_show = $forum['son'] ? arrlist_cond_orderby($forumlist_show, array('fup' => $fid, 'type' => 1, 'category' => 0), array('fid' => -1), 1, 1000) : NULL;
    // hook model_index_category_end.php
    return $forum_show;
}

// 返回网站所有频道
function all_channel($forumlist)
{
    $channellist = arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => 1), array(), 1, 100);
    $fidarr = arrlist_key_values($channellist, 'fid', 'name');
    $arr = array('0' => lang('first_level_forum'));
    foreach ($fidarr as $key => $v) {
        $arr[$key] = $v;
    }
    return $arr;
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
    return arrlist_cond_orderby($forumlist, array('type' => 1, 'category' => array('<' => 2)), array(), 1, 1000);
}

/**
 * @param $forumlist    所有版块列表
 * @param int $display 0全部CMS栏目 1在首页和频道显示内容的栏目
 * @param int $category 0列表 1频道 2单页 3外链
 * @return array
 */
function category_list($forumlist, $display = 0, $category = 0)
{
    $arrlist = array();
    if ($display) {
        foreach ($forumlist as $key => $val) {
            if ($val['display'] == 1 && $val['type'] == 1 && $val['category'] == $category) {
                $arrlist[$key] = $val;
            }
        }
    } else {
        foreach ($forumlist as $key => $val) {
            if ($val['type'] == 1 && $val['category'] == $category) {
                $arrlist[$key] = $val;
            }
        }
    }

    return $arrlist;
}

/**
 * @param $forumlist    所有版块列表
 * @return mixed    BBS栏目数据(仅列表) 尚未开放bbs频道功能
 */
function forum_list($forumlist)
{
    $arrlist = arrlist_cond_orderby($forumlist, array('type' => 0), array(), 1, 1000);

    return $arrlist;
}

// 导航显示的版块
function nav_list($forumlist)
{
    foreach ($forumlist as $fid => $forum) {
        if ($forum['nav_display'] == 0) {
            unset($forumlist[$fid]);
        }
    }
    return $forumlist;
}

// hook model_misc_category_end.php
?>