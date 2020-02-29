<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');

// hook list_start.php

$fid = param(1, 0);
empty($fid) AND message(1, lang('data_malformation'));

$page = param(2, 1);
$extra = array(); // 插件预留
$active = 'default';
// 管理时使用
(forum_access_mod($fid, $gid, 'allowdelete') OR forum_access_mod($fid, $gid, 'allowtop')) AND $extra['fid'] = $fid;

// hook list_before.php

$forum = forum_read($fid);
empty($forum) AND message(1, lang('forum_not_exists'));

// hook list_forum_after.php

// 导航外链
if ($forum['category'] == 3) {
    // hook list_link_before.php
    http_location(trim($forum['brief']));
}

// hook list_link_after.php

if ($forum['type'] == 1) {
    // CMS版块
    if ($forum['category'] == 0) {
        // 列表
        // hook list_access_before.php

        // 用户是否有读取该版块的权限
        forum_access_user($fid, $gid, 'allowread') || message(-1, lang('insufficient_visit_forum_privilege'));

        $pagesize = empty($forum['pagesize']) ? $conf['pagesize'] : $forum['pagesize'];

        // hook list_from_default_before.php

        // 从默认的地方读取主题列表
        $thread_list_from_default = 1;

        // hook list_from_default_before.php

        if ($thread_list_from_default) {

            // hook list_thread_start.php

            $orderby = FALSE;
            // $orderby = $forum['thread_rank'] ? TRUE : FALSE;

            // hook list_thread_before.php

            // 返回版块下tid
            $orderby == FALSE AND $tidlist = well_thread_find_tid($fid, $page, $pagesize);

            //$orderby == TRUE AND $tidlist = well_thread_find_desc($fid, $page, $pagesize);

            // hook list_thread_end.php
        }

        // hook list_sticky_before.php

        // 查找置顶 1栏目 2频道 3全局
        $stickylist = $page == 1 ? sticky_list_thread($fid) : array();

        // hook list_sticky_after.php

        $arr = array('tidlist' => $tidlist, 'stickylist' => $stickylist);

        // hook list_unified_pull_before.php

        $arrlist = thread_unified_pull($arr);
        $threadlist = array_value($arrlist, 'threadlist');
        $flaglist = array_value($arrlist, 'flaglist');

        // hook list_unified_pull_after.php

        // ajax数据
        //$arrlist = array('threadlist' => $threadlist, 'flaglist' => $flaglist);

        // hook list_ajax_after.php

        $forum_threads = $forum['threads'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $forum['threads'];

        // hook list_forum_threads_after.php

        $pagination = pagination(url('list-' . $fid . '-{page}', $extra), $forum_threads, $page, $pagesize);

        // hook list_header_before.php

        $seo_title = ($forum['seo_title'] ? $forum['seo_title'] : $forum['name']) . '-' . $conf['sitename'];
        $header['title'] = strip_tags($seo_title);
        $header['mobile_title'] = '';
        $header['mobile_link'] = url('list-' . $fid);
        $seo_keywords = $forum['seo_keywords'] ? $forum['seo_keywords'] : $forum['name'];
        $header['keywords'] = strip_tags($seo_keywords);
        $header['description'] = strip_tags($forum['brief']);
        $_SESSION['fid'] = $fid;

        // hook list_header_after.php

        if ($ajax) {

            empty($conf['api_on']) AND message(0, lang('closed'));

            if (isset($arrlist['threadlist'])) {
                foreach ($arrlist['threadlist'] as &$val) {
                    well_thread_filter($val);
                }
            }
            message(0, $arrlist);
        } else {
            include _include(theme_load(1, $fid));
        }

    } elseif ($forum['category'] == 1) {
        http_location(url('category-' . $forum['fid']));
    } elseif ($forum['category'] == 2) {
        $forum['threads'] ? http_location(url('read-' . trim($forum['brief']))) : message(1, lang('none'));
    }

    // hook list_end.php
}

?>
