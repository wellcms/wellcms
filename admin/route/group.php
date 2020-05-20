<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') AND exit('Access Denied.');

$action = param(1, 'list');

$system_group = array(0, 1, 2, 3, 4, 5, 6, 7, 101);

// hook admin_group_start.php

switch ($action) {
    // hook admin_group_case_start.php
    case 'list':
        // hook admin_group_list_get_post.php

        if ('GET' == $method) {

            // hook admin_group_list_get_start.php

            $header['title'] = lang('group_admin');
            $header['mobile_title'] = lang('group_admin');

            $maxgid = group_maxid();
            $safe_token = well_token_set($uid);

            // hook admin_group_list_get_end.php

            include _include(ADMIN_PATH . 'view/htm/group_list.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            FALSE === group_access($gid, 'managegroup') AND message(1, lang('user_group_insufficient_privilege'));

            $gidarr = param('_gid', array(0));
            $namearr = param('name', array(''));
            $creditsfromarr = param('creditsfrom', array(0));
            $creditstoarr = param('creditsto', array(0));
            $arrlist = array();

            // hook admin_group_list_post_start.php

            foreach ($gidarr as $k => $v) {
                $arr = array(
                    'gid' => $k,
                    'name' => $namearr[$k],
                    'creditsfrom' => $creditsfromarr[$k],
                    'creditsto' => $creditstoarr[$k],
                );
                isset($grouplist[$k]) ? group_update($k, $arr) : group_create($arr);
            }

            // 删除 / delete
            $deletearr = array_diff_key($grouplist, $gidarr);
            foreach ($deletearr as $k => $v) {
                if (in_array($k, $system_group)) continue;
                group_delete($k);
            }

            group_list_cache_delete();

            // hook admin_group_list_post_end.php

            message(0, lang('save_successfully'));
        }
        break;
    case 'update':
        $_gid = param('gid', 0);
        $_group = group_read($_gid);
        empty($_group) AND message(-1, lang('group_not_exists'));

        // hook admin_group_update_get_post.php

        if ('GET' == $method) {

            $extra = array('gid' => $_gid); // 插件预留

            // hook admin_group_update_get_start.php

            $header['title'] = lang('group_admin');
            $header['mobile_title'] = lang('group_admin');

            FALSE === group_access($gid, 'manageupdategroup') AND message(1, lang('user_group_insufficient_privilege'));

            $input = array();
            $input['name'] = form_text('name', $_group['name']);
            $input['creditsfrom'] = form_text('creditsfrom', $_group['creditsfrom']);
            $input['creditsto'] = form_text('creditsto', $_group['creditsto']);

            $input['allowread'] = form_checkbox('allowread', $_group['allowread'], lang('allow_view'));
            $input['allowthread'] = form_checkbox('allowthread', $_group['allowthread'] && $_gid != 0, lang('allow_thread'));
            $input['allowpost'] = form_checkbox('allowpost', $_group['allowpost'] && $_gid != 0, lang('allow_post'));
            $input['allowattach'] = form_checkbox('allowattach', $_group['allowattach'] && $_gid != 0, lang('allow_upload'));
            $input['allowdown'] = form_checkbox('allowdown', $_group['allowdown'], lang('allow_download'));
            $input['allowuserdelete'] = form_checkbox('allowuserdelete', $_group['allowuserdelete'], lang('delete'));

            $input['allowtop'] = form_checkbox('allowtop', $_group['allowtop'], lang('top'));
            $input['allowupdate'] = form_checkbox('allowupdate', $_group['allowupdate'], lang('edit'));
            $input['allowdelete'] = form_checkbox('allowdelete', $_group['allowdelete'], lang('delete'));
            $input['allowmove'] = form_checkbox('allowmove', $_group['allowmove'], lang('move'));
            $input['allowbanuser'] = form_checkbox('allowbanuser', $_group['allowbanuser'], lang('ban_user'));
            $input['allowdeleteuser'] = form_checkbox('allowdeleteuser', $_group['allowdeleteuser'], lang('delete_user'));
            $input['allowviewip'] = form_checkbox('allowviewip', $_group['allowviewip'], lang('view_user_info'));

            $input['intoadmin'] = form_checkbox('intoadmin', $_group['intoadmin'], lang('in_to_admin'));
            $input['managecontent'] = form_checkbox('managecontent', $_group['managecontent'], lang('manage_content'));
            $input['managecreatethread'] = form_checkbox('managecreatethread', $_group['managecreatethread'], lang('manage_create_thread'));
            $input['manageupdatethread'] = form_checkbox('manageupdatethread', $_group['manageupdatethread'], lang('manage_update_thread'));
            $input['managedeletethread'] = form_checkbox('managedeletethread', $_group['managedeletethread'], lang('manage_delete_thread'));

            $input['managesticky'] = form_checkbox('managesticky', $_group['managesticky'], lang('manage_sticky'));
            $input['managecomment'] = form_checkbox('managecomment', $_group['managecomment'], lang('manage_comment'));
            $input['managepage'] = form_checkbox('managepage', $_group['managepage'], lang('manage_single_page'));
            $input['manageforum'] = form_checkbox('manageforum', $_group['manageforum'], lang('manage_forum'));
            $input['managecategory'] = form_checkbox('managecategory', $_group['managecategory'], lang('manage_category'));

            $input['manageuser'] = form_checkbox('manageuser', $_group['manageuser'], lang('manage_user'));
            $input['managecreateuser'] = form_checkbox('managecreateuser', $_group['managecreateuser'], lang('manage_create_user'));
            $input['manageupdateuser'] = form_checkbox('manageupdateuser', $_group['manageupdateuser'], lang('manage_update_user'));
            $input['managedeleteuser'] = form_checkbox('managedeleteuser', $_group['managedeleteuser'], lang('manage_delete_user'));

            $input['managegroup'] = form_checkbox('managegroup', $_group['managegroup'], lang('manage_group'));
            $input['manageupdategroup'] = form_checkbox('manageupdategroup', $_group['manageupdategroup'], lang('manage_update_group'));

            $input['manageplugin'] = form_checkbox('manageplugin', $_group['manageplugin'], lang('manage_plugin'));
            $input['manageother'] = form_checkbox('manageother', $_group['manageother'], lang('manage_other'));
            $input['managesetting'] = form_checkbox('managesetting', $_group['managesetting'], lang('manage_setting'));
            $safe_token = well_token_set($uid);
            $input['safe_token'] = form_hidden('safe_token', $safe_token);
            // hook admin_group_update_get_end.php

            include _include(ADMIN_PATH . 'view/htm/group_update.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            $name = param('name');
            $creditsfrom = param('creditsfrom');
            $creditsto = param('creditsto');
            $allowread = param('allowread', 0);
            $allowthread = param('allowthread', 0);
            $allowpost = param('allowpost', 0);
            $allowattach = param('allowattach', 0);
            $allowdown = param('allowdown', 0);
            $allowuserdelete = param('allowuserdelete', 0);
            $intoadmin = param('intoadmin', 0);
            $managecontent = param('managecontent', 0);
            $managecreatethread = param('managecreatethread', 0);
            $manageupdatethread = param('manageupdatethread', 0);
            $managedeletethread = param('managedeletethread', 0);
            $managesticky = param('managesticky', 0);
            $managecomment = param('managecomment', 0);
            $managepage = param('managepage', 0);
            $manageforum = param('manageforum', 0);
            $managecategory = param('managecategory', 0);
            $manageuser = param('manageuser', 0);
            $managecreateuser = param('managecreateuser', 0);
            $manageupdateuser = param('manageupdateuser', 0);
            $managedeleteuser = param('managedeleteuser', 0);
            $managegroup = param('managegroup', 0);
            $manageupdategroup = param('manageupdategroup', 0);
            $manageplugin = param('manageplugin', 0);
            $manageother = param('manageother', 0);
            $managesetting = param('managesetting', 0);

            // hook admin_group_update_post_start.php

            $arr = array(
                'name' => $name,
                'creditsfrom' => $creditsfrom,
                'creditsto' => $creditsto,
                'allowread' => $allowread,
                'allowthread' => $allowthread,
                'allowpost' => $allowpost,
                'allowuserdelete' => $allowuserdelete,
                'allowattach' => $allowattach,
                'allowdown' => $allowdown,
                'intoadmin' => $intoadmin,
                'managecontent' => $managecontent,
                'managecreatethread' => $managecreatethread,
                'manageupdatethread' => $manageupdatethread,
                'managedeletethread' => $managedeletethread,
                'managesticky' => $managesticky,
                'managecomment' => $managecomment,
                'managepage' => $managepage,
                'manageforum' => $manageforum,
                'managecategory' => $managecategory,
                'managecreateuser' => $managecreateuser,
                'manageupdateuser' => $manageupdateuser,
                'managedeleteuser' => $managedeleteuser,
                'managegroup' => $managegroup,
                'manageupdategroup' => $manageupdategroup,
                'manageplugin' => $manageplugin,
                'manageother' => $manageother,
                'managesetting' => $managesetting
            );
            // hook admin_group_update_post_before.php
            if ($_gid >= 1 && $_gid <= 5) {

                $allowtop = param('allowtop', 0);
                $allowupdate = param('allowupdate', 0);
                $allowdelete = param('allowdelete', 0);
                $allowmove = param('allowmove', 0);
                $allowbanuser = param('allowbanuser', 0);
                $allowdeleteuser = param('allowdeleteuser', 0);
                $allowviewip = param('allowviewip', 0);

                // hook admin_group_update_post_center.php
                $arr += array(
                    'allowtop' => $allowtop,
                    'allowupdate' => $allowupdate,
                    'allowdelete' => $allowdelete,
                    'allowmove' => $allowmove,
                    'allowbanuser' => $allowbanuser,
                    'allowdeleteuser' => $allowdeleteuser,
                    'allowviewip' => $allowviewip
                );

                // hook admin_group_update_post_after.php
            }
            group_update($_gid, $arr);

            // hook admin_group_update_post_end.php

            message(0, lang('edit_successfully'));
        }
        break;
    // hook admin_group_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_group_end.php

?>