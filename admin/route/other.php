<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') AND exit('Access Denied.');

FALSE === group_access($gid, 'manageother') AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'cache');

// hook admin_other_start.php

switch ($action) {
    // hook admin_other_case_start.php
    case 'cache':
        // hook admin_other_cache_get_post.php

        if ('GET' == $method) {

            // hook admin_other_cache_get_start.php

            $input = array();
            $input['clear_tmp'] = form_checkbox('clear_tmp', 1);
            $input['clear_cache'] = form_checkbox('clear_cache', 1);
            $safe_token = well_token_set($uid);
            $input['safe_token'] = form_hidden('safe_token', $safe_token);

            // hook admin_other_cache_get_end.php

            $header['title'] = lang('admin_clear_cache');
            $header['mobile_title'] = lang('admin_clear_cache');
            $header['mobile_link'] = url('other-cache', '', TRUE);

            include _include(ADMIN_PATH . 'view/htm/other_cache.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            // hook admin_other_cache_post_start.php

            $clear_tmp = param('clear_tmp');
            $clear_cache = param('clear_cache');

            $clear_cache AND cache_truncate();
            $clear_cache AND $runtime = NULL; // 清空

            $g_website = kv_cache_get('website');
            $g_website['flag'] = '';
            $g_website['flag_thread'] = '';
            // hook admin_other_cache_post_before.php
            kv_cache_set('website', $g_website);

            $clear_tmp AND rmdir_recusive($conf['tmp_path'], 1);

            // hook admin_other_cache_post_end.php

            message(0, lang('admin_clear_successfully'));
        }
        break;
    case 'link':
        if ('GET' == $method) {

            // hook admin_other_link_get_start.php

            $page = param('page', 1);
            $pagesize = 20;
            $extra = array('page' => '{page}');

            $input = array();
            $input['name'] = form_text('name', '', $width = FALSE, lang('site_name'));
            $input['url'] = form_text('url', '', $width = FALSE, lang('site_url'));

            $safe_token = well_token_set($uid);

            // hook admin_other_link_get_before.php

            $n = link_count();
            $arrlist = link_get($page, $n);

            // hook admin_other_link_get_after.php

            $pagination = pagination(url('other-link', $extra, TRUE), $n, $page, $pagesize);

            $header['title'] = lang('friends_link');
            $header['mobile_title'] = lang('friends_link');
            $header['mobile_link'] = url('other-link', '', TRUE);

            // hook admin_other_link_get_end.php

            include _include(ADMIN_PATH . 'view/htm/other_link.htm');

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));

            $type = param('type', 0);

            if (1 == $type) {
                $name = param('name');
                $name = filter_all_html($name);
                $url = param('url');

                FALSE === link_create(array('name' => $name, 'url' => $url, 'create_date' => $time)) AND message(-1, lang('create_failed'));

                message(0, lang('create_successfully'));

            } elseif (2 == $type) {
                // 排序
                $arr = _POST('data');

                empty($arr) && message(1, lang('data_is_empty'));

                foreach ($arr as &$val) {
                    $rank = intval($val['rank']);
                    $id = intval($val['id']);
                    intval($val['oldrank']) != $rank && $id && link_update($id, array('rank' => $rank));
                }

                message(0, lang('update_successfully'));

            } else {

                $id = param('id', 0);
                FALSE === link_delete($id) AND message(-1, lang('delete_failed'));

                message(0, lang('delete_successfully'));
            }
        }
        break;
    case 'upgrade':
        // 获取更新文件 打包 下载
        if ('GET' == $method) {

            $type = param('type', 0);
            $upgrade = 0;
            $official = array();

            if (0 == $type) {
                $json = https_request('http://www.wellcms.cn/version.html?type=2&version=' . array_value($config, 'version') . '&version_date=' . array_value($config, 'version_date', 0), '', '', 500, 1);
                // 每天限更新一次
                if ($config['last_version'] > $time && isset($json) && !in_array($json, array('1', '2', 'fail'))) {
                    $official = xn_json_decode($json);
                    if (isset($official['version'], $official['version_date'])) {
                        if (-1 == version_compare($config['official_version'], $official['version']) || array_value($config, 'version_date', 0) < $official['version_date']) {
                            $upgrade = 1; // 可更新
                            $config['official_version'] = $official['version'];
                            $config['upgrade'] = 1; // 有更新
                        }

                        isset($official['message']) AND kv_set('official-message', $official['message']);
                        $config['last_version'] = clock_twenty_four();
                    }
                } else {
                    $message = kv_get('official-message');
                    $official = array('message' => $message);
                }

                setting_set('conf', $config);

            } elseif (1 == $type) {

                if (0 == version_compare($config['version'], $config['official_version']) && 0 == $config['upgrade']) message(0, jump(lang('no_upgrade_required'), url('other-upgrade', '', TRUE), 2));

                // 获取更新包
                $url = 'http://www.wellcms.cn/version-upgrade.html?domain=' . xn_urlencode(_SERVER('HTTP_HOST')) . '&siteid=' . plugin_siteid() . '&version=' . array_value($config, 'version') . '&version_date=' . array_value($config, 'version_date', 0);
                $json = https_request($url, '', '', 500, 1);

                if (empty($json) || 'fail' == $json) {
                    message(0, jump(lang('upgrade_failed'), url('other-upgrade', '', TRUE), 2));
                } elseif (1 == $json) {
                    message(0, jump('No upgrade package', url('other-upgrade', '', TRUE), 2));
                } elseif (2 == $json) {
                    message(0, jump('Updates available, no downloads available', url('other-upgrade', '', TRUE), 2));
                } else {
                    $res = xn_json_decode($json);
                    // 服务端开始下载升级包
                    set_time_limit(0);
                    $s = https_request($res['url'], '', '', 90);
                    empty($s) AND message(-1, jump(lang('plugin_return_data_error') . lang('server_response_empty'), url('other-upgrade', '', TRUE), 2));

                    if (substr($s, 0, 2) != 'PK') message(-1, jump(lang('plugin_return_data_error') . $s, url('other-upgrade', '', TRUE), 2));

                    $zipfile = $conf['tmp_path'] . 'upgrade_' . date('Y.m.d_H.i.s', $res['version_date']) . '.zip';
                    file_put_contents($zipfile, $s);

                    include XIUNOPHP_PATH . 'xn_zip.func.php';
                    // 覆盖 win主机转换\
                    xn_unzip($zipfile, str_replace('\\', '/', APP_PATH));

                    // 升级mysql
                    $upgradefile = APP_PATH . 'tmp/upgrade.php';
                    if (!empty($res['upgrade_db']) && is_file($upgradefile)) include _include($upgradefile);

                    https_request('http://www.wellcms.cn/version-upgrade.html?upgrade=1&id=' . $res['id'], '', '', 500, 1);

                    rmdir_recusive($conf['tmp_path'], 1);

                    http_location(url('other-upgrade', array('type' => 2), TRUE));
                }

            } elseif (2 == $type) {
                // 更新完成
                $config['version'] = $config['official_version'];
                $config['upgrade'] = 0;
                setting_set('conf', $config);
            }

            $header['title'] = lang('online_upgrade');
            $header['mobile_title'] = lang('online_upgrade');
            $header['mobile_link'] = url('other-upgrade', '', TRUE);

            include _include(ADMIN_PATH . 'view/htm/upgrade.htm');
        }
        break;
    // hook admin_other_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook admin_other_end.php

?>