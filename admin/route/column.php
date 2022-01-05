<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') AND exit('Access Denied.');

FALSE === group_access($gid, 'manageforum') AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// hook admin_column_start.php

switch ($action) {
    // hook admin_column_case_start.php
    case 'list':
        // hook admin_column_list_get_post.php

        if ('GET' == $method) {

            // hook admin_column_list_get_start.php

            $header['title'] = lang('website') . lang('column');
            $header['mobile_title'] = lang('website') . lang('column');

            $safe_token = well_token_set($uid);

            // 后台栏目管理列表
            $arrlist = category_tree($forumlist);

            // hook admin_column_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/column_list.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            $fidarr = param('fid', array(0));
            $rankarr = param('rank', array(0));

            // hook admin_column_list_post_start.php

            $arrlist = array();
            foreach ($fidarr as $k => $v) {
                // hook admin_column_list_update_before.php
                forum_update($k, array('fid' => $k, 'rank' => array_value($rankarr, $k)));
                // hook admin_column_list_update_after.php
            }

            // hook admin_column_list_post_end.php

            message(0, lang('save_successfully'));
        }
        break;
    case 'create':
        // hook admin_column_create_start.php

        if ('GET' == $method) {

            // hook admin_column_create_get_start.php

            $header['title'] = lang('create') . lang('column');
            $header['mobile_title'] = lang('create') . lang('column');
            $breadcrumb = lang('increase') . lang('column');
            $extra = array();

            $next = param('next', 0);

            // hook admin_column_create_get_before.php

            $accesslist = array();
            foreach ($grouplist as $group) {
                $accesslist[$group['gid']] = $group; // 字段名相同，直接覆盖。 / same field, directly overwrite
            }

            // hook admin_column_create_get_center.php

            // 所属频道
            $channelarr = all_channel($forumlist);

            $name = '';
            $seo_title = '';
            $seo_keywords = '';
            $brief = '';
            $announcement = '';
            $modnames = '';
            $category = param('category', 0); // 0列表 1频道
            $catearr = array(lang('first_level_forum'), lang('channel'), lang('single_page'), lang('outer_chain'));
            // hook admin_column_create_get_catearr_after.php
            $fup = param('fup', 0);
            $extra['fup'] = $fup;
            $model = 0;
            $nav_display = 1;
            $comment = 1;
            $display = 1;
            $index_new = 10;
            $channel_new = 10;
            $pagesize = 20;
            $accesson = 0;
            $width = array_value($conf, 'thumbnail_width', 400);
            $height = array_value($conf, 'thumbnail_height', 280);
            $son = 0;
            $threads = 0;
            $checked_fup = 0;
            $disabled_category = FALSE;
            $disabled_fup = FALSE;
            $disabled_model = FALSE;

            $extra['category'] = $category;
            $form_action = url('column-create', $extra, TRUE);

            $safe_token = well_token_set($uid);

            // hook admin_column_create_get_end.php

            include _include(ADMIN_PATH . 'view/htm/column_post.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            // hook admin_column_create_post_start.php

            $name = param('name');
            $name = filter_all_html($name);
            empty($name) AND message(1, lang('data_empty_to_last_step'));

            $rank = param('rank', 0);
            $brief = param('brief', '', FALSE);
            $brief = xn_html_safe($brief);
            $announcement = param('announcement', '', FALSE);
            $announcement = xn_html_safe($announcement);
            $accesson = param('accesson', 0);
            $modnames = param('modnames');
            $moduids = user_names_to_ids($modnames);
            $nav_display = param('nav_display', 0);
            $model = param('model', 0);
            $width = param('width', 0);
            $width = $width ? $width : 400;
            $height = param('height', 0);
            $height = $height ? $height : 280;
            $thumbnail = json_encode(array('width' => $width, 'height' => $height));
            $seo_title = param('seo_title');
            $seo_keywords = param('seo_keywords');
            // 0列表 1频道 2单页 3外链
            $category = param('category', 0);
            $fup = _POST('fup', 0);
            $fup = intval($fup);
            $pagesize = param('pagesize', 20);

            // hook admin_column_create_post_before.php

            // 频道不显示
            if (1 == $category) {
                $comment = 0;
                $display = 0;
                //$thread_rank = 0;
                $index_new = 0;
                $channel_new = 0;
            } else {
                // 列表需要显示数据
                $comment = param('comment', 0);
                $display = param('display', 0);
                //$thread_rank = param('thread_rank', 0);
                $index_new = param('index_new', 10);
                $index_new = $display ? $index_new : 10;
                $channel_new = param('channel_new', 10);
            }

            // hook admin_column_create_post_center.php

            $arr = array(
                'fup' => $fup,
                'type' => 1,
                'model' => $model,
                'category' => $category,
                'name' => $name,
                'rank' => $rank,
                'accesson' => $accesson,
                'create_date' => $time,
                'display' => $display,
                'nav_display' => $nav_display,
                'index_new' => $index_new,
                'channel_new' => $channel_new,
                'comment' => $comment,
                'pagesize' => $pagesize,
                'thumbnail' => $thumbnail,
                'moduids' => $moduids,
                'seo_title' => $seo_title,
                'seo_keywords' => $seo_keywords,
                'brief' => $brief,
                'announcement' => $announcement,
            );

            // hook admin_column_create_post_after.php

            $_fid = forum_create($arr);

            $fup AND forum_update($fup, array('son+' => 1));

            if ($accesson) {
                $allowread = param('allowread', array(0));
                $allowthread = param('allowthread', array(0));
                $allowpost = param('allowpost', array(0));
                $allowattach = param('allowattach', array(0));
                $allowdown = param('allowdown', array(0));
                foreach ($grouplist as $_gid => $v) {
                    $access = array(
                        'allowread' => array_value($allowread, $_gid, 0),
                        'allowthread' => array_value($allowthread, $_gid, 0),
                        'allowpost' => array_value($allowpost, $_gid, 0),
                        'allowattach' => array_value($allowattach, $_gid, 0),
                        'allowdown' => array_value($allowdown, $_gid, 0),
                    );
                    forum_access_replace($_fid, $_gid, $access);
                }
            } else {
                forum_access_delete_by_fid($_fid);
            }

            // hook admin_column_create_post_end.php

            message(0, lang('save_successfully'));
        }
        break;
    case 'update':
        $_fid = param('fid', 0);
        $_forum = forum_read($_fid);
        empty($_forum) AND message(-1, lang('forum_not_exists'));

        // hook admin_column_update_get_post.php

        if ('GET' == $method) {

            $extra = array('fid' => $_fid);

            // hook admin_column_update_get_start.php

            $accesslist = forum_access_find_by_fid($_fid);

            if (empty($accesslist)) {
                foreach ($grouplist as $group) {
                    $accesslist[$group['gid']] = $group; // 字段名相同，直接覆盖。 / same field, directly overwrite
                }
            } else {
                foreach ($accesslist as &$access) {
                    $access['name'] = $grouplist[$access['gid']]['name']; // 字段名相同，直接覆盖。 / same field, directly overwrite
                }
            }
            array_htmlspecialchars($_forum);

            // hook admin_column_update_get_before.php

            // 所属频道
            $channelarr = all_channel($forumlist);

            // hook admin_column_update_get_center.php

            $next = 1;
            $form_action = url('column-update', $extra, TRUE);
            $fid = $_forum['fid'];
            $name = $_forum['name'];
            $seo_title = $_forum['seo_title'];
            $seo_keywords = $_forum['seo_keywords'];
            $brief = $_forum['brief'];
            $announcement = $_forum['announcement'];

            $moduids = $_forum['moduids'];
            $modnames = user_ids_to_names($moduids);

            $category = $_forum['category']; // 0列表 1频道
            $catearr = array(lang('first_level_forum'), lang('channel'), lang('single_page'), lang('outer_chain'));
            // hook admin_column_update_get_catearr_after.php
            $fup = $_forum['fup'];
            $model = $_forum['model'];
            $nav_display = $_forum['nav_display'];
            $comment = $_forum['comment'];
            $display = $_forum['display'];
            $index_new = $_forum['index_new'];
            $channel_new = $_forum['channel_new'];
            $pagesize = $_forum['pagesize'];
            $accesson = $_forum['accesson'];

            $thumbnail_size = $_forum['thumbnail'];
            $width = array_value($_forum['thumbnail'], 'width', 400);
            $height = array_value($_forum['thumbnail'], 'height', 280);

            // hook admin_column_update_get_middle.php

            if ($_forum['threads'] || $_forum['son']) {
                $checked_fup = 1;
                $disabled_category = TRUE;
                $disabled_fup = FALSE;
                $disabled_model = TRUE;
            } else {
                $checked_fup = 0;
                $disabled_category = FALSE;
                $disabled_fup = FALSE;
                $disabled_model = FALSE;
            }

            $safe_token = well_token_set($uid);

            $header['title'] = lang('edit') . lang('column');
            $header['mobile_title'] = lang('edit') . lang('column');
            $breadcrumb = lang('edit') . lang('column');

            // hook admin_column_update_get_end.php

            include _include(ADMIN_PATH . 'view/htm/column_post.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            // hook admin_column_update_post_start.php

            $name = param('name');
            $name = filter_all_html($name);
            empty($name) AND message(1, lang('data_empty_to_last_step'));

            $rank = param('rank', 0);
            $brief = param('brief', '', FALSE);
            $brief = xn_html_safe($brief);
            $announcement = param('announcement', '', FALSE);
            $announcement = xn_html_safe($announcement);
            $accesson = param('accesson', 0);
            $modnames = param('modnames', '', FALSE);
            $moduids = user_names_to_ids($modnames);
            $nav_display = param('nav_display', 0);
            $model = ($_forum['threads'] OR $_forum['son']) ? $_forum['model'] : param('model', 0);
            $width = param('width', 0);
            $width = $width ? $width : 400;
            $height = param('height', 0);
            $height = $height ? $height : 280;
            $thumbnail = json_encode(array('width' => $width, 'height' => $height));
            $seo_title = param('seo_title');
            $seo_keywords = param('seo_keywords');

            // 有主题 有子版块 单页 外链 都不修改该值
            $category = ($_forum['threads'] || $_forum['son'] || in_array($_forum['category'], array(2, 3))) ? $_forum['category'] : param('category', 0);

            $fup = param('fup', 0);
            $pagesize = param('pagesize', 20);

            // hook admin_column_update_post_before.php

            // 频道 单页 外链不显示
            if (in_array($category, array(1, 2, 3))) {
                $comment = 0;
                $display = 0;
                //$thread_rank = 0;
                $index_new = 0;
                $channel_new = 0;
            } else {
                // 列表需要显示数据
                $comment = param('comment', 0);
                $display = param('display', 0);
                //$thread_rank = param('thread_rank', 0);
                $index_new = param('index_new', 10);
                $index_new = $display ? $index_new : 10;
                $channel_new = param('channel_new', 10);
            }

            // hook admin_column_update_post_center.php

            $arr = array(
                'fup' => $fup,
                'type' => 1,
                'model' => $model,
                'category' => $category,
                'name' => $name,
                'rank' => $rank,
                'accesson' => $accesson,
                'create_date' => $time,
                'display' => $display,
                'nav_display' => $nav_display,
                'index_new' => $index_new,
                'channel_new' => $channel_new,
                'comment' => $comment,
                'pagesize' => $pagesize,
                'thumbnail' => $thumbnail,
                'moduids' => $moduids,
                'seo_title' => $seo_title,
                'seo_keywords' => $seo_keywords,
                'brief' => $brief,
                'announcement' => $announcement,
            );

            // hook admin_column_update_post_after.php

            forum_update($_fid, $arr);

            if ($_forum['fup'] != $fup) {
                forum_update($fup, array('son+' => 1));
                forum_update($_forum['fup'], array('son-' => 1));
            }

            if ($accesson) {
                $allowread = param('allowread', array(0));
                $allowthread = param('allowthread', array(0));
                $allowpost = param('allowpost', array(0));
                $allowattach = param('allowattach', array(0));
                $allowdown = param('allowdown', array(0));
                foreach ($grouplist as $_gid => $v) {
                    $access = array(
                        'allowread' => array_value($allowread, $_gid, 0),
                        'allowthread' => array_value($allowthread, $_gid, 0),
                        'allowpost' => array_value($allowpost, $_gid, 0),
                        'allowattach' => array_value($allowattach, $_gid, 0),
                        'allowdown' => array_value($allowdown, $_gid, 0),
                    );
                    forum_access_replace($_fid, $_gid, $access);
                }
            } else {
                forum_access_delete_by_fid($_fid);
            }

            // hook admin_column_update_post_end.php

            message(0, lang('edit_successfully'));
        }
        break;
    case 'delete':
        if ('POST' != $method) message(-1, lang('method_error'));

        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

        $_fid = param('fid', 0);
        $_forum = forum_read($_fid);
        empty($_forum) AND message(-1, lang('forum_not_exists'));

        // hook admin_column_delete_start.php

        $threadlist = thread_tid_find_by_fid($_fid, 1, 20);
        empty($threadlist) || message(-1, lang('forum_delete_thread_before_delete_forum'));

        $_forum['son'] AND message(-1, lang('forum_please_delete_sub_forum'));

        forum_delete($_fid);

        $_forum['accesson'] AND forum_access_delete_by_fid($_fid);

        // hook admin_column_delete_end.php

        message(0, lang('forum_delete_successfully'));
        break;
    // hook admin_column_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_column_after.php

function user_names_to_ids($names, $sep = ',')
{
    if (empty($names)) return '';
    $namearr = explode($sep, $names);
    $r = array();
    foreach ($namearr as $name) {
        $user = user_read_by_username($name);
        if (empty($user)) continue;
        $r[] = $user ? $user['uid'] : 0;
    }
    return implode($sep, $r);
}

function user_ids_to_names($ids, $sep = ',')
{
    if (empty($ids)) return '';
    $idarr = explode($sep, $ids);
    $r = array();
    foreach ($idarr as $id) {
        $user = user_read($id);
        if (empty($user)) continue;
        $r[] = $user ? $user['username'] : '';
    }
    return implode($sep, $r);
}

// hook admin_column_end.php

?>