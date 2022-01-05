<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') and exit('Access Denied.');

include _include(APP_PATH . 'model/operate.func.php');

$action = param(1);

// hook operate_start.php

// 后台访问前台文件
$backstage = param('backstage', 0);
$url_path = '';
if ($backstage) {
    $conf['path'] = $conf['url_rewrite_on'] > 1 ? $conf['path'] : '../';
    $url_path = $conf['url_rewrite_on'] > 1 ? '' : '../';
}

// hook operate_before.php

switch ($action) {
    // hook operate_case_start.php
    case 'sticky':
        if ('GET' == $method) {

            // hook operate_sticky_get_start.php

            $fid = param('fid', 0);
            if (isset($forumlist[$fid])) {
                $forum = $forumlist[$fid];
                $fup = 1 == $forum['category'] ? $forum['fid'] : $forum['fup'];
            } else {
                $fup = 0;
            }

            // hook operate_sticky_get_center.php

            $safe_token = well_token_set($uid);
            $header['title'] = lang('top');
            $form_action = $url_path.url('operate-sticky');

            // hook operate_sticky_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['form_action'] = $form_action;
                $apilist['fid'] = $fid;
                $apilist['fup'] = $fup;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('operate_sticky'));
            }

        } elseif ('POST' == $method) {

            $backstage && FALSE === group_access($gid, 'managesticky') and message(1, lang('user_group_insufficient_privilege'));

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            // hook operate_sticky_start.php

            $sticky = param('sticky', 0);

            $tidarr = param('tidarr', array(0));
            empty($tidarr) and message(1, lang('please_choose_thread'));

            // hook operate_sticky_before.php

            $threadlist = well_thread_find_by_tids($tidarr);

            // hook operate_sticky_after.php

            $arr_create = array();
            $arr_delete = array();
            $arr = array();
            $index_stickys = 0;
            foreach ($threadlist as &$thread) {
                $fid = $thread['fid'];
                $tid = $thread['tid'];
                if (!$fid) continue;

                // hook operate_sticky_log_create_start.php

                if (2 == $sticky && empty($forumlist[$fid]['fup'])) continue;

                if (3 == $sticky && (1 != $gid && 2 != $gid)) continue;

                if ($sticky == $thread['sticky']) continue;

                if (FALSE === forum_access_mod($fid, $gid, 'allowtop')) continue;
                // hook operate_sticky_log_create_before.php

                $arr[$fid] = isset($arr[$fid]) ? $arr[$thread['fid']] : 0;

                if ($sticky > 0) {

                    // 全站置顶
                    3 == $sticky and $index_stickys += 1;
                    3 == $thread['sticky'] and $sticky < 3 and $index_stickys -= 1;

                    if (!$thread['sticky']) {
                        $arr_create[$fid] = $fid;
                        $arr[$fid] += 1;
                    }

                    // 创建或更新置顶
                    sticky_thread_change($tid, $sticky, $thread);

                } else {
                    // 清理置顶
                    sticky_thread_delete($tid);

                    $arr_delete[$tid] = $fid;

                    3 == $thread['sticky'] and $index_stickys -= 1;

                    $thread['sticky'] and $arr[$fid] -= 1;
                }

                // hook operate_sticky_log_create_center.php

                $operate = array(
                    'type' => ($sticky ? 3 : 4),
                    'uid' => $uid,
                    'tid' => $tid,
                    'subject' => $thread['subject'],
                    'comment' => '',
                    'create_date' => $time
                );

                // hook operate_sticky_log_create_after.php

                operate_create($operate);

                // hook operate_sticky_log_create_end.php
            }

            if (0 != $index_stickys) {
                $config['index_stickys'] += $index_stickys;
                setting_set('conf', $config);
            }

            if (!empty($arr)) {
                foreach ($arr as $k => $v) {
                    forum_update($k, array('tops+' => $v));
                }
            }

            // hook operate_sticky_end.php

            message(0, lang('set_completely'));
        }
        break;
    case 'close':
        if ('GET' == $method) {

            // hook operate_close_get_start.php

            $safe_token = well_token_set($uid);
            $header['title'] = lang('close_thread');
            $form_action = $url_path.url('operate-close');

            // hook operate_close_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['form_action'] = $form_action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('operate_close'));
            }

        } elseif ('POST' == $method) {

            $backstage && FALSE === group_access($gid, 'manageupdatethread') and message(1, lang('user_group_insufficient_privilege'));

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            $close = param('close', 0);

            $tidarr = param('tidarr', array(0));
            empty($tidarr) and message(1, lang('please_choose_thread'));
            $threadlist = well_thread_find_by_tids($tidarr);

            // hook operate_close_start.php

            $tids = array();

            if (1 == $close) {
                $type = 5;
            } elseif (2 == $close) {
                $type = 6;
            } else {
                $type = 7;
            }

            foreach ($threadlist as &$thread) {
                $tid = $thread['tid'];
                $fid = $thread['fid'];

                $thread['sticky'] and $thread['closed'] != $close and cache_delete('sticky_thread_list');

                if ($fid && forum_access_mod($fid, $gid, 'allowtop')) {

                    $tids[] = $thread['tid'];

                    // hook operate_close_log_create_before.php

                    $arr = array('type' => $type, 'uid' => $uid, 'tid' => $tid, 'subject' => $thread['subject'], 'comment' => '', 'create_date' => $time);

                    // hook operate_close_log_create_after.php

                    operate_create($arr);
                }
            }

            !empty($tids) and well_thread_update($tids, array('closed' => $close));

            // hook operate_close_end.php

            message(0, lang('set_completely'));
        }
        break;
    case 'delete':
        if ('GET' == $method) {

            // hook operate_delete_get_start.php
            
            $safe_token = well_token_set($uid);
            $header['title'] = lang('close_thread');
            $form_action = $url_path.url('operate-delete');

            // hook operate_delete_get_end.php
            
            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['form_action'] = $form_action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('operate_delete'));
            }

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            if ($backstage) {
                FALSE === group_access($gid, 'managedeletethread') and message(1, lang('user_group_insufficient_privilege'));
            } else {
                $allowdelete = group_access($gid, 'allowdelete') || group_access($gid, 'allowuserdelete') || 1 == $gid;
                empty($allowdelete) and message(1, lang('user_group_insufficient_privilege'));
            }

            // hook operate_delete_start.php

            $tid = param(2, 0);

            // hook operate_delete_before.php

            if ($tid) {
                // 单条删除
                // hook operate_delete_content_before.php
                well_thread_delete_all($tid);
                // hook operate_delete_content_after.php
            } else {
                // 选择框批量删除
                $tidarr = param('tidarr', array(0));
                empty($tidarr) and message(1, lang('please_choose_thread'));
                // hook operate_delete_tidarr.php
                well_thread_delete_all($tidarr);
                // hook operate_delete_after.php
            }

            // hook operate_delete_end.php

            message(0, lang('delete_completely'));
        }
        break;
    case 'move':
        if ('GET' == $method) {

            // hook operate_move_get_start.php

            $fid = param('fid', 0);

            $cond = array('type' => 1, 'category' => 0);

            if (isset($forumlist[$fid])) {
                $forum = $forumlist[$fid];
                $forum['model'] and $cond['model'] = $forum['model'];
            }

            $forumlist_show = arrlist_cond_orderby($forumlist_show, $cond, array(), 1, 1000);
            $forumarr = arrlist_key_values($forumlist_show, 'fid', 'name');

            $safe_token = well_token_set($uid);
            $header['title'] = lang('move');
            $form_action = $url_path.url('operate-move');

            // hook operate_move_get_end.php

            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['fid'] = $fid;
                $apilist['forumarr'] = $forumarr;
                $apilist['form_action'] = $form_action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(theme_load('operate_move'));
            }

        } elseif ('POST' == $method) {

            $backstage && (FALSE === group_access($gid, 'manageupdatethread') || FALSE === group_access($gid, 'allowmove')) and message(1, lang('user_group_insufficient_privilege'));

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            $tidarr = param('tidarr', array(0));
            empty($tidarr) and message(1, lang('please_choose_thread'));
            //$threadlist = well_thread_find_by_tids($tidarr);
            $threadlist = well_thread__find(array('tid' => $tidarr), array('tid' => 1), 1, count($tidarr));

            $newfid = param('newfid', 0);
            forum_read($newfid) || message(1, lang('forum_not_exists'));

            // hook operate_move_start.php

            $tids = array();
            $fids = array();
            $thread_tid = 0;
            // hook operate_move_before.php
            foreach ($threadlist as &$thread) {
                $tid = $thread['tid'];
                $fid = $thread['fid'];
                if (!$fid) continue;

                // hook operate_move_foreach_start.php

                $forum = array_value($forumlist, $fid);
                //if (empty($forum)) continue;

                // hook operate_move_foreach_before.php

                if (forum_access_mod($fid, $gid, 'allowmove')) {

                    if ($fid == $newfid) continue;

                    switch ($forum['model']) {
                        case '0': // 移动文章
                            $thread_tid = 1;
                            break;
                        // hook operate_move_foreach_case.php
                    }

                    $tids[] = $tid;
                    $fids[$tid] = $fid;

                    // hook operate_move_foreach_center.php

                    $arr = array('type' => 2, 'uid' => $uid, 'tid' => $tid, 'subject' => $thread['subject'], 'create_date' => $time);

                    // hook operate_move_foreach_end.php

                    operate_create($arr);
                }
            }

            // hook operate_move_fids_before.php

            if (!empty($fids)) {
                // 旧栏目主题数需要更新
                $fids = array_count_values($fids);
                foreach ($fids as $k => $v) {
                    forum__update($k, array('threads-' => $v));
                }
            }

            // hook operate_move_thread_update_before.php

            // 主题主表 附表 回复 所属栏目更新
            if (!empty($tids)) {

                // hook operate_move_thread_update_middle.php

                well_thread_update_all($tids, array('fid' => $newfid));

                $thread_tid and thread_tid_update($tids, $newfid);

                // hook operate_move_thread_update_after.php

                // 新栏目增加主题数
                forum_update($newfid, array('threads+' => (count($tids))));

                // hook operate_move_forum_update_after.php
            }

            // hook operate_move_end.php

            message(0, lang('move_completely'));

        }
        break;
    case 'search':
        // hook operate_search_start.php

        $keyword = param('keyword');
        empty($keyword) and $keyword = param(2);
        $keyword = trim($keyword);
        $range = param(3, 1);
        $page = param(4, 1);
        $pagesize = 20;
        $extra = array(); // 插件预留

        // hook operate_search_before.php

        $keyword_decode = well_search_keyword_safe(xn_urldecode($keyword));
        $keyword_arr = explode(' ', $keyword_decode);
        $threadlist = array();
        $pagination = '';
        $form_action = url('operate-search');
        $active = 'default';

        // hook operate_search_middle.php

        $search_type = 'like';

        if ($keyword) {
            // hook operate_search_keyword_start.php
            if ('like' == $search_type) {

                // hook operate_search_keyword_like_start.php

                if (1 == $range) {
                    $threadlist = well_thread_find_by_keyword($keyword_decode);
                }

                // hook operate_search_keyword_like_end.php

            } elseif ('site_url' == $search_type) {

                $site_url = 'https://www.baidu.com/s?wd=site%3A' . _SERVER('HTTP_HOST') . '%20{keyword}';
                $url = str_replace('{keyword}', $keyword_decode, $site_url);
                http_location($url);
            }
            // hook operate_search_keyword_end.php
        }

        // hook operate_search_end.php

        if ($ajax) {
            if ($threadlist) {
                foreach ($threadlist as &$thread) $thread = well_thread_safe_info($thread);
            }

            $apilist['keyword'] = $keyword;
            $apilist['range'] = $range;
            $apilist['page'] = $page;
            $apilist['pagesize'] = $pagesize;
            $apilist['extra'] = $extra;
            $apilist['active'] = $active;
            $apilist['threadlist'] = $threadlist;
            $apilist['form_action'] = $form_action;
            $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
        } else {
            include _include(theme_load('search'));
        }
        break;
    // hook operate_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook operate_after.php

function well_search_keyword_safe($s)
{
    $s = strip_tags($s);
    $s = str_replace(array('\'', '\\', '"', '%', '<', '>', '`', '*', '&', '#'), '', $s);
    $s = preg_replace('#\s+#', ' ', $s);
    $s = trim($s);
    //$s = preg_replace('#[^\w\-\x4e00-\x9fa5]+#i', '', $s);
    return $s;
}

// hook operate_end.php

?>