<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') AND exit('Access Denied.');

group_access($gid, 'manageother') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'cache');

// hook admin_other_start.php

if ($action == 'cache') {

    // hook admin_other_cache_get_post.php

    if ($method == 'GET') {

        // hook admin_other_cache_get_start.php

        $input = array();
        $input['clear_tmp'] = form_checkbox('clear_tmp', 1);
        $input['clear_cache'] = form_checkbox('clear_cache', 1);
        $safe_token = well_token_set($uid);
        $input['safe_token'] = form_hidden('safe_token', $safe_token);

        // hook admin_other_cache_get_end.php

        $header['title'] = lang('admin_clear_cache');
        $header['mobile_title'] = lang('admin_clear_cache');
        $header['mobile_link'] = url('other-cache');

        include _include(ADMIN_PATH . 'view/htm/other_cache.htm');

    } elseif ($method == 'POST') {

        $safe_token = param('safe_token');
        well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

        // hook admin_other_cache_post_start.php

        $clear_tmp = param('clear_tmp');
        $clear_cache = param('clear_cache');

        $clear_cache AND cache_truncate();
        $clear_cache AND $runtime = NULL; // 清空

        $g_website = kv_cache_get('website');
        $g_website['forumlist'] = '';
        $g_website['flag'] = '';
        $g_website['flag_thread'] = '';
        // hook admin_other_cache_post_before.php
        kv_cache_set('website', $g_website);

        $clear_tmp AND rmdir_recusive($conf['tmp_path'], 1);

        // hook admin_other_cache_post_end.php

        message(0, lang('admin_clear_successfully'));
    }
} elseif ($action == 'map') {

    // hook admin_other_map_start.php

    $setting = array_value($config, 'setting');

    if ($method == 'GET') {

        $header['title'] = lang('map');
        $header['mobile_title'] = lang('map');
        $header['mobile_link'] = url('other-map');

        // hook admin_other_map_get_start.php

        // 1生成地图
        $type = param('type', 0);

        if ($type == 1) {

            // hook admin_other_map_xml_start.php

            !file_exists(array_value($setting, 'map', 'sitemap')) AND xn_mkdir(APP_PATH . array_value($setting, 'map', 'sitemap'), 0777);

            $page = param('page', 0); // 当前页数
            $n = param('n', 0); // 总页数
            $pagesize = 40000;
            $fids = param('fids');
            $fid = param('fid', 0);

            $forum_xml = $xml = '';
            $lastmod = date('Y-m-d');

            // hook admin_other_map_xml_before.php

            $dir = array_value($setting, 'map', 'sitemap') . '/';

            // hook admin_other_map_xml_middle.php

            //$forumlist_show = category_list($forumlist);

            // hook admin_other_map_xml_after.php

            if (!empty($forumlist_show) && !$fids) {
                // 生成栏目索引

                $fids = '';
                foreach ($forumlist_show as $_forum) {

                    if ($_forum['threads'] == 0) continue;
                    if (in_array($_forum['category'], array(1, 2))) continue;

                    $fids .= $_forum['fid'] . '|';

                    $n = ceil($_forum['threads'] / $pagesize);
                    //str_replace('.html', '-' . $i, $_forum['url']);
                    //--------------生成栏目索引---------------
                    for ($i = 0; $i < $n; ++$i) {
                        $forum_xml .= "\r\n<sitemap>
    <loc>" . url_prefix() . '/' . $dir . str_replace('.html', '-' . $i, $_forum['url']) . '.xml</loc>
</sitemap>';
                    }

                    if ($forum_xml) {
                        $forum_xml = trim($forum_xml, "\r\n");
                        $forum_map = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$forum_xml}  
</sitemapindex>
EOT;

                        file_put_contents_try(APP_PATH . $setting['map'] . '/index.xml', $forum_map);
                    }
                }

                $thread = rtrim($fids, '|');

                message(0, jump('Create a section index', url('other-map', array('type' => 1, 'fids' => rtrim($fids, '|'))), 1));

            } else {

                $fidarr = explode('|', $fids);
                empty($fid) AND $fid = $fidarr[0];

                // 按照栏目生成内容索引 可以创建已生成标识，不用重复生成旧数据，VIP版再添加吧

                $forum = $forumlist_show[$fid];
                empty($n) AND $n = ceil($forum['threads'] / $pagesize);

                $arrlist = thread_tid_find_by_fid($fid, $page, $pagesize, FALSE);

                foreach ($arrlist as $_thread) {
/*
百度在标准Sitemap协议基础上增加了<mobile:mobile/>标签，四种取值：
<mobile:mobile/> ：移动网页
<mobile:mobile type="mobile"/> 移动网页
<mobile:mobile type="pc,mobile"/> 自适应网页
<mobile:mobile type="htmladapt"/> 代码适配
如需要在浏览器查看，删除百度的标签
*/
                    $xml .= '    <url>
        <loc>' . url_prefix() . '/' . map_url_format($fid, $_thread['tid']) . '</loc>
        <mobile:mobile type="pc,mobile"/>
        <lastmod>' . $lastmod . '</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>' . "\r\n";
                }

                if ($xml) {
                    $xml = trim($xml, "\r\n");
                    $map = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$xml}
</urlset>
EOT;
                    file_put_contents_try(APP_PATH . $setting['map'] . '/' . str_replace('.html', '-' . $page, $forum['url']) . '.xml', $map);
                }

                $page += 1;
                if ($page == $n) {
                    $page = 0;
                    $fid = array_value($fidarr, 1, 0);
                    $n = 0;
                    unset($fidarr[0]);

                    $fidarr = array_filter($fidarr);
                    empty($fidarr) AND message(0, jump('Complete', url('other-map'), 2));
                }
                $fids = implode('|', $fidarr);

                message(0, jump('Forum : ' . $forum['name'] . ' Number : ' . ($page + 1), url('other-map', array('type' => 1, 'fid' => $fid, 'fids' => $fids, 'n' => $n, 'page' => $page)), 1));

            }

            // hook admin_other_map_xml_end.php

        } else {

            $input = array();
            $input['map'] = form_text('map', array_value($setting, 'map', 'sitemap'), FALSE, lang('setting_map_tips'));
            $safe_token = well_token_set($uid);
            $input['safe_token'] = form_hidden('safe_token', $safe_token);

            // hook admin_other_map_get_before.php

            $arr = array();
            if (array_value($setting, 'map')) {
                foreach (glob('../' . array_value($setting, 'map') . '/' . '*.*') as $file) {
                    $arr[] = url_prefix() . '/' . str_replace('../', '', $file);
                }
            }
        }

        // hook admin_other_map_get_end.php

        include _include(ADMIN_PATH . 'view/htm/other_map.htm');

    } elseif ($method == 'POST') {

        $safe_token = param('safe_token');
        well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

        // hook admin_other_map_post_start.php

        $map = param('map');
        // 英文 数字 下划线及三种组合 不支持其他字符
        !preg_match('#^[\w]*$#i', $map) AND message(1, lang('english_number_tips'));

        // hook admin_other_map_post_before.php

        $setting['map'] = $map;

        // hook admin_other_map_post_middle.php

        $config['setting'] = $setting;

        // hook admin_other_map_post_after.php

        setting_set('conf', $config);

        // hook admin_other_map_post_end.php

        message(0, lang('save_successfully'));
    }

} elseif ($action == 'increase') {

    $installed = array_value($config, 'installed');

    $type = param('type', 0);

    if ($method == 'GET') {

        $page = param('page', 0);
        $page += 1;
        $n = param('n', 0);
        $fid = param('fid', 0);
        $tid = param('tid', 0);

        if ($n == 1) {
            $count = 250;
        } elseif ($n == 2) {
            $count = 500;
        } elseif ($n == 3) {
            $count = 2500;
        } elseif ($n == 4) {
            $count = 5000;
        } else {
            $count = 50;
        }

        if ($type == 1 && $page <= $count) {

            empty($fid) AND message(0, jump('No section', url('other-increase'), 1));

            // 投入正常运营的站点不能灌水
            $runtime['articles'] AND $installed == 0 AND message(0, lang('create_failed'));

            if (empty($tid)) {
                $tid = well_thread_maxid();
                $tid += 1;
            }

            $number = $tid + 20000;
            $subject = 'WellCMS 性能测试';
            $message = 'WellCMS 性能测试';
            $thread = $thread_tid = $data = '';
            for ($tid; $tid < $number; ++$tid) {
                $thread .= '(' . $tid . ',' . $fid . ',"' . $subject . '",' . $uid . ',' . $time . '),';
                $thread_tid .= '(' . $tid . ',' . $fid . ',' . $uid . '),';
                $data .= '(' . $tid . ',"' . $message . '"),';
            }

            $thread = rtrim($thread, ',');
            $r = db_exec("REPLACE INTO `{$db->tablepre}website_thread` (`tid`,`fid`,`subject`,`uid`,`create_date`) VALUES $thread");
            $r === FALSE AND message(-1, 'Create thread error');

            $thread_tid = rtrim($thread_tid, ',');
            db_exec("REPLACE INTO `{$db->tablepre}website_thread_tid` (`tid`,`fid`,`uid`) VALUES $thread_tid") === FALSE AND message(-1, 'Create thread tid error');

            $data = rtrim($data, ',');
            db_exec("REPLACE INTO `{$db->tablepre}website_data` (`tid`,`message`) VALUES $data") === FALSE AND message(-1, 'Create data error');

            forum_update($fid, array('threads+' => 20000));
            user_update($uid, array('articles+' => 20000));

            // 灌水标识
            if ($installed == 0) {
                $config['installed'] = 1;
                setting_set('conf', $config);
            }

            message(0, jump('Number : ' . $page . '<br><br>threads : ' . ($page * 20000), url('other-increase', array('type' => 1, 'fid' => $fid, 'n' => $n, 'tid' => $tid, 'page' => $page)), 1));

        } else {
            $columnlist = category_list($forumlist);
        }

        $header['title'] = lang('increase_thread');
        $header['mobile_title'] = lang('increase_thread');
        $header['mobile_link'] = url('other-increase');

        include _include(ADMIN_PATH . 'view/htm/other_increase.htm');

    } elseif ($method == 'POST') {

        if ($type == 1) {

            db_exec("TRUNCATE  `{$db->tablepre}website_attach`");
            db_exec("TRUNCATE  `{$db->tablepre}website_comment`");
            db_exec("TRUNCATE  `{$db->tablepre}website_comment_pid`");
            db_exec("TRUNCATE  `{$db->tablepre}website_data`");
            db_exec("TRUNCATE  `{$db->tablepre}website_flag`");
            db_exec("TRUNCATE  `{$db->tablepre}website_flag_thread`");
            db_exec("TRUNCATE  `{$db->tablepre}website_link`");
            db_exec("TRUNCATE  `{$db->tablepre}website_operate`");
            db_exec("TRUNCATE  `{$db->tablepre}website_page`");
            db_exec("TRUNCATE  `{$db->tablepre}website_tag`");
            db_exec("TRUNCATE  `{$db->tablepre}website_tag_thread`");
            db_exec("TRUNCATE  `{$db->tablepre}website_thread`");
            db_exec("TRUNCATE  `{$db->tablepre}website_thread_sticky`");
            db_exec("TRUNCATE  `{$db->tablepre}website_thread_tid`");

            cache_truncate();
            kv_cache_delete('website');

            $n = user_count();
            $arrlist = user_find(array(), array(), 1, $n);
            $uids = array();
            foreach ($arrlist as $val) {
                $uids[] = $val['uid'];
            }
            user__update($uids, array('articles' => 0, 'comments' => 0));
            unset($arrlist);

            $fids = array();
            foreach ($forumlist as $val) {
                $fids[] = $val['fid'];
            }

            forum_update($fids, array('threads' => 0,'flagstr' => '','flags' => 0));

            // 灌水标识
            if ($installed == 1) {
                $config['installed'] = 0;
                setting_set('conf', $config);
            }
        }

        message(0, lang('delete_successfully'));
    }
} elseif ($action == 'link') {

    if ($method == 'GET') {

        // hook admin_other_link_get_start.php

        $page = param(2, 1);
        $pagesize = 20;

        $input = array();
        $input['name'] = form_text('name', '', $width = FALSE, lang('site_name'));
        $input['url'] = form_text('url', '', $width = FALSE, lang('site_url'));

        $safe_token = well_token_set($uid);

        // hook admin_other_link_get_before.php

        $n = link_count();
        $arrlist = link_get($page, $n);

        // hook admin_other_link_get_after.php

        $pagination = pagination(url('other-link-{page}'), $n, $page, $pagesize);

        $header['title'] = lang('friends_link');
        $header['mobile_title'] = lang('friends_link');
        $header['mobile_link'] = url('other-link');

        // hook admin_other_link_get_end.php

        include _include(ADMIN_PATH . 'view/htm/other_link.htm');

    } elseif ($method == 'POST') {

        $safe_token = param('safe_token');
        well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));
        
        $type = param('type', 0);

        if ($type == 1) {
            $name = param('name');
            $name = filter_all_html($name);
            $url = param('url');

            link_create(array('name' => $name, 'url' => $url, 'create_date' => $time)) === FALSE AND message(-1, lang('create_failed'));

            message(0, lang('create_successfully'));

        } elseif ($type == 2) {
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

            $id = param('2', 0);
            link_delete($id) === FALSE AND message(-1, lang('delete_failed'));

            message(0, lang('delete_successfully'));
        }
    }

} elseif ($action == 'upgrade') {

    // 获取更新文件 打包 下载
    if ($method == 'GET') {

        $type = param(2, 0);
        $upgrade = 0;
        $official = array();

        if ($type == 0) {
            $json = https_request('http://www.wellcms.cn/version.html?type=2', '', '', 500, 1);
            if ($json && !in_array($json, array('1', '2', 'failed'))) {
                $official = xn_json_decode($json);
                if (isset($official['version']) && version_compare($config['official_version'], $official['version']) == -1) {
                    $upgrade = 1; // 可以更新
                    $config['official_version'] = $official['version'];
                    $config['upgrade'] = 1; // 有更新
                }
            }

            $config['last_version'] = clock_twenty_four();
            setting_set('conf', $config);

        } elseif ($type == 1) {

            if ($config['version'] == $config['official_version'] && $config['upgrade'] == 0) message(0, jump(lang('no_upgrade_required'), url('other-upgrade'), 2));

            // 获取更新包
            $url = 'http://www.wellcms.cn/version-upgrade.html?domain=' . xn_urlencode(_SERVER('HTTP_HOST')) . '&siteid=' . plugin_siteid() . '&version=' . $config['version'];
            $json = https_request($url, '', '', 500, 1);

            if (empty($json) || $json == 'failed') {
                // 更新失败
                message(0, jump(lang('upgrade_failed'), url('other-upgrade'), 2));
            } elseif ($json == 1) {
                // 更新失败 No upgrade package
                message(0, jump('No upgrade package', url('other-upgrade'), 2));
            } elseif ($json == 2) {
                // 更新失败 Updates available, no downloads available
                message(0, jump('Updates available, no downloads available', url('other-upgrade'), 2));
            } else {

                $res = xn_json_decode($json);
                // 服务端开始下载升级包
                set_time_limit(0);
                $s = https_request($res['url'], '', '', 60);
                empty($s) AND message(-1, jump(lang('plugin_return_data_error') . lang('server_response_empty'), url('other-upgrade'), 2));

                if (substr($s, 0, 2) != 'PK') message(-1, jump(lang('plugin_return_data_error') . $s, url('other-upgrade'), 2));

                $zipfile = $conf['tmp_path'] . 'upgrade_' . date('Y.m.d_H.i.s', $res['version_date']) . '.zip';
                file_put_contents($zipfile, $s);

                include XIUNOPHP_PATH . 'xn_zip.func.php';
                // 覆盖 win主机转换\
                xn_unzip($zipfile, str_replace('\\', '/', APP_PATH));

                // 升级mysql
                $upgradefile = APP_PATH . 'tmp/upgrade.php';
                if ($res['upgrade_db'] && is_file($upgradefile)) include _include($upgradefile);

                https_request('http://www.wellcms.cn/version-upgrade.html?upgrade=1&id=' . $res['id'], '', '', 500, 1);

                rmdir_recusive($conf['tmp_path'], 1);

                http_location(url('other-upgrade-2'));
            }

        } elseif ($type == 2) {
            // 更新完成
            $config['version'] = $config['official_version'];
            $config['upgrade'] = 0; // 已更新
            setting_set('conf', $config);
        }

        $header['title'] = lang('online_upgrade');
        $header['mobile_title'] = lang('online_upgrade');
        $header['mobile_link'] = url('other-upgrade');

        include _include(ADMIN_PATH . 'view/htm/upgrade.htm');
    }
}

// hook admin_other_end.php

function map_url_format($fid, $tid)
{
    global $forumlist;
    // hook model_url_format_start.php
    if (empty($forumlist[$fid])) return url('read-' . $tid);
    // hook model_url_format_before.php
    $forum = $forumlist[$fid];
    // hook model_url_format_after.php
    if ($forum['type']) {
        // CMS
        // hook model_url_format_model_start.php
        // 自己可以根据需要按照model区分路径
        $url = url('read-' . $tid);
        // hook model_url_format_model_end.php
    } else {
        // BBS
        // hook model_url_format_thread_before.php
        $url = url('thread-' . $tid);
        // hook model_url_format_thread_after.php
    }
    // hook model_url_format_end.php
    return $url;
}

?>