<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');
// 属性

// hook flag_start.php

$flagid = param(1, 0);
empty($flagid) AND message(1, lang('data_malformation'));

$page = param(2, 1);
$pagesize = $conf['pagesize'];
$extra = array(); // 插件预留
$threadlist = NULL;

// hook flag_before.php

$read = flag_read_cache($flagid);
// hook flag_read_after.php
empty($read)AND message(1, lang('thread_not_exists'));

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
    include _include(theme_load('flag', $flagid));
}

?>