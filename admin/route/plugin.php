<?php
!defined('DEBUG') AND exit('Access Denied.');

group_access($gid, 'manageplugin') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

include XIUNOPHP_PATH . 'xn_zip.func.php';

$action = param(1);

// 初始化插件变量 / init plugin var
plugin_init();

empty($action) AND $action = 'local';

if ($action == 'list') {

    // 本地插件 local plugin list
    $pluginlist = $plugins;

    $pagination = $plugin_cate_html = '';
    $safe_token = well_token_set($uid);

    $header['title'] = lang('local_plugin');
    $header['mobile_title'] = lang('local_plugin');

    include _include(ADMIN_PATH . "view/htm/plugin_list.htm");

} elseif ($action == 'theme') {

    if ($method == 'GET') {

        $pagination = '';

        $header['title'] = lang('local') . lang('theme');
        $header['mobile_title'] = lang('local') . lang('theme');

        include _include(ADMIN_PATH . "view/htm/theme_list.htm");

    } elseif ($method == 'POST') {

        group_access($gid, 'manageplugin') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

        $dir = param_word(2);
        $type = param(3, 0);

        empty($dir) AND message(1, lang('data_malformation'));

        if ($type) {
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

} elseif ($action == 'store') {

    // 拉取用户在官方收录的主题和插件
    if ($method == 'GET') {
        // 0 所有插件 1主题风格 2功能增强 3大型插件 4接口整合
        $type = param(2, 0);
        $page = param(3, 1);
        $free = param('free', 0); // 免费和付费插件
        $extra = array('free' => $free);
        $pagesize = 10;
        $cond = $type ? array('type' => $type) : array();
        $cond['price'] = $free ? array('>' => 0) : 0;

        // plugin category
        $plugin_cates = array(0 => lang('plugin_cate_0'), 1 => lang('plugin_cate_1'), 2 => lang('plugin_cate_2'), 3 => lang('plugin_cate_3'), 4 => lang('plugin_cate_4'));

        $plugin_cate_html = plugin_cate_active($action, $plugin_cates, $type, $page);

        // official plugin
        $total = plugin_official_total($cond);
        $pluginlist = plugin_official_list($cond, array('storeid' => -1), $page, $pagesize);

        $pagination = pagination(url("plugin-$action-$type-{page}", $extra), $total, $page, $pagesize);

        $data_verify = plugin_data_verify();
        $server = filter_var(gethostbyname(_SERVER('HTTP_HOST')), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        $safe_token = well_token_set($uid);

        $header['title'] = lang('official_store');
        $header['mobile_title'] = lang('official_store');

        include _include(ADMIN_PATH . "view/htm/plugin_list.htm");

    } elseif ($method == 'POST') {

        $safe_token = param('safe_token');
        well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

        plugin_official_storehouse(1);

        message(0, lang('operator_complete'));
    }

} elseif ($action == 'read') {

    // 给出二维码扫描后开始下载
    if ($method == 'GET') {
        // 给出插件的介绍，1.已购买直接下载安装；2.未购买显示，登录框，登录后显示付款二维码
        $dir = param_word(2);

        $plugin = plugin_read_by_dir($dir);
        empty($plugin) AND message(-1, lang('plugin_not_exists'));

        $verify_token = TRUE;
        $download_url = '';
        $errmsg = '';
        $server = filter_var(gethostbyname(_SERVER('HTTP_HOST')), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        // 线上模式可登录 升级 购买
        $url = $server ? '' : FALSE;

        if (!empty($plugin['official']['storeid']) && !empty($plugin['official']) && $server) {
            /*
             * 1.判断是否购买过，传token到官方核对
             * 2.之前免费，后来收费，则判断是否已经支付，传token到官方核对
             * 3.如果收费，判断是否购买过，传token到官方核对
             * 4.未购买登录官方账号，获取支付二维码
             * 5.付款后给出下载地址，下载时传token到官方核对
             */
            if ($plugin['official']['price'] > 0) {
                $verify_token = plugin_data_verify(); // return FALSE re-login
                if ($verify_token === TRUE) {
                    /* code 已经购买过，或者发生错误
                       0: 返回支付 URL二维码
                       1: 已经支付
                       2: 不需要支付
                       -1: 业务逻辑错误
                       <-1: 系统错误
                    */
                    $url = plugin_order_buy_qrcode($plugin['official']['storeid']);

                    if ($url === FALSE) {
                        if ($errno == 1 || $errno == 2) {
                            // 已经支付，就给出下载地址。
                            $download_url = url("plugin-download-$dir");
                        } else {
                            $download_url = '';
                            $errmsg = $errstr;
                        }
                    }
                }
            }
        }

        // 本地是否有该插件
        $islocal = plugin_is_local($dir);
        $tab = empty($islocal) ? ($plugin['price'] > 0 ? 'official_fee' : 'official_free') : 'local';

        $safe_token = well_token_set($uid);

        $header['title'] = lang('plugin_detail') . '-' . $plugin['name'];
        $header['mobile_title'] = $plugin['name'];

        include _include(ADMIN_PATH . "view/htm/plugin_read.htm");

    } elseif ($method == 'POST') {

        $email = param('email');
        empty($email) AND message('email', lang('email_is_empty'));

        $password = param('password');
        empty($password) AND message('password', lang('please_input_password'));

        $post = array('email' => $email, 'password' => md5($password), 'auth_key' => $conf['auth_key'], 'siteip' => _SERVER('SERVER_ADDR'), 'domain' => xn_urlencode(_SERVER('HTTP_HOST')), 'ua' => md5($useragent));
        $url = PLUGIN_OFFICIAL_URL . 'plugin-login.html';
        $json = https_request($url, $post, '', 500, 1);
        empty($json) AND message(-1, lang('server_response_empty'));
        $r = xn_json_decode($json);
        // -1用户不存在 -2用户被锁 0正常 1用户名错误 2密码错误
        if ($r['code'] == 0) {
            setting_set('plugin_data', $r['data']);
            message(0, lang('login_successfully'));
        }
        $r['code'] == 1 AND message(-1, lang('password_incorrect'));
        $r['code'] == 2 AND message(-1, lang('username_not_exists'));
        $r['code'] == -1 AND message(-1, lang('user_not_exists'));
        $r['code'] == -2 AND message(-1, lang('user_locked'));
    }

} elseif ($action == 'is_bought') {

    // 定时查询是否支付成功
    $dir = param_word(2);
    plugin_check_exists($dir, FALSE);
    $plugin = plugin_read_by_dir($dir);

    $plugin['official']['price'] == 0 AND message(1, lang('plugin_is_free'));

    plugin_bought($plugin['official']['storeid']) ? message(0, lang('plugin_is_bought')) : message(2, lang('plugin_not_bought'));

} elseif ($action == 'download') {

    $dir = param_word(2);
    plugin_check_exists($dir, FALSE);

    plugin_verify_token() === FALSE AND message(-1, jump(lang('plugin_token_error'), url("plugin-read-$dir"), 1));

    // 下载官方插件 区分插件和主题 / download official plugin
    plugin_lock_start();

    $official = plugin_official_read($dir);
    empty($official) AND message(-1, lang('plugin_not_exists'));

    // 检查版本  / check version match
    version_compare($conf['version'], $official['software_version']) == -1 AND message(-1, lang('plugin_version_not_match', array('software_version' => $official['software_version'], 'version' => $conf['version'])));

    // 下载，解压 / download and zip
    plugin_download_unzip($dir, $official['storeid']);

    plugin_lock_end();

    message(0, jump(lang('plugin_download_sucessfully', array('dir' => $dir)), url('plugin-read-' . $dir), 3));

} elseif ($action == 'install') {

    $safe_token = param('safe_token');
    well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

    plugin_lock_start();

    $dir = param_word(2);
    plugin_check_exists($dir);
    $name = $plugins[$dir]['name'];

    // 插件依赖检查 / check plugin dependency
    plugin_check_dependency($dir, 'install');

    // 安装插件 / install plugin
    plugin_install($dir);

    $installfile = APP_PATH . "plugin/$dir/install.php";
    is_file($installfile) AND include _include($installfile);

    plugin_lock_end();

    // 卸载同类插件，防止安装类似插件 自动卸载掉其他已经安装的主题 / automatically uninstall other theme plugin.
    if (strpos($dir, '_theme_') !== FALSE) {
        foreach ($plugins as $_dir => $_plugin) {
            if ($dir == $_dir) continue;
            strpos($_dir, '_theme_') !== FALSE AND plugin_uninstall($_dir);
        }
    } else {
        // 卸载掉同类插件
        $suffix = substr($dir, strpos($dir, '_'));
        foreach ($plugins as $_dir => $_plugin) {
            if ($dir == $_dir) continue;
            $_suffix = substr($_dir, strpos($_dir, '_'));
            $suffix == $_suffix AND plugin_uninstall($_dir);
        }
    }

    $msg = lang('plugin_install_successfully', array('name' => $name));
    message(0, jump($msg, url('plugin-list'), 2));

} elseif ($action == 'uninstall') {

    $safe_token = param('safe_token');
    well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

    plugin_lock_start();

    $dir = param_word(2);
    plugin_check_exists($dir);
    $name = $plugins[$dir]['name'];

    // 插件依赖检查
    plugin_check_dependency($dir, 'uninstall');

    // 卸载插件
    plugin_uninstall($dir);

    $uninstallfile = APP_PATH . "plugin/$dir/uninstall.php";
    is_file($uninstallfile) AND include _include($uninstallfile);

    // 删除插件
    //!DEBUG && rmdir_recusive("../plugin/$dir");

    plugin_lock_end();

    $msg = lang('plugin_uninstall_successfully', array('name' => $name, 'dir' => "plugin/$dir"));
    message(0, jump($msg, url('plugin-list'), 3));

} elseif ($action == 'enable') {

    $safe_token = param('safe_token');
    well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

    plugin_lock_start();

    $dir = param_word(2);
    plugin_check_exists($dir);
    $name = $plugins[$dir]['name'];

    // 插件依赖检查
    plugin_check_dependency($dir, 'install');

    // 启用插件
    plugin_enable($dir);

    plugin_lock_end();

    $msg = lang('plugin_enable_successfully', array('name' => $name));
    message(0, jump($msg, url('plugin-read-' . $dir), 1));

} elseif ($action == 'disable') {

    $safe_token = param('safe_token');
    well_token_verify($uid, $safe_token) === FALSE AND message(1, lang('illegal_operation'));

    plugin_lock_start();

    $dir = param_word(2);
    plugin_check_exists($dir);
    $name = $plugins[$dir]['name'];

    // 插件依赖检查
    plugin_check_dependency($dir, 'uninstall');

    // 禁用插件
    plugin_disable($dir);

    plugin_lock_end();

    $msg = lang('plugin_disable_successfully', array('name' => $name));
    message(0, jump($msg, url('plugin-read-' . $dir), 3));

} elseif ($action == 'upgrade') {

    plugin_lock_start();

    $dir = param_word(2);
    plugin_check_exists($dir, FALSE);
    $name = $plugins[$dir]['name'];

    // 判断插件版本
    $plugin = plugin_read_by_dir($dir);

    // 插件依赖检查
    plugin_check_dependency($dir, 'install');
    $official = plugin_read_by_dir($dir, FALSE);

    // 检查版本  / check version match
    if (version_compare($conf['version'], $official['software_version']) == -1) {
        message(-1, lang('plugin_version_not_match', array('software_version' => $official['software_version'], 'version' => $conf['version'])));
    }

    // 下载，解压 / download and zip
    plugin_download_unzip($dir, $official['storeid']);

    if (empty($official['type'])) {
        // 安装插件
        plugin_install($dir);
        $upgradefile = APP_PATH . "plugin/$dir/upgrade.php";
        is_file($upgradefile) AND include _include($upgradefile);
    } else {
        theme_install($dir);
    }

    plugin_lock_end();

    $msg = lang('plugin_upgrade_sucessfully', array('name' => $name));
    message(0, jump($msg, http_referer(), 3));

} elseif ($action == 'setting') {

    $dir = param_word(2);
    plugin_check_exists($dir);
    $name = $plugins[$dir]['name'];

    include _include(APP_PATH . "plugin/$dir/setting.php");
}

function plugin_check_dependency($dir, $action = 'install')
{
    global $plugins, $themes;
    $name = isset($plugins[$dir]) ? $plugins[$dir]['name'] : $themes[$dir]['name'];
    if ($action == 'install') {
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
        $url = url("plugin-read-$dir");
        $s .= " <a href=\"$url\">【{$name}】</a> ";
    }
    return $s;
}

function plugin_verify_token()
{
    $arr = plugin_data_verify();
    if ($arr === FALSE) {
        setting_delete('plugin_data');
        return FALSE;
    }
    $domain = xn_urlencode(_SERVER('HTTP_HOST'));
    $url = PLUGIN_OFFICIAL_URL . 'plugin-verify.html';
    $post = array('siteid' => plugin_siteid(), 'domain' => $domain, 'token' => $arr[4], 'uid' => $arr[0]);
    // return TRUE or FALSE
    return https_request($url, $post, '', 500, 1);
}

function plugin_data_verify()
{
    global $conf, $time;

    $data = setting_get('plugin_data');
    if (empty($data)) return FALSE;

    $key = md5($conf['auth_key']);
    $s = xn_decrypt($data, $key);
    if (empty($s)) return FALSE;

    $arr = explode("\t", $s);
    if (count($arr) != 5) return FALSE;

    $domain = _SERVER('HTTP_HOST');
    if ($arr[1] != plugin_siteid() || $time - $arr[3] > 2592000 || strpos($domain, $arr[2]) === FALSE) return FALSE;

    return $arr;
}

function plugin_download_unzip($dir, $storeid)
{
    global $conf;

    $data = plugin_data_verify();
    empty($data) AND message(-1, jump(lang('plugin_token_error'), url("plugin-read-$dir")));

    $app_url = xn_urlencode(http_url_path());
    $domain = xn_urlencode(_SERVER('HTTP_HOST'));
    $url = PLUGIN_OFFICIAL_URL . 'plugin-download.html';

    $post = array('storeid' => $storeid, 'siteid' => plugin_siteid(), 'app_url' => $app_url, 'domain' => $domain, 'token' => $data[4], 'uid' => $data[0]);

    // 服务端获取下载地址开始下载 readfile() 直接输出也可以
    $res = https_request($url, $post, '', 500, 1);
    ($res == -1 || empty($res)) AND message(-1, jump(lang('server_response_empty'), url("plugin-read-$dir"), 3));
    $res == -2 AND message(-1, jump(lang('user_locked'), url("plugin-read-$dir"), 3));
    set_time_limit(0);
    $s = https_request($res, $post, '', 60);
    empty($s) AND message(-1, $url . lang('plugin_return_data_error') . lang('server_response_empty'));
    if (substr($s, 0, 2) != 'PK') {
        $arr = xn_json_decode($s);

        empty($arr) AND message(-1, $url . lang('plugin_return_data_error') . $s);

        $arr['code'] == -2 AND message(-2, jump(lang('plugin_is_not_free'), url("plugin-read-$dir")));

        $arr['code'] == -1 AND message(-1, jump(lang('plugin_token_error'), url("plugin-read-$dir")));

        message($arr['code'], $url . lang('plugin_return_data_error') . $arr['message']);
    }

    $zipfile = $conf['tmp_path'] . 'plugin_' . $dir . '.zip';
    file_put_contents($zipfile, $s);

    $official = plugin_official_read($dir);
    // 0插件 1主题
    if (empty($official['type'])) {
        // 清理原来的钩子，防止叠加。
        rmdir_recusive(APP_PATH . "plugin/$dir/hook/", 1);
        rmdir_recusive(APP_PATH . "plugin/$dir/overwrite/", 1);
        $destpath = APP_PATH . 'plugin/';
    } elseif ($official['type'] == 1) {
        //rmdir_recusive(APP_PATH . "view/template/$dir/", 1);
        $destpath = APP_PATH . 'view/template/';
    }

    // 直接覆盖原来的 plugin 目录下的插件目录
    xn_unzip($zipfile, $destpath);

    // 检查配置文件
    $conffile = $destpath . $dir . '/conf.json';
    !is_file($conffile) AND message(-1, 'conf.json ' . lang('not_exists'));
    $arr = xn_json_decode(file_get_contents($conffile));
    empty($arr['name']) AND message(-1, 'conf.json ' . lang('format_maybe_error'));

    // 检查解压是否成功 / check the zip if sucess
    !is_dir($destpath . $dir) AND message(-1, lang('plugin_maybe_download_failed'));

    $url = PLUGIN_OFFICIAL_URL . 'plugin-notice.html';
    https_request($url, $post, '', 500, 1);

    return TRUE;
}

// 查询是否购买了
function plugin_bought($storeid)
{
    $data = plugin_data_verify();
    if (empty($data)) return xn_error(-1, lang('plugin_token_error'));

    $domain = xn_urlencode(_SERVER('HTTP_HOST'));
    $app_url = xn_urlencode(http_url_path());
    $url = PLUGIN_OFFICIAL_URL . 'plugin-bought.html';
    $post = array('storeid' => $storeid, 'siteid' => plugin_siteid(), 'app_url' => $app_url, 'domain' => $domain, 'token' => $data[4], 'uid' => $data[0]);
    $s = https_request($url, $post, '', 500, 1);
    $arr = xn_json_decode($s);
    empty($arr) AND message(-1, $url . lang('plugin_return_data_error') . $s);
    if ($arr['code'] == 0) {
        return TRUE;
    } else {
        return xn_error($arr['code'], $arr['message']);
    }
}

function plugin_order_buy_qrcode($storeid)
{
    $data = plugin_data_verify();
    if (empty($data)) return xn_error(-1, lang('plugin_token_error'));

    $domain = xn_urlencode(_SERVER('HTTP_HOST'));
    $siteid = plugin_siteid();
    $app_url = xn_urlencode(http_url_path());
    $url = PLUGIN_OFFICIAL_URL . 'plugin-qrcode.html';
    $post = array('storeid' => $storeid, 'siteid' => $siteid, 'app_url' => $app_url, 'domain' => $domain, 'token' => $data[4], 'uid' => $data[0]);
    $s = https_request($url, $post, '', 1);
    if (empty($s)) return xn_error(-1, lang('server_response_empty'));
    $arr = xn_json_decode($s);

    if (empty($arr) || !isset($arr['code'])) return xn_error($arr['code'], $url . lang('plugin_return_data_error') . $s);

    if ($arr['code'] == 0) {
        return $arr['message']; // 支付成功
    } elseif ($arr['code'] == -1) {
        return xn_error(-1, lang('plugin_token_error'));
    } else {
        return xn_error($arr['code'], $url . lang('plugin_return_data_error') . $arr['message']);
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
    !is_word($dir) AND message(-1, lang('plugin_name_error'));
    if ($local) {
        empty($plugins[$dir]) AND !isset($themes[$dir]) AND message(-1, lang('plugin_not_exists'));
    } else {
        !isset($official_plugins[$dir]) AND message(-1, lang('plugin_not_exists'));
    }
}

function plugin_cate_active($action, $arr, $type, $page)
{
    $s = '';
    foreach ($arr as $_type => $name) {
        $url = url("plugin-$action-$_type-$page");
        $s .= '<a role="button" class="btn btn btn-secondary' . ($type == $_type ? ' active' : '') . '" href="' . $url . '">' . $name . '</a>';
    }
    return $s;
}

function plugin_lock_start()
{
    global $route, $action;
    !xn_lock_start($route . '_' . $action) AND message(-1, lang('plugin_task_locked'));
}

function plugin_lock_end()
{
    global $route, $action;
    xn_lock_end($route . '_' . $action);
}

// 传入主题名，英文或数字，不允许空格 特殊字符
function theme_install($dir)
{
    global $conf, $config;

    $dir = trim($dir);
    !empty($config['theme']) AND $config['theme'] != $dir AND theme_uninstall($config['theme']);
    $path = APP_PATH . 'view/template/' . $dir;

    $conffile = $path . '/conf.json';
    !is_file($conffile) AND message(1, lang('not_exists'));

    $arr = xn_json_decode(file_get_contents($conffile));
    empty($arr) AND message(1, lang('data_malformation'));

    $arr['installed'] = 1;
    // 写入配置文件
    file_replace_var($conffile, $arr, TRUE);

    $config['theme'] = $dir;
    setting_set('conf', $config);

    rmdir_recusive($conf['tmp_path'], 1);

    return TRUE;
}

function theme_uninstall($dir)
{
    global $conf, $config;

    $path = APP_PATH . 'view/template/' . $dir;

    $conffile = $path . '/conf.json';
    is_file($conffile) === FALSE AND message(1, lang('not_exists'));

    $arr = xn_json_decode(file_get_contents($conffile));
    empty($arr) AND message(1, lang('data_malformation'));

    $arr['installed'] = 0;
    // 写入配置文件
    file_replace_var($conffile, $arr, TRUE);

    $config['theme'] = '';
    setting_set('conf', $config);

    rmdir_recusive($conf['tmp_path'], 1);

    return TRUE;
}

?>