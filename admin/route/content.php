<?php
/*
 * Copyright (C) www.wellcms.cn
 */

!defined('DEBUG') AND exit('Access Denied.');

FALSE === group_access($gid, 'managecontent') AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// hook admin_content_start.php

// 返回CMS栏目数据(仅列表)
$columnlist = category_list($forumlist);

// hook admin_content_before.php

switch ($action) {
    // hook admin_content_case_start.php
    case 'list':
        // hook admin_content_list_start.php

        if ('GET' == $method) {

            // hook admin_content_list_get_start.php

            $fid = param('fid', 0);
            $page = param('page', 1);
            $pagesize = 20;
            $orderby = param(4, 0); // 主题排序

            // hook admin_content_list_get_before.php

            // 插件预留
            $extra = array('page' => '{page}', 'fid' => $fid, 'backstage' => 1);

            // hook admin_content_list_get_center.php

            /* 所有通过审核的内容，免费版无审核功能
             * 遍历所有tid，然后合并tid再查询thread表，避免重复查询
             * */
            if ($fid) { // 版块下的主题

                // hook admin_content_list_get_forum_before.php

                $forum = array_value($forumlist, $fid);
                empty($forum) AND message(1, lang('forum_not_exists'));

                // hook admin_content_list_get_forum_after.php

                $n = $forum['threads'];

                // hook admin_content_list_get_forum_thread_before.php

                // 栏目下主题
                if (0 == $orderby) {
                    // 返回栏目下tid
                    $tidlist = $n ? well_thread_find_tid($fid, $page, $pagesize) : NULL;
                }
                /* else {
                    // 主题排序
                    $tidlist = $n ? well_thread_find_desc($fid, $page, $pagesize) : NULL;
                }*/

                // hook admin_content_list_get_forum_thread_after.php

            } else {
                // 主页读取全部主题

                // hook admin_content_list_get_count_before.php

                $n = thread_tid_count();

                // hook admin_content_list_get_count_after.php

                $tidlist = $n ? thread_tid_find($page, $pagesize) : NULL;

                // hook admin_content_list_get_page_after.php
            }

            // hook admin_content_list_get_middle.php

            // 查找置顶 1栏目 2频道 3全局
            if (1 == $page) {
                $stickylist = $fid ? sticky_list_thread($fid) : sticky_index_thread();
                $tidlist = (array)$stickylist + (array)$tidlist;
            }

            // hook admin_content_list_get_sticky_after.php

            if (empty($tidlist)) {
                $threadlist = NULL;
            } else {
                $tidarr = arrlist_values($tidlist, 'tid');
                $threadlist = well_thread_find($tidarr, count($tidlist));
                // 按之前tidlist排序
                $threadlist = array2_sort_key($threadlist, $tidlist, 'tid');
            }

            $pagination = pagination(url('content-list', $extra, TRUE), $n, $page, $pagesize);

            $safe_token = well_token_set($uid);

            // hook admin_content_list_get_after.php

            $header['title'] = lang('content');
            $header['mobile_title'] = lang('content');

            // hook admin_content_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/content_list.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            // hook admin_content_list_post_start.php

            // 主题排序
            /*$arr = _POST('data');

            empty($arr) && message(1, lang('update_failed'));

            foreach ($arr as &$val) {
                $rank = intval($val['rank']);
                $tid = intval($val['tid']);
                intval($val['oldrank']) != $rank && $tid && $r = thread_tid_update_rank($tid, $rank);

            }

            message(0, lang('update_successfully'));*/

            // hook admin_content_list_post_end.php
        }
        break;
    case 'create':
        // hook admin_content_create_start.php

        if ('GET' == $method) {

            // hook admin_content_create_get_start.php

            $extra = array();
            $fid = param('fid', 0);
            $forum = $fid ? array_value($forumlist, $fid) : array();
            $model = array_value($forum, 'model', 0);
            // 插件预留
            $fid AND $extra += array('fid' => $fid);

            // hook admin_content_create_get_before.php

            $forum_flagids = array();
            $category_flagids = array();
            $index_flagids = array();

            $index_flag = flag_forum_show(0);
            $index_flag AND flag_filter($index_flag);

            // hook admin_content_create_get_middle.php

            // 过滤权限
            $forumlist_allowthread = forum_list_access_filter($forumlist, $gid, 'allowthread');

            empty($forumlist_allowthread) AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_content_create_get_filter_after.php

            // 获取主图
            $thumbnail = admin_view_path() . 'img/nopic.png';

            // hook admin_content_create_get_thumbnail_after.php

            $picture = $config['picture_size'];
            $pic_width = $picture['width'];
            $pic_height = $picture['height'];

            // hook admin_content_create_get_form_before.php

            $input = $filelist = array();
            $form_title = lang('increase') . lang('content');
            $form_action = url('content-create', '', TRUE);
            $form_submit_txt = lang('submit');
            $form_subject = $form_message = $form_brief = $form_link = $form_closed = $form_keyword = $form_description = $tagstr = '';

            $setting = array_value($config, 'setting');
            $thumbnail_on = 1 == array_value($setting, 'thumbnail_on', 0) ? 'checked="checked"' : '';
            $save_image = 1 == array_value($setting, 'save_image_on', 0) ? 'checked="checked"' : '';
            $form_doctype = 0;
            $_fid = 0;
            $page = 0;

            // 初始化附件
            $_SESSION['tmp_thumbnail'] = $_SESSION['tmp_website_files'] = array();

            // hook admin_content_create_get_form_after.php

            $breadcrumb_flag = lang('increase') . lang('content');

            // hook admin_content_create_get_after.php

            $header['title'] = lang('increase') . lang('content');
            $header['mobile_title'] = lang('increase') . lang('content');
            $referer = http_referer();

            // 过滤版块相关数据
            $forumlist = forum_filter($forumlist);

            $safe_token = well_token_set($uid);

            // hook admin_content_create_get_end.php

            // 可以根据自己设计的添加内容界面绑定栏目，绑定模型，显示不同的界面
            switch ($model) {
                /*case '0':
                    break;*/
                // hook admin_content_create_get_case_end.php
                default:
                    include _include(ADMIN_PATH . 'view/htm/content_post.htm');
                    break;
            }

        } elseif ('POST' == $method) {

            // 验证token
            if (array_value($conf, 'message_token', 0)) {
                $safe_token = param('safe_token');
                FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));
            }

            FALSE === group_access($gid, 'managecreatethread') AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_content_create_post_start.php

            $fid = param('fid', 0);
            $forum = array_value($forumlist, $fid);
            empty($forum) AND message('fid', lang('forum_not_exists'));

            // hook admin_content_create_post_forum_after.php

            // 普通用户权限判断
            !forum_access_user($fid, $gid, 'allowthread') AND message(1, lang('user_group_insufficient_privilege'));

            // hook admin_content_create_post_access_after.php

            $subject = param('subject');
            $subject = filter_all_html($subject);
            empty($subject) AND message('subject', lang('please_input_subject'));
            xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength' => 128)));
            // 过滤标题 关键词

            // hook admin_content_create_post_subject_after.php

            $link = param('link', 0);
            $type = $link ? 10 : 0;
            // hook admin_content_create_post_link_after.php

            $closed = param('closed', 0);
            $thumbnail = param('thumbnail', 0);
            $delete_pic = param('delete_pic', 0);
            $save_image = param('save_image', 0);
            $brief_auto = param('brief_auto', 0);
            $doctype = param('doctype', 0);
            $doctype > 10 AND message(1, lang('doc_type_not_supported'));

            // hook admin_content_create_post_before.php

            $message = $_message = '';
            if (0 == $link) {
                $message = param('message', '', FALSE);
                $message = trim($message);
                empty($message) ? message('message', lang('please_input_message')) : xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));

                // 过滤所有html标签
                $_message = htmlspecialchars(filter_all_html($message), ENT_QUOTES);

                // 过滤内容 关键词

                // hook admin_content_create_post_message_after.php
            }

            // hook admin_content_create_post_brief_start.php

            $brief = param('brief');
            if ($brief) {
                // 过滤简介 关键词
                // hook admin_content_create_post_brief_before.php

                xn_strlen($brief) > 120 AND $brief = xn_substr($brief, 0, 120);
            } else {
                $brief = ($brief_auto AND $_message) ? xn_substr($_message, 0, 120) : '';
            }
            $brief and $brief = filter_all_html($brief);

            // hook admin_content_create_post_brief_end.php

            $keyword = param('keyword');
            // 过滤内容 关键词
            // hook admin_content_create_post_keyword_before.php
            // 超出则截取
            xn_strlen($keyword) > 64 AND $keyword = xn_substr($keyword, 0, 64);

            // hook admin_content_create_post_description_before.php

            $description = param('description');
            // 过滤内容 关键词
            // hook admin_content_create_post_description_center.php
            // 超出则截取
            xn_strlen($description) > 120 AND $description = xn_substr($description, 0, 120);

            // hook admin_content_create_post_description_after.php

            $tags = param('tags', '', FALSE);
            $tags = xn_html_safe(filter_all_html($tags));
            // 过滤标签 关键词
            // hook admin_content_create_post_tag_center.php

            // hook admin_content_create_post_tag_after.php

            // 首页flag
            $flag_index_arr = array_filter(param('index', array()));
            // 频道flag
            $flag_cate_arr = array_filter(param('category', array()));
            // 栏目flag
            $flag_forum_arr = array_filter(param('forum', array()));
            // 统计主题绑定flag数量
            $flags = count($flag_index_arr) + count($flag_cate_arr) + count($flag_forum_arr);

            // hook admin_content_create_post_flags.php

            $thread = array(
                'fid' => $fid,
                'type' => $type,
                'doctype' => $doctype,
                'subject' => $subject,
                'brief' => $brief,
                'keyword' => $keyword,
                'description' => $description,
                'closed' => $closed,
                'flags' => $flags,
                'thumbnail' => $thumbnail,
                'save_image' => $save_image,
                'delete_pic' => $delete_pic,
                'message' => $message
            );

            // hook admin_content_create_post_middle.php

            $tid = well_thread_create($thread);
            FALSE === $tid AND message(-1, lang('create_thread_failed'));
            unset($thread);

            // hook admin_content_create_post_after.php

            $tag_json = well_tag_post($tid, $fid, $tags);
            if (xn_strlen($subject) >= 120) {
                $s = xn_substr($tag_json, -1, NULL);
                if ('}' != $s) {
                    $len = mb_strripos($tag_json, ',', 0, 'UTF-8');
                    $tag_json = $len ? xn_substr($tag_json, 0, $len) . '}' : '';
                }
            }
            $tag_json && FALSE === well_thread_update($tid, array('tag' => $tag_json)) AND message(-1, lang('update_thread_failed'));

            // 首页flag
            !empty($flag_index_arr) && FALSE === flag_create_thread(0, 1, $tid, $flag_index_arr) AND message(-1, lang('create_failed'));

            // 频道flag
            $forum['fup'] && !empty($flag_cate_arr) && FALSE === flag_create_thread($forum['fup'], 2, $tid, $flag_cate_arr) AND message(-1, lang('create_failed'));

            // 栏目flag
            !empty($flag_forum_arr) && FALSE === flag_create_thread($fid, 3, $tid, $flag_forum_arr) AND message(-1, lang('create_failed'));

            // hook admin_content_create_post_end.php

            message(0, lang('create_successfully'));
        }
        break;
    case 'update':
        FALSE === group_access($gid, 'managecreatethread') AND message(1, lang('user_group_insufficient_privilege'));

        // hook admin_content_update_start.php

        $tid = param('tid', 0);
        empty($tid) AND message(1, lang('data_malformation'));

        $_fid = param('fid', 0);
        $page = param('page', 0);

        $thread = well_thread_read($tid);
        empty($thread) AND message(-1, lang('thread_not_exists'));
        $fid = $thread['fid'];

        // hook admin_content_update_before.php

        $thread_data = data_read($tid);

        // hook admin_content_update_after.php

        // 主题绑定了哪些flag array(1,2,3)
        list($index_flagids, $category_flagids, $forum_flagids, $flagarr) = flag_forum_by_tid($tid);

        // hook admin_content_update_end.php

        if ('GET' == $method) {

            // 插件预留
            $extra = array('tid' => $tid, 'page' => $page);

            // hook admin_content_update_get_start.php

            $thread_data['message'] = str_replace('="upload/', '="'.admin_attach_path().'upload/', $thread_data['message']);
            $thread_data['message'] = htmlspecialchars($thread_data['message']);
            ($uid != $thread['uid']) AND $thread_data['message'] = xn_html_safe($thread_data['message']);

            $forum = array_value($forumlist, $fid);
            $model = array_value($forum, 'model', 0);

            // hook admin_content_update_get_forum_after.php

            $index_flag = flag_forum_show(0);
            $index_flag AND flag_filter($index_flag);

            // hook admin_content_update_get_flag_after.php

            // 获取主图
            $thread['icon_fmt'] = admin_attach_path() . $thread['icon_fmt'];

            // 初始化附件
            $_SESSION['tmp_thumbnail'] = $_SESSION['tmp_website_files'] = array();

            // hook admin_content_update_get_icon_after.php

            $picture = $config['picture_size'];
            $pic_width = $picture['width'];
            $pic_height = $picture['height'];

            // hook admin_content_update_get_files_before.php

            $attachlist = array();
            $imagelist = array();
            $input = array();
            $filelist = array();
            $thread['files'] AND list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($tid, $thread['files']);

            $tagstr = $thread['tag_fmt'] ? implode(',', $thread['tag_fmt']) . ',' : '';

            // hook admin_content_update_get_files_after.php

            $form_title = lang('edit');
            $form_action = url('content-update', $extra, TRUE);
            $form_submit_txt = lang('submit');
            $form_subject = $thread['subject'];
            $form_message = $thread_data['message'];
            $form_brief = $thread['brief'];
            $form_doctype = $thread_data['doctype'];
            $form_link = 10 == $thread['type'] ? 'checked="checked"' : '';
            $form_closed = $thread['closed'] >= 1 ? 'checked="checked"' : '';
            $form_keyword = $thread['keyword'];
            $form_description = $thread['description'];
            empty($filelist) || $filelist += (array)_SESSION('tmp_website_files');
            $thumbnail = $thread['icon_fmt'];

            $setting = array_value($config, 'setting');
            $thumbnail_on = '';
            $save_image = 1 == array_value($setting, 'save_image_on', 0) ? 'checked="checked"' : '';
            // hook admin_content_update_get_form_after.php

            $breadcrumb_flag = lang('edit');

            // hook admin_content_update_get_after.php

            $header['title'] = lang('edit');
            $header['mobile_title'] = lang('edit');
            $referer = http_referer();

            // 过滤版块相关数据
            $forumlist = forum_filter($forumlist);
            $safe_token = well_token_set($uid);

            // hook admin_content_update_get_end.php

            // 可以根据自己设计的添加内容界面绑定栏目，绑定模型，显示不同的界面
            switch ($model) {
                /*case '0':
                    break;*/
                // hook admin_content_update_get_case_end.php
                default:
                    include _include(ADMIN_PATH . 'view/htm/content_post.htm');
                    break;
            }

        } elseif ('POST' == $method) {

            // 验证token
            if (array_value($conf, 'message_token', 0)) {
                $safe_token = param('safe_token');
                FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));
            }

            // hook admin_content_update_post_start.php

            $arr = array();

            $subject = param('subject');
            $subject = filter_all_html($subject);
            empty($subject) AND message('subject', lang('please_input_subject'));

            xn_strlen($subject) > 128 AND message('subject', lang('subject_length_over_limit', array('maxlength' => 128)));
            // 过滤标题 关键词

            // hook admin_content_update_post_subject_before.php

            if ($subject != $thread['subject']) {
                $arr['subject'] = $subject;
                $thread['sticky'] > 0 AND cache_delete('sticky_thread_list');
            }

            // hook admin_content_update_post_subject_after.php

            $link = param('link', 0);
            if ($link && 10 != $thread['type']) {
                $arr['type'] = 10;
            } elseif (empty($link) && 10 == $thread['type']) {
                $arr['type'] = 0;
            }

            // hook admin_content_update_post_link_after.php

            $closed = param('closed', 0);
            $closed != $thread['closed'] AND $arr['closed'] = $closed;

            // hook admin_content_update_post_closed_after.php

            $doctype = param('doctype', 0);
            $doctype > 10 AND message(1, lang('doc_type_not_supported'));

            // hook admin_content_update_post_message_before.php

            $message = $_message = '';
            if (0 == $link) {
                $message = param('message', '', FALSE);
                $message = trim($message);
                empty($message) ? message('message', lang('please_input_message')) : xn_strlen($message) > 2028000 AND message('message', lang('message_too_long'));

                $_message = htmlspecialchars(filter_all_html($message), ENT_QUOTES);
                // 过滤内容 关键词

                // hook admin_content_update_post_message_center.php
            }

            // hook admin_content_update_post_message_after.php

            $brief_auto = param('brief_auto', 0);
            $brief = param('brief');
            if ($brief) {
                // 过滤简介 关键词
                // hook admin_content_update_post_brief_before.php

                xn_strlen($brief) > 120 AND $brief = xn_substr($brief, 0, 120);
            } else {
                $brief = ($brief_auto AND $_message) ? xn_html_safe(xn_substr($_message, 0, 120)) : '';
            }
            $brief and $brief = filter_all_html($brief);

            // hook admin_content_update_post_brief_after.php

            $brief != $thread['brief'] AND $arr['brief'] = $brief;

            // hook admin_content_update_post_keyword_before.php

            $keyword = param('keyword');
            // 过滤内容 关键词
            // hook admin_content_update_post_keyword_center.php
            // 超出则截取
            xn_strlen($keyword) > 64 AND $keyword = xn_substr($keyword, 0, 64);

            $keyword != $thread['keyword'] AND $arr['keyword'] = $keyword;

            // hook admin_content_update_post_keyword_after.php

            $description = param('description');
            // 过滤内容 关键词
            // hook admin_content_update_post_description_before.php
            // 超出则截取
            xn_strlen($description) > 120 AND $description = xn_substr($description, 0, 120);
            $description != $thread['description'] AND $arr['description'] = $description;

            // hook admin_content_update_post_fid_before.php

            $newfid = param('fid', 0);
            $forum = array_value($forumlist, $fid);
            empty($forum) AND message('fid', lang('forum_not_exists'));

            // hook admin_content_update_post_fid_center.php

            if ($fid != $newfid) {

                // hook admin_content_update_post_fid_access.php

                if ($thread['uid'] != $uid && !forum_access_mod($fid, $gid, 'allowupdate')) message(1, lang('user_group_insufficient_privilege'));

                // hook admin_content_update_post_fid_update.php

                forum__update($newfid, array('threads+' => 1));
                forum_update($thread['fid'], array('threads-' => 1));
                sticky_thread_update_by_tid($tid, $newfid);

                thread_tid_update($tid, $newfid);

                $arr['fid'] = $newfid;
            }

            $thumbnail = param('thumbnail', 0);
            // 1 删除主图
            $delete_pic = param('delete_pic', 0);
            // hook admin_content_update_post_fid_after.php
            $upload_thumbnail = well_attach_assoc_type(0);
            if (!empty($upload_thumbnail) || $delete_pic || $thumbnail) {
                // Ym变更删除旧图
                $attach_dir_save_rule = array_value($conf, 'well_attach_dir_save_rule', 'Ym');
                $old_day = $thread['icon'] ? date($attach_dir_save_rule, $thread['icon']) : '';

                // hook admin_content_update_post_unlink_before.php

                if ($delete_pic || $upload_thumbnail || $thumbnail) {
                    $file = $conf['upload_path'] . 'thumbnail/' . $old_day . '/' . $thread['uid'] . '_' . $tid . '_' . $thread['icon'] . '.jpeg';
                    is_file($file) AND unlink($file);
                }

                // hook admin_content_update_post_unlink_after.php

                if ($delete_pic) $arr['icon'] = 0;

                if ($upload_thumbnail) {
                    $arr['icon'] = $time;
                    // 关联主图 assoc thumbnail主题主图 post:内容图片或附件
                    $thumbnail_assoc = array('tid' => $tid, 'uid' => $thread['uid'], 'type' => $thread['type'], 'assoc' => 'thumbnail');
                    // hook admin_content_update_post_attach_before.php
                    well_attach_assoc_post($thumbnail_assoc);
                    unset($thumbnail_assoc);
                }

                if ($thumbnail) {
                    // 内容中上传的图片
                    if (well_attach_assoc_type('post') && preg_match_all('#<img.+src=\"?(.+\.(jpg|jpeg|gif|png))\"?.+>#i', $message)) {
                        $arr['icon'] = $time;
                        $create_thumbnail = array('tid' => $tid, 'uid' => $thread['uid'], 'fid' => $fid);
                        well_attach_create_thumbnail($create_thumbnail);
                        unset($create_thumbnail);
                    } elseif (preg_match_all('#<img[^>]+src="(.*?)"#i', $message, $match)) {
                        $arr['icon'] = $time;
                        $i = 0;
                        foreach ($match[1] as $_url) {
                            if (1 == ++$i) {
                                if (FALSE !== strpos($_url, 'http')) {
                                    // 本地化
                                    well_save_remote_image(array('tid' => $tid, 'fid' => $fid, 'uid' => $thread['uid'], 'message' => $message, 'thumbnail' => $thumbnail, 'save_image' => 0));
                                } else {
                                    // 裁切第一张图xn_strlen($_url)
                                    $destpath = APP_PATH . xn_substr($_url, strpos($_url, 'upload/'), xn_strlen($_url));

                                    $picture = $config['picture_size'];
                                    $forum = array_value($forumlist, $fid);
                                    $picture = isset($forum['thumbnail']) ? $forum['thumbnail'] : $picture['picture_size'];
                                    $pic_width = $picture['width'];
                                    $pic_height = $picture['height'];

                                    $thumbnail_path = $conf['upload_path'] . 'thumbnail/' . date($attach_dir_save_rule, $time);
                                    is_dir($thumbnail_path) || mkdir($thumbnail_path, 0777, TRUE);

                                    $tmp_file = $thumbnail_path . '/' . $thread['uid'] . '_' . $tid . '_' . $time . '.jpeg';

                                    'clip' == array_value($conf, 'upload_resize', 'clip') ? well_image_clip_thumb($destpath, $tmp_file, $pic_width, $pic_height) : well_image_thumb($destpath, $tmp_file, $pic_width, $pic_height);
                                }
                            }
                        }
                    }
                }
            }

            // hook admin_content_update_post_attach_after.php

            $tags = param('tags', '', FALSE);
            $tags = xn_html_safe(filter_all_html($tags));
            // 过滤标签 关键词
            // hook admin_content_update_post_tag_center.php

            $tag_json = well_tag_post_update($tid, $fid, $tags, $thread['tag_fmt']);
            if (xn_strlen($subject) >= 120) {
                $s = xn_substr($tag_json, -1, NULL);
                if ('}' != $s) {
                    $len = mb_strripos($tag_json, ',', 0, 'UTF-8');
                    $tag_json = $len ? xn_substr($tag_json, 0, $len) . '}' : '';
                }
            }

            $tag_json != $thread['tag'] AND $arr['tag'] = $tag_json;

            // hook admin_content_update_post_tag_after.php

            // 首页flag
            $flag_index_arr = array_filter(param('index', array()));
            // 首页需要再创建的
            $new_index_flagids = empty($flag_index_arr) ? array() : array_diff($flag_index_arr, $index_flagids);
            // 返回首页被取消的flagid
            $old_index_flagids = array_diff($index_flagids, $flag_index_arr);

            // 频道flag
            $flag_cate_arr = array_filter(param('category', array()));
            // 频道需要再创建的
            $new_cate_flagids = empty($flag_cate_arr) ? array() : array_diff($flag_cate_arr, $category_flagids);
            // 返回频道被取消的flagid
            $old_cate_flagids = array_diff($category_flagids, $flag_cate_arr);

            // 栏目flag
            $flag_forum_arr = array_filter(param('forum', array()));
            // 需要再创建的
            $new_forum_flagids = empty($flag_forum_arr) ? array() : array_diff($flag_forum_arr, $forum_flagids);
            // 返回被取消的flagid
            $old_forum_flagids = array_diff($forum_flagids, $flag_forum_arr);

            $flags = $thread['flags'] + count($new_index_flagids) + count($new_cate_flagids) + count($new_forum_flagids) - count($old_index_flagids) - count($old_cate_flagids) - count($old_forum_flagids);
            $thread['flags'] != $flags AND $arr['flags'] = $flags;

            // hook admin_content_update_post_arr_after.php

            !empty($arr) && FALSE === well_thread_update($tid, $arr) AND message(-1, lang('update_thread_failed'));
            unset($arr);

            // hook admin_content_update_post_before.php

            $update = array();

            // $link = 1 为站外链接 无需更新数据表
            if (0 == $link) {
                $tmp_file = well_attach_assoc_type('post');
                if (md5($message) != md5($thread_data['message']) || !empty($tmp_file)) {
                    // 如果开启云储存或使用图床，需要把内容中的附件链接替换掉
                    $message = data_message_replace_url($tid, $message);

                    $save_image = param('save_image', 0);
                    $save_image AND $message = well_save_remote_image(array('tid' => $tid, 'fid' => $fid, 'uid' => $thread['uid'], 'message' => $message));

                    // 关联附件
                    $attach = array('tid' => $tid, 'uid' => $thread['uid'], 'assoc' => 'post', 'images' => $thread['images'], 'files' => $thread['files'], 'message' => $message);
                    $message = well_attach_assoc_post($attach);
                    unset($attach);

                    $update = array('tid' => $tid, 'gid' => $gid, 'doctype' => $doctype, 'message' => $message);
                    // hook admin_content_data_update_before.php
                }
            }

            // hook admin_content_data_update.php

            !empty($update) && FALSE === data_update($tid, $update) AND message(-1, lang('update_post_failed'));
            unset($update);

            // hook admin_content_update_post_center.php

            // 首页flag
            !empty($new_index_flagids) && FALSE === flag_create_thread(0, 1, $tid, $new_index_flagids) AND message(-1, lang('create_failed'));

            // 返回首页被取消的flagid
            !empty($old_index_flagids) AND flag_thread_delete_by_ids($old_index_flagids, $flagarr);

            // 频道flag
            $forum['fup'] && !empty($new_cate_flagids) && FALSE === flag_create_thread($forum['fup'], 2, $tid, $new_cate_flagids) AND message(-1, lang('create_failed'));
            // 返回频道被取消的flagid
            !empty($old_cate_flagids) AND flag_thread_delete_by_ids($old_cate_flagids, $flagarr);

            // 栏目flag
            !empty($new_forum_flagids) && FALSE === flag_create_thread($fid, 3, $tid, $new_forum_flagids) AND message(-1, lang('create_failed'));
            // 返回被取消的flagid
            !empty($old_forum_flagids) AND flag_thread_delete_by_ids($old_forum_flagids, $flagarr);

            // hook admin_content_update_post_end.php

            message(0, lang('update_successfully'));
        }
        break;
    // hook admin_content_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_content_end.php

?>