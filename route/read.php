<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') and exit('Access Denied.');

$tid = param(1, 0);
$page = param(2, 1);
$pagesize = $conf['comment_pagesize'];
$extra = array(); // 插件预留

// hook read_start.php

$thread = 1 == array_value($conf, 'cache_thread') ? well_thread_read_cache($tid) : well_thread_read($tid);
// hook read_cache_after.php
empty($thread) and message(-1, lang('thread_not_exists'));

// hook read_status_before.php

0 != $thread['status'] && $uid != $thread['uid'] && !group_access($gid, 'allowdelete') and http_location($conf['path']);

// hook read_before.php

$fid = $thread['fid'];
$forum = isset($forumlist[$fid]) ? $forumlist[$fid] : NULL;
empty($forum) and message(-1, lang('forum_not_exists'));

// hook read_center.php

// 用户读取版块主题的权限
forum_access_user($fid, $gid, 'allowread') || message(-1, lang('user_group_insufficient_privilege'));

// hook read_middle.php

// 大站可用单独的点击服务，减少 db 压力 / if request is huge, separate it from mysql server
well_thread_inc_views($tid);

// hook read_after.php

switch ($thread['type']) {
    case '0':
        // 文章 / Article
        // hook read_article_start.php

        $data = NULL;
        $arrlist = NULL;
        $attachlist = NULL;
        $imagelist = NULL;
        $thread['filelist'] = NULL;
        $safe_token = well_token_set($uid);
        // 从默认的地方读取主题数据
        $thread_read_from_default = 1;

        // hook read_article_default_start.php

        if (1 == $thread_read_from_default) {

            // hook read_article_default_before.php

            $postlist = $forum['comment'] && $thread['closed'] < 2 && $thread['posts'] ? comment_find_by_tid($tid, $page, $pagesize) : NULL;

            // hook read_article_default_center.php

            if (1 == $page) {

                $attachlist = array();
                $imagelist = array();
                $thread['filelist'] = array();

                // hook read_article_default_page_before.php

                $thread['files'] > 0 and list($attachlist, $imagelist, $thread['filelist']) = well_attach_find_by_tid($tid);

                // hook read_article_default_page_center.php

                $data = data_read_cache($tid);
                empty($data) and message(-1, lang('data_malformation'));

                // hook read_article_default_page_after.php
            }

            // hook read_article_default_middle.php
        }

        // hook read_article_default_end.php

        // 默认拉取其他主题
        $pull_other_from_default = 1;

        // hook read_article_center.php

        if (1 == $pull_other_from_default) {
            // hook read_article_pull_other_start.php

            // 相关主题等调用，统一遍历tid合并去重，再遍历主题表
            $arrlist = thread_other_pull($thread);

            // hook read_article_pull_other_center.php

            // 主题所在版块下所有展示属性主题
            $flaglist = array_value($arrlist, 'flaglist');

            // hook read_article_pull_other_end.php
        }

        // hook read_article_middle.php

        $page_url = url('read-' . $tid . '-{page}', $extra);
        $num = $thread['posts'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $thread['posts'];

        // hook read_article_pagination_before.php

        $pagination = pagination($page_url, $num, $page, $pagesize);

        $allowpost = $forum['comment'] && $thread['closed'] < 2 && 0 == $thread['status'] && forum_access_user($fid, $gid, 'allowpost');
        $allowupdate = $uid == $thread['uid'] || forum_access_mod($thread['fid'], $gid, 'allowupdate');
        $allowdelete = ($uid == $thread['uid'] and forum_access_mod($fid, $gid, 'allowuserdelete')) || forum_access_mod($fid, $gid, 'allowdelete');

        $access = array('allowpost' => $allowpost, 'allowupdate' => $allowupdate, 'allowdelete' => $allowdelete);

        $comment_action = url('comment-create-' . $tid, array('safe_token' => $safe_token));

        // hook read_article_after.php

        $header['title'] = $thread['subject'];
        $header['mobile_link'] = $thread['url'];
        $header['keywords'] = $thread['keyword'] ? $thread['keyword'] : $thread['subject'];
        $header['description'] = $thread['description'] ? $thread['description'] : $thread['brief'];
        $_SESSION['fid'] = $fid;

        // hook read_article_end.php

        if ($ajax) {
            empty($conf['api_on']) and message(0, lang('closed'));

            $apilist['header'] = $header;
            $apilist['extra'] = $extra;
            $apilist['safe_token'] = $safe_token;
            $apilist['access'] = $access;
            $apilist['thread'] = well_thread_safe_info($thread);
            $apilist['thread_data'] = $data;
            $apilist['forum'] = $forum;
            $apilist['arrlist'] = $arrlist;
            $apilist['imagelist'] = $imagelist;
            $apilist['filelist'] = $thread['filelist'];
            if ($postlist) {
                foreach ($postlist as $key => $val) {
                    unset($postlist[$key]['userip']);
                    if (!empty($val['replylist'])) {
                        foreach ($val['replylist'] as $k => $_reply) {
                            unset($postlist[$key]['replylist'][$k]['userip']);
                        }
                    }
                }
            }
            $apilist['comment'] = array('num' => $num, 'page' => $page, 'pagesize' => $pagesize, 'page_url' => $page_url, 'postlist' => $postlist, 'comment_action' => $comment_action);

            message(0, $apilist);
        } else {
            // 可使用模板绑定版块功能，也可根据模型 hook 不同模板
            switch ($forum['model']) {
                /*case '0':
                    include _include(APP_PATH . 'view/htm/read.htm');
                    break;*/
                // hook read_article_case.php
                default:
                    include _include(theme_load('read', $fid));
                    break;
            }
        }
        break;
    case '10':
        // 主题外链 / thread external link
        // hook read_link_before.php
        http_location(htmlspecialchars_decode(trim($thread['description'])));
        break;
    case '11':
        // 单页 / single page
        // hook read_single_page_start.php

        $attachlist = array();
        $imagelist = array();
        $thread['filelist'] = array();
        $threadlist = NULL;

        // hook read_single_page_before.php

        $thread['files'] > 0 and list($attachlist, $imagelist, $thread['filelist']) = well_attach_find_by_tid($tid);

        // hook read_single_page_center.php

        $data = data_read_cache($tid);
        empty($data) and message(-1, lang('data_malformation'));

        // hook read_single_page_middle.php

        $tidlist = $forum['threads'] ? page_find_by_fid($fid, $page, $pagesize) : NULL;

        // hook read_single_page_threadlist_before.php

        if ($tidlist) {
            $tidarr = arrlist_values($tidlist, 'tid');
            // hook read_single_page_threadlist_center.php
            $threadlist = well_thread_find($tidarr, $pagesize);
            // 按之前tidlist排序
            $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
            // hook read_single_page_threadlist_after.php
        }

        $allowpost = forum_access_user($fid, $gid, 'allowpost');
        $allowupdate = forum_access_mod($fid, $gid, 'allowupdate');
        $allowdelete = forum_access_mod($fid, $gid, 'allowdelete');

        $access = array('allowpost' => $allowpost, 'allowupdate' => $allowupdate, 'allowdelete' => $allowdelete);

        // hook read_single_page_after.php

        $header['title'] = $thread['subject'];
        $header['mobile_link'] = $thread['url'];
        $header['keywords'] = $thread['keyword'] ? $thread['keyword'] : $thread['subject'];
        $header['description'] = $thread['description'] ? $thread['description'] : $thread['brief'];
        $_SESSION['fid'] = $fid;

        // hook read_single_page_end.php

        if ($ajax) {
            empty($conf['api_on']) and message(0, lang('closed'));

            $apilist['header'] = $header;
            $apilist['extra'] = $extra;
            $apilist['access'] = $access;
            $apilist['thread'] = well_thread_safe_info($thread);
            $apilist['thread_data'] = $data;
            $apilist['forum'] = $forum;
            $apilist['imagelist'] = $imagelist;
            $apilist['filelist'] = $thread['filelist'];
            $apilist['threadlist'] = $threadlist;

            message(0, $apilist);
        } else {
            include _include(theme_load('single_page', $fid));
        }
        break;
    // hook read_type_case_after.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook read_end.php

?>