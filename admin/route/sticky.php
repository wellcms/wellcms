<?php
/*
 * Copyright (C) www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1, 'list');

$columnlist = category_list($forumlist);

// hook admin_sticky_start.php

switch ($action) {
    // hook admin_sticky_case_start.php
    case 'list':
        // hook admin_sticky_list_start.php

        if ('GET' == $method) {

            // hook admin_sticky_list_get_start.php

            $header['title'] = lang('sticky');
            $header['mobile_title'] = lang('sticky');

            $fid = param('fid', 0);
            $page = param('page', 1);
            $pagesize = 25;
            $extra = array('page' => '{page}', 'fid' => $fid, 'backstage' => 1); // 插件预留

            // hook admin_sticky_list_get_before.php

            $forum = array_value($forumlist, $fid);

            if ($fid) {

                // hook admin_sticky_list_get_forum_after.php

                $forum_fup = array_value($forumlist, $forum['fup']);

                $n = $forum['tops'] + ($forum_fup ? $forum_fup['tops'] : 0);

                // hook admin_sticky_list_get_sticky_after.php

            } else {
                $n = $config['index_stickys'];
                // hook admin_sticky_list_get_sticky_middle.php
            }

            // hook admin_sticky_list_get_find_before.php

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

            // hook admin_sticky_list_get_after.php

            $pagination = pagination(url('sticky-list', $extra, TRUE), $n, $page, $pagesize);

            // hook admin_sticky_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/content_list.htm');
        }

        // hook admin_sticky_list_end.php
        break;
    // hook admin_sticky_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_sticky_end.php

?>