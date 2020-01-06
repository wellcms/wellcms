<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');
// 属性

// hook flag_start.php

$action = param(1);

if ($action == 'list') {

    $page = param(2, 1);

    $pagesize = 20;
    $extra = array(); // 插件预留

    // hook flag_list_start.php

    // hook flag_list_before.php

    $pagination = pagination(url('flag-list-{page}', $extra), $n, $page, $pagesize);

    // hook flag_list_after.php

    $header['title'] = lang('flag') . '-' . $conf['sitename'];
    $header['mobile_title'] = '';
    $header['mobile_link'] = url('flag-list', $extra);
    $header['keywords'] = lang('flag') . '-' . $conf['sitename'];
    $header['description'] = lang('flag') . '-' . $conf['sitename'];
    $_SESSION['fid'] = 0;

    // hook flag_list_end.php

    if ($ajax) {
        $conf['api_on'] ? message(0, $arrlist) : message(0, lang('closed'));
    } else {
        // hook flag_list_template_htm.php
        include _include(APP_PATH . 'view/htm/flag_default.htm');
    }

} else {

    $flagid = param(1, 0);
    empty($flagid) AND message(1, lang('data_malformation'));

    $page = param(2, 1);
    $pagesize = $conf['pagesize'];
    $extra = array(); // 插件预留
    $threadlist = NULL;

    // hook flag_before.php

    $read = flag_read_cache($flagid);
    empty($read)AND message(1, lang('thread_not_exists'));

    // hook flag_center.php
    $arrlist = $read['count'] ? flag_thread_find_by_flagid($flagid, $page, $pagesize) : NULL;

    // hook flag_middle.php

    if ($arrlist) {
        $tidarr = arrlist_values($arrlist, 'tid');
        $threadlist = well_thread_find($tidarr, $pagesize);
        // hook flag_threadlist_after.php
    }

    $threads = $read['count'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $read['count'];

    // hook flag_pagination_before.php

    $pagination = pagination(url('flag-' . $flagid . '-{page}', $extra), $threads, $page, $pagesize);

    // hook flag_after.php

    $header['title'] = empty($read['title']) ? $read['name'] . '-' . $conf['sitename'] : $read['title'];
    $header['mobile_title'] = '';
    $header['mobile_link'] = url('flag-' . $flagid);
    $header['keywords'] = empty($read['keywords']) ? $read['name'] : $read['keywords'];
    $header['description'] = empty($read['description']) ? $read['name'] : $read['description'];
    $_SESSION['fid'] = 0;
    $flag_link = '';

    // hook flag_end.php

    if ($ajax) {
        $conf['api_on'] ? message(0, array('flag' => $read, 'threadlist' => $threadlist)) : message(0, lang('closed'));
    } else {
        include _include(theme_load(6, $flagid));
    }
}

?>