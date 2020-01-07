<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1, 'list');

$columnlist = category_list($forumlist);

// hook website_admin_sticky_start.php

if ($action == 'list') {

    // hook website_admin_sticky_list_start.php

    if ($method == 'GET') {

        // hook website_admin_sticky_list_get_start.php

        $header['title'] = lang('sticky');
        $header['mobile_title'] = lang('sticky');

        $fid = param(2, 0);
        $page = param(3, 1);
        $pagesize = 25;
        $extra = array('fid' => $fid, 'backstage' => 1); // 插件预留

        // hook website_admin_sticky_list_get_before.php

        $forum = array_value($forumlist, $fid);

        if ($fid) {

            // hook website_admin_sticky_list_get_forum_after.php

            $forum_fup = array_value($forumlist, $forum['fup']);

            $n = $forum['tops'] + ($forum_fup ? $forum_fup['tops'] : 0);

            // hook website_admin_sticky_list_get_sticky_after.php

        } else {
            $n = $config['index_stickys'];
            // hook website_admin_sticky_list_get_sticky_middle.php
        }

        // hook website_admin_sticky_list_get_find_before.php

        $tidlist = $fid ? sticky_list_thread($fid) : sticky_index_thread();

        if ($n) {

            $tidarr = arrlist_values($tidlist, 'tid');

            // 遍历thread表
            $threadlist = well_thread_find($tidarr, $pagesize);
            // 按之前tidlist排序
            $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
        } else {
            $threadlist = NULL;
        }

        // hook website_admin_sticky_list_get_after.php

        $pagination = pagination(url('sticky-' . $fid . '-{page}', $extra), $n, $page, $pagesize);

        // hook website_admin_sticky_list_get_end.php

        include _include(ADMIN_PATH . 'view/htm/content_list.htm');
    }

    // hook website_admin_sticky_list_end.php
}

// hook website_admin_sticky_end.php

?>