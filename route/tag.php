<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook tag_start.php

if ($action == 'list') {

    // hook tag_list_start.php

    $page = param(2, 1);
    $extra = array(); // 插件预留

    // hook tag_list_before.php

    $count = well_tag_count();

    $taglist = $count ? well_tag_find($page, $conf['tagsize']) : NULL;

    // hook tag_list_middle.php

    $threads = $count > $conf['tagsize'] * $conf['listsize'] ? $conf['tagsize'] * $conf['listsize'] : $count;

    $pagination = pagination(url('tag-list-{page}', $extra), $threads, $page, $conf['tagsize']);

    // hook tag_list_after.php

    $header['title'] = lang('well_tag') . '-' . $conf['sitename'];
    $header['mobile_title'] = '';
    $header['mobile_link'] = url('tag-list', $extra);
    $header['keywords'] = lang('well_tag') . ',' . $conf['sitename'];
    $header['description'] = lang('well_tag') . ',' . $conf['sitename'];
    $_SESSION['fid'] = 0;

    // hook tag_list_end.php

    if ($ajax) {
        $conf['api_on'] ? message(0, $taglist) : message(0, lang('closed'));
    } else {
        include _include(theme_load(4));
    }

} else {

    // tag-tagid-page.htm
    $tagid = param(1, 0);
    empty($tagid) AND message(-1, lang('data_malformation'));

    $page = param(2, 1);
    $extra = array(); // 插件预留

    // hook tag_before.php

    $read = well_tag_read_by_tagid_cache($tagid);
    empty($read) AND message(-1, lang('well_tag_not_existed'));

    // hook tag_center.php

    $arr = well_tag_thread_find($tagid, $page, $conf['pagesize']);
    if (empty($arr)) {
        $threadlist = NULL;
    } else {
        $tidarr = arrlist_values($arr, 'tid');
        $threadlist = well_thread_find($tidarr, $conf['pagesize']);
    }

    // hook tag_middle.php

    $count = well_tag_count();
    $threads = $read['count'] > $conf['pagesize'] * $conf['listsize'] ? $conf['pagesize'] * $conf['listsize'] : $read['count'];

    $pagination = pagination(url('tag-' . $tagid . '-{page}', $extra), $threads, $page, $conf['pagesize']);

    // hook tag_after.php

    $header['title'] = empty($read['title']) ? $read['name'] : $read['title'];
    $header['mobile_title'] = '';
    $header['mobile_link'] = url('tag-' . $tagid, $extra);
    $header['keywords'] = empty($read['keywords']) ? $read['name'] : $read['keywords'];
    $header['description'] = empty($read['description']) ? $read['name'] : $read['description'];
    $_SESSION['fid'] = 0;

    // hook tag_end.php

    if ($ajax) {
        $conf['api_on'] ? message(0, array('tag' => $read, 'threadlist' => $threadlist)) : message(0, lang('closed'));
    } else {
        include _include(theme_load(5, $tagid));
    }
}

?>