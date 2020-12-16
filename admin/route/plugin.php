<?php
!defined('DEBUG') and exit('Access Denied.');

FALSE === group_access($gid, 'manageplugin') and message(1, lang('user_group_insufficient_privilege'));

$action = param(1, 'list');

// 初始化插件变量 / init plugin var
plugin_init();

switch ($action) {
    case 'list':

        $page = param('page', 1);
        $type = param('type', 0);
        $pagesize = 20;
        $extra = array('page' => '{page}', 'type' => $type);

        $plugin_cates = array(0 => lang('all'), 1 => lang('enabled'), 2 => lang('not_enabled'), 3 => lang('disable'));
        $plugin_cate_html = plugin_cate_active($action, $plugin_cates, $type, $page);

        if (1 == $type) {
            $cond = array('installed' => 1, 'enable' => 1);
        } elseif (2 == $type) {
            $cond = array('installed' => 0, 'enable' => 0);
        } elseif (3 == $type) {
            $cond = array('installed' => 1, 'enable' => 0);
        } else {
            $cond = array();
        }

        // 本地插件 local plugin list
        $pluginlist = plugin_list($cond, $orderby = array(), $page, $pagesize, TRUE);
        $total = arrlist_cond_orderby($plugins, $cond, array(), 1, 1000);

        $pagination = pagination(url('plugin-' . $action, $extra, TRUE), count($total), $page, $pagesize);

        $safe_token = well_token_set($uid);
        $extra += array('safe_token' => $safe_token);
        $header['title'] = lang('local_plugin');
        $header['mobile_title'] = lang('local_plugin');
        $active = 'plugin';

        include _include(ADMIN_PATH . 'view/htm/plugin_list.htm');
        break;
    case 'theme':
        if ('GET' == $method) {

            $page = param('page', 1);
            $pagesize = 30;
            $extra = array('page' => '{page}');
            $cond = array();
            $pluginlist = plugin_list($cond, $orderby = array(), $page, $pagesize, FALSE);
            $total = arrlist_cond_orderby($themes, $cond, array(), 1, 1000);

            $read = array_value($pluginlist, $config['theme']);
            if (!array_value($read, 'installed')) {
                foreach ($pluginlist as $dir => $theme) {
                    if (1 == $theme['installed']) {
                        $read = $theme;
                        theme_install($dir);
                        unset($pluginlist[$dir]);
                        continue;
                    }
                }
            }

            !empty($read) and $pluginlist = array($config['theme'] => $read) + $pluginlist;

            $pagination = pagination(url('plugin-' . $action, $extra, TRUE), count($total), $page, $pagesize);

            $safe_token = well_token_set($uid);
            $extra += array('safe_token' => $safe_token);
            $header['title'] = lang('local') . lang('theme');
            $header['mobile_title'] = lang('local') . lang('theme');

            include _include(ADMIN_PATH . "view/htm/theme_list.htm");

        } elseif ('POST' == $method) {

            FALSE === group_access($gid, 'manageplugin') and message(1, lang('user_group_insufficient_privilege'));

            $dir = param_word('dir');
            $type = param('type', 0);

            empty($dir) and message(1, lang('data_malformation'));

            if (1 == $type) {
                plugin_check_dependency($dir);
                theme_install($dir);

                plugin_clear_tmp_dir();
                message(0, lang('install_successfully'));
            } else {
                theme_uninstall($config['theme']);
                plugin_clear_tmp_dir();
                message(0, lang('uninstall_successfully'));
            }
        }
        break;
    case 'store':
        if ('GET' == $method) {
            // 0 所有插件 1主题风格 2功能增强 3大型插件 4接口整合
            $extra = array();
            $page = param('page', 1);
            $pagesize = 12;
            $type = param('type', 0);
            $extra += array('type' => $type);
            $cond = $type ? array('type' => $type) : array();

            $srchtype = param('srchtype', 'name');
            $keyword = trim(xn_urldecode(param('keyword')));
            $keyword and $cond['name'] = array('LIKE' => $keyword);
            $keyword and $cond['author'] = array('author' => $keyword);

            // plugin category
            $plugin_cates = array(0 => lang('plugin_cate_0'), 1 => lang('plugin_cate_1'), 2 => lang('plugin_cate_2'), 3 => lang('plugin_cate_3'), 4 => lang('plugin_cate_4'));

            $plugin_cate_html = plugin_cate_active($action, $plugin_cates, $type, 1);

            // official plugin
            $total = plugin_official_total($cond);
            $pluginlist = plugin_official_list($cond, array('storeid' => -1), $page, $pagesize);

            $pagination = pagination(url('plugin-' . $action, $extra += array('page' => '{page}'), TRUE), $total, $page, $pagesize);

            $data_verify = plugin_data_verify();
            $safe_token = well_token_set($uid);

            $header['title'] = lang('official_store');
            $header['mobile_title'] = lang('official_store');
            $active = 'store';

            include _include(ADMIN_PATH . "view/htm/plugin_store.htm");

        } elseif ('POST' == $method) {

            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

            plugin_official_store(1);

            message(0, lang('operator_complete'));
        }
        break;
    case 'read':
        if ('GET' == $method) {

            $safe_token = well_token_set($uid);

            $dir = param_word('dir');
            $extra = array('dir' => $dir, 'safe_token' => $safe_token); // 插件预留

            $plugin = plugin_read_by_dir($dir);
            empty($plugin) and message(-1, lang('plugin_not_exists'));

            $return = FALSE;
            $verify_token = TRUE;
            $download_url = '';
            $errno = '';
            $errstr = '';
            $payment_tips = '';
            $server = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

            // 本地是否有该插件
            $islocal = plugin_is_local($dir);

            // 线上模式可登录 升级 购买
            if (!$islocal && !empty($plugin['storeid']) && $server) {
                /*
                 查询应用和用户权限
                 0免费或已购买，显示下载地址或升级
                 1付费显示有权限购买
                 2已关闭或已下架
                 3系统错误
                 -1无人认领担保，不能购买应用
                 -2权限不足，已被限制权限
                 -3业务逻辑错误
                 -4数据错误
                 */
                $verify_token = plugin_data_verify(); // return FALSE re-login
                if ($verify_token) {
                    $return = plugin_query($plugin['storeid']);
                    if (FALSE !== $return) {
                        if (0 == $return['code']) {
                            $download_url = url('plugin-download', $extra, TRUE);
                        } elseif (1 == $return['code']) {
                            if (1 == $return['pay_type']) {
                                $payment_tips = 'RMB: ' . $return['price'];
                            } elseif (2 == $return['pay_type']) {
                                $payment_tips = lang('credits') . ':' . $return['price'];
                            } elseif (3 == $return['pay_type']) {
                                $payment_tips = lang('golds') . ':' . $return['price'];
                            }
                        }
                    }
                }
            }

            $tab = empty($islocal) ? ($plugin['price'] > 0 ? 'official_fee' : 'official_free') : 'local';

            $header['title'] = lang('plugin_detail') . '-' . $plugin['name'];
            $header['mobile_title'] = lang('plugin_detail') . '-' . $plugin['name'];

            include _include(ADMIN_PATH . 'view/htm/plugin_read.htm');

        } elseif ('POST' == $method) {

            $email = param('email');
            empty($email) and message('email', lang('email_is_empty'));

            $password = param('password');
            empty($password) and message('password', lang('please_input_password'));

            $siteip = ip2long(_SERVER('SERVER_ADDR'));
            $siteip < 0 and $siteip = sprintf("%u", $siteip);
            $post = array('email' => $email, 'password' => md5($password), 'auth_key' => xn_key(), 'domain' => xn_urlencode(_SERVER('HTTP_HOST')), 'ua' => md5($useragent), 'siteip' => $siteip, 'longip' => $longip);
            $url = PLUGIN_OFFICIAL_URL . 'plugin-login.html?' . http_build_query($post);
            $json = https_post($url, $post);
            empty($json) and message(-1, lang('server_response_empty'));
            $r = xn_json_decode($json);
            // -1用户不存在 -2用户被锁 0正常 1用户名错误 2密码错误
            if (0 == $r['code']) {
                isset($r['data']) and setting_set('plugin_data', $r['data']);
                message(0, lang('login_successfully'));
            }

            switch ($r['code']) {
                case 1:
                    message(-1, lang('password_incorrect'));
                    break;
                case 2:
                    message(-1, lang('username_not_exists'));
                    break;
                case -1:
                    message(-1, lang('user_not_exists'));
                    break;
                case -2:
                    message(-1, lang('user_locked'));
                    break;
                default:
                    message(-1, array_value($r, 'message', lang('data_malformation')));
                    break;
            }
        }
        break;
    case 'buy':
        FALSE === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) and message(1, lang('plugin_read_tips'));

        $data = plugin_data_verify();
        FALSE === $data and message(1, lang('please_login'));

        if ('GET' == $method) {

            $dir = param_word('dir');

            $plugin = plugin_read_by_dir($dir);
            empty($plugin) and message(-1, lang('plugin_not_exists'));

            $errno = '';
            $errstr = '';
            $return = plugin_query($plugin['official']['storeid'], TRUE);

            if (FALSE === $return) {
                if (-1 == $errno) {
                    message(-1, lang('insufficient_balance') . ',' . $return['message']);
                } else {
                    message($errno, $errstr);
                }
            }

            if (0 == $return['code']) message(1, lang('plugin_is_bought'));

            // 钱包优先，支付宝优先
            $pay_api = '';
            switch ($return['pay_api']) {
                case 1:
                    empty($return['url']) and message(1, lang('server_response_error'));
                    $payment_tips = lang('alipay') . ' RMB: ' . $return['price'];
                    break;
                case 2:
                    empty($return['url']) and message(1, lang('server_response_error'));
                    $payment_tips = lang('wxpay') . ' RMB: ' . $return['price'];
                    break;
                case 3:
                    $payment_tips = lang('wallet') . ' RMB: ' . $return['price'];
                    break;
                case 4:
                    $payment_tips = lang('credits') . ':' . $return['price'];
                    break;
                case 5:
                    $payment_tips = lang('golds') . ':' . $return['price'];
                    break;
                default:
                    message(-1, lang('plugin_return_data_error'));
                    break;
            }

            $header['title'] = lang('buy_application') . '-' . $plugin['name'];
            $header['mobile_title'] = lang('buy_application') . '-' . $plugin['name'];

            include _include(ADMIN_PATH . 'view/htm/plugin_buy.htm');

        } elseif ('POST' == $method) {

            $storeid = param('storeid', 0);

            $password = param('password');
            empty($password) and message('password', lang('please_input_password'));

            $siteip = ip2long(_SERVER('SERVER_ADDR'));
            $siteip < 0 and $siteip = sprintf("%u", $siteip);
            $post = array('siteid' => plugin_siteid(), 'app_url' => xn_urlencode(http_url_path()), 'domain' => xn_urlencode(_SERVER('HTTP_HOST')), 'token' => $data[4], 'storeid' => $storeid, 'uid' => $data[0], 'ua' => md5($useragent), 'password' => $password, 'siteip' => $siteip, 'longip' => $longip);
            $url = PLUGIN_OFFICIAL_URL . 'plugin-payment.html';
            $json = https_post($url, $post);
            empty($json) and message(-1, lang('server_response_empty'));
            $arr = xn_json_decode($json);

            cache_delete('store-' . $storeid);
            if (0 == $arr['code']) {
                message($arr['code'], lang('payment_successful'));
            } elseif (1 == $arr['code']) {
                message($arr['code'], lang('payment_failed'));
            } else {
                message($arr['code'], $arr['message']);
            }

        }
        break;
    case 'is_bought':
        // 定时查询是否支付成功
        $dir = param_word('dir');
        plugin_check_exists($dir, FALSE);
        $plugin = plugin_read_by_dir($dir);

        0 == $plugin['official']['price'] and message(1, lang('plugin_is_free'));

        TRUE === plugin_bought($plugin['official']['storeid']) ? message(0, lang('plugin_is_bought')) : message($errno, $errstr);
        break;
    case 'download':
        $dir = param_word('dir');
        plugin_check_exists($dir, FALSE);

        'fail' == plugin_verify_token() and message(-1, jump(lang('plugin_token_error'), url('plugin-read', array('dir' => $dir), TRUE), 1));

        // 下载官方插件 区分插件和主题 / download official plugin
        plugin_lock_start();

        $official = plugin_official_read($dir);
        empty($official) and message(-1, lang('plugin_not_exists'));

        // 检查版本  / check version match
        -1 == version_compare($conf['version'], $official['software_version']) and message(-1, lang('plugin_version_not_match', array('software_version' => $official['software_version'], 'version' => $conf['version'])));

        // 下载，解压 / download and zip
        plugin_download_unzip($dir, $official['storeid']);

        plugin_lock_end();

        message(0, jump(lang('plugin_download_successfully', array('dir' => $dir)), url('plugin-read', array('dir' => $dir), TRUE), 3));
        break;
    case 'install':
        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

        plugin_lock_start();

        $dir = param_word('dir');
        plugin_check_exists($dir);
        $name = $plugins[$dir]['name'];

        // 插件依赖检查 / check plugin dependency
        plugin_check_dependency($dir, 'install');

        plugin_notice($dir);

        // 安装插件 / install plugin
        plugin_install($dir);

        $installfile = APP_PATH . 'plugin/' . $dir . '/install.php';
        is_file($installfile) and include _include($installfile);

        plugin_lock_end();

        // 卸载同类插件，防止安装类似插件 自动卸载掉其他已经安装的主题 / automatically uninstall other theme plugin.
        if (FALSE !== strpos($dir, '_theme_')) {
            foreach ($plugins as $_dir => $_plugin) {
                if ($dir == $_dir) continue;
                FALSE !== strpos($_dir, '_theme_') and plugin_uninstall($_dir);
            }
        } else {
            // 卸载掉同类插件
            $suffix = substr($dir, strpos($dir, '_'));
            foreach ($plugins as $_dir => $_plugin) {
                if ($dir == $_dir) continue;
                $_suffix = substr($_dir, strpos($_dir, '_'));
                $suffix == $_suffix and plugin_uninstall($_dir);
            }
        }

        $msg = lang('plugin_install_successfully', array('name' => $name));
        message(0, jump($msg, url('plugin-list', array('type' => 1), TRUE), 2));
        break;
    case 'uninstall':

        if ('POST' != $method) message(1, lang('method_error'));

        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

        plugin_lock_start();

        $dir = param_word('dir');
        plugin_check_exists($dir);
        $name = $plugins[$dir]['name'];

        // 插件依赖检查
        plugin_check_dependency($dir, 'uninstall');

        // 卸载插件
        plugin_uninstall($dir);

        $uninstallfile = APP_PATH . 'plugin/' . $dir . '/uninstall.php';
        is_file($uninstallfile) and include _include($uninstallfile);

        // 删除插件
        //!DEBUG && rmdir_recusive(APP_PATH . "plugin/$dir");

        plugin_lock_end();

        $msg = lang('plugin_uninstall_successfully', array('name' => $name, 'dir' => "plugin/$dir"));
        message(0, jump($msg, url('plugin-list', array('type' => 2), TRUE), 3));
        break;
    case 'enable':
        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

        plugin_lock_start();

        $dir = param_word('dir');
        plugin_check_exists($dir);
        $name = $plugins[$dir]['name'];

        // 插件依赖检查
        plugin_check_dependency($dir, 'install');

        // 启用插件
        plugin_enable($dir);

        plugin_lock_end();

        $msg = lang('plugin_enable_successfully', array('name' => $name));
        message(0, jump($msg, url('plugin-read', array('dir' => $dir), TRUE), 1));
        break;
    case 'disable':
        $safe_token = param('safe_token');
        FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));

        plugin_lock_start();

        $dir = param_word('dir');
        plugin_check_exists($dir);
        $name = $plugins[$dir]['name'];

        // 插件依赖检查
        plugin_check_dependency($dir, 'uninstall');

        // 禁用插件
        plugin_disable($dir);

        plugin_lock_end();

        $msg = lang('plugin_disable_successfully', array('name' => $name));
        message(0, jump($msg, url('plugin-read', array('dir' => $dir), TRUE), 3));
        break;
    case 'upgrade':
        plugin_lock_start();

        $dir = param_word('dir');
        plugin_check_exists($dir, FALSE);

        $local = plugin_read_by_dir($dir);

        // 插件依赖检查
        plugin_check_dependency($dir, 'install');
        $official = plugin_read_by_dir($dir, FALSE);
        if (empty($official['storeid'])) message(1, jump(lang('data_malformation'), url('plugin-read', array('dir' => $dir), TRUE), 3));

        // 检查版本  / check version match
        if (-1 == version_compare($conf['version'], $official['software_version'])) {
            message(-1, lang('plugin_version_not_match', array('software_version' => $official['software_version'], 'version' => $conf['version'])));
        }

        // 下载，解压 / download and zip
        plugin_download_unzip($dir, $official['storeid'], 1);

        if (empty($local['type'])) {
            plugin_install($dir);
            $upgradefile = APP_PATH . 'plugin/' . $dir . '/upgrade.php';
        } else {
            theme_install($dir);
            $upgradefile = APP_PATH . 'view/template/' . $dir . '/upgrade.php';
        }

        is_file($upgradefile) and include _include($upgradefile);

        plugin_lock_end();

        $msg = lang('plugin_upgrade_successfully', array('name' => $local['name']));
        message(0, jump($msg, url('plugin-read', array('dir' => $dir), TRUE), 3));
        break;
    case 'setting':
        $dir = param_word('dir');
        empty($dir) and $dir = param_word(2); // 兼容旧插件
        plugin_check_exists($dir);
        $name = $plugins[$dir]['name'];

        include _include(APP_PATH . 'plugin/' . $dir . '/setting.php');
        break;
    default:
        message(-1, lang('data_malformation'));
        break;
}

function plugin_check_dependency($dir, $action = 'install')
{
    global $plugins, $themes;
    $name = isset($plugins[$dir]) ? $plugins[$dir]['name'] : $themes[$dir]['name'];
    if ('install' == $action) {
        $arr = plugin_dependencies($dir);
        if (!empty($arr)) {
            $s = plugin_dependency_arr_to_links($arr);
            message(-1, lang('plugin_dependency_following', array('name' => $name, 's' => $s)));
        }
    } else {
        $arr = plugin_by_dependencies($dir);
        if (!empty($arr)) {
            $s = plugin_dependency_arr_to_links($arr);
            message(-1, lang('plugin_being_dependent_cant_delete', array('name' => $name, 's' => $s)));
        }
    }
}

function plugin_dependency_arr_to_links($arr)
{
    global $plugins;
    $s = '';
    foreach ($arr as $dir => $version) {
        $name = isset($plugins[$dir]['name']) ? $plugins[$dir]['name'] : $dir;
        $url = url('plugin-read', array('dir' => $dir), TRUE);
        $s .= " <a href=\"$url\">【{$name}】</a> ";
    }
    return $s;
}

function plugin_verify_token()
{
    global $longip;
    $arr = plugin_data_verify();
    if (FALSE === $arr) {
        setting_delete('plugin_data');
        return FALSE;
    }
    $domain = xn_urlencode(_SERVER('HTTP_HOST'));

    $siteip = ip2long(_SERVER('SERVER_ADDR'));
    $siteip < 0 and $siteip = sprintf("%u", $siteip);
    $post = array('siteid' => plugin_siteid(), 'siteip' => $siteip, 'longip' => $longip, 'domain' => $domain, 'token' => $arr[4], 'uid' => $arr[0]);
    $url = PLUGIN_OFFICIAL_URL . 'plugin-verify.html';
    // return 'success' or 'fail'
    return https_post($url, $post);
}

function plugin_data_verify()
{
    $data = setting_get('plugin_data');
    if (empty($data)) return FALSE;

    $key = md5(xn_key());
    $s = xn_decrypt($data, $key);
    if (empty($s)) return FALSE;

    $arr = explode("\t", $s);
    if (5 != count($arr)) return FALSE;

    $domain = _SERVER('HTTP_HOST');
    if (plugin_siteid() != $arr[1] || FALSE === strpos($domain, $arr[2])) return FALSE;

    return $arr;
}

function plugin_download_unzip($dir, $storeid, $upgrade = 0)
{
    global $conf, $longip;

    $data = plugin_data_verify();
    empty($data) and message(-1, jump(lang('plugin_token_error'), url('plugin-read', array('dir' => $dir), TRUE)));

    $app_url = xn_urlencode(http_url_path());
    $domain = xn_urlencode(_SERVER('HTTP_HOST'));

    $siteip = ip2long(_SERVER('SERVER_ADDR'));
    $siteip < 0 and $siteip = sprintf("%u", $siteip);
    $post = array('storeid' => $storeid, 'siteid' => plugin_siteid(), 'siteip' => $siteip, 'longip' => $longip, 'app_url' => $app_url, 'upgrade' => $upgrade, 'domain' => $domain, 'auth_key' => xn_key(), 'token' => $data[4], 'uid' => $data[0]);
    $url = PLUGIN_OFFICIAL_URL . 'plugin-download.html';
    set_time_limit(0);
    // 服务端获取下载地址开始下载
    $s = https_post($url, $post, '', 180);
    empty($s) and message(-1, $url . lang('plugin_return_data_error') . lang('server_response_empty'));
    if ('PK' != substr($s, 0, 2)) {
        $res = xn_json_decode($s);

        empty($res) and message(-1, $url . lang('plugin_return_data_error') . $s);

        -2 == $res['code'] and message($res['code'], jump(lang('plugin_is_not_free'), url('plugin-read', array('dir' => $dir), TRUE), 3));

        -1 == $res['code'] and message($res['code'], jump(lang('plugin_token_error'), url('plugin-read', array('dir' => $dir), TRUE), 3));

        message($res['code'], $res['message']);
    } else {
        $arr = explode('zip_' . $storeid, $s);
    }

    $zipfile = $conf['tmp_path'] . 'plugin_' . $dir . '.zip';
    file_put_contents($zipfile, $s);

    $official = plugin_official_read($dir);
    // 1模板主题 其他插件
    if (1 == $official['type']) {
        // 直接覆盖，如需删除执行 upgrade.php 文件
        $destpath = APP_PATH . 'view/template/';
    } else {
        if (empty($official['upgrade'])) {
            // 完整包 清理原来的钩子，防止叠加
            rmdir_recusive(APP_PATH . 'plugin/' . $dir . '/hook/', 1);
            rmdir_recusive(APP_PATH . 'plugin/' . $dir . '/overwrite/', 1);
        }
        $destpath = APP_PATH . 'plugin/';
    }

    is_dir($destpath) || mkdir($destpath, 0777, TRUE);

    include XIUNOPHP_PATH . 'xn_zip.func.php';
    // 直接覆盖原来应用目录
    xn_unzip($zipfile, $destpath);
    if (is_file($zipfile)) unlink($zipfile);

    // 检查解压是否成功 / check the zip if success
    if (is_dir($destpath . $dir)) {
        $post += array('res' => $arr[1]);
        https_post(PLUGIN_OFFICIAL_URL . 'plugin-notice.html', $post);
    } else {
        message(-1, lang('plugin_maybe_download_failed'));
    }

    // 检查配置文件
    $conffile = $destpath . $dir . '/conf.json';
    !is_file($conffile) and message(-1, 'conf.json ' . lang('not_exists'));
    $arr = xn_json_decode(file_get_contents($conffile));
    empty($arr['name']) and message(-1, 'conf.json ' . lang('format_maybe_error'));

    return TRUE;
}

function plugin_bought($storeid)
{
    global $longip;
    if (!$storeid) return xn_error(-1, lang('data_malformation'));
    $data = plugin_data_verify();
    if (empty($data)) return xn_error(-1, lang('plugin_token_error'));

    $domain = xn_urlencode(_SERVER('HTTP_HOST'));
    $app_url = xn_urlencode(http_url_path());

    $siteip = ip2long(_SERVER('SERVER_ADDR'));
    $siteip < 0 and $siteip = sprintf("%u", $siteip);
    $post = array('storeid' => $storeid, 'siteid' => plugin_siteid(), 'siteip' => $siteip, 'longip' => $longip, 'app_url' => $app_url, 'domain' => $domain, 'token' => $data[4], 'uid' => $data[0]);
    $url = PLUGIN_OFFICIAL_URL . 'plugin-bought.html';
    $s = https_post($url, $post);
    $arr = xn_json_decode($s);
    empty($arr) and message(-1, $url . lang('plugin_return_data_error') . $s);
    if (0 == $arr['code']) {
        return TRUE;
    } else {
        return xn_error($arr['code'], $arr['message']);
    }
}

function plugin_query($storeid, $paying = FALSE)
{
    global $longip;
    if (!$storeid) xn_error(-1, lang('data_malformation'));
    $data = plugin_data_verify();
    if (empty($data)) {
        xn_error(-4, lang('plugin_token_error'));
        return FALSE;
    }

    $arr = cache_get('store-' . $storeid);
    if (empty($arr) || FALSE !== $paying) {
        $domain = xn_urlencode(_SERVER('HTTP_HOST'));
        $siteid = plugin_siteid();
        $siteip = ip2long(_SERVER('SERVER_ADDR'));
        $siteip < 0 and $siteip = sprintf("%u", $siteip);
        $app_url = xn_urlencode(http_url_path());

        $post = array('storeid' => $storeid, 'siteid' => $siteid, 'siteip' => $siteip, 'longip' => $longip, 'app_url' => $app_url, 'domain' => $domain, 'token' => $data[4], 'uid' => $data[0]);
        FALSE !== $paying and $post['paying'] = 1;
        $url = PLUGIN_OFFICIAL_URL . 'plugin-query.html';
        $s = https_post($url, $post);
        if (empty($s)) {
            xn_error(-4, lang('server_response_empty'));
            return FALSE;
        }
        $arr = xn_json_decode($s);

        if (empty($arr) || !isset($arr['code'])) {
            xn_error(-4, lang('plugin_return_data_error') . ',' . $s);
            return FALSE;
        }

        FALSE !== $paying and cache_set('store-' . $storeid, $arr, 7200);
    }

    if (!in_array($arr['code'], array(0, 1))) {
        xn_error($arr['code'], $arr['message']);
        return FALSE;
    }
    // code:0下载 1付费 -1余额不足 -2支付错误 -3数据错误
    // pay_type:0免费 1现金 2积分 3金币
    // pay_api:1支付宝 2微信 3钱包 4积分 5支金币
    // url:qrcode
    // array('code' => 1, 'message' => 'string', 'pay_type' => 0, 'pay_api' => 0, 'url' => 0, 'price' => 1)
    return $arr;
}

function plugin_notice($dir, $type = 0)
{
    global $longip, $ip;

    if (FALSE === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return;

    $url = PLUGIN_OFFICIAL_URL . 'plugin-query.html';
    $s = https_post($url, array('dir' => $dir));
    if ($s) {
        $res = xn_json_decode($s);
        if (1 == $res['code']) {
            $domain = xn_urlencode(_SERVER('HTTP_HOST'));
            $siteid = plugin_siteid();
            $siteip = ip2long(_SERVER('SERVER_ADDR'));
            $siteip < 0 and $siteip = sprintf("%u", $siteip);
            $app_url = xn_urlencode(http_url_path());

            $post = array('storeid' => $res['message'], 'siteid' => $siteid, 'siteip' => $siteip, 'auth_key' => xn_key(), 'longip' => $longip, 'app_url' => $app_url, 'domain' => $domain);
            $data = setting_get('plugin_data');
            if ($data) {
                $key = md5(xn_key());
                $s = xn_decrypt($data, $key);
                $arr = explode("\t", $s);
                isset($arr[4]) and $post += array('token' => $arr[4], 'uid' => $arr[0]);
            }
            $conffile = $type ? APP_PATH . 'view/template/' . $dir . '/conf.json' : APP_PATH . 'plugin/' . $dir . '/conf.json';
            if ($conffile) {
                $arr = xn_json_decode(file_get_contents($conffile));
                isset($arr['key']) and $post += array('key' => $arr['key']);
            }
            if (!$type) {
                $files = well_search_dir(APP_PATH . 'plugin/' . $dir . '/');
                if (!empty($files)) {
                    $funlist = array();
                    foreach ($files as $file) {
                        $s = file_get_contents($file);
                        preg_match_all('#function\s+(.*?)\(#', $s, $arr);
                        if (isset($arr[1])) {
                            foreach ($arr[1] as $name) {
                                $funlist[] = $name;
                            }
                        }
                    }
                    !empty($funlist) and $post += array('funlist' => xn_json_encode($funlist));
                }
            }
            $url = PLUGIN_OFFICIAL_URL . 'plugin-notice.html?' . http_build_query($post);
            https_post($url, $post);
        }
    }
}

function plugin_is_local($dir)
{
    global $plugins, $themes;
    if (isset($plugins[$dir])) {
        return TRUE;
    } else {
        return isset($themes[$dir]) ? TRUE : FALSE;
    }
}

function plugin_check_exists($dir, $local = TRUE)
{
    global $plugins, $official_plugins, $themes;
    !is_word($dir) and message(-1, lang('plugin_name_error'));
    if ($local) {
        empty($plugins[$dir]) and !isset($themes[$dir]) and message(-1, lang('plugin_not_exists'));
    } else {
        !isset($official_plugins[$dir]) and message(-1, lang('plugin_not_exists'));
    }
}

function plugin_cate_active($action, $arr, $type, $page)
{
    $s = '';
    foreach ($arr as $_type => $name) {
        $url = url('plugin-' . $action, array('type' => $_type, 'page' => $page), TRUE);
        $s .= '<a role="button" class="btn btn btn-secondary' . ($type == $_type ? ' active' : '') . '" href="' . $url . '">' . $name . '</a>';
    }
    return $s;
}

function plugin_lock_start()
{
    global $route, $action;
    !xn_lock_start($route . '_' . $action) and message(-1, lang('plugin_task_locked'));
}

function plugin_lock_end()
{
    global $route, $action;
    xn_lock_end($route . '_' . $action);
}

function theme_install($dir)
{
    global $conf, $config;

    $dir = trim($dir);
    !empty($config['theme']) and $config['theme'] != $dir and theme_uninstall($config['theme']);

    $path = APP_PATH . 'view/template/' . $dir;

    $conffile = $path . '/conf.json';
    !is_file($conffile) and message(1, lang('not_exists'));

    $arr = xn_json_decode(file_get_contents($conffile));
    empty($arr) and message(1, lang('data_malformation'));

    $arr['installed'] = 1;
    // 写入配置文件
    file_replace_var($conffile, $arr, TRUE);

    $config['theme'] = $dir;
    setting_set('conf', $config);

    $installfile = $path . '/install.php';
    is_file($installfile) and include _include($installfile);

    rmdir_recusive($conf['tmp_path'], 1);

    return TRUE;
}

function theme_uninstall($dir)
{
    global $conf, $config;

    $path = APP_PATH . 'view/template/' . $dir;

    $conffile = $path . '/conf.json';
    FALSE === is_file($conffile) and message(1, lang('not_exists'));

    $arr = xn_json_decode(file_get_contents($conffile));
    empty($arr) and message(1, lang('data_malformation'));

    $arr['installed'] = 0;
    // 写入配置文件
    file_replace_var($conffile, $arr, TRUE);

    $config['theme'] = '';
    setting_set('conf', $config);

    $uninstallfile = $path . '/uninstall.php';
    is_file($uninstallfile) and include _include($uninstallfile);

    rmdir_recusive($conf['tmp_path'], 1);

    return TRUE;
}

?>