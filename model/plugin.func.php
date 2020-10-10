<?php
// 本地插件
$plugin_paths = array();
$plugins = array(); // 合并官方插件
$themes = array(); // 初始化主题 作者上传后再根据作者增加uid

// 我的仓库列表
$official_plugins = array();

define('PLUGIN_OFFICIAL_URL', 3 == DEBUG ? 'http://www.x.com/' : 'http://www.wellcms.cn/');

$g_include_slot_kv = array();
function _include($srcfile)
{
    global $conf;
    // 合并插件，存入 tmp_path
    $tmpfile = $conf['tmp_path'] . substr(str_replace('/', '_', $srcfile), strlen(APP_PATH));
    // tmp不存在文件则进行编译
    if (!is_file($tmpfile) || DEBUG > 1) {
        // 开始编译
        $s = plugin_compile_srcfile($srcfile);

        // 支持 <template> <slot>$g_include_slot_kv = array();
        for ($i = 0; $i < 10; ++$i) {
            $s = preg_replace_callback('#<template\sinclude="(.*?)">(.*?)</template>#is', '_include_callback_1', $s);
            if (FALSE === strpos($s, '<template')) break;
        }

        file_put_contents_try($tmpfile, $s);

        if ('php' == file_ext($tmpfile) && 0 == DEBUG && $conf['compress'] > 0) {

            $s = trim(php_strip_whitespace($tmpfile));

        } elseif (in_array(file_ext($tmpfile), array('htm', 'html')) && 0 == DEBUG && $conf['compress'] > 0) {

            $s = plugin_compile_srcfile($tmpfile);

            if (1 == $conf['compress']) {
                // 不压缩换行
                $s = str_replace(array("\t"), '', $s);
                $s = preg_replace(array("#> *([^ ]*) *<#", "#<!--[\\w\\W\r\\n]*?-->#", "# \"#", '#>\s+<#', "#/\*[^*]*\*/#", "//", '#\/\*(\s|.)*?\*\/#', "#>\s+\r\n#"), array(">\\1<", '', "\"", '><', '', '', '', '>'), $s);
            } elseif (2 == $conf['compress']) {
                // 全压缩
                $s = preg_replace(array("#> *([^ ]*) *<#", "#[\s]+#", "#<!--[\\w\\W\r\\n]*?-->#", "# \"#", "#/\*[^*]*\*/#", "//", '#>\s+<#', '#\/\*(\s|.)*?\*\/#'), array(">\\1<", ' ', '', "\"", '', '', '><', ''), $s);
            }

        } else {
            $s = plugin_compile_srcfile($tmpfile);
        }
        file_put_contents_try($tmpfile, $s);
    }
    return $tmpfile;
}

function _include_callback_1($m)
{
    global $g_include_slot_kv;
    $r = file_get_contents($m[1]);
    preg_match_all('#<slot\sname="(.*?)">(.*?)</slot>#is', $m[2], $m2);
    if (!empty($m2[1])) {
        $kv = array_combine($m2[1], $m2[2]);
        $g_include_slot_kv += $kv;
        foreach ($g_include_slot_kv as $slot => $content) {
            $r = preg_replace('#<slot\sname="' . $slot . '"\s*/>#is', $content, $r);
        }
    }
    return $r;
}

// 在安装、卸载插件的时候，需要先初始化
function plugin_init()
{
    global $plugin_paths, $themes, $plugins, $official_plugins, $conf;

    $official_plugins = kv_cache_get('plugin_official_list');
    empty($official_plugins) AND $official_plugins = array();

    $plugin_paths = glob(APP_PATH . 'plugin/*', GLOB_ONLYDIR);
    if (is_array($plugin_paths)) {
        foreach ($plugin_paths as $path) {
            $dir = file_name($path);
            $conffile = $path . "/conf.json";
            if (!is_file($conffile)) continue;
            $arr = xn_json_decode(file_get_contents($conffile));
            if (empty($arr)) continue;
            $plugins[$dir] = $arr;

            // 额外的信息
            $plugins[$dir]['hooks'] = array();
            $hookpaths = glob(APP_PATH . "plugin/$dir/hook/*.*"); // path
            if (is_array($hookpaths)) {
                foreach ($hookpaths as $hookpath) {
                    $hookname = file_name($hookpath);
                    $plugins[$dir]['hooks'][$hookname] = $hookpath;
                }
            }

            // 本地 + 线上数据
            $plugins[$dir] = plugin_read_by_dir($dir);
        }
    }

    $theme_paths = glob(APP_PATH . 'view/template/*', GLOB_ONLYDIR);
    if (is_array($theme_paths)) {
        foreach ($theme_paths as $path) {
            $dir = file_name($path);
            $conffile = $path . '/conf.json';
            if (!is_file($conffile)) continue;
            $arr = xn_json_decode(file_get_contents($conffile));
            if (empty($arr)) continue;
            $themes[$dir] = $arr;
            $themes[$dir]['icon'] = '../view/template/' . $dir . '/icon.png';
        }
    }
}

// 插件依赖检测，返回依赖的插件列表，如果返回为空则表示不依赖
/*
	返回依赖的插件数组：
	array(
		'ad'=>'1.0',
		'umeditor'=>'1.0',
	);
*/
function plugin_dependencies($dir)
{
    global $plugin_paths, $plugins, $themes;

    $plugin = isset($plugins[$dir]) ? $plugins[$dir] : $themes[$dir];
    $dependencies = $plugin['dependencies'];

    // 检查插件依赖关系
    $arr = array();
    foreach ($dependencies as $_dir => $version) {
        if (!isset($plugins[$_dir]) || !$plugins[$_dir]['enable']) {
            $arr[$_dir] = $version;
        }
    }
    return $arr;
}

/*
	返回被依赖的插件数组：
	array(
		'ad'=>'1.0',
		'umeditor'=>'1.0',
	);
*/
function plugin_by_dependencies($dir)
{
    global $plugins;
    $arr = array();
    foreach ($plugins as $_dir => $plugin) {
        if (isset($plugin['dependencies'][$dir]) && $plugin['enable']) {
            $arr[$_dir] = $plugin['version'];
        }
    }
    return $arr;
}

function plugin_enable($dir)
{
    global $plugins;
    if (!isset($plugins[$dir])) return FALSE;
    $plugins[$dir]['enable'] = 1;
    file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('enable' => 1), TRUE);
    plugin_clear_tmp_dir();
    return TRUE;
}

// 清空插件的临时目录
function plugin_clear_tmp_dir()
{
    global $conf;
    rmdir_recusive($conf['tmp_path'], TRUE);
    xn_unlink($conf['tmp_path'] . 'model.min.php');
}

function plugin_disable($dir)
{
    global $plugins;
    if (!isset($plugins[$dir])) return FALSE;
    $plugins[$dir]['enable'] = 0;
    file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('enable' => 0), TRUE);
    plugin_clear_tmp_dir();
    return TRUE;
}

// 安装所有的本地插件
/*function plugin_install_all()
{
    global $plugins;

    // 检查文件更新
    foreach ($plugins as $dir => $plugin) {
        plugin_install($dir);
    }
}*/

// 卸载所有的本地插件
/*function plugin_uninstall_all()
{
    global $plugins;

    // 检查文件更新
    foreach ($plugins as $dir => $plugin) {
        plugin_uninstall($dir);
    }
}*/

/*
	插件安装：
	把所有的插件点合并，重新写入文件。如果没有备份文件，则备份一份。
	插件名可以为源文件名：view/header.htm
*/
function plugin_install($dir)
{
    global $plugins;
    if (!isset($plugins[$dir])) return FALSE;
    $plugins[$dir]['installed'] = 1;
    $plugins[$dir]['enable'] = 1;
    // 写入配置文件
    file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('installed' => 1, 'enable' => 1), TRUE);
    plugin_clear_tmp_dir();
    return TRUE;
}

// copy from plugin_install 修改
function plugin_uninstall($dir)
{
    global $plugins;
    if (!isset($plugins[$dir])) return TRUE;
    $plugins[$dir]['installed'] = 0;
    $plugins[$dir]['enable'] = 0;
    // 写入配置文件
    file_replace_var(APP_PATH . "plugin/$dir/conf.json", array('installed' => 0, 'enable' => 0), TRUE);
    plugin_clear_tmp_dir();
    return TRUE;
}

// 返回所有开启的插件
function plugin_paths_enabled()
{
    static $return_paths;
    if (empty($return_paths)) {
        $return_paths = array();
        $plugin_paths = glob(APP_PATH . 'plugin/*', GLOB_ONLYDIR);
        if (empty($plugin_paths)) return array();
        foreach ($plugin_paths as $path) {
            $conffile = $path . '/conf.json';
            if (!is_file($conffile)) continue;
            $pconf = xn_json_decode(file_get_contents($conffile));
            if (empty($pconf)) continue;
            if (empty($pconf['enable']) || empty($pconf['installed'])) continue;
            $return_paths[$path] = $pconf;
        }
    }
    return $return_paths;
}

// 编译源文件，把插件合并到该文件，不需要递归，执行的过程中 include _include() 自动递归
function plugin_compile_srcfile($srcfile)
{
    global $conf;
    // 判断是否开启插件
    if (!empty($conf['disabled_plugin'])) {
        $s = file_get_contents($srcfile);
        return $s;
    }

    // 如果有 overwrite，则用 overwrite 替换掉
    $srcfile = plugin_find_overwrite($srcfile);
    $s = file_get_contents($srcfile);

    // 最多支持 10 层 合并html模板hook和php文件hook
    for ($i = 0; $i <= 10; ++$i) {
        if (FALSE !== strpos($s, '<!--{hook') || FALSE !== strpos($s, '// hook')) {
            $s = preg_replace('#<!--{hook\s+(.*?)}-->#', '// hook \\1', $s);
            $s = preg_replace_callback('#//\s*hook\s+(\S+)#is', 'plugin_compile_srcfile_callback', $s);
        } else {
            break;
        }
    }

    return $s;
}

/* 只返回一个权重最高的文件名，最大值overwrite，read.php 文件:值
 * "overwrites_rank":{"read.php": 100}
 * */
function plugin_find_overwrite($srcfile)
{
    // 遍历所有开启的插件
    $plugin_paths = plugin_paths_enabled();
    $len = strlen(APP_PATH);
    $returnfile = $srcfile;
    $maxrank = 0;

    foreach ($plugin_paths as $path => $pconf) {
        // 获取插件目录名
        $dir = file_name($path);
        $filepath_half = substr($srcfile, $len);
        $overwrite_name = file_name($srcfile); // 获取覆盖的文件
        $overwrite_file = APP_PATH . "plugin/$dir/overwrite/$filepath_half";
        if (is_file($overwrite_file)) {
            $rank = isset($pconf['overwrites_rank'][$overwrite_name]) ? $pconf['overwrites_rank'][$overwrite_name] : 0;
            if ($rank >= $maxrank) {
                $returnfile = $overwrite_file;
                $maxrank = $rank;
            }
        }
    }

    return $returnfile;
}

/* 多文件同时hook一点，最大值先hook
 * "hooks_rank":{"read_start.php": 100} file:val
 * "hooks_rank":{"read_start.htm": 100} file:val
 * */
function plugin_compile_srcfile_callback($m)
{
    static $hooks;
    if (empty($hooks)) {
        $hooks = array();
        $plugin_paths = plugin_paths_enabled();

        foreach ($plugin_paths as $path => $pconf) {
            $dir = file_name($path);
            $hookpaths = glob(APP_PATH . "plugin/$dir/hook/*.*"); // path
            if (is_array($hookpaths)) {
                foreach ($hookpaths as $hookpath) {
                    $hookname = file_name($hookpath);
                    $rank = isset($pconf['hooks_rank']["$hookname"]) ? $pconf['hooks_rank']["$hookname"] : 0;
                    $hooks[$hookname][] = array('hookpath' => $hookpath, 'rank' => $rank);

                }
            }
        }

        foreach ($hooks as $hookname => $arrlist) {
            $arrlist = arrlist_multisort($arrlist, 'rank', FALSE);
            $hooks[$hookname] = arrlist_values($arrlist, 'hookpath');
        }
    }

    $s = '';
    $hookname = $m[1];
    if (!empty($hooks[$hookname])) {
        $fileext = file_ext($hookname);
        foreach ($hooks[$hookname] as $path) {
            $t = file_get_contents($path);
            if ('php' == $fileext && preg_match('#^\s*<\?php\s+exit;#is', $t)) {
                // 正则表达式去除兼容性比较好。
                $t = preg_replace('#^\s*<\?php\s*exit;(.*?)(?:\?>)?\s*$#is', '\\1', $t);
            }
            $s .= $t;
        }
    }

    return $s;
}

// -------------------> 官方插件列表缓存到本地。

// 条件满足的总数
function plugin_official_total($cond = array())
{
    global $official_plugins;
    $offlist = $official_plugins;
    $offlist = arrlist_cond_orderby($offlist, $cond, array(), 1, 1000);
    return count($offlist);
}

function plugin_official_list($cond = array(), $orderby = array('storeid' => -1), $page = 1, $pagesize = 20)
{
    global $official_plugins;
    // 服务端信息，缓存起来
    $offlist = $official_plugins;
    $offlist = arrlist_cond_orderby($offlist, $cond, $orderby, $page, $pagesize);
    foreach ($offlist as &$plugin) $plugin = plugin_read_by_dir($plugin['dir'], FALSE);
    return $offlist;
}

/* 从官方服务器获取数据
 * @param int $type 1 Synchronous Data
 * @return bool|mixed|null|string
 */
function plugin_official_store($type = 0)
{
    global $conf, $ip;

    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return NULL;

    $s = '';
    if ($type) {
        $cookie = _COOKIE($conf['cookie_pre'] . 'plugin_official_list');
        if ($cookie) $s = cache_get('plugin_official_list');
    }

    if (empty($s)) {
        $arr = plugin_data_verify();
        if (FALSE === $arr) {
            setting_delete('plugin_data');
            return NULL;
        }

        $url = PLUGIN_OFFICIAL_URL . 'plugin-store.html';
        $post = array('siteid' => plugin_siteid(), 'domain' => xn_urlencode(_SERVER('HTTP_HOST')), 'token' => $arr[4], 'uid' => $arr[0]);
        $s = https_request($url, $post, '', 500, 1);

        // 检查返回值是否正确
        if (empty($s)) return xn_error(-1, lang('plugin_get_data_failed'));
        $s = xn_json_decode($s);
        if (empty($s)) return xn_error(-1, lang('plugin_get_data_fmt_failed'));

        cache_set('plugin_official_list', $s);
        cookie_set('plugin_official_list', 1, 120);
    }

    return $s;
}

function plugin_official_read($dir)
{
    global $official_plugins;
    $offlist = $official_plugins;
    $plugin = isset($offlist[$dir]) ? $offlist[$dir] : array();
    return $plugin;
}

// -------------------> 本地插件列表缓存到本地
// TRUE:插件 FALSE:主题风格
function plugin_list($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $type = TRUE)
{
    global $plugins, $themes;
    $arrlist = TRUE === $type ? $plugins : $themes;
    $offlist = arrlist_cond_orderby($arrlist, $cond, $orderby, $page, $pagesize);
    return $offlist;
}

// 安装，卸载，禁用，更新
function plugin_read_by_dir($dir, $local_first = TRUE)
{
    global $plugins, $themes;

    $type = 0;
    $icon = is_file(APP_PATH . 'plugin/' . $dir . '/icon.png') ? '../plugin/' . $dir . '/icon.png' : '';
    $local = array_value($plugins, $dir, array());
    if (empty($local)) {
        if (isset($themes[$dir]) && $local = $themes[$dir]) {
            $type = 1;
            $icon = is_file(APP_PATH . 'view/template/' . $dir . '/icon.png') ? '../view/template/' . $dir . '/icon.png' : '';
        }
    }

    $official = plugin_official_read($dir);
    if (empty($local) && empty($official)) return array();
    if (empty($local)) $local_first = FALSE;

    // 本地插件信息
    !isset($local['name']) && $local['name'] = '';
    !isset($local['price']) && $local['price'] = 0;
    !isset($local['brief']) && $local['brief'] = '';
    !isset($local['version']) && $local['version'] = '1.0.0';
    !isset($local['software_version']) && $local['software_version'] = '2.0';
    !isset($local['installed']) && $local['installed'] = 0;
    !isset($local['enable']) && $local['enable'] = 0;
    !isset($local['hooks']) && $local['hooks'] = array();
    !isset($local['hooks_rank']) && $local['hooks_rank'] = array();
    !isset($local['dependencies']) && $local['dependencies'] = array();
    !isset($local['icon_url']) && $local['icon_url'] = '';
    !isset($local['have_setting']) && $local['have_setting'] = 0;
    !isset($local['setting_url']) && $local['setting_url'] = 0;
    !isset($local['author']) && $local['author'] = 0;
    !isset($local['domain']) && $local['domain'] = 0;
    !isset($local['type']) && $local['type'] = $type; // 0插件 1主题

    // 加上官方插件的信息
    !isset($official['storeid']) && $official['storeid'] = 0;
    !isset($official['name']) && $official['name'] = '';
    !isset($official['price']) && $official['price'] = 0;
    !isset($official['original_price']) && $official['original_price'] = 0;
    !isset($official['brief']) && $official['brief'] = '';
    !isset($official['software_version']) && $official['software_version'] = '2.0';
    !isset($official['version']) && $official['version'] = '1.0.0';
    // 0 所有插件 1主题风格 2功能增强 3大型插件 4接口整合 99未分类
    !isset($official['type']) && $official['type'] = 0;
    !isset($official['last_update']) && $official['last_update'] = 0;
    !isset($official['create_date']) && $official['create_date'] = 0;
    !isset($official['last_update_fmt']) && $official['last_update_fmt'] = lang('none');
    !isset($official['stars']) && $official['stars'] = 0;
    !isset($official['user_stars']) && $official['user_stars'] = 0;
    !isset($official['downloads']) && $official['downloads'] = 0;
    !isset($official['sells']) && $official['sells'] = 0;
    !isset($official['file_md5']) && $official['file_md5'] = '';
    !isset($official['filename']) && $official['filename'] = '';
    !isset($official['is_cert']) && $official['is_cert'] = 0;
    !isset($official['is_show']) && $official['is_show'] = 0;
    !isset($official['brief_url']) && $official['brief_url'] = '';
    !isset($official['qq']) && $official['qq'] = '';
    !isset($official['author']) && $official['author'] = '';
    !isset($official['uid']) && $official['uid'] = '';
    !isset($official['domain']) && $official['domain'] = '';
    !isset($official['upgrade']) && $official['upgrade'] = 0;

    $local['official'] = $official;

    if ($local_first) {
        $plugin = $local + $official;
    } else {
        $plugin = $official + $local;
    }

    // 额外的判断
    $plugin['icon_url'] = $icon ? $icon : ($official['storeid'] ? PLUGIN_OFFICIAL_URL . 'upload/plugin/'.date('Ym', $plugin['create_date']).'/'.$plugin['storeid'].'/icon.png' : '');
    $plugin['setting_url'] = $plugin['installed'] && is_file(APP_PATH . "plugin/$dir/setting.php") ? "plugin-setting-$dir.html" : "";
    $plugin['downloaded'] = isset($plugins[$dir]);
    // 10赞一星 100赞二星 1k赞三星 10k赞四星 100k+五星
    $plugin['stars_fmt'] = $official['storeid'] ? str_repeat('<span class="icon-star"></span>', $official['stars']) : '';
    $plugin['user_stars_fmt'] = $official['storeid'] ? str_repeat('<span class="icon-star"></span>', $official['user_stars']) : '';
    $plugin['have_upgrade'] = $plugin['installed'] && version_compare($official['version'], $local['version']) > 0 ? TRUE : FALSE;
    $plugin['official_version'] = $official['version'];
    $plugin['upgrade'] = $official['upgrade'];
    $plugin['downloads'] = $official['downloads'];

    return $plugin;
}

function plugin_siteid()
{
    global $conf;
    $auth_key = $conf['auth_key'];
    $siteip = _SERVER('SERVER_ADDR');
    return md5($auth_key . $siteip);
}

?>