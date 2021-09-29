<?php

function xn_message($code, $message)
{
    $ajax = $_SERVER['ajax'];
    echo $ajax ? xn_json_encode(array('code' => $code, 'message' => $message)) : $message;
    exit;
}

function xn_log_post_data()
{
    $method = $_SERVER['method'];
    if ('POST' != $method) return;
    $post = $_POST;
    isset($post['password']) and $post['password'] = '******';        // 干掉密码信息
    isset($post['password_new']) and $post['password_new'] = '******';    // 干掉密码信息
    isset($post['password_old']) and $post['password_old'] = '******';    // 干掉密码信息

    xn_log(xn_json_encode($post), 'post_data');
}

// 捕获全局异常 throw new Exception('exception')
function exception_handler($exception)
{
    $ajax = $_SERVER['ajax'];

    $message = 0 == DEBUG ? $exception->getMessage() : $exception;

    $html = $s = "<fieldset class=\"fieldset small notice\"><div>" . $message . "</div></fieldset>";
    echo ($ajax || IN_CMD) ? $message : $html;

    2 == DEBUG and xn_log($exception, 'debug_error');
}

// 中断流程很危险！可能会导致数据问题，线上模式不允许中断流程！
function error_handle($errno, $errstr, $errfile, $errline)
{

    // PHP 内部默认处理
    if (0 == DEBUG) return FALSE;

    // 如果放在 register_shutdown_function 里面，文件句柄会被关闭，然后这里就写入不了文件了！
    // if(FALSE !== strpos($s, 'error_log(')) return TRUE;
    $time = $_SERVER['time'];
    $ajax = $_SERVER['ajax'];
    IN_CMD and $errstr = str_replace('<br>', "\n", $errstr);

    $subject = "Error[$errno]: $errstr, File: $errfile, Line: $errline";
    $message = array();
    xn_log($subject, 'php_error'); // 所有PHP错误报告都记录日志

    $arr = debug_backtrace();
    array_shift($arr);
    foreach ($arr as $v) {
        $args = '';
        if (!empty($v['args']) && is_array($v['args'])) foreach ($v['args'] as $v2) $args .= ($args ? ' , ' : '') . (is_array($v2) ? 'array(' . count($v2) . ')' : (is_object($v2) ? 'object' : $v2));
        !isset($v['file']) and $v['file'] = '';
        !isset($v['line']) and $v['line'] = '';
        $message [] = "File: $v[file], Line: $v[line], $v[function]($args) ";
    }
    $txt = $subject . "\r\n" . implode("\r\n", $message);
    $html = $s = "<fieldset class=\"fieldset small notice\">
			<b>$subject</b>
			<div>" . implode("<br>\r\n", $message) . "</div>
		</fieldset>";
    echo ($ajax || IN_CMD) ? $txt : $html;
    2 == DEBUG and xn_log($txt, 'debug_error');
    return TRUE;
}

// 使用全局变量记录错误信息
function xn_error($no, $str, $return = FALSE)
{
    global $errno, $errstr;
    $errno = $no;
    $errstr = $str;
    return $return;
}

/*
	param(1);
	param(1, '');
	param(1, 0);
	param(1, array());
	param(1, array(''));
	param(1, array(0));
*/
function param($key, $defval = '', $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    if (!isset($_REQUEST[$key]) || (0 == $key && empty($_REQUEST[$key]))) {
        if (is_array($defval)) {
            return array();
        } else {
            return $defval;
        }
    }
    $val = $_REQUEST[$key];
    $val = param_force($val, $defval, $htmlspecialchars, $addslashes);
    return $val;
}

// 安全获取单词类参数
function param_word($key, $len = 32)
{
    $s = param($key);
    $s = xn_safe_word($s, $len);
    return $s;
}

function param_base64($key, $len = 0)
{
    $s = param($key, '', FALSE);
    if (empty($s)) return '';
    $s = substr($s, strpos($s, ',') + 1);
    $s = base64_decode($s);
    $len and $s = substr($s, 0, $len);
    return $s;
}

function param_json($key)
{
    $s = param($key, '', FALSE);
    if (empty($s)) return '';
    $arr = xn_json_decode($s);
    return $arr;
}

function param_url($key)
{
    $s = param($key, '', FALSE);
    $arr = xn_urldecode($s);
    return $arr;
}

// 安全过滤字符串，仅仅保留 [a-zA-Z0-9_]
function xn_safe_word($s, $len)
{
    $s = preg_replace('#\W+#', '', $s);
    $s = substr($s, 0, $len);
    return $s;
}

/*
	仅支持一维数组的类型强制转换。
	param_force($val);
	param_force($val, '');
	param_force($val, 0);
	param_force($arr, array());
	param_force($arr, array(''));
	param_force($arr, array(0));
*/
function param_force($val, $defval, $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    $get_magic_quotes_gpc = _SERVER('get_magic_quotes_gpc');
    if (is_array($defval)) {
        $defval = empty($defval) ? '' : $defval[0]; // 数组的第一个元素，如果没有则为空字符串
        if (is_array($val)) {
            foreach ($val as &$v) {
                if (is_array($v)) {
                    $v = $defval;
                } else {
                    if (is_string($defval)) {
                        //$v = trim($v);
                        $addslashes and !$get_magic_quotes_gpc && $v = addslashes($v);
                        !$addslashes and $get_magic_quotes_gpc && $v = stripslashes($v);
                        $htmlspecialchars and $v = htmlspecialchars($v, ENT_QUOTES);
                    } else {
                        $v = intval($v);
                    }
                }
            }
        } else {
            return array();
        }
    } else {
        if (is_array($val)) {
            $val = $defval;
        } else {
            if (is_string($defval)) {
                //$val = trim($val);
                $addslashes and !$get_magic_quotes_gpc && $val = addslashes($val);
                !$addslashes and $get_magic_quotes_gpc && $val = stripslashes($val);
                $htmlspecialchars and $val = htmlspecialchars($val, ENT_QUOTES);
            } else {
                $val = intval($val);
            }
        }
    }
    return $val;
}

/*
	lang('mobile_length_error');
	lang('mobile_length_error', array('mobile'=>$mobile));
*/
function lang($key, $arr = array())
{
    $lang = $_SERVER['lang'];
    if (!isset($lang[$key])) return 'lang[' . $key . ']';
    $s = $lang[$key];
    if (!empty($arr)) {
        foreach ($arr as $k => $v) {
            $s = str_replace('{' . $k . '}', $v, $s);
        }
    }
    return $s;
}

function jump($message, $url = '', $delay = 3)
{
    $ajax = $_SERVER['ajax'];
    if ($ajax) return $message;
    if (!$url) return $message;
    'back' == $url and $url = 'javascript:history.back()';
    $htmladd = '<script>setTimeout(function() {window.location=\'' . $url . '\'}, ' . ($delay * 1000) . ');</script>';
    return '<a href="' . $url . '">' . $message . '</a>' . $htmladd;
}

function xn_strlen($s)
{
    return mb_strlen($s, 'UTF-8');
}

function xn_substr($s, $start, $len)
{
    return mb_substr($s, $start, $len, 'UTF-8');
}

// txt 转换到 html
function xn_txt_to_html($s)
{
    $s = htmlspecialchars($s, ENT_QUOTES);
    $s = str_replace(" ", '&nbsp;', $s);
    $s = str_replace("\t", ' &nbsp; &nbsp; &nbsp; &nbsp;', $s);
    $s = str_replace("\r\n", "\n", $s);
    $s = str_replace("\n", '<br>', $s);
    return $s;
}

function xn_urlencode($s)
{
    $s = urlencode($s);
    $s = str_replace('_', '_5f', $s);
    $s = str_replace('-', '_2d', $s);
    $s = str_replace('.', '_2e', $s);
    $s = str_replace('+', '_2b', $s);
    $s = str_replace('=', '_3d', $s);
    $s = str_replace('%', '_', $s);
    return $s;
}

function xn_urldecode($s)
{
    $s = str_replace('_', '%', $s);
    $s = urldecode($s);
    return $s;
}

// $pretty = TRUE 格式化 FALSE 字串 wellcms@qq.com 2019.12.12修改
function xn_json_encode($data, $pretty = FALSE, $level = 0)
{
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        return $pretty ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $tab = $pretty ? str_repeat("\t", $level) : '';
    $tab2 = $pretty ? str_repeat("\t", $level + 1) : '';
    $br = $pretty ? "\r\n" : '';
    switch ($type = gettype($data)) {
        case 'NULL':
            return 'null';
        case 'boolean':
            return ($data ? 'true' : 'false');
        case 'integer':
        case 'double':
        case 'float':
            return $data;
        case 'string':
            $data = '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $data) . '"';
            $data = str_replace("\r", '\\r', $data);
            $data = str_replace("\n", '\\n', $data);
            $data = str_replace("\t", '\\t', $data);
            return $data;
        case 'object':
            return get_object_vars($data);
        case 'array':
            $output_index_count = 0;
            $output_indexed = array();
            $output_associative = array();
            foreach ($data as $key => $value) {
                $output_indexed[] = xn_json_encode($value, $pretty, $level + 1);
                $output_associative[] = $tab2 . '"' . $key . '":' . xn_json_encode($value, $pretty, $level + 1);
                if (NULL !== $output_index_count && $output_index_count++ !== $key) {
                    $output_index_count = NULL;
                }
            }
            if (NULL !== $output_index_count) {
                return '[' . implode(",$br", $output_indexed) . ']';
            } else {
                return "{{$br}" . implode(",$br", $output_associative) . "{$br}{$tab}}";
            }
        default:
            return ''; // Not supported
    }
}

function xn_json_decode($json)
{
    $json = trim($json, "\xEF\xBB\xBF");
    $json = trim($json, "\xFE\xFF");
    return json_decode($json, TRUE);
}

// ---------------------> encrypt function end

function pagination_tpl($url, $text, $active = '')
{
    global $g_pagination_tpl;
    empty($g_pagination_tpl) and $g_pagination_tpl = '<li class="page-item{active}"><a href="{url}" class="page-link">{text}</a></li>';
    return str_replace(array('{url}', '{text}', '{active}'), array($url, $text, $active), $g_pagination_tpl);
}

// bootstrap 翻页，命名与 bootstrap 保持一致
function pagination($url, $totalnum, $page, $pagesize = 20)
{
    $url = trim(xn_urldecode($url));
    $totalpage = ceil($totalnum / $pagesize);
    if ($totalpage < 2) return '';

    $page = min($totalpage, $page);
    $shownum = 3; // 显示多少个页 * 2

    $start = max(1, $page - $shownum);
    $end = min($totalpage, $page + $shownum);

    // 不足 $shownum，补全左右两侧
    $right = $page + $shownum - $totalpage;
    $right > 0 && $start = max(1, $start -= $right);
    $left = $page - $shownum;
    $left < 0 && $end = min($totalpage, $end -= $left);

    $s = '';
    $page != 1 && $s .= pagination_tpl(str_replace('{page}', $page - 1, $url), '&laquo;', '');
    if ($start > 1) $s .= pagination_tpl(str_replace('{page}', 1, $url), '1 ' . ($start > 2 ? '...' : ''));
    for ($i = $start; $i <= $end; $i++) {
        $s .= pagination_tpl(str_replace('{page}', $i, $url), $i, $i == $page ? ' active' : '');
    }
    if ($end != $totalpage) $s .= pagination_tpl(str_replace('{page}', $totalpage, $url), ($totalpage - $end > 1 ? '...' : '') . $totalpage);
    $page != $totalpage && $s .= pagination_tpl(str_replace('{page}', $page + 1, $url), '&raquo;');
    return $s;
}

// 简单的上一页，下一页，比较省资源，不用count(), 推荐使用，命名与 bootstrap 保持一致
function pager($url, $totalnum, $page, $pagesize = 20)
{
    $url = trim(xn_urldecode($url));
    $totalpage = ceil($totalnum / $pagesize);
    if ($totalpage < 2) return '';
    $page = min($totalpage, $page);

    $s = '';
    $page > 1 and $s .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $page - 1, $url) . '">Prev</a></li>';
    $s .= "<li class=\"page-item page-link\">$page / $totalpage</li>";
    $totalnum >= $pagesize and $page != $totalpage and $s .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $page + 1, $url) . '">Next</a></li>';
    return $s;
}

function mid($n, $min, $max)
{
    if ($n < $min) return $min;
    if ($n > $max) return $max;
    return $n;
}

function humandate($timestamp, $lan = array())
{
    $time = $_SERVER['time'];
    $lang = $_SERVER['lang'];

    static $custom_humandate = NULL;
    if (NULL === $custom_humandate) $custom_humandate = function_exists('custom_humandate');
    if ($custom_humandate) return custom_humandate($timestamp, $lan);

    $seconds = $time - $timestamp;
    $lan = empty($lang) ? $lan : $lang;
    empty($lan) and $lan = array(
        'month_ago' => '月前',
        'day_ago' => '天前',
        'hour_ago' => '小时前',
        'minute_ago' => '分钟前',
        'second_ago' => '秒前',
    );
    if ($seconds > 31536000) {
        return date('Y-n-j', $timestamp);
    } elseif ($seconds > 2592000) {
        return floor($seconds / 2592000) . $lan['month_ago'];
    } elseif ($seconds > 86400) {
        return floor($seconds / 86400) . $lan['day_ago'];
    } elseif ($seconds > 3600) {
        return floor($seconds / 3600) . $lan['hour_ago'];
    } elseif ($seconds > 60) {
        return floor($seconds / 60) . $lan['minute_ago'];
    } else {
        return $seconds . $lan['second_ago'];
    }
}

function humannumber($num)
{
    $num > 100000 && $num = ceil($num / 10000) . '万';
    return $num;
}

function humansize($num)
{
    if ($num > 1073741824) {
        return number_format($num / 1073741824, 2, '.', '') . 'G';
    } elseif ($num > 1048576) {
        return number_format($num / 1048576, 2, '.', '') . 'M';
    } elseif ($num > 1024) {
        return number_format($num / 1024, 2, '.', '') . 'K';
    } else {
        return $num . 'B';
    }
}

// 不安全的获取 IP 方式，在开启 CDN 的时候，如果被人猜到真实 IP，则可以伪造。
function ip()
{
    $conf = _SERVER('conf');
    if (empty($conf['cdn_on'])) {
        $ip = _SERVER('REMOTE_ADDR');
    } else {
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
            $ip = $_SERVER['HTTP_CLIENTIP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $arr = array_filter(explode(',', $ip));
            $ip = trim(end($arr));
        } else {
            $ip = _SERVER('REMOTE_ADDR');
        }
    }
    return $ip;
}

// 转IP格式，支持IPV4 IPV6
function safe_long2ip($longip)
{
    if (!is_numeric($longip)) return htmlspecialchars($longip, ENT_QUOTES);

    // IPV6
    if (strlen($longip) > 10) return long2ip_v6($longip);

    $longip = intval(4294967295 - ($longip - 1));
    return long2ip(-$longip);

    // IPV4转换32位
    /*$str = sprintf("%032s", decbin((float)$longip));
    $arr = array();
    for ($i = 0; $i < 4; ++$i) {
        $arr[] = bindec(substr($str, $i * 8, 8));
    }

    return implode('.', $arr);*/
}

// IPV6转数字
function ip2long_v6($ip)
{
    $ip_n = inet_pton($ip);
    $bin = '';
    for ($bit = strlen($ip_n) - 1; $bit >= 0; $bit--) {
        $bin = sprintf('%08b', ord($ip_n[$bit])) . $bin;
    }

    if (function_exists('gmp_init')) {
        return gmp_strval(gmp_init($bin, 2), 10);
    } elseif (function_exists('bcadd')) {
        $dec = '0';
        for ($i = 0; $i < strlen($bin); $i++) {
            $dec = bcmul($dec, '2', 0);
            $dec = bcadd($dec, $bin[$i], 0);
        }
        return $dec;
    } else {
        trigger_error('GMP or BCMATH extension not installed!', E_USER_ERROR);
    }
}

// 转IPV6
function long2ip_v6($dec)
{
    if (function_exists('gmp_init')) {
        $bin = gmp_strval(gmp_init($dec, 10), 2);
    } elseif (function_exists('bcadd')) {
        $bin = '';
        do {
            $bin = bcmod($dec, '2') . $bin;
            $dec = bcdiv($dec, '2', 0);
        } while (bccomp($dec, '0'));
    } else {
        trigger_error('GMP or BCMATH extension not installed!', E_USER_ERROR);
    }

    $bin = str_pad($bin, 128, '0', STR_PAD_LEFT);
    $ip = array();
    for ($bit = 0; $bit <= 7; $bit++) {
        $bin_part = substr($bin, $bit * 16, 16);
        $ip[] = dechex(bindec($bin_part));
    }
    $ip = implode(':', $ip);
    return inet_ntop(inet_pton($ip));
}

// 安全获取用户IP，信任 CDN 发过来的 X-FORWARDED-FOR
/*
function ip() {
	global $conf;
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'; // 如果有 CDN 的时候，为离服务器最近的 IP
	if(empty($conf['cdn_ip']) || empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		return $ip;
	} else {
		// 判断 cdnip 合法性，严格过滤 HTTP_X_FORWARDED_FOR
		// X-Forwarded-For: client1, proxy1, proxy2, ...
		// 离服务器最最近的为最后一个 proxy2，应该在 $conf['cdn_ip'] 当中才安全可信
		foreach($conf['cdn_ip'] as $cdnip) {
			$pos1 = strrpos($cdnip, '.');
			$pos2 = strrpos($ip, '.');
			// 合法 CDN IP 段
			if($ip == $cdnip || ($pos1 == $pos2 && substr($cdnip, $pos1) == '.*' && substr($cdnip, 0, $pos1) == substr($ip, 0, $pos2))) {
				$userips = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['HTTP_X_REAL_IP'];
				if(empty($userips)) return $ip; // 此处 CDN 未转发 userip，有错误，可能需要记录日志
				$arr = array_values(array_filter(explode(',', $userips)));
				return long2ip(ip2long(end($arr)));
			}
		}
		return $ip;
	}
}
*/

// 日志记录
function xn_log($s, $file = 'error')
{
    if (0 == DEBUG && FALSE === strpos($file, 'error')) return;
    $time = $_SERVER['time'];
    $ip = $_SERVER['ip'];
    $conf = _SERVER('conf');
    $uid = intval(G('uid')); // xiunophp 未定义 $uid
    $day = date('Ym', $time); // 按照月存放，否则 Ymd 目录太多。
    $mtime = date('Y-m-d H:i:s'); // 默认值为 time()
    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $logpath = $conf['log_path'] . $day;
    !is_dir($logpath) and mkdir($logpath, 0777, true);

    $s = str_replace(array("\r\n", "\n", "\t"), ' ', $s);
    $s = "<?php exit;?>\t$mtime\t$ip\t$url\t$uid\t$s\r\n";

    @error_log($s, 3, $logpath . "/$file.php");
}

function get__browser()
{
    // 默认为 chrome 标准浏览器
    $browser = array(
        'device' => 'pc', // pc|mobile|pad
        'name' => 'chrome', // chrome|firefox|ie|opera
        'version' => 30,
    );
    $agent = _SERVER('HTTP_USER_AGENT');
    // 主要判断是否为垃圾 IE6789
    if (FALSE !== strpos($agent, 'msie') || FALSE !== stripos($agent, 'trident')) {
        $browser['name'] = 'ie';
        $browser['version'] = 8;
        preg_match('#msie\s*([\d\.]+)#is', $agent, $m);
        if (!empty($m[1])) {
            if (FALSE !== strpos($agent, 'compatible; msie 7.0;')) {
                $browser['version'] = 8;
            } else {
                $browser['version'] = intval($m[1]);
            }
        } else {
            // 匹配兼容模式 Trident/7.0，兼容模式下会有此标志 $trident = 7;
            preg_match('#Trident/([\d\.]+)#is', $agent, $m);
            if (!empty($m[1])) {
                $trident = intval($m[1]);
                4 == $trident and $browser['version'] = 8;
                5 == $trident and $browser['version'] = 9;
                $trident > 5 and $browser['version'] = 10;
            }
        }
    }

    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap") || stripos($agent, 'phone') || stripos($agent, 'mobile') || strpos($agent, 'ipod'))) {
        $browser['device'] = 'mobile';
    } elseif (FALSE !== strpos($agent, 'pad')) {
        $browser['device'] = 'pad';
        $browser['name'] = '';
        $browser['version'] = '';
    } else {
        $robots = array('bot', 'spider', 'slurp');
        foreach ($robots as $robot) {
            if (FALSE !== strpos($agent, $robot)) {
                $browser['name'] = 'robot';
                return $browser;
            }
        }
    }
    return $browser;
}

function check_browser($browser)
{
    if ('ie' == $browser['name'] && $browser['version'] < 8) {
        include _include(APP_PATH . 'view/htm/browser.htm');
        exit;
    }
}

function is_robot()
{
    $agent = _SERVER('HTTP_USER_AGENT');
    $agent = strtolower($agent);
    $robots = array('bot', 'spider', 'slurp');
    foreach ($robots as $robot) {
        if (FALSE !== strpos($agent, $robot)) {
            return TRUE;
        }
    }
    return FALSE;
}

function browser_lang()
{
    // return 'zh-cn';
    $accept = _SERVER('HTTP_ACCEPT_LANGUAGE');
    $accept = substr($accept, 0, strpos($accept, ';'));
    if (FALSE !== strpos($accept, 'ko-kr')) {
        return 'ko-kr';
        // } elseif(FALSE !== strpos($accept, 'en')) {
        // 	return 'en';
    } else {
        return 'zh-cn';
    }
}

// 安全请求一个 URL
// ini_set('default_socket_timeout', 60);
function http_get($url, $cookie = '', $timeout = 30, $times = 3)
{
    if (extension_loaded('curl')) return https_post($url, '', $cookie, $timeout, 'GET');

    $arr = array(
        'http' => array(
            'method' => 'GET',
            'timeout' => $timeout
        )
    );
    $stream = stream_context_create($arr);
    while ($times-- > 0) {
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        if (FALSE !== $s) return $s;
    }
    return FALSE;
}

function http_post($url, $post = '', $cookie = '', $timeout = 30, $times = 3)
{
    if (extension_loaded('curl')) return https_post($url, $post, $cookie, $timeout);

    is_array($post) and $post = http_build_query($post);
    is_array($cookie) and $cookie = http_build_query($cookie);
    $stream = stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => 'POST', 'content' => $post, 'timeout' => $timeout)));
    while ($times-- > 0) {
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        if (FALSE !== $s) return $s;
    }
    return FALSE;
}

function https_get($url, $cookie = '', $timeout = 30, $times = 1)
{
    return https_post($url, '', $cookie, $timeout, 'GET');
}

function https_post($url, $post = '', $cookie = '', $timeout = 30, $method = 'POST')
{
    $allow_url_fopen = strtolower(ini_get('allow_url_fopen'));
    $allow_url_fopen = (empty($allow_url_fopen) || 'off' == $allow_url_fopen) ? 0 : 1;
    $allow_get_contents = $allow_url_fopen && strtolower(ini_get('user_agent'));
    $allow_curl = extension_loaded('curl');

    if (!$allow_curl && !$allow_get_contents) return xn_error(-1, 'CURL and OpenSSL are not installed on the server.');

    is_array($post) and $post = http_build_query($post);
    is_array($cookie) and $cookie = http_build_query($cookie);

    //$w = stream_get_wrappers(); //  && in_array('https', $w)
    if (!$allow_curl) {
        if ('https://' == substr($url, 0, 8) && !extension_loaded('openssl')) return xn_error(-1, 'CURL and OpenSSL are not installed on the server.');

        $stream = stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => $method, 'content' => $post, 'timeout' => $timeout)));
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        return $s;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
    if (class_exists('\CURLFile')) {
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
    } else {
        defined('CURLOPT_SAFE_UPLOAD') and curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
    }
    curl_setopt($ch, CURLOPT_HEADER, 2); // 1/2
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, _SERVER('HTTP_USER_AGENT'));

    // 兼容HTTPS
    if (false !== stripos($url, 'https://')) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //ssl版本控制
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSLVERSION, true);
    }

    if ('POST' == $method) {
        curl_setopt($ch, CURLOPT_POST, true);
        // 自动设置Referer
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }

    $header = array('Content-type: application/x-www-form-urlencoded', 'X-Requested-With: XMLHttpRequest');
    $cookie and $header[] = "Cookie: $cookie";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // 使用自动跳转, 安全模式不允许
    (!ini_get('safe_mode') && !ini_get('open_basedir')) && curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    //优先解析 IPv6 超时后IPv4
    //curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        return xn_error(-1, 'Errno' . curl_error($ch));
    }
    if (!$data) {
        curl_close($ch);
        return '';
    }

    list($header, $data) = explode("\r\n\r\n", $data);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (301 == $http_code || 302 == $http_code) {
        $matches = array();
        preg_match('/Location:(.*?)\n/', $header, $matches);
        $url = trim(array_pop($matches));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
    }
    curl_close($ch);
    return $data;
}

// 多线程抓取数据，需要CURL支持，一般在命令行下执行，此函数收集于互联网，由 xiuno 整理，经过测试，会导致 CPU 100%。
function http_multi_get($urls)
{
    // 如果不支持，则转为单线程顺序抓取
    $data = array();
    if (!function_exists('curl_multi_init')) {
        foreach ($urls as $k => $url) {
            $data[$k] = https_get($url);
        }
        return $data;
    }

    $multi_handle = curl_multi_init();
    foreach ($urls as $i => $url) {
        $conn[$i] = curl_init($url);
        curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
        $timeout = 3;
        curl_setopt($conn[$i], CURLOPT_CONNECTTIMEOUT, $timeout); // 超时 seconds
        curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
        //curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);
        curl_multi_add_handle($multi_handle, $conn[$i]);
    }
    do {
        $mrc = curl_multi_exec($multi_handle, $active);
    } while (CURLM_CALL_MULTI_PERFORM == $mrc);

    while ($active and CURLM_OK == $mrc) {
        if (curl_multi_select($multi_handle) != -1) {
            do {
                $mrc = curl_multi_exec($multi_handle, $active);
            } while (CURLM_CALL_MULTI_PERFORM == $mrc);
        }
    }
    foreach ($urls as $i => $url) {
        $data[$i] = curl_multi_getcontent($conn[$i]);
        curl_multi_remove_handle($multi_handle, $conn[$i]);
        curl_close($conn[$i]);
    }
    return $data;
}

// 将变量写入到文件，根据后缀判断文件格式，先备份，再写入，写入失败，还原备份
function file_replace_var($filepath, $replace = array(), $pretty = FALSE)
{
    $ext = file_ext($filepath);
    if ('php' == $ext) {
        $arr = include $filepath;
        $arr = array_merge($arr, $replace);
        $s = "<?php\r\nreturn " . var_export($arr, true) . ";\r\n?>";
        // 备份文件
        file_backup($filepath);
        $r = file_put_contents_try($filepath, $s);
        $r != strlen($s) ? file_backup_restore($filepath) : file_backup_unlink($filepath);
        return $r;
    } elseif ('js' == $ext || 'json' == $ext) {
        $s = file_get_contents_try($filepath);
        $arr = xn_json_decode($s);
        if (empty($arr)) return FALSE;
        $arr = array_merge($arr, $replace);
        $s = xn_json_encode($arr, $pretty);
        file_backup($filepath);
        $r = file_put_contents_try($filepath, $s);
        $r != strlen($s) ? file_backup_restore($filepath) : file_backup_unlink($filepath);
        return $r;
    }
}

function file_backname($filepath)
{
    $filepre = file_pre($filepath);
    $fileext = file_ext($filepath);
    $s = "$filepre.backup.$fileext";
    return $s;
}

function is_backfile($filepath)
{
    return FALSE !== strpos($filepath, '.backup.');
}

// 备份文件
function file_backup($filepath)
{
    $backfile = file_backname($filepath);
    if (is_file($backfile)) return TRUE; // 备份已经存在
    $r = xn_copy($filepath, $backfile);
    clearstatcache();
    return $r && filesize($backfile) == filesize($filepath);
}

// 还原备份
function file_backup_restore($filepath)
{
    $backfile = file_backname($filepath);
    $r = xn_copy($backfile, $filepath);
    clearstatcache();
    $r && filesize($backfile) == filesize($filepath) && xn_unlink($backfile);
    return $r;
}

// 删除备份
function file_backup_unlink($filepath)
{
    $backfile = file_backname($filepath);
    $r = xn_unlink($backfile);
    return $r;
}

function file_get_contents_try($file, $times = 3)
{
    while ($times-- > 0) {
        $fp = fopen($file, 'rb');
        if ($fp) {
            $size = filesize($file);
            if (0 == $size) return '';
            $s = fread($fp, $size);
            fclose($fp);
            return $s;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}

function file_put_contents_try($file, $s, $times = 3)
{
    while ($times-- > 0) {
        $fp = fopen($file, 'wb');
        if ($fp and flock($fp, LOCK_EX)) {
            $n = fwrite($fp, $s);
            version_compare(PHP_VERSION, '5.3.2', '>=') and flock($fp, LOCK_UN);
            fclose($fp);
            clearstatcache();
            return $n;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}

// 判断一个字符串是否在另外一个字符串里面，分隔符 ,
function in_string($s, $str)
{
    if (!$s || !$str) return FALSE;
    $s = ",$s,";
    $str = ",$str,";
    return FALSE !== strpos($str, $s);
}

function move_upload_file($srcfile, $destfile)
{
    //$r = move_uploaded_file($srcfile, $destfile);
    $r = xn_copy($srcfile, $destfile);
    return $r;
}

// 文件后缀名，不包含 .
function file_ext($filename, $max = 16)
{
    $ext = strtolower(substr(strrchr($filename, '.'), 1));
    $ext = xn_urlencode($ext);
    strlen($ext) > $max and $ext = substr($ext, 0, $max);
    if (!preg_match('#^\w+$#', $ext)) $ext = 'attach';
    return $ext;
}

// 文件的前缀，不包含最后一个 .
function file_pre($filename, $max = 32)
{
    return substr($filename, 0, strrpos($filename, '.'));
}

// 获取路径中的文件名
function file_name($path)
{
    return substr($path, strrpos($path, '/') + 1);
}

// 在 header 头中发送DEBUG信息
/*function t($name = '') {
	global $starttime;
	header("Time $name:".substr(microtime(1) - $starttime, 0, 7));
}*/

// 获取 http://xxx.com/path/
function http_url_path()
{
    $conf = _SERVER('conf');
    $port = _SERVER('SERVER_PORT');
    $host = _SERVER('HTTP_HOST');
    $https = strtolower(_SERVER('HTTPS', 'off'));
    $proto = strtolower(_SERVER('HTTP_X_FORWARDED_PROTO'));
    $len = strrpos($_SERVER['PHP_SELF'], '//');
    FALSE === $len and $len = strrpos($_SERVER['PHP_SELF'], '/');
    $path = substr($_SERVER['PHP_SELF'], 0, $len);
    !isset($conf['url_rewrite_on']) and $conf['url_rewrite_on'] = 0;
    $conf['url_rewrite_on'] < 2 and $path = $path . '/';
    $http = ((443 == $port) || 'https' == $proto || ($https && 'off' != $https)) ? 'https' : 'http';
    return "$http://$host$path";
}

// 将参数添加到 URL
function xn_url_add_arg($url, $k, $v)
{
    $pos = strpos($url, '.html');
    if (FALSE === $pos) {
        return FALSE === strpos($url, '?') ? $url . '&' . $k . '=' . $v : $url . '?' . $k . '=' . $v;
    } else {
        return substr($url, 0, $pos) . '-' . $v . substr($url, $pos);
    }
}

/**
 * URL format: http://www.domain.com/demo/?user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/?user-login.htm&a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm&a=b&c=d
 * array(
 *     0 => user,
 *     1 => login
 *     a => b
 *     c => d
 * )
 */
function xn_url_parse($request_url)
{
    $conf = _SERVER('conf');
    !isset($conf['url_rewrite_on']) and $conf['url_rewrite_on'] = 0;

    if ($conf['url_rewrite_on'] < 2) {

        0 == $conf['url_rewrite_on'] and $request_url = str_replace('/?', '/', $request_url);

        $arr = parse_url($request_url);
        $q = array_value($arr, 'path');
        $pos = strrpos($q, '/');
        FALSE === $pos && $pos = -1;
        $q = substr($q, $pos + 1); // 截取最后一个 / 后面的内容
        // 查找第一个 ? & 进行分割
        $sep = FALSE === strpos($q, '?') ? strpos($q, '&') : FALSE;
        if (FALSE !== $sep) {
            // 对后半部分截取，并且分析
            $front = substr($q, 0, $sep);
            $behind = substr($q, $sep + 1);
        } else {
            $front = $q;
            $behind = '';
        }

        if ('.html' == substr($front, -5)) $front = substr($front, 0, -5);
        $r = $front ? explode('-', $front) : array();

        // 将后半部分合并
        $arr1 = $arr2 = $arr3 = array();
        $behind and parse_str($behind, $arr1);

        // 将 xxx.htm?a=b&c=d 放到后面，并且修正 $_GET
        if (!empty($arr['query'])) {
            parse_str($arr['query'], $arr2);
        } else {
            !empty($_GET) and $_GET = array();
        }
        $arr3 = $arr1 + $arr2;
        if ($arr3) {
            //array_diff_key($arr3, $_GET) || array_diff_key($_GET, $arr3);
            count($arr3) != count($_GET) and $_GET = $arr3;
        } else {
            !empty($_GET) and $_GET = array();
        }
        $r += $arr3;
    } else {
        $r = xn_url_parse_path_format($_SERVER['REQUEST_URI']);
    }

    isset($r[0]) && ('admin' == $r[0] || 'index.php' == $r[0]) and $r[0] = 'index';

    return $r;
}

/**
 * 支持 URL format: http://www.domain.com/user/login?a=1&b=2
 * array(
 *     0 => user,
 *     1 => login,
 *     a => 1,
 *     b => 2
 * )
 */
function xn_url_parse_path_format($s)
{
    $request_url = explode('?', $s);
    $url = str_replace('.html', '', $request_url[0]);
    $url = trim($url, '/');
    $get = explode('/', $url);
    if (!empty($request_url[1])) {
        parse_str($request_url[1], $arr2);
        $get = array_merge($get, $arr2);
    }
    return $get;
}

// 递归遍历目录
function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}

// 递归删除目录，这个函数比较危险，传参一定要小心
function rmdir_recusive($dir, $keepdir = 0)
{
    if ('/' == $dir || './' == $dir || '../' == $dir) return FALSE;// 不允许删除根目录，避免程序意外删除数据。
    if (!is_dir($dir)) return FALSE;

    '/' != substr($dir, -1) and $dir .= '/';

    $files = glob($dir . '*'); // +glob($dir.'.*')
    foreach (glob($dir . '.*') as $v) {
        if (substr($v, -1) != '.' && substr($v, -2) != '..') $files[] = $v;
    }
    $filearr = $dirarr = array();
    if ($files) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirarr[] = $file;
            } else {
                $filearr[] = $file;
            }
        }
    }
    if ($filearr) {
        foreach ($filearr as $file) {
            xn_unlink($file);
        }
    }
    if ($dirarr) {
        foreach ($dirarr as $file) {
            rmdir_recusive($file);
        }
    }
    if (!$keepdir) xn_rmdir($dir);
    return TRUE;
}

function xn_copy($src, $dest)
{
    $r = is_file($src) ? copy($src, $dest) : FALSE;
    return $r;
}

function xn_mkdir($dir, $mod = NULL, $recusive = NULL)
{
    $r = !is_dir($dir) ? mkdir($dir, $mod, $recusive) : FALSE;
    return $r;
}

function xn_rmdir($dir)
{
    $r = is_dir($dir) ? rmdir($dir) : FALSE;
    return $r;
}

function xn_unlink($file)
{
    $r = is_file($file) ? unlink($file) : FALSE;
    return $r;
}

function xn_filemtime($file)
{
    return is_file($file) ? filemtime($file) : 0;
}

/*
	实例：
	xn_set_dir(123, APP_PATH.'upload');
	
	000/000/1.jpg
	000/000/100.jpg
	000/000/100.jpg
	000/000/999.jpg
	000/001/1000.jpg
	000/001/001.jpg
	000/002/001.jpg
*/
function xn_set_dir($id, $dir = './')
{

    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    $dir1 = $dir . $s1;
    $dir2 = $dir . "$s1/$s2";

    !is_dir($dir1) && mkdir($dir1, 0777);
    !is_dir($dir2) && mkdir($dir2, 0777);
    return "$s1/$s2";
}

// 取得路径：001/123
function xn_get_dir($id)
{
    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    return "$s1/$s2";
}

// 递归拷贝目录
function copy_recusive($src, $dst)
{
    '/' == substr($src, -1) and $src = substr($src, 0, -1);
    '/' == substr($dst, -1) and $dst = substr($dst, 0, -1);
    $dir = opendir($src);
    !is_dir($dst) and mkdir($dst);
    while (FALSE !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_recusive($src . '/' . $file, $dst . '/' . $file);
            } else {
                xn_copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// 随机字符
function xn_rand($n = 16)
{
    $str = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $len = strlen($str);
    $return = '';
    for ($i = 0; $i < $n; $i++) {
        $r = mt_rand(1, $len);
        $return .= $str[$r - 1];
    }
    return $return;
}

// 检测文件是否可写，兼容 windows
function xn_is_writable($file)
{
    if (PHP_OS != 'WINNT') {
        return is_writable($file);
    } else {
        // 如果是 windows，比较麻烦，这也只是大致检测，不够精准。
        if (is_file($file)) {
            $fp = fopen($file, 'a+');
            if (!$fp) return FALSE;
            fclose($fp);
            return TRUE;
        } elseif (is_dir($file)) {
            $tmpfile = $file . uniqid() . '.tmp';
            $r = touch($tmpfile);
            if (!$r) return FALSE;
            if (!is_file($tmpfile)) return FALSE;
            xn_unlink($tmpfile);
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

function xn_debug_info()
{
    $db = $_SERVER['db'];
    $starttime = $_SERVER['starttime'];
    $s = '';
    if (DEBUG > 1) {
        $s .= '<fieldset class="fieldset small debug break-all">';
        $s .= '<p>Processed Time:' . (microtime(1) - $starttime) . '</p>';
        if (IN_CMD) {
            foreach ($db->sqls as $sql) {
                $s .= "$sql\r\n";
            }
        } else {
            $s .= "\r\n<ul>\r\n";
            foreach ($db->sqls as $sql) {
                $s .= "<li>$sql</li>\r\n";
            }
            $s .= "</ul>\r\n";
            $s .= '_REQUEST:<br>';
            $s .= xn_txt_to_html(print_r($_REQUEST, 1));
            if (!empty($_SESSION)) {
                $s .= '_SESSION:<br>';
                $s .= xn_txt_to_html(print_r($_SESSION, 1));
            }
            $s .= '';
        }
        $s .= '</fieldset>';
    }
    return $s;
}

// 解码客户端提交的 base64 数据
function base64_decode_file_data($data)
{
    if ('data:' == substr($data, 0, 5)) {
        $data = substr($data, strpos($data, ',') + 1);    // 去掉 data:image/png;base64,
    }
    $data = base64_decode($data);
    return $data;
}

// 输出
function http_404()
{
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    echo '<h1>404 Not Found</h1>';
    exit;
}

// 无权限访问
function http_403()
{
    header('HTTP/1.1 403 Forbidden');
    header('Status: 403 Forbidden');
    echo '<h1>403 Forbidden</h1>';
    exit;
}

function http_location($url)
{
    header('Location:' . $url);
    exit;
}

// 获取 referer
function http_referer()
{
    $referer = param('referer');
    empty($referer) and $referer = _SERVER('HTTP_REFERER');
    if (FALSE !== strpos($referer, url('user-login')) || FALSE !== strpos($referer, url('user-logout')) || FALSE !== strpos($referer, url('user-create'))) {
        $referer = http_url_path();
    }
    // 安全过滤，只支持站内跳转，不允许跳到外部，否则可能会被 XSS
    $parse_url = parse_url($referer);
    if (isset($parse_url['host']) && $parse_url['host'] != $_SERVER['HTTP_HOST']) $referer = './';
    return $referer;
}

function str_push($str, $v, $sep = '_')
{
    if (empty($str)) return $v;
    if (FALSE === strpos($str, $v . $sep)) {
        return $str . $sep . $v;
    }
    return $str;
}

function y2f($rmb)
{
    $rmb = floor($rmb * 10 * 10);
    return $rmb;
}

// $round: float round ceil floor
function f2y($rmb, $round = 'float')
{
    $rmb = floor($rmb * 100) / 10000;
    if ('float' == $round) {
        $rmb = number_format($rmb, 2, '.', '');
    } elseif ('round' == $round) {
        $rmb = round($rmb);
    } elseif ('ceil' == $round) {
        $rmb = ceil($rmb);
    } elseif ('floor' == $round) {
        $rmb = floor($rmb);
    }
    return $rmb;
}

// 无 Notice 方式的获取超级全局变量中的 key
function _GET($k, $def = NULL)
{
    return isset($_GET[$k]) ? $_GET[$k] : $def;
}

function _POST($k, $def = NULL)
{
    return isset($_POST[$k]) ? $_POST[$k] : $def;
}

function _COOKIE($k, $def = NULL)
{
    return isset($_COOKIE[$k]) ? $_COOKIE[$k] : $def;
}

function _REQUEST($k, $def = NULL)
{
    return isset($_REQUEST[$k]) ? $_REQUEST[$k] : $def;
}

function _ENV($k, $def = NULL)
{
    return isset($_ENV[$k]) ? $_ENV[$k] : $def;
}

function _SERVER($k, $def = NULL)
{
    return isset($_SERVER[$k]) ? $_SERVER[$k] : $def;
}

function GLOBALS($k, $def = NULL)
{
    return isset($GLOBALS[$k]) ? $GLOBALS[$k] : $def;
}

function G($k, $def = NULL)
{
    return isset($GLOBALS[$k]) ? $GLOBALS[$k] : $def;
}

function _SESSION($k, $def = NULL)
{
    global $g_session;
    return isset($_SESSION[$k]) ? $_SESSION[$k] : (isset($g_session[$k]) ? $g_session[$k] : $def);
}

?>