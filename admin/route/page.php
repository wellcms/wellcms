<?php
/*
 * Copyright (C) www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1, 'list');

// hook admin_page_start.php

// 返回单页版块列表数据(仅列表)
$columnlist = category_list_show($forumlist, 0, 2);

// hook admin_page_before.php

switch ($action) {
    // hook admin_page_case_start.php
    case 'list':
        $fid = param('fid', 0);

        // hook admin_page_list_start.php

        if ('GET' == $method) {

            // hook admin_page_list_get_start.php

            $page = param('page', 1);
            $pagesize = 20;

            // hook admin_page_list_get_before.php

            // 插件预留
            $extra = array('page' => '{page}', 'fid' => $fid, 'backstage' => 1);

            // hook admin_page_list_get_center.php

            /* 所有通过审核的内容，免费版无审核功能
             * 遍历所有tid，然后合并tid再查询thread表，避免重复查询
             * */
            if ($fid) { // 版块下的主题

                // hook admin_page_list_get_forum_before.php

                $forum = array_value($forumlist, $fid);
                empty($forum) AND message(1, lang('forum_not_exists'));

                // hook admin_page_list_get_forum_after.php

                $n = $forum['threads'];

                // hook admin_page_list_get_forum_thread_before.php

                // 版块下主题
                $tidlist = $n ? page_find_by_fid($fid, $page, $pagesize) : NULL;

                // hook admin_page_list_get_forum_thread_after.php

            } else {
                // 主页读取全部主题

                // hook admin_page_list_get_count_before.php

                $n = page__count();

                // hook admin_page_list_get_count_after.php

                $tidlist = $n ? page_find($page, $pagesize) : NULL;

                // hook admin_page_list_get_page_after.php
            }

            // hook admin_page_list_get_middle.php

            if (empty($tidlist)) {
                $threadlist = NULL;
            } else {
                $tidarr = arrlist_values($tidlist, 'tid');
                $threadlist = well_thread_find($tidarr, $pagesize);
                $threadlist = array2_merge($tidlist, $threadlist, 'tid');
                // 按之前tidlist排序
                $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
            }

            $pagination = pagination(url('page-list', $extra, TRUE), $n, $page, $pagesize);

            $safe_token = well_token_set($uid);

            // hook admin_page_list_get_after.php

            $header['title'] = lang('single_page');
            $header['mobile_title'] = lang('single_page');

            // hook admin_page_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/page_list.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            // 排序时最大值作为首页
            FALSE === group_access($gid, 'managepage') AND message(1, lang('user_group_insufficient_privilege'));

            $arr = _POST('data');

            empty($arr) && message(1, lang('data_is_empty'));

            foreach ($arr as &$val) {
                $rank = intval($val['rank']);
                $tid = intval($val['tid']);
                intval($val['oldrank']) != $rank && $tid && $r = page_update_rank($tid, $rank);
            }

            // 查找rank最大值
            $arrlist = page_find_by_fid($fid, 1, 100);
            $read = reset($arrlist);
            forum_update($fid, array('brief' => $read['tid']));

            message(0, lang('update_successfully'));
        }
        break;
    case 'create':
        // hook admin_page_create_start.php

        if ('GET' == $method) {

            $extra = array(); // 插件预留
            $fid = param('fid', 0);
            $fid AND $extra += array('fid' => $fid);

            // hook admin_page_create_get_start.php

            $forum = $fid ? array_value($forumlist, $fid) : array();
            $model = array_value($forum, 'model', 0);

            // hook admin_page_create_get_before.php

            // 过滤权限
            $forumlist_allowthread = forum_list_access_filter($forumlist, $gid, 'allowthread');
            empty($forumlist_allowthread) AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_page_create_get_middle.php

            $input = array();
            $form_title = lang('increase') . lang('content');
            $form_action = url('page-create', $extra, TRUE);
            $form_submit_txt = lang('submit');
            $form_subject = $form_message = '';
            $form_doctype = $quotepid = 0;
            $_SESSION['tmp_website_files'] = array();
            $safe_token = well_token_set($uid);

            // hook admin_page_create_get_form_after.php

            $breadcrumb_flag = lang('increase') . lang('content');

            // hook admin_page_create_get_after.php

            $header['title'] = lang('increase') . lang('content');
            $header['mobile_title'] = lang('increase') . lang('content');

            // hook admin_page_create_get_end.php

            include _include(ADMIN_PATH . 'view/htm/page_post.htm');

        } elseif ('POST' == $method) {

            // 验证token
            if (array_value($conf, 'message_token', 0)) {
                $safe_token = param('safe_token');
                FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));
            }

            FALSE === group_access($gid, 'managepage') AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_page_create_post_start.php

            $fid = param('fid', 0);
            $forum = array_value($forumlist, $fid);
            empty($forum) AND message('fid', lang('forum_not_exists'));

            // hook admin_page_create_post_forum_after.php

            // 普通用户权限判断
            $r = forum_access_user($fid, $gid, 'allowthread');
            empty($r) AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_page_create_post_center.php

            $subject = param('subject');
            $subject = filter_all_html($subject);
            empty($subject) AND message('subject', lang('please_input_subject'));
            xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength' => 128)));
            // 过滤标题 关键词

            // hook admin_page_create_post_middle.php

            $doctype = param('doctype', 0);
            $doctype > 10 AND message(1, lang('doc_type_not_supported'));

            // hook admin_page_create_post_before.php

            $message = param('message', '', FALSE);
            $message = trim($message);
            empty($message) ? message('message', lang('please_input_message')) : xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));

            $message = xn_html_safe($message);

            // 过滤内容 关键词

            // hook admin_page_create_post_after.php

            $tid = well_thread__create(array('fid' => $fid, 'uid' => $uid, 'type' => 11, 'subject' => $subject, 'userip' => $longip, 'create_date' => $time));
            FALSE === $tid AND message(-1, lang('create_thread_failed'));

            // 关联附件
            $attach = array('tid' => $tid, 'uid' => $uid, 'assoc' => 'post', 'images' => 0, 'files' => 0, 'message' => $message);
            // hook admin_page_create_post_attach_before.php
            $message = well_attach_assoc_post($attach);
            unset($attach);

            $tid = data_create(array('tid' => $tid, 'gid' => $gid, 'message' => $message, 'doctype' => $doctype));
            FALSE === $tid AND message(-1, lang('create_thread_failed'));

            FALSE === page_create(array('tid' => $tid, 'fid' => $fid)) AND message(-1, lang('create_thread_failed'));

            $update = array('threads+' => 1, 'todaythreads+' => 1);
            // 第一篇主题作为单页的首页
            empty($forum['threads']) || empty($forum['brief']) AND $update['brief'] = $tid;
            forum_update($fid, $update);

            // 全站内容数
            runtime_set('articles+', 1);
            runtime_set('todayarticles+', 1);

            // hook admin_page_create_post_end.php

            message(0, lang('create_thread_successfully'));
        }
        break;
    case 'update':
        // hook admin_page_update_start.php

        $tid = param('tid', 0);
        empty($tid) AND message(1, lang('data_malformation'));

        $thread = well_thread_read_cache($tid);
        empty($thread) AND message(-1, lang('thread_not_exists'));
        $fid = $thread['fid'];

        // hook admin_page_update_before.php

        $thread_data = data_read($tid);

        // hook admin_page_update_end.php

        if ('GET' == $method) {

            $extra = array('tid' => $tid); // 插件预留
            // hook admin_page_update_get_start.php

            $forum = array_value($forumlist, $fid);
            $model = array_value($forum, 'model', 0);

            // hook admin_page_update_get_before.php

            $form_title = lang('edit');
            $form_action = url('page-update', $extra, TRUE);
            $form_submit_txt = lang('submit');
            $form_subject = $thread['subject'];
            $form_message = str_replace('="upload/', '="'.admin_attach_path().'upload/', $thread_data['message']);
            $form_doctype = $thread_data['doctype'];

            // hook admin_page_update_get_center.php

            $breadcrumb_flag = lang('edit');

            // hook admin_page_update_get_middle.php

            $safe_token = well_token_set($uid);

            $header['title'] = lang('edit');
            $header['mobile_title'] = lang('edit');

            // hook admin_page_update_get_end.php

            include _include(ADMIN_PATH . 'view/htm/page_post.htm');

        } elseif ('POST' == $method) {

            // 验证token
            if (array_value($conf, 'message_token', 0)) {
                $safe_token = param('safe_token');
                FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));
            }

            FALSE === group_access($gid, 'managepage') AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_page_update_post_start.php

            $arr = array();

            $subject = param('subject');
            $subject = filter_all_html($subject);
            empty($subject) AND message('subject', lang('please_input_subject'));

            xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength' => 128)));
            // 过滤标题 关键词

            // hook admin_page_update_post_subject_before.php

            if ($subject != $thread['subject']) {
                //mb_strlen($subject, 'UTF-8') > 80 AND message('subject', lang('subject_max_length', array('max' => 80)));
                //$arr['subject'] = $subject;

                (mb_strlen($subject, 'UTF-8') > 80) ? message('subject', lang('subject_max_length', array('max' => 80))) : $arr['subject'] = $subject;

                $thread['sticky'] > 0 AND cache_delete('sticky_thread_list');
            }

            // hook admin_page_update_post_subject_after.php

            $doctype = param('doctype', 0);
            $doctype > 10 AND message(1, lang('doc_type_not_supported'));

            // hook admin_page_update_post_message_before.php

            $message = param('message', '', FALSE);
            $message = trim($message);
            empty($message) ? message('message', lang('please_input_message')) : xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));

            $message = xn_html_safe($message);
            // 过滤内容 关键词

            // hook admin_page_update_post_message_after.php

            $newfid = param('fid', 0);
            $forum = array_value($forumlist, $fid);
            empty($forum) AND message('fid', lang('forum_not_exists:'));

            // hook admin_page_update_post_fid_center.php

            if ($fid != $newfid) {

                // hook admin_page_update_post_fid_access.php

                $thread['uid'] != $uid && !forum_access_mod($fid, $gid, 'allowupdate') AND message(1, lang('user_group_insufficient_privilege'));

                // hook admin_page_update_post_fid_update.php

                forum__update($newfid, array('threads+' => 1));
                forum_update($thread['fid'], array('threads-' => 1));
                sticky_thread_update_by_tid($tid, $newfid);

                thread_tid_update($tid, $newfid);

                $arr['fid'] = $newfid;
            }

            // hook admin_page_update_post_arr_after.php

            $arr['userip'] = $longip;

            !empty($arr) && FALSE === well_thread_update($tid, $arr) AND message(-1, lang('update_thread_failed'));
            unset($arr);

            // 关联附件 assoc thumbnail主题主图 post:内容图片或附件
            $attach = array('tid' => $tid, 'uid' => $uid, 'assoc' => 'post', 'images' => $thread['images'], 'files' => $thread['files'], 'message' => $message);
            $message = well_attach_assoc_post($attach);
            unset($attach);

            // hook admin_page_update_post_before.php

            // 如果开启云储存或使用图床，需要把内容中的附件链接替换掉
            $message = data_message_replace_url($tid, $message);

            FALSE === data_update($tid, array('tid' => $tid, 'gid' => $gid, 'doctype' => $doctype, 'message' => $message)) AND message(-1, lang('update_post_failed'));

            // hook admin_page_update_post_end.php

            message(0, lang('update_successfully'));
        }
        break;
    case 'delete':
        FALSE === group_access($gid, 'managepage') AND message(1, lang('user_group_insufficient_privilege'));
        if ('POST' == $method) {

            // hook admin_page_delete_start.php

            $tid = param('tid', 0);
            $thread = well_thread__read(array('tid' => $tid));
            empty($thread) AND message(-1, lang('thread_not_exists'));

            // hook admin_page_delete_before.php

            // 权限判断 仅限管理员和用户本人有权限
            $allowdelete = ($uid == $thread['uid']) || forum_access_mod($thread['fid'], $gid, 'allowdelete');

            // 删除内容
            data_delete($tid);

            // 删除附件
            ($thread['images'] || $thread['files']) && well_attach_delete_by_tid($tid);

            // hook admin_page_delete_center.php

            // 删除主题
            well_thread_delete($tid);

            // hook admin_page_delete_after.php

            // 删除单页
            FALSE === page_delete($tid) AND message(-1, lang('delete_failed'));

            $forum = array_value($forumlist, $thread['fid']);
            $update = array('threads-' => 1);
            if ($tid == trim($forum['brief'])) {
                if (1 == $forum['threads']) {
                    $update['brief'] = '';
                } else {
                    // 查找rank最大值
                    $arrlist = page_find_by_fid($thread['fid'], 1, 100);
                    $r = reset($arrlist);
                    $update['brief'] = $r['tid'];
                }
            }

            // hook admin_page_delete_forum_update_after.php

            forum_update($thread['fid'], $update);

            // hook admin_page_delete_end.php

            message(0, lang('delete_completely'));
        }
        break;
    // hook admin_page_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_page_end.php

?>