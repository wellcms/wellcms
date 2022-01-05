<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') and exit('Access Denied.');

// hook flag_start.php

$flagid = param(1, 0);
empty($flagid) and message(1, lang('data_malformation'));

$page = param(2, 1);
$pagesize = $conf['pagesize'];
$extra = array(); // 插件预留
$threadlist = NULL;

// hook flag_before.php

$read = flag_read_cache($flagid);
// hook flag_read_after.php
empty($read) and message(1, lang('thread_not_exists'));

// hook flag_center.php
$arrlist = $read['count'] ? flag_thread_find_by_flagid($flagid, $page, $pagesize) : NULL;

// hook flag_middle.php

if ($arrlist) {
    $tidarr = arrlist_values($arrlist, 'tid');
    $threadlist = well_thread_find($tidarr, $pagesize);
    // hook flag_threadlist_after.php
}

$page_url = url('flag-' . $flagid . '-{page}', $extra);
$num = $read['count'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $read['count'];

// hook flag_pagination_before.php

$pagination = pagination($page_url, $num, $page, $pagesize);

// hook flag_after.php

$header['title'] = empty($read['title']) ? $read['name'] . '-' . $conf['sitename'] : $read['title'];
$header['mobile_link'] = $read['url'];
$header['keywords'] = empty($read['keywords']) ? $read['name'] : $read['keywords'];
$header['description'] = empty($read['description']) ? $read['name'] : $read['description'];
$flag_link = '';
$safe_token = well_token_set($uid);

// hook flag_end.php

if ($ajax) {
    if ($threadlist) {
        foreach ($threadlist as &$thread) $thread = well_thread_safe_info($thread);
    }
    $apilist['header'] = $header;
    $apilist['extra'] = $extra;
    $apilist['num'] = $num;
    $apilist['page'] = $page;
    $apilist['pagesize'] = $pagesize;
    $apilist['page_url'] = $page_url;
    $apilist['safe_token'] = $safe_token;
    $apilist['flag'] = $read;
    $apilist['threadlist'] = $threadlist;
    $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
} else {
    include _include(theme_load('flag', $flagid));
}

?>