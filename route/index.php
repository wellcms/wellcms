<?php
/*
 * Copyright (C) www.wellcms.cn
 * 扩展时可hook也可overwrite
*/
!defined('DEBUG') and exit('Access Denied.');

// hook index_start.php

$arrlist = array();
$extra = array(); // 插件预留
$fid = 0;
// hook index_before.php

$website_setting = $config['setting'];
// website_mode
$website_mode = $website_setting['website_mode'];
// tpl_mode
$tpl_mode = $website_setting['tpl_mode'];

// hook index_before.php

// 从默认的地方读取主题列表
$thread_list_from_default = 1;

// hook index_mode_before.php

if (0 == $website_mode) {
    // 自定义模式 custom 仅调用首页属性主题和置顶
    $tidlist = array();
    // hook index_custom_start.php

    /*
     * flagid对应首页显示的flagid 实例时填写对应数字
     * 属性名 $flaglist[flagid]['name'];
     * 属性链接 $flaglist[flagid]['url'];
     * 属性所有主题数组 $flaglist[flagid]['list'];
     * */
    // 栏目自定义 返回flag 和flag下主题tids
    list($flaglist, $flagtids) = flag_thread_by_fid($fid);
    !empty($flagtids) and $tidlist += $flagtids;
    // hook index_custom_before.php

    // 查找置顶tid
    $stickylist = sticky_index_thread();
    !empty($stickylist) and $tidlist += $stickylist;

    // hook index_custom_center.php
    /************ 在这之前合并所有tid 二维数组 *************/

    $tidarr = arrlist_values($tidlist, 'tid');

    // 在这之前使用array_merge()前合并所有一维数组 tid/array(1,2,3)
    // hook index_custom_thread_find_before.php

    // 主题相关统一遍历后再归类
    $arrlist = well_thread_find(array_unique($tidarr), count($tidarr));

    // 过滤没有权限访问的主题 / filter no permission thread
    well_thread_list_access_filter($arrlist, $gid);

    $threadlist = array();
    foreach ($arrlist as $_tid => &$_thread) {
        // 归类列表数据
        isset($tidlist[$_thread['tid']]) and $threadlist[$_tid] = well_thread_safe_info($_thread);

        // hook index_custom_threadlist.php

        // flag thread
        if (!empty($flaglist)) {
            foreach ($flaglist as $key => $val) {
                if (in_array($_thread['tid'], $val['tids'])) {
                    $flaglist[$key]['list'][array_search($_thread['tid'], $val['tids'])] = $_thread;
                    ksort($flaglist[$key]['list']);
                    // hook index_custom_flag.php
                }
            }
        }

        // hook index_custom_flaglist.php
    }

    // hook index_custom_middle.php
    // 按之前tidlist排序
    $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
    unset($tidlist);

    // hook index_custom_after.php

    // 友情链接
    $linklist = link_get(1, $conf['linksize']);

    // ajax数据
    $arrlist = array('threadlist' => $threadlist, 'flaglist' => $flaglist);

    // hook index_custom_end.php

} elseif (1 == $website_mode) {
    // 门户模式 portal

    // hook index_portal_start.php

    /*
     * $arrlist['list']对应每个需要显示的栏目;
     * // 栏目按照rank排序，调用时单独栏目可直接$arrlist['list'][fid值]
     * $arrlist['list'][fid值]['name']栏目名;
     * $arrlist['list'][fid值]['url']栏目路径;
     * $arrlist['list'][fid值]['news']栏目下显示的主题二维数组;
     * $arrlist['flag']首页需要显示的全部主题二维数组;
     * $arrlist['flaglist'][flagid]对应flagid显示的主题二维数组;
     * $arrlist['sticky']首页置顶需要显示的主题二维数组;
     * */
    $arrlist = portal_index_thread_cache($forumlist);

    // 轮播凑整 双列排版 防止错版 单一列注释该代码
    $slide = array_value($arrlist, 'sticky');
    /*if ($slide) {
        if (0 != count($arrlist['sticky']) % 2) {
            $i = 0;
            foreach ($arrlist['sticky'] as $key => &$_thread) {
                if (1 == ++$i) {
                    $slide[] = $_thread;
                }
            }
        }
    }*/

    $first_flag = NULL;
    $flaglist = NULL;
    if (isset($arrlist['flaglist'])) {
        $flaglist = $arrlist['flaglist'];
        $first_flag = reset($arrlist['flaglist']);
        $first_flag = $first_flag['list'];
    }

    // 友情链接
    $linklist = link_get(1, $conf['linksize']);

    // hook index_portal_end.php

} elseif (2 == $website_mode) {
    // 扁平模式

    // hook index_flat_start.php

    $page = param(1, 1);
    $pagesize = $conf['pagesize'];
    $threadlist = $tidlist = NULL;
    $threads = 0;

    // hook index_flat_after.php

    if (1 == $thread_list_from_default) {

        // hook index_flat_thread_find_start.php

        if (empty($forumlist_show)) {

            // hook index_flat_thread_find_before.php

        } else {

            $fids = array();
            $threads = 0;
            foreach ($forumlist_show as $key => $val) {
                if (1 == $val['type'] && 1 == $val['display'] && 0 == $val['category']) {
                    $fids[] = $val['fid'];
                    $threads += $val['threads'];
                }
            }

            // hook index_flat_thread_find_center.php

            $tidlist = empty($fids) ? array() : thread_tid_find_by_fid($fids, $page, $conf['pagesize'], TRUE);

            // hook index_flat_thread_find_after.php
        }

        // hook index_flat_thread_find_end.php
    }

    // hook index_flat_center.php

    // 置顶
    $stickylist = 1 == $page ? sticky_index_thread() : array();

    // hook index_flat_sticky_after.php

    $arr = array('tidlist' => $tidlist, 'stickylist' => $stickylist);

    // hook index_flat_unified_pull_before.php

    $arrlist = thread_unified_pull($arr);
    $threadlist = array_value($arrlist, 'threadlist');
    $flaglist = array_value($arrlist, 'flaglist');

    // hook index_flat_unified_pull_after.php

    // 友情链接
    $linklist = link_get(1, $conf['linksize']);

    // hook index_flat_link_after.php

    $page_url = url($route . '-{page}', $extra);
    $num = $threads > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $threads;

    // hook index_flat_pagination_before.php

    $pagination = pagination($page_url, $num, $page, $pagesize);

    // hook index_flat_end.php
}

// hook index_after.php

// SEO
$header['title'] = $conf['sitename'];
$header['keywords'] = $conf['sitename'];
$header['description'] = strip_tags($conf['sitebrief']);
$_SESSION['fid'] = $fid;
$active = 'default';

// hook index_end.php

if ($ajax) {
    $apilist['header'] = $header;
    $apilist['extra'] = $extra;
    $apilist['num'] = $num;
    $apilist['page'] = $page;
    $apilist['pagesize'] = $pagesize;
    $apilist['page_url'] = $page_url;
    $apilist['active'] = $active;
    $apilist['arrlist'] = $arrlist;
    $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
} else {
    include _include(theme_load('index'));
}

?>