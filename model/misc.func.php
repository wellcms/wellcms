<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model_misc_start.php

/*
	url("thread-create-1.htm");
	根据 $conf['url_rewrite_on'] 设置，返回以下四种格式：
	?thread-create-1.htm
	thread-create-1.htm
	?/thread/create/1
	/thread/create/1
*/
function url($url, $extra = array())
{
    $conf = _SERVER('conf');
    !isset($conf['url_rewrite_on']) AND $conf['url_rewrite_on'] = 0;
    // hook model_url_start.php
    $r = $path = $query = '';
    if (strpos($url, '/') !== FALSE) {
        $path = substr($url, 0, strrpos($url, '/') + 1);
        $query = substr($url, strrpos($url, '/') + 1);
    } else {
        $path = '';
        $query = $url;
    }
    // hook model_url_before.php
    if ($conf['url_rewrite_on'] == 0) {
        $r = $path . '?' . $query . '.html';
    } elseif ($conf['url_rewrite_on'] == 1) {
        $r = $path . $query . '.html';
    } elseif ($conf['url_rewrite_on'] == 2) {
        $r = $conf['path'] . $path . '?' . str_replace('-', '/', $query);
    } elseif ($conf['url_rewrite_on'] == 3) {
        $r = $conf['path'] . $path . str_replace('-', '/', $query);
    }
    $admin_access = GLOBALS('admin_access');
    if (isset($admin_access) && $conf['url_rewrite_on'] > 1 && strpos($r, '/operate/') === FALSE) $r = '/admin' . $r;
    // 附加参数
    if ($extra) {
        $args = http_build_query($extra);
        $sep = strpos($r, '?') === FALSE ? '?' : '&';
        $r .= $sep . $args;
    }
    // hook model_url_end.php
    return $r;
}

// 检测站点的运行级别
function check_runlevel()
{
    global $conf, $method, $gid;
    $rules = array(
        'user' => array('login', 'create', 'logout', 'sendinitpw', 'resetpw', 'resetpw_sendcode', 'resetpw_complete', 'synlogin')
    );
    // hook model_check_runlevel_start.php
    if ($gid == 1) return;
    $param0 = param(0);
    $param1 = param(1);
    foreach ($rules as $route => $actions) {
        if ($param0 == $route && (empty($actions) || in_array($param1, $actions))) {
            return;
        }
    }
    switch ($conf['runlevel']) {
        case 0:
            message(-1, $conf['runlevel_reason']);
            break;
        case 1:
            message(-1, lang('runlevel_reson_1'));
            break;
        case 2:
            ($gid == 0 || $method != 'GET') AND message(-1, lang('runlevel_reson_2'));
            break;
        case 3:
            $gid == 0 AND message(-1, lang('runlevel_reson_3'));
            break;
        case 4:
            $method != 'GET' AND message(-1, lang('runlevel_reson_4'));
            break;
        //case 5: break;
    }
    // hook model_check_runlevel_end.php
}

/*
	message(0, '登录成功');
	message(1, '密码错误');
	message(2, '权限错误');
	message(-1, '数据库连接失败');

	code:
		< 0 全局错误，比如：系统错误：数据库丢失连接/文件不可读写
		= 0 正确
		> 0 一般业务逻辑错误，可以定位到具体控件，比如：用户名为空/密码为空
*/
function message($code, $message, $extra = array())
{
    global $ajax, $header, $conf;

    $arr = $extra;
    $arr['code'] = $code . '';
    $arr['message'] = $message;
    $header['title'] = $conf['sitename'];

    // hook model_message_start.php

    // 防止 message 本身出现错误死循环
    static $called = FALSE;
    $called ? exit(xn_json_encode($arr)) : $called = TRUE;
    if ($ajax) {
        echo xn_json_encode($arr);
    } else {
        if (IN_CMD) {
            if (is_array($message) || is_object($message)) {
                print_r($message);
            } else {
                echo $message;
            }
            exit;
        } else {
            if (defined('MESSAGE_HTM_PATH')) {
                include _include(MESSAGE_HTM_PATH);
            } else {
                include _include(APP_PATH . "view/htm/message.htm");
            }
        }
    }
    // hook model_message_end.php
    exit;
}

// 上锁
function xn_lock_start($lockname = '', $life = 10)
{
    global $conf, $time;
    $lockfile = $conf['tmp_path'] . 'lock_' . $lockname . '.lock';
    if (is_file($lockfile)) {
        // 大于 $life 秒，删除锁
        if ($time - filemtime($lockfile) > $life) {
            xn_unlink($lockfile);
        } else {
            // 锁存在，上锁失败。
            return FALSE;
        }
    }

    $r = file_put_contents($lockfile, $time, LOCK_EX);
    return $r;
}

// 删除锁
function xn_lock_end($lockname = '')
{
    global $conf;
    $lockfile = $conf['tmp_path'] . 'lock_' . $lockname . '.lock';
    xn_unlink($lockfile);
}

// class xn_html_safe 由 axiuno@gmail.com 编写

include_once XIUNOPHP_PATH . 'xn_html_safe.func.php';

function xn_html_safe($doc, $arg = array())
{

    // hook model_xn_html_safe_start.php

    empty($arg['table_max_width']) AND $arg['table_max_width'] = 746; // 这个宽度为 回帖宽度

    $pattern = array(
        //'img_url'=>'#^(https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*$#is',
        'img_url' => '#^(((https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*)|(data:image/png;base64,[\w\/+]+))$#is',
        'url' => '#^(https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*$#is', // '#https?://[\w\-/%?.=]+#is'
        'mailto' => '#^mailto:([\w%\-\.]+)@([\w%\-\.]+)(\.[\w%\-\.]+?)+$#is',
        'ftp_url' => '#^ftp:([\w%\-\.]+)@([\w%\-\.]+)(\.[\w%\-\.]+?)+$#is',
        'ed2k_url' => '#^(?:ed2k|thunder|qvod|magnet)://[^\s\'\"\\\\<>]+$#is',
        'color' => '#^(\#\w{3,6})|(rgb\(\d+,\s*\d+,\s*\d+\)|(\w{3,10}))$#is',
        'safe' => '#^[\w\-:;\.\s\x7f-\xff]+$#is',
        'css' => '#^[\(,\)\#;\w\-\.\s\x7f-\xff]+$#is',
        'word' => '#^[\w\-\x7f-\xff]+$#is',
    );

    $white_tag = array('a', 'b', 'i', 'u', 'font', 'strong', 'em', 'span',
        'table', 'tr', 'td', 'th', 'tbody', 'thead', 'tfoot', 'caption',
        'ol', 'ul', 'li', 'dl', 'dt', 'dd', 'menu', 'multicol',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'p', 'div', 'pre',
        'br', 'img', 'area', 'embed', 'code', 'blockquote', 'iframe', 'section', 'fieldset', 'legend'
    );
    $white_value = array(
        'href' => array('pcre', '', array($pattern['url'], $pattern['ed2k_url'])),
        'src' => array('pcre', '', array($pattern['img_url'])),
        'width' => array('range', '', array(0, 4096)),
        'height' => array('range', 'auto', array(0, 80000)),
        'size' => array('range', 4, array(-10, 10)),
        'border' => array('range', 0, array(0, 10)),
        'family' => array('pcre', '', array($pattern['word'])),
        'class' => array('pcre', '', array($pattern['safe'])),
        'face' => array('pcre', '', array($pattern['word'])),
        'color' => array('pcre', '', array($pattern['color'])),
        'alt' => array('pcre', '', array($pattern['safe'])),
        'label' => array('pcre', '', array($pattern['safe'])),
        'title' => array('pcre', '', array($pattern['safe'])),
        'target' => array('list', '_self', array('_blank', '_self')),
        'type' => array('pcre', '', array('#^[\w/\-]+$#')),
        'allowfullscreen' => array('list', 'true', array('true', '1', 'on')),
        'wmode' => array('list', 'transparent', array('transparent', '')),
        'allowscriptaccess' => array('list', 'never', array('never')),
        'value' => array('list', '', array('#^[\w+/\-]$#')),
        'cellspacing' => array('range', 0, array(0, 10)),
        'cellpadding' => array('range', 0, array(0, 10)),
        'frameborder' => array('range', 0, array(0, 10)),
        'allowfullscreen' => array('range', 0, array(0, 10)),
        'align' => array('list', 'left', array('left', 'center', 'right')),
        'valign' => array('list', 'middle', array('middle', 'top', 'bottom')),
        'name' => array('pcre', '', array($pattern['word'])),
    );
    $white_css = array(
        'font' => array('pcre', 'none', array($pattern['safe'])),
        'font-style' => array('pcre', 'none', array($pattern['safe'])),
        'font-weight' => array('pcre', 'none', array($pattern['safe'])),
        'font-family' => array('pcre', 'none', array($pattern['word'])),
        'font-size' => array('range', 12, array(6, 48)),
        'width' => array('range', '100%', array(1, 1800)),
        'height' => array('range', '', array(1, 80000)),
        'min-width' => array('range', 1, array(1, 80000)),
        'min-height' => array('range', 400, array(1, 80000)),
        'max-width' => array('range', 1800, array(1, 80000)),
        'max-height' => array('range', 80000, array(1, 80000)),
        'line-height' => array('range', '14px', array(1, 50)),
        'color' => array('pcre', '#000000', array($pattern['color'])),
        'background' => array('pcre', 'none', array($pattern['color'], '#url\((https?://[^\'"\\\\<>]+?:?\d?)?([^\'"\\\\<>:]+?)*\)[\w\s\-]*$#')),
        'background-color' => array('pcre', 'none', array($pattern['color'])),
        'background-image' => array('pcre', 'none', array($pattern['img_url'])),
        'background-position' => array('pcre', 'none', array($pattern['safe'])),
        'border' => array('pcre', 'none', array($pattern['css'])),
        'border-left' => array('pcre', 'none', array($pattern['css'])),
        'border-right' => array('pcre', 'none', array($pattern['css'])),
        'border-top' => array('pcre', 'none', array($pattern['css'])),
        'border-left-color' => array('pcre', 'none', array($pattern['css'])),
        'border-right-color' => array('pcre', 'none', array($pattern['css'])),
        'border-top-color' => array('pcre', 'none', array($pattern['css'])),
        'border-bottom-color' => array('pcre', 'none', array($pattern['css'])),
        'border-left-width' => array('pcre', 'none', array($pattern['css'])),
        'border-right-width' => array('pcre', 'none', array($pattern['css'])),
        'border-top-width' => array('pcre', 'none', array($pattern['css'])),
        'border-bottom-width' => array('pcre', 'none', array($pattern['css'])),
        'border-bottom-style' => array('pcre', 'none', array($pattern['css'])),
        'margin-left' => array('range', 0, array(0, 100)),
        'margin-right' => array('range', 0, array(0, 100)),
        'margin-top' => array('range', 0, array(0, 100)),
        'margin-bottom' => array('range', 0, array(0, 100)),
        'margin' => array('pcre', '', array($pattern['safe'])),
        'padding' => array('pcre', '', array($pattern['safe'])),
        'padding-left' => array('range', 0, array(0, 100)),
        'padding-right' => array('range', 0, array(0, 100)),
        'padding-top' => array('range', 0, array(0, 100)),
        'padding-bottom' => array('range', 0, array(0, 100)),
        'zoom' => array('range', 1, array(1, 10)),
        'list-style' => array('list', 'none', array('disc', 'circle', 'square', 'decimal', 'lower-roman', 'upper-roman', 'none')),
        'text-align' => array('list', 'left', array('left', 'right', 'center', 'justify')),
        'text-indent' => array('range', 0, array(0, 100)),
    );

    // hook model_xn_html_safe_new_before.php
    $safehtml = new HTML_White($white_tag, $white_value, $white_css, $arg);

    // hook model_xn_html_safe_parse_before.php
    $result = $safehtml->parse($doc);

    // hook model_xn_html_safe_end.php

    return $result;
}

// view目录下文件路径
function view_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    if ($conf['view_url'] == 'view/') {
        $admin_access = GLOBALS('admin_access');
        // 使用目录化伪静态 域名"/"结尾或使用绝对路径"/"
        $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['view_url'] : (empty($admin_access) ? $conf['view_url'] : '../' . $conf['view_url']);
    } else {
        $path = $conf['view_url']; // 云储存
    }
    return $path;
}

// 后台访问前台view目录下文件路径
function admin_view_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    if ($conf['view_url'] == 'view/') {
        $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['view_url'] : '../' . $conf['view_url'];
    } else {
        $path = $conf['view_url']; // 云储存
    }
    return $path;
}

// 附件路径
function file_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    if ($conf['attach_on'] == 0) {
        // 本地
        $admin_access = GLOBALS('admin_access');
        $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['upload_url'] : (empty($admin_access) ? $conf['upload_url'] : '../' . $conf['upload_url']);
    } elseif ($conf['attach_on'] == 1) {
        // 云储存
        $path = $conf['cloud_url'] . $conf['upload_url'];
    } elseif ($conf['attach_on'] == 2) {
        // 云储存
        $path = $conf['cloud_url'] . $conf['upload_url'];
    }
    return $path;
}

// 后台访问附件路径
function admin_file_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    if ($conf['attach_on'] == 0) {
        // 本地
        $path = $conf['url_rewrite_on'] > 1 ? file_path() : '../' . $conf['upload_url'];
    } elseif ($conf['attach_on'] == 1) {
        // 云储存
        $path = file_path();
    } elseif ($conf['attach_on'] == 2) {
        // 云储存
        $path = file_path();
    }
    return $path;
}

// 针对后台访问
function url_path()
{
    static $path = '';
    if ($path) return $path;
    $conf = _SERVER('conf');
    $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] : '../';
    return $path;
}

// 设置token
function well_token_set($uid = 0)
{
    global $conf, $time, $useragent;
    if ($uid) {
        $user = user_read_cache($uid);
        if (empty($user)) return FALSE;
        $pwd = md5($user['password']);
    } else {
        $pwd = md5($useragent);
    }
    $token = well_token_gen($uid, $pwd);
    setcookie('well_safe_token', $token, $time + 36800, '/', $conf['cookie_domain'], '', TRUE);
    return $token;
}

// 验证token 返回 FALSE 验证失败 $life token 生命期
function well_token_verify($uid, $token, $life = 1800)
{
    global $useragent;
    if (empty($token)) return FALSE;
    if ($uid) {
        $user = user_read_cache($uid);
        if (empty($user)) return FALSE;
        $pwd = md5($user['password']);
    } else {
        $pwd = md5($useragent);
    }
    $_token = param('well_safe_token');
    if (empty($_token) || $_token != $token) return FALSE;
    $r = well_token_decrypt($token, $uid, $pwd, $life);
    return $r;
}

// 生成token / salt 混淆码用于加解密
function well_token_gen($uid, $salt = '')
{
    global $time, $ip;
    $token_key = md5(xn_key() . $salt);
    $token = xn_encrypt("$ip	$uid	$time", $token_key);
    return $token;
}

// 解密token 正确则返回新token 错误返回FALSE
function well_token_decrypt($token, $uid, $salt = '', $life = 1800)
{
    global $time, $ip;
    $token_key = md5(xn_key() . $salt);
    $s = xn_decrypt($token, $token_key);
    if (empty($s)) return FALSE;
    $arr = explode("\t", $s);
    if (count($arr) != 3) return FALSE;
    list($_ip, $_uid, $_time) = $arr;
    if ($uid != $_uid || $ip != $_ip) return FALSE;
    if ($time - $_time > $life) return FALSE;
    return well_token_gen($uid, $salt);
}

// 清理token
function well_token_clear()
{
    global $conf, $time;
    setcookie('well_safe_token', '', $time - 86400, '/', $conf['cookie_domain'], '', TRUE);
}

// 格式化数字 1k
function format_number($number)
{
    return $number ? ($number / 1000) . 'k' : $number;
}

//---------------表单安全过滤---------------
/*
 * 专门处理表单多维数组安全过滤 指定最终级一维数组key为字符串安全处理
    $string 为需要按照字符串处理的key数组 array('key')
    如需按照int型处理时 $string 数组为空或省略
    $string = array('name','message','brief');
	well_param(1, array(), $string);
    well_param('warm_up', array(), array('name','message','brief'));
*/
function well_param($key, $defval = '', $string = array(), $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    if (!isset($_REQUEST[$key]) || ($key === 0 && empty($_REQUEST[$key]))) {
        if (is_array($defval)) {
            return array();
        } else {
            return $defval;
        }
    }
    $val = $_REQUEST[$key];
    $val = well_param_force($val, $string, $htmlspecialchars, $addslashes);
    return $val;
}

function well_param_force($val, $string, $htmlspecialchars, $addslashes)
{
    if (empty($val)) return array();

    foreach ($val as $k => &$v) {
        if (is_array($v)) {
            $v = well_mulit_array_safe($v, array(), $string, $htmlspecialchars, $addslashes);
        } else {
            $defval = well_safe_defval($k, $string);
            $v = well_safe($v, $defval, $htmlspecialchars, $addslashes);
        }
    }

    return $val;
}

// 遍历多维数组安全过滤 $string一维数组中能找到的一律按照字符处理
function well_mulit_array_safe($array, $arr = array(), $string, $htmlspecialchars, $addslashes)
{
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                well_mulit_array_safe($value, $arr[$key], $string, $htmlspecialchars, $addslashes);
            } else {
                $defval = well_safe_defval($key, $string);
                $arr[$key] = well_safe($value, $defval, $htmlspecialchars, $addslashes);
            }
        }
    }
    return $arr;
}

// 返回1则按照字符串处理
function well_safe_defval($key, $string)
{
    $defval = 0;
    if (is_array($string)) {
        // 限定的 key值 按照字符串处理
        $defval = in_array($key, $string) ? 1 : 0;
    }
    return $defval;
}

// 参数安全处理
function well_safe($val, $defval, $htmlspecialchars, $addslashes)
{
    $get_magic_quotes_gpc = _SERVER('get_magic_quotes_gpc');
    // 处理字符串
    if ($defval == 1) {
        //$val = trim($val);
        $addslashes AND empty($get_magic_quotes_gpc) && $val = addslashes($val);
        empty($addslashes) AND $get_magic_quotes_gpc && $val = stripslashes($val);
        $htmlspecialchars AND $val = htmlspecialchars($val);
    } else {
        $val = intval($val);
    }
    return $val;
}

// 专门处理表单多维数组安全过滤 哪些表单限定数字提醒
// well_mulit_array_int(array(), array('id','fid'));
function well_mulit_array_int($array = array(), $string = array())
{
    if (empty($array)) return;

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            well_mulit_array_int($value, $string);
        } else {
            if (in_array($key, $string) && !is_numeric($value)) message(1, lang('well_please_fill_in_the_numbers'));
        }
    }
}

//---------------表单安全过滤结束---------------

/*
 * @param $str 转换字符
 * @param string $type 转换编码
 * @return string
 */
function code_conversion($str, $type = 'utf-8')
{
    $encoding_list = $type == 'utf-8' ? array('gb2312', 'big5', 'ascii', 'gbk', 'utf-16', 'ucs-2', 'utf-8') : array('utf-8', 'utf-16', 'ascii', 'gb2312', 'gbk');
    $encoding = mb_detect_encoding($str, $encoding_list);

    return $encoding === FALSE ? mb_convert_encoding($str, $type, $encoding) : iconv($encoding, $type, $str);
}

// 过滤用户昵称里面的特殊字符
function filter_username($username)
{
    $username = preg_replace_callback('/./u', "filter_emoji", $username);
    return $username;
}

// emoji过滤
function filter_emoji($match)
{
    return strlen($match[0]) >= 4 ? '' : $match[0];
}

// check plugin installation / $dir插件目录名
function check_plugin($dir, $file = NULL, $return = FALSE)
{
    $r = pull_plugin_info($dir);
    if (empty($r)) return FALSE;

    $destpath = APP_PATH . 'plugin/' . $dir . '/';

    if ($file) {
        $getfile = $destpath . $file;
        $str = file_get_contents($getfile);
        return $return ? htmlspecialchars($str) : $str;
    } else {
        if ($r['installed'] && $r['enable']) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

// pull plugin info
function pull_plugin_info($dir)
{
    $destpath = APP_PATH . 'plugin/' . $dir . '/';
    if (!file_exists($destpath)) return FALSE;

    $conffile = $destpath . 'conf.json';
    $r = xn_json_decode(file_get_contents($conffile));
    return $r;
}

// 0:pc 1:wechat 2:pad 3:mobile
function get_device()
{
    $agent = _SERVER('HTTP_USER_AGENT');
    static $cache = array();
    $md5 = md5($agent);
    if (isset($cache[$md5])) return $cache[$md5];
    if (strpos($agent, 'MicroMessenger') !== false) {
        $cache[$md5] = 1;//微信
    } elseif (strpos($agent, 'pad') || strpos($agent, 'Pad')) {
        $cache[$md5] = 2;//pad;
    } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap") || stripos($agent, 'phone') || stripos($agent, 'mobile') || strpos($agent, 'ipod'))) {
        $cache[$md5] = 3;// 手机
    } else {
        $cache[$md5] = 0;
    }
    return $cache[$md5];
}

// random string, no number
function rand_str($length)
{
    $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    return substr(str_shuffle($str), 26, $length);
}

// html换行转换为\r\n
function br_to_chars($data)
{
    //$data = htmlspecialchars_decode($data);
    return str_replace("<br>", "\r\n", $data);
}

// 直接传message 也可以传数组$arr = array('message' => message, 'doctype' => 1, 'gid' => $gid)
// 格式转换: 类型，0: html, 1: txt; 2: markdown; 3: ubb
// 入库时进行转换，编辑时再转码
function code_safe($arr)
{
    if (empty($arr)) return array();

    // 如果没有传doctype变量 默认为 0 安全格式
    $doctype = isset($arr['doctype']) ? intval($arr['doctype']) : 0;
    $gid = empty($arr['gid']) ? 0 : intval($arr['gid']);
    $message = isset($arr['message']) ? $arr['message'] : $arr;

    if ($message) {
        // 格式转换: 类型，0: html, 1: txt; 2: markdown; 3: ubb
        $message = htmlspecialchars($message, ENT_QUOTES);
        // html格式过滤不安全代码 管理员html格式时不转换
        $doctype == 0 && $message = group_access($gid, 'managecontent') ? $message : xn_html_safe($message);
        // text转html格式\r\n会被转换html代码
        $doctype == 1 && $message = xn_txt_to_html($message);
    }

    return $message;
}

// 过滤所有html标签
function filter_all_html($text)
{
    $text = trim($text);
    $text = stripslashes($text);
    $text = strip_tags($text);
    $text = str_replace(array("\r\n", "\r", "\n", '  ', '   ', '    ', '	'), '', $text);
    //$text = htmlspecialchars($text, ENT_QUOTES); // 入库前保留干净，入库时转码 输出时无需htmlspecialchars_decode()
    return $text;
}

function filter_html($text)
{
    global $config;

    $filter = array_value($config, 'filter');
    $arr = array_value($filter, 'content');
    $html_enable = array_value($arr, 'html_enable');
    $html_tag = array_value($arr, 'html_tag');

    if ($html_enable == 0 || empty($html_tag)) return TRUE;
    $html_tag = htmlspecialchars_decode($html_tag);

    $text = trim($text);
    $text = stripslashes($text);
    $text = strip_tags($text, "$html_tag"); // 需要保留的字符在后台设置
    $text = str_replace(array("\r\n", "\r", "\n", '  ', '   ', '    ', '	'), '', $text);
    //$text = preg_replace('#\s+#', '', $text);//空白区域 会过滤图片等
    //$text = preg_replace("#<(.*?)>#is", "", $text);
    // 过滤所有的style
    $text = preg_replace("#style=.+?['|\"]#i", '', $text);
    // 过滤所有的class
    $text = preg_replace("#class=.+?['|\"]#i", '', $text);
    // 获取img= 过滤标签中其他属性
    $text = preg_replace('#(<img.*?)(class=.+?[\'|\"])|(data-src=.+?[\'|"])|(data-type=.+?[\'|"])|(data-ratio=.+?[\'|"])|(data-s=.+?[\'|"])|(data-fail=.+?[\'|"])|(crossorigin=.+?[\'|"])|((data-w)=[\'"]+[0-9]+[\'"]+)|(_width=.+?[\'|"]+)|(_height=.+?[\'|"]+)|(style=.+?[\'|"])|((width)=[\'"]+[0-9]+[\'"]+)|((height)=[\'"]+[0-9]+[\'"]+)#i', '$1', $text);

    return $text;
}

// filter keyword
function filter_keyword($keyword, $type, &$error)
{
    global $config;

    $filter = array_value($config, 'filter');
    $arr = array_value($filter, $type);
    $enable = array_value($arr, 'enable');
    $wordarr = array_value($arr, 'keyword');

    if ($enable == 0 || empty($wordarr)) return FALSE;

    foreach ($wordarr as $_keyword) {
        $r = strpos(strtolower($keyword), strtolower($_keyword));
        if ($r !== FALSE) {
            $error = $_keyword;
            return TRUE;
        }
    }
    return FALSE;
}

// return http://domain.com OR https://domain.com
function url_prefix()
{
    $http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    return $http . $_SERVER['HTTP_HOST'];
}

// 唯一身份ID
function uniq_id()
{
    return uniqid(substr(md5(microtime(true) . mt_rand(1000, 9999)), 8, 8));
}

// 生成订单号 14位
function trade_no()
{
    $trade_no = str_replace('.', '', microtime(1));
    $strlen = mb_strlen($trade_no, 'UTF-8');
    $strlen = 14 - $strlen;
    $str = '';
    if ($strlen) {
        for ($i = 0; $i <= $strlen; $i++) {
            if ($i < $strlen) $str .= '0';
        }
    }
    return $trade_no . $str;
}

// 生成订单号 16位
function trade_no_16()
{
    $explode = explode(' ', microtime());
    $trade_no = $explode[1] . mb_substr($explode[0], 2, 6, 'UTF-8');
    return $trade_no;
}

// 当前年的天数
function date_year($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('L', $time) + 365;
}

// 当前年份中的第几天
function date_z($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('z', $time);
}

// 当前月份中的第几天，没有前导零 1 到 31
function date_j($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('j', $time);
}

// 当前月份中的第几天，有前导零的2位数字 01 到 31
function date_d($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('d', $time);
}

// 当前时间为星期中的第几天 数字表示 1表示星期一 到 7表示星期天
function date_w_n($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('N', $time);
}

// 当前日第几周
function date_d_w($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('W', $time);
}

// 当前几月 没有前导零1-12
function date_n($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('n', $time);
}

// 当前月的天数
function date_t($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('t', $time);
}

// 0 o'clock on the day
function clock_zero()
{
    return strtotime(date('Ymd'));
}

// 24 o'clock on the day
function clock_twenty_four()
{
    return strtotime(date('Ymd')) + 86400;
}

// 8点过期 / expired at 8 a.m.
function eight_expired($time = NULL)
{
    $time = intval($time) ? $time : time();
    // 当前时间大于8点则改为第二天8点过期
    $life = date('G') <= 8 ? (strtotime(date('Ymd')) + 28800 - $time) : well_clock_twenty_four() - $time + 28800;
    return $life;
}

// 24点过期 / expired at 24 a.m.
function twenty_four_expired($time = NULL)
{
    $time = intval($time) ? $time : time();
    $twenty_four = clock_twenty_four();
    $life = $twenty_four - $time;
    return $life;
}

/**
 * @param $url 提交地址
 * @param string $post POST数组
 * @param string $cookie cookie
 * @param int $timeout 超时
 * @param int $ms 设为1是毫秒
 * @return mixed    返回数据
 */
function https_request($url, $post = '', $cookie = '', $timeout = 30, $ms = 0)
{
    if (empty($url)) return FALSE;

    if (version_compare(PHP_VERSION, '5.2.3', '<')) {
        $ms = 0;
        $timeout = 30;
    }

    is_array($post) AND $post = http_build_query($post);

    // 没有安装curl 使用http的形式，支持post
    if (!function_exists('curl_init')) {
        //throw new Exception('server not install curl');
        if ($post) {
            return http_post($url, $post, $cookie, $timeout);
        } else {
            return http_get($url, $cookie, $timeout);
        }
    }

    is_array($cookie) AND $cookie = http_build_query($cookie);
    $curl = curl_init();
    //php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
    if (class_exists('\CURLFile')) {
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
    } else {
        defined('CURLOPT_SAFE_UPLOAD') AND curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
    }
    // 设定请求的RUL
    curl_setopt($curl, CURLOPT_URL, $url);
    // 设定返回信息中包含响应信息头 启用时会将头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    // 兼容HTTPS
    if (stripos($url, 'https://') !== FALSE) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //ssl版本控制
        //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($curl, CURLOPT_SSLVERSION, true);
    }

    $header = array('Content-type: application/x-www-form-urlencoded', 'X-Requested-With: XMLHttpRequest');
    $cookie AND $header[] = "Cookie: $cookie";
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

    if ($post) {
        curl_setopt($curl, CURLOPT_POST, true);
        // 使用自动跳转, 安全模式不允许
        (!ini_get('safe_mode') && !ini_get('open_basedir')) && curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if ($ms) {
        curl_setopt($curl, CURLOPT_NOSIGNAL, true); // 设置毫秒超时
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, intval($timeout)); // 超时毫秒
    } else {
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, intval($timeout)); // 秒超时
    }
    //优先解析 IPv6 超时后IPv4
    //curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

function save_image($img)
{
    $ch = curl_init();
    // 设定请求的RUL
    curl_setopt($ch, CURLOPT_URL, $img);
    // 设定返回信息中包含响应信息头 启用时会将头文件的信息作为数据流输出
    //curl_setopt($ch, CURLOPT_HEADER, false);
    //curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    // true表示$html,false表示echo $html
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

// 绝对路径 获取图片信息:数组返回[0]宽度 [1]高度 [2]类型 返回数字，其中1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
function image_size($image_url)
{
    return getimagesize($image_url);
}

// 计算字串宽度:剧中对齐(字体大小/字串内容/字体链接/背景宽度/倍数)
function calculate_str_width($size, $str, $font, $width, $multiple = 2)
{
    $box = imagettfbbox($size, 0, $font, $str);
    return ($width - $box[4] - $box[6]) / $multiple;
}

// 搜索目录下的文件 比对文件后缀
function search_directory($path)
{
    if (is_dir($path)) {
        $paths = scandir($path);
        foreach ($paths as $val) {
            $sub_path = $path . '/' . $val;
            if ($val == '.' || $val == '..') {
                continue;
            } else if (is_dir($sub_path)) {
                //echo '目录名:' . $val . '<br/>';
                search_directory($sub_path);
            } else {
                //echo ' 最底层文件: ' . $path . '/' . $val . ' <hr/>';
                $ext = file_ext($sub_path);
                if (in_array($ext, array('php', 'asp', 'jsp', 'cgi', 'exe', 'dll'))) {
                    echo '异常文件：' . $sub_path . ' <hr/>';
                }
            }
        }
    }
}

// hook model_misc_end.php

?>