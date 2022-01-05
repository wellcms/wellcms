<?php
/*
 * Copyright (C) www.wellcms.cn
 */

!defined('DEBUG') and exit('Access Denied.');

FALSE === group_access($gid, 'managecategory') and message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

$columnlist = all_category($forumlist);

// hook admin_flag_start.php

switch ($action) {
    // hook admin_flag_case_start.php
    case 'list':
        // hook admin_flag_list_start.php

        if ('GET' == $method) {

            // hook admin_flag_list_get_start.php

            $fid = param('fid', 0);
            $page = param('page', 1);
            $orderby = param('orderby', 0); // 0降序 1自定义排序降序
            $pagesize = $conf['pagesize'];
            $extra = array('page' => $page, 'orderby' => $orderby); // 插件预留
            $fid and $extra += array('fid' => $fid);

            // hook admin_flag_list_get_forum_before.php

            $forum = forum_read($fid);

            // hook admin_flag_list_get_forum_after.php

            $n = $fid ? $forum['flags'] : flag_count($fid);

            $n and $arrlist = flag_find($fid, $page, $pagesize);

            // hook admin_flag_list_get_before.php

            $orderby && $n && $arrlist = array_multisort_key($arrlist, 'rank', FALSE, 'flagid');

            // hook admin_flag_list_get_after.php

            $pagination = pagination(url('flag-list', $extra, TRUE), $n, $page, $pagesize);

            $safe_token = well_token_set($uid);

            $header['title'] = lang('flag');
            $header['mobile_title'] = lang('flag');

            // hook admin_flag_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/flag_list.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            $type = param('type', 0);

            // hook admin_flag_list_post_start.php

            if (1 == $type) {
                // 排序
                $arr = _POST('data');
                empty($arr) and message(1, lang('data_malformation'));

                // hook admin_flag_list_rank_post_start.php

                foreach ($arr as &$val) {
                    $rank = intval($val['rank']);
                    $flagid = intval($val['flagid']);
                    intval($val['oldrank']) != $rank && $flagid and $r = flag_update($flagid, array('rank' => $rank));
                    // hook admin_flag_list_rank_post_before.php
                }

                // hook admin_flag_list_rank_post_end.php

                message(0, lang('update_successfully'));

            } else {

                // 删除
                $fid = param('fid', 0);
                $flagid = param('flagid', 0);
                empty($flagid) and message(1, lang('data_malformation'));

                // hook admin_flag_list_post_before.php

                if ($fid) {
                    $forum = forum_read($fid);

                    $flagarr = $forum['flagstr'] ? explode(',', $forum['flagstr']) : array();
                    $key = array_search($flagid, $flagarr);
                    unset($flagarr[$key]);
                    forum_update($fid, $arr = array('flags-' => 1, 'flagstr' => trim(implode(',', $flagarr), ',')));
                } else {

                    $flagarr = $config['index_flagstr'] ? explode(',', $config['index_flagstr']) : array();
                    $key = array_search($flagid, $flagarr);
                    unset($flagarr[$key]);

                    $config['index_flags'] -= 1;
                    $config['index_flagstr'] = trim(implode(',', $flagarr), ',');

                    setting_set('conf', $config);
                }

                // hook admin_flag_list_post_after.php

                // 清空主题 大数据量超时 暂时这样处理，以后优化再改成遍历主键删除
                FALSE === flag_thread_delete_by_flagid($flagid) and message(-1, lang('delete_failed'));

                FALSE === flag_delete($flagid) and message(-1, lang('delete_failed'));

                $iconfile = $conf['upload_path'] . 'flag/' . $flagid . '.png';
                file_exists($iconfile) and unlink($iconfile);

                // hook admin_flag_list_post_end.php

                message(0, lang('delete_successfully'));
            }
        }
        break;
    case 'create':
        // hook admin_flag_create_start.php

        if ('GET' == $method) {

            // hook admin_flag_create_get_start.php

            $fid = param('fid', 0);
            $forum = forum_read($fid);
            $extra = array(); // 插件预留
            $fid and $extra += array('fid' => $fid);

            // hook admin_flag_create_get_before.php

            $input = array();
            $input['name'] = form_text('name', '', FALSE, lang('customize_name'));
            $input['display'] = form_radio_yes_no('display');
            $input['number'] = form_text('number', '', FALSE, lang('display_number'));

            $thumbnail = admin_view_path() . 'img/nopic.png';

            // hook admin_flag_create_get_middle.php

            $breadcrumb_flag = lang('increase') . lang('customize');
            $disabled = '';
            $form_action = url('flag-create', $extra, TRUE);

            // hook admin_flag_create_get_after.php

            $safe_token = well_token_set($uid);

            $header['title'] = lang('increase') . lang('flag') . '-' . ($fid ? $forum['name'] : lang('flag'));
            $header['mobile_title'] = lang('increase') . lang('flag') . '-' . ($fid ? $forum['name'] : lang('flag'));

            // hook admin_flag_create_get_end.php

            include _include(ADMIN_PATH . 'view/htm/flag_post.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            // hook admin_flag_create_post_start.php

            $fid = _POST('fid', 0);
            $name = param('name');
            $name = filter_all_html($name);
            empty($name) and message('name', lang('flag_empty'));

            $name = htmlspecialchars($name);
            // 查询该属性是否存在
            $read = flag_read_by_name_and_fid($name, $fid);
            $read and message('name', lang('flag_existed'));

            // hook admin_flag_create_post_before.php

            $display = param('display', 0);
            $number = param('number', 10);

            $flagarr = array();
            if ($display && $fid) {
                $forum = array_value($forumlist, $fid);
                $flagarr = explode(',', $forum['flagstr']);
                // 显示最大限制20个属性
                count($flagarr) >= 20 and message(1, lang('display_limit_number', array('n' => 20)));
            }

            // 首页
            if ($display && empty($fid)) {
                $flagarr = explode(',', $config['index_flagstr']);
                // 显示最大限制20个属性
                count($flagarr) >= 20 and message(1, lang('display_limit_number', array('n' => 20)));
            }

            $delete = param('delete', 0);
            $icon = param('icon');

            // hook admin_flag_create_post_middle.php

            $number = $display ? $number : 0;
            $arr = array('name' => $name, 'fid' => $fid, 'display' => $display, 'number' => $number, 'create_date' => $time);

            empty($delete) and $icon and $arr['icon'] = $time;

            // hook admin_flag_create_post_array.php

            $flagid = flag_create($arr);
            FALSE === $flagid and message(-1, lang('create_failed'));

            if (0 == $delete && $icon) {
                $data = substr($icon, strpos($icon, ',') + 1);
                $data = base64_decode($data);
                $path = $conf['upload_path'] . 'flag/';
                !is_dir($path) and mkdir($path, 0777, TRUE);
                $iconfile = $path . $flagid . '.png';
                file_put_contents($iconfile, $data);
            }

            // hook admin_flag_create_post_after.php
            $update = array('flags+' => 1);
            if ($display && $fid) {
                $flagarr[] = $flagid;
                $flagarr = array_unique($flagarr);
                $flagstr = implode(',', $flagarr);
                $update['flagstr'] = trim($flagstr, ',');
            }

            if ($fid) {
                forum_update($fid, $update);
            } else {
                // 首页flag统计
                $config['index_flags'] += 1;
                $flagarr[] = $flagid;
                $flagarr = array_unique($flagarr);
                $flagstr = implode(',', $flagarr);
                // 首页显示的flag字串
                $config['index_flagstr'] = trim($flagstr, ',');
                setting_set('conf', $config);
            }

            // hook admin_flag_create_post_end.php

            message(0, lang('create_successfully'));
        }
        break;
    case 'update':
        // hook admin_flag_update_start.php

        $fid = param('fid', 0);
        $forum = array_value($forumlist, $fid);

        // hook admin_flag_update_before.php

        $flagid = param('flagid', 0);

        $read = flag_read_cache($flagid);
        empty($read) and message(-1, lang('flag_empty'));

        // hook admin_flag_update_end.php

        if ('GET' == $method) {

            $extra = array('flagid' => $flagid); // 插件预留
            $fid and $extra += array('fid' => $fid);

            // hook admin_flag_update_get_start.php

            $input = array();
            $input['name'] = form_text('name', $read['name'], FALSE, lang('customize_name'));
            $input['display'] = form_radio_yes_no('display', $read['display']);
            $input['number'] = form_text('number', $read['number'], FALSE, lang('display_number'));

            $thumbnail = admin_access_file($read['icon'], $read['icon_fmt']);

            // hook admin_flag_update_get_before.php

            $breadcrumb_flag = lang('edit') . lang('customize');
            $disabled = 'disabled="disabled"';
            $form_action = url('flag-update', $extra, TRUE);

            // hook admin_flag_update_get_after.php

            $safe_token = well_token_set($uid);

            $header['title'] = lang('edit') . lang('flag');
            $header['mobile_title'] = lang('edit') . lang('flag');

            // hook admin_flag_update_get_end.php

            include _include(ADMIN_PATH . 'view/htm/flag_post.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            // hook admin_flag_update_post_start.php

            $update = array();
            $name = param('name');
            $name = filter_all_html($name);
            if ($name && $name != $read['name']) {
                // 查询该属性是否存在
                flag_read_by_name_and_fid($name, $read['fid']) and message('name', lang('flag_existed'));

                $update['name'] = $name;
            }

            // hook admin_flag_update_post_before.php

            $display = param('display', 0);
            // 原来显示 现在不显示 清理
            if ($display != $read['display']) {
                //  栏目
                if ($read['fid']) {
                    $forum = array_value($forumlist, $read['fid']);
                    $flagarr = $forum['flagstr'] ? explode(',', $forum['flagstr']) : array();
                    $flagarr = array_unique($flagarr);

                    if ($read['display']) {
                        $key = array_search($read['flagid'], $flagarr);
                        unset($flagarr[$key]);
                    } else {
                        // 改为显示 追加
                        count($flagarr) >= 20 and message(1, lang('display_limit_number', array('n' => 20)));
                        $flagarr[] = $read['flagid'];
                    }

                    forum_update($fid, array('flagstr' => trim(implode(',', $flagarr), ',')));
                } else {

                    $flagarr = explode(',', $config['index_flagstr']);

                    if ($read['display']) {
                        $key = array_search($read['flagid'], $flagarr);
                        unset($flagarr[$key]);
                    } else {
                        // 改为显示 追加
                        count($flagarr) >= 20 and message(1, lang('display_limit_number', array('n' => 20)));
                        $flagarr[] = $read['flagid'];
                    }
                    $flagarr = array_unique($flagarr);
                    $flagstr = implode(',', $flagarr);
                    // 首页显示的flag字串
                    $config['index_flagstr'] = trim($flagstr, ',');
                    setting_set('conf', $config);
                }

                $update['display'] = $display;
            }

            // hook admin_flag_update_post_middle.php

            $number = param('number', 10);
            $update['number'] = $display ? $number : 0;

            $delete = param('delete', 0);
            if ($delete) {
                $update['icon'] = 0;
                $iconfile = $conf['upload_path'] . 'flag/' . $flagid . '.png';
                is_file($iconfile) and unlink($iconfile);
            }

            $icon = param('icon');
            if (0 == $delete && $icon) {
                $update['icon'] = $time;
                $data = substr($icon, strpos($icon, ',') + 1);
                $data = base64_decode($data);
                $path = $conf['upload_path'] . 'flag/';
                !is_dir($path) and mkdir($path, 0777, TRUE);

                file_put_contents($path . $flagid . '.png', $data);
            }

            // hook admin_flag_update_post_after.php

            !empty($update) and flag_update($read['flagid'], $update);

            // hook admin_flag_update_post_end.php

            message(0, lang('update_successfully'));
        }
        break;
    case 'read':
        // hook admin_flag_read_start.php

        $flagid = param('flagid', 0);
        $read = flag_read_cache($flagid);
        empty($read) and message(-1, lang('flag_empty'));

        // hook admin_flag_read_end.php

        if ('GET' == $method) {

            // hook admin_flag_read_get_start.php

            $page = param('page', 1);
            $pagesize = 25;
            // 插件预留
            $extra = array('page' => '{page}', 'flagid' => $flagid);

            // hook admin_flag_read_get_before.php

            if ($read['count'] > 0) {

                $arrlist = flag_thread_find_by_flagid($flagid, $page, $pagesize);

                // hook admin_flag_read_get_flag_after.php

                $idarr = arrlist_key_values($arrlist, 'tid', 'id');
                $tidarr = arrlist_values($arrlist, 'tid');
                // 遍历flag所有主题

                // hook admin_flag_read_get_thread_before.php

                $threadlist = well_thread_find($tidarr, $pagesize);

                // hook admin_flag_read_get_thread_before.php
            }

            // hook admin_flag_read_get_before.php

            $pagination = pagination(url('flag-read', $extra, TRUE), $read['count'], $page, $pagesize);

            // hook admin_flag_read_get_after.php

            $header['title'] = lang('flag_list');
            $header['mobile_title'] = lang('flag_list');

            // hook admin_flag_read_get_end.php

            include _include(ADMIN_PATH . 'view/htm/flag_read_list.htm');

        } elseif ('POST' == $method) {

            // hook admin_flag_read_post_start.php

            $type = param('type', 0);
            /*$type AND $id = param('id', array());
            empty($type) AND $id = param(3, 0);
            empty($id) AND message(1, lang('data_malformation'));*/

            if ($type) {
                // 一维数组
                $id = param('id', array());
                empty($id) and message(1, lang('data_malformation'));

                // 删除的同一个flag下的主题
                flag_update($flagid, array('count-' => count($id)));

                $arrlist = flag_thread_find_by_id($id, 1, count($id));
                //$tidarr = array();
                foreach ($arrlist as $val) {
                    //$tidarr[] = $val['tid'];
                    well_thread_update($val['tid'], array('flags-' => 1));
                }

                // hook admin_flag_read_post_before.php

            } else {
                $id = param('id', 0);
                empty($id) and message(1, lang('data_malformation'));

                $thread = flag_thread__read($id);
                well_thread_update($thread['tid'], array('flags-' => 1));
                flag_thread_delete_by_tid($thread['tid']);
            }

            // hook admin_flag_read_post_after.php

            FALSE === flag_thread_delete($id) and message(-1, lang('delete_failed'));

            // hook admin_flag_read_post_end.php

            message(0, lang('delete_successfully'));
        }
        break;
    // hook admin_flag_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_flag_end.php

?>