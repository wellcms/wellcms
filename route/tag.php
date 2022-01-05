<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') and exit('Access Denied.');

$action = param(1);

// hook tag_start.php

switch ($action) {
    // hook tag_case_start.php
    case 'list':
        // hook tag_list_start.php

        $page = param(2, 1);
        $pagesize = $conf['tagsize'];
        $extra = array(); // 插件预留

        // hook tag_list_before.php

        $count = well_tag_count();

        $taglist = $count ? well_tag_find($page, $pagesize) : NULL;

        // hook tag_list_middle.php

        $page_url = url('tag-list-{page}', $extra);
        $num = $count > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $count;

        // hook tag_list_pagination_before.php

        $pagination = pagination($page_url, $num, $page, $pagesize);

        // hook tag_list_after.php

        $header['title'] = lang('tag') . '-' . $conf['sitename'];
        $header['mobile_link'] = url('tag-list', $extra);
        $header['keywords'] = lang('tag') . ',' . $conf['sitename'];
        $header['description'] = lang('tag') . ',' . $conf['sitename'];
        $_SESSION['fid'] = 0;

        // hook tag_list_end.php

        if ($ajax) {
            $apilist['header'] = $header;
            $apilist['extra'] = $extra;
            $apilist['num'] = $num;
            $apilist['page'] = $page;
            $apilist['pagesize'] = $pagesize;
            $apilist['page_url'] = $page_url;
            $apilist['arrlist'] = $taglist;
            $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
        } else {
            include _include(theme_load('tag_list'));
        }
        break;
    // hook tag_case_end.php
    default:
        // tag-tagid-page.htm

        $tagid = param(1, 0);
        $page = param(2, 1);
        $pagesize = $conf['pagesize'];
        $extra = array(); // 插件预留

        // hook tag_before.php

        $read = well_tag_read_by_tagid_cache($tagid);
        // hook tag_cache_after.php
        empty($read) and message(-1, lang('well_tag_not_existed'));

        // hook tag_center.php

        $arr = well_tag_thread_find($tagid, $page, $pagesize);
        if (empty($arr)) {
            $threadlist = NULL;
        } else {
            $tidarr = arrlist_values($arr, 'tid');
            $threadlist = well_thread_find($tidarr, $pagesize);
        }

        // hook tag_middle.php

        $count = well_tag_count();
        $page_url = url('tag-' . $tagid . '-{page}', $extra);
        $num = $read['count'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $read['count'];

        // hook tag_pagination_before.php

        $pagination = pagination($page_url, $num, $page, $pagesize);

        // hook tag_after.php

        $header['title'] = empty($read['title']) ? $read['name'] : $read['title'];
        $header['mobile_link'] = url('tag-' . $tagid, $extra);
        $header['keywords'] = empty($read['keywords']) ? $read['name'] : $read['keywords'];
        $header['description'] = empty($read['description']) ? $read['name'] : $read['description'];
        $_SESSION['fid'] = 0;

        // hook tag_end.php

        if ($ajax) {
            $apilist['header'] = $header;
            $apilist['extra'] = $extra;
            $apilist['tag'] = $read;
            $apilist['num'] = $num;
            $apilist['page'] = $page;
            $apilist['pagesize'] = $pagesize;
            $apilist['page_url'] = $page_url;
            $apilist['threadlist'] = $threadlist;

            $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
        } else {
            include _include(theme_load('tag', $tagid));
        }
        break;
}

// hook tag_end.php

?>