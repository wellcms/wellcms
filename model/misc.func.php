<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model_misc_start.php

/*
 * 传入url('user-login', array('pid' => 1))
 * 根据 $conf['url_rewrite_on'] 设置，返回以下四种格式
 * 0:?user-login.html&pid=1
 * 1:user-login.html?pid=1
 * 2:/user/login.html?pid=1
 * 3:/user/login?pid=1
 *
 * @param $url  解析链接格式 'user-login'
 * @param array $extra 附加参数格式 array('pid' => 1, 'uid' => 1)
 * @param bool $url_access FALSE前台链接 TRUE后台链接 2后台调用前台链接 3不受限后台链接(不受过滤限制)
 * @return string 返回解析的链接
 */
function url($url, $extra = array(), $url_access = NULL)
{
    $conf = _SERVER('conf');
    NULL === $url_access and $url_access = GLOBALS('url_access');

    !isset($conf['url_rewrite_on']) and $conf['url_rewrite_on'] = 0;

    // hook model_url_start.php

    $r = $path = $query = '';
    if ($url && FALSE !== strpos($url, '/')) {
        $path = substr($url, 0, strrpos($url, '/') + 1);
        $query = substr($url, strrpos($url, '/') + 1);
    } else {
        $path = '';
        $query = $url;
    }

    // hook model_url_before.php

    if (0 == $conf['url_rewrite_on']) {
        $r = $path . '?' . $query . '.html';
    } elseif (1 == $conf['url_rewrite_on']) {
        $r = $path . $query . '.html';
    } elseif (2 == $conf['url_rewrite_on'] || 3 == $conf['url_rewrite_on']) {

        $arr = explode('-', $query);
        $filter = array('operate', 'attach', 'read', 'category', 'list', 'my', 'forum', 'thread');

        // hook model_url_center.php

        // 后台链接
        if ((TRUE === $url_access && !in_array($arr[0], $filter, TRUE)) || 3 === $url_access) {
            $r = 'index.php?' . http_build_query($arr);
        } else {
            $r = $conf['path'] . str_replace('-', '/', $query) . (2 == $conf['url_rewrite_on'] ? '.html' : '');

            $ajax = param('ajax', 0);
            $ajax and $extra += array('ajax' => $ajax);
        }
    }

    // hook model_url_after.php

    // 附加参数
    if ($extra) {
        $args = http_build_query($extra);
        $sep = FALSE === strpos($r, '?') ? '?' : '&';
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
    if (1 == $gid) return;
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
            (0 == $gid || 'GET' != $method) and message(-1, lang('runlevel_reson_2'));
            break;
        case 3:
            0 == $gid and message(-1, lang('runlevel_reson_3'));
            break;
        case 4:
            'GET' != $method and message(-1, lang('runlevel_reson_4'));
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
    $arr['code'] = $code;
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
                //include _include(APP_PATH . "view/htm/message.htm");
                include _include(theme_load('message'));
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

include XIUNOPHP_PATH . 'xn_html_safe.func.php';

function xn_html_safe($doc, $arg = array())
{

    // hook model_xn_html_safe_start.php

    empty($arg['table_max_width']) and $arg['table_max_width'] = 746; // 这个宽度为 回帖宽度

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
        'wmode' => array('list', 'transparent', array('transparent', '')),
        'allowscriptaccess' => array('list', 'never', array('never')),
        'value' => array('list', '', array('#^[\w+/\-]$#')),
        'cellspacing' => array('range', 0, array(0, 10)),
        'cellpadding' => array('range', 0, array(0, 10)),
        'frameborder' => array('range', 0, array(0, 10)),
        'allowfullscreen' => array('list', 'true', array('true', '1', 'on'), 'range', 0, array(0, 10)),
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
        'display' => array('range', 0, array(0, 100)),
    );

    // hook model_xn_html_safe_new_before.php
    $safehtml = new HTML_White($white_tag, $white_value, $white_css, $arg);

    // hook model_xn_html_safe_parse_before.php
    $result = $safehtml->parse($doc);

    // hook model_xn_html_safe_end.php

    return $result;
}

// 前台访问view目录下文件路径/支持分离
function view_path()
{
    static $path = array();
    if (isset($path['view_path'])) return $path['view_path'];
    $conf = _SERVER('conf');
    $conf_path = $conf['url_rewrite_on'] > 1 ? $conf['path'] : '';
    $path['view_path'] = $conf_path . $conf['view_url'];
    return $path['view_path'];
}

// 附件路径/支持分离
function file_path()
{
    static $path = array();
    if (isset($path['file_path'])) return $path['file_path'];
    $conf = _SERVER('conf');
    if (0 == $conf['attach_on']) {
        // 本地
        $path['file_path'] = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['upload_url'] : $conf['upload_url'];
    } elseif (1 == $conf['attach_on'] || 2 == $conf['attach_on']) {
        // 云储存
        $path['file_path'] = $conf['cloud_url'] . $conf['upload_url'];
    }
    return $path['file_path'];
}

// 后台访问view目录下文件路径/支持分离
function admin_view_path()
{
    static $path = array();
    if (isset($path['admin_view_path'])) return $path['admin_view_path'];
    $conf = _SERVER('conf');
    $path['admin_view_path'] = 'view/' == $conf['view_url'] ? '../' . $conf['view_url'] : $conf['view_url'];
    return $path['admin_view_path'];
}

// 后台处理头像或主题缩略图、自定义图标
function admin_access_file($icon = 0, $icon_fmt = '')
{
    global $conf;
    if (empty($icon_fmt)) return $icon_fmt;
    $local = FALSE;
    // 本地未分离
    if ($icon) {
        // 上传文件
        if (0 == $conf['attach_on']) $local = TRUE;
    } else {
        // icon 默认图片，view 目录
        if ('view/' == $conf['view_url']) $local = TRUE;
    }
    if ($local) {
        // 伪静态 1 追加 ../ 伪静态 2 追加 ..
        $icon_fmt = $conf['url_rewrite_on'] < 2 ? '../' . $icon_fmt : '..' . $icon_fmt;
    }
    return $icon_fmt;
}

// 后台处理内容图、附件路径
function admin_attach_path()
{
    global $conf;
    static $cache = array();
    $key = 'admin_attach_path';
    if (isset($cache[$key])) return $cache[$key];
    $cache[$key] = '';
    // 未分离图片
    if (0 == $conf['attach_on']) {
        // 伪静态 1 追加 ../
        if ($conf['url_rewrite_on'] < 2) {
            $cache[$key] = '../';
        } else {
            $cache[$key] = '..';
        }
    }
    return $cache[$key];
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
    $key = md5($conf['auth_key'] . '_safe_token_' . $uid);
    setcookie($key, $token, $time + 3600, '/', $conf['cookie_domain'], '', TRUE);
    return $token;
}

/*
 * @param $uid 当前用户UID
 * @param $token 获取的token
 * @param int $verify cookie中的token 0不比对 / 1比对 2,3比对使用次数
 * @param int $life token 生命期
 * @return bool|mixed|string 返回 token 验证成功 / FALSE 验证失败
 */
function well_token_verify($uid, $token, $verify = 0, $life = 3600)
{
    global $conf, $time, $useragent;
    if (empty($token)) return FALSE;
    if ($uid) {
        $user = user_read_cache($uid);
        if (empty($user)) return FALSE;
        $pwd = md5($user['password']);
    } else {
        if (empty($useragent)) return FALSE;
        $pwd = md5($useragent);
    }

    $key = md5($conf['auth_key'] . '_safe_token_' . $uid);
    if (1 == $verify) {
        $_token = _COOKIE($key, 0);
        if ($_token != $token) return FALSE;
        well_token_clear();
    } elseif (2 == $verify) {
        if (!_COOKIE($key, 0)) return FALSE;
        $num = _COOKIE(md5($token), 0);
        if ($num) return FALSE; // 发表主题仅限使用1次
        well_token_clear();
        setcookie(md5($token), $num + 1, $time + 600, '/', $conf['cookie_domain'], '', TRUE);
    } elseif (3 == $verify) {
        if (!_COOKIE($key, 0)) return FALSE;
        $num = _COOKIE(md5($token), 0);
        if ($num >= 10) {
            well_token_clear();
            return FALSE; // 评论仅限使用10次
        }
        setcookie(md5($token), $num + 1, $time + 600, '/', $conf['cookie_domain'], '', TRUE);
    }

    return well_token_decrypt($token, $uid, $pwd, $life);
}

// 生成token / salt 混淆码用于加解密
function well_token_gen($uid, $salt = '')
{
    global $time, $ip, $useragent;
    $token_key = md5(xn_key() . $salt);
    $ua_md5 = md5($useragent);
    $token = xn_encrypt("$ip	$uid	$time	$ua_md5", $token_key);
    return $token;
}

// 解密token 正确则返回新token 错误返回FALSE
function well_token_decrypt($token, $uid, $salt = '', $life = 3600)
{
    global $time, $ip, $useragent;
    $token_key = md5(xn_key() . $salt);
    $s = xn_decrypt($token, $token_key);
    if (empty($s)) return FALSE;
    $arr = explode("\t", $s);
    if (count($arr) != 4) return FALSE;
    list($_ip, $_uid, $_time, $ua_md5) = $arr;
    if ($ua_md5 != md5($useragent) || $time - $_time > $life || $uid != $_uid || $ip != $_ip) return FALSE;
    return well_token_gen($uid, $salt);
}

// 清理token
function well_token_clear($token = 0)
{
    global $uid, $conf, $time;
    $key = md5($conf['auth_key'] . '_safe_token_' . $uid);
    setcookie($key, '', $time - 1, '/', $conf['cookie_domain'], '', TRUE);
    $token and setcookie(md5($token), 0, $time - 1, '/', $conf['cookie_domain'], '', TRUE);
}

// 格式化数字 1k
function format_number($number)
{
    $number = intval($number);
    return $number > 1000 ? ($number > 1100 ? number_format(($number / 1000), 1) : intval($number / 1000)) . 'K+' : $number;
}

//---------------表单安全过滤---------------
/*
 * 专门处理表单多维数组安全过滤 指定最终级一维数组key为字符串安全处理
    $filter 为需要按照字符串处理的key数组 array('key1','key2')
    如需按照int型处理时 $filter 数组为空或省略
    $filter = array('name','message','brief');
	well_param(1, array(), $filter);
    well_param('warm_up', array(), array('name','message','brief'));
*/
function well_param($key, $defval = '', $filter = array(), $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    if (!isset($_REQUEST[$key]) || (0 == $key && empty($_REQUEST[$key]))) {
        if (is_array($defval)) {
            return array();
        } else {
            return $defval;
        }
    }
    $val = $_REQUEST[$key];
    $val = well_param_force($val, $filter, $htmlspecialchars, $addslashes);
    return $val;
}

function well_param_force($val, $filter, $htmlspecialchars, $addslashes)
{
    if (empty($val)) return array();

    foreach ($val as $k => &$v) {
        if (is_array($v)) {
            $v = well_mulit_array_safe($v, array(), $filter, $htmlspecialchars, $addslashes);
        } else {
            $defval = well_safe_defval($k, $filter);
            $v = well_safe($v, $defval, $htmlspecialchars, $addslashes);
        }
    }

    return $val;
}

// 遍历多维数组安全过滤 $filter一维数组中能找到的一律按照字符处理
function well_mulit_array_safe($array, $arr, $filter, $htmlspecialchars, $addslashes)
{
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                well_mulit_array_safe($value, $arr[$key], $filter, $htmlspecialchars, $addslashes);
            } else {
                $defval = well_safe_defval($key, $filter);
                $arr[$key] = well_safe($value, $defval, $htmlspecialchars, $addslashes);
            }
        }
    }
    return $arr;
}

// 返回1则按照字符串处理
function well_safe_defval($key, $filter)
{
    $defval = 0;
    if (is_array($filter)) {
        // 限定的 key值 按照字符串处理
        $defval = in_array($key, $filter) ? 1 : 0;
    }
    return $defval;
}

// 参数安全处理
function well_safe($val, $defval, $htmlspecialchars, $addslashes)
{
    $get_magic_quotes_gpc = _SERVER('get_magic_quotes_gpc');
    // 处理字符串
    if (1 == $defval) {
        //$val = trim($val);
        $addslashes and empty($get_magic_quotes_gpc) && $val = addslashes($val);
        empty($addslashes) and $get_magic_quotes_gpc && $val = stripslashes($val);
        $htmlspecialchars and $val = htmlspecialchars($val, ENT_QUOTES);
    } else {
        $val = intval($val);
    }
    return $val;
}

// 专门处理表单多维数组安全过滤 哪些表单限定数字
// well_mulit_array_int(array(), array('id','fid'));
function well_mulit_array_int($array = array(), $filter = array())
{
    if (empty($array)) return;

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            well_mulit_array_int($value, $filter);
        } else {
            if (in_array($key, $filter) && !is_numeric($value)) message(1, lang('type_error'));
        }
    }
}

//---------------表单安全过滤结束---------------

/*
 * @param $str 转换字符串
 * @param string $charset 转换编码
 * @param string $original 字符串原始编码
 * @return string
 */
function code_conversion($str, $charset = 'utf-8', $original = '')
{
    if ($original) return iconv($original, $charset . '//IGNORE', $str);

    $list = array('gb2312', 'big5', 'ascii', 'gbk', 'utf-16', 'ucs-2', 'utf-8');
    $encoding_list = $charset == 'utf-8' ? $list : array('utf-8', 'utf-16', 'ascii', 'gb2312', 'gbk');
    $encoding = mb_detect_encoding($str, $encoding_list);
    // 强制转换
    $encoding = in_array($encoding, $list) ? $encoding : $charset;
    return mb_convert_encoding($str, $charset, $encoding);
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
    if (FALSE !== strpos($agent, 'MicroMessenger')) {
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
        0 == $doctype && $message = group_access($gid, 'managecontent') ? $message : xn_html_safe($message);
        // text转html格式\r\n会被转换html代码
        1 == $doctype && $message = xn_txt_to_html($message);
    }

    return $message;
}

// 过滤所有html标签
function filter_all_html($text)
{
    $text = trim($text);
    $text = stripslashes($text);
    $text = strip_tags($text);
    $text = str_replace(array('/', "\t", "\r\n", "\r", "\n", '  ', '   ', '    ', '	'), '', $text);
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

    if (0 == $html_enable || empty($html_tag)) return TRUE;
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

    if (0 == $enable || empty($wordarr)) return FALSE;

    foreach ($wordarr as $_keyword) {
        $r = strpos(strtolower($keyword), strtolower($_keyword));
        if (FALSE !== $r) {
            $error = $_keyword;
            return TRUE;
        }
    }
    return FALSE;
}

// return http://domain.com OR https://domain.com
function url_prefix()
{
    $http = ((isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
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
 * @param string $post POST数组 / 空为GET获取数据 / $post='GET'获取连续跳转最终URL
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

    is_array($post) and $post = http_build_query($post);

    // 没有安装curl 使用http的形式，支持post
    if (!extension_loaded('curl')) {
        //throw new Exception('server not install CURL');
        if ($post) {
            return https_post($url, $post, $cookie, $timeout);
        } else {
            return http_get($url, $cookie, $timeout);
        }
    }

    is_array($cookie) and $cookie = http_build_query($cookie);
    $curl = curl_init();
    // 返回执行结果，不输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
    if (class_exists('\CURLFile')) {
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
    } else {
        defined('CURLOPT_SAFE_UPLOAD') and curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
    }
    // 设定请求的RUL
    curl_setopt($curl, CURLOPT_URL, $url);
    // 设定返回信息中包含响应信息头
    if (ini_get('safe_mode') && ini_get('open_basedir')) {
        // $post参数必须为GET
        if ('GET' == $post) {
            // 安全模式时将头文件的信息作为数据流输出
            curl_setopt($curl, CURLOPT_HEADER, true);
            // 安全模式采用连续抓取
            curl_setopt($curl, CURLOPT_NOBODY, true);
        }
    } else {
        curl_setopt($curl, CURLOPT_HEADER, false);
        // 允许跳转10次
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        // 使用自动跳转，返回最后的Location
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    }
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    // 兼容HTTPS
    if (FALSE !== stripos($url, 'https://')) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //ssl版本控制
        //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($curl, CURLOPT_SSLVERSION, true);
    }

    $header = array('Content-type: application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With: XMLHttpRequest');
    $cookie and $header[] = "Cookie: $cookie";
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

    if ($post) {
        // POST
        curl_setopt($curl, CURLOPT_POST, true);
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }

    if ($ms) {
        curl_setopt($curl, CURLOPT_NOSIGNAL, true); // 设置毫秒超时
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, intval($timeout)); // 超时毫秒
    } else {
        curl_setopt($curl, CURLOPT_TIMEOUT, intval($timeout)); // 秒超时
    }
    //优先解析 IPv6 超时后IPv4
    //curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    // 返回执行结果
    $output = curl_exec($curl);
    // 有效URL，输出URL非URL页面内容 CURLOPT_RETURNTRANSFER 必须为false
    'GET' == $post and $output = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
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
            if ('.' == $val || '..' == $val) {
                continue;
            } else if (is_dir($sub_path)) {
                //echo '目录名:' . $val . '<br/>';
                search_directory($sub_path);
            } else {
                //echo ' 最底层文件: ' . $path . '/' . $val . ' <hr/>';
                $ext = strtolower(file_ext($sub_path));
                if (in_array($ext, array('php', 'asp', 'jsp', 'cgi', 'exe', 'dll'), TRUE)) {
                    echo '异常文件：' . $sub_path . ' <hr/>';
                }
            }
        }
    }
}

// 一维数组转字符串 $sign待签名字符串 $url为urlencode转码GET参数字符串
function array_to_string($arr, &$sign = '', &$url = '')
{
    if (count($arr) != count($arr, 1)) throw new Exception('Does not support multi-dimensional array to string');

    // 注销签名
    unset($arr['sign']);

    // 排序
    ksort($arr);
    reset($arr);

    // 转字符串做签名
    $url = '';
    $sign = '';
    foreach ($arr as $key => $val) {
        if (empty($val) || is_array($val)) continue;
        $url .= $key . '=' . urlencode($val) . '&';
        $sign .= $key . '=' . $val . '&';
    }
    $url = substr($url, 0, -1);
    $sign = substr($sign, 0, -1);
}

// 私钥生成签名
function rsa_create_sign($data, $key, $sign_type = 'RSA')
{
    if (!function_exists('openssl_sign')) throw new Exception('OpenSSL extension is not enabled');

    if (!defined('OPENSSL_ALGO_SHA256')) throw new Exception('Only versions above PHP 5.4.8 support SHA256');

    $key = wordwrap($key, 64, "\n", true);
    if (FALSE === $key) throw new Exception('Private Key Error');

    $key = "-----BEGIN RSA PRIVATE KEY-----\n$key\n-----END RSA PRIVATE KEY-----";

    if ('RSA2' == $sign_type) {
        openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA256);
    } else {
        openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA1);
    }

    // 加密
    return base64_encode($sign);
}

// 公钥验证签名
function rsa_verify_sign($data, $sign, $key, $sign_type = 'RSA')
{
    $key = wordwrap($key, 64, "\n", true);
    if (FALSE === $key) throw new Exception('Public Key Error');

    $key = "-----BEGIN PUBLIC KEY-----\n$key\n-----END PUBLIC KEY-----";

    // 签名正确返回1 签名不正确返回0 错误-1
    if ('RSA2' == $sign_type) {
        $result = openssl_verify($data, base64_decode($sign), $key, OPENSSL_ALGO_SHA256);
    } else {
        $result = openssl_verify($data, base64_decode($sign), $key, OPENSSL_ALGO_SHA1);
    }

    return $result === 1;
}

// Array to xml array('appid' => 'appid', 'code' => 'success')
function array_to_xml($arr)
{
    if (!is_array($arr) || empty($arr)) throw new Exception('Array Error');

    $xml = "<xml>";
    foreach ($arr as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml;
}

// Xml to array
function xml_to_array($xml)
{
    if (!$xml) throw new Exception('XML error');

    $old = libxml_disable_entity_loader(true);

    // xml解析
    $result = (array)simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_COMPACT);
    // 恢复旧值
    if (FALSE === $old) libxml_disable_entity_loader(false);

    return $result;
}

// 逐行读取
function well_import($file)
{
    if ($handle = fopen($file, 'r')) {
        while (!feof($handle)) {
            yield trim(fgets($handle));
        }
        fclose($handle);
    }
}

// 计算总行数
function well_import_total($file, $key = 'well_import_total')
{
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $count = cache_get($key);
    if (NULL === $count) {
        $count = 0;
        $globs = well_import($file);
        while ($globs->valid()) {
            ++$count;
            $globs->next(); // 指向下一个
        }
        $count and cache_set($key, $count, 300);
    }

    return $cache[$key] = $count;
}

$g_dir_file = FALSE;
function well_search_dir($path)
{
    global $g_dir_file;
    FALSE === $g_dir_file and $g_dir_file = array();
    if (is_dir($path)) {
        $paths = scandir($path);
        foreach ($paths as $val) {
            $sub_path = $path . '/' . $val;
            if ('.' == $val || '..' == $val) {
                continue;
            } else if (is_dir($sub_path)) {
                well_search_dir($sub_path);
            } else {
                $g_dir_file[] = $sub_path;
            }

        }
    }

    return $g_dir_file;
}

// hook model_misc_end.php

?>