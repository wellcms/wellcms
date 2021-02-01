<?php

/*
	php 默认的 session 采用文件存储，并且使用 flock() 文件锁避免并发访问不出问题（实际上还是无法解决业务层的并发读后再写入）
	自定义的 session 采用数据表来存储，同样无法解决业务层并发请求问题。
	xiuno.js $.each_sync() 串行化并发请求，可以避免客户端并发访问导致的 session 写入问题。
*/

// hook model_session_start.php

$sid = '';
$g_session = array();
$g_session_invalid = FALSE; // 0: 有效， 1：无效

// 可以指定独立的 session 服务器，在系统压力巨大的时候可以考虑优化
//$g_sess_db = $db;

// 如果是管理员, sid, 与 ip 绑定，一旦 IP 发生变化，则需要重新登录。管理员采用 token (绑定IP) 双重验证，避免 sid 被中间窃取。

// hook model_session_before.php

// ------------> 最原生的 CURD，无关联其他数据。

function session_create($arr, $d = NULL)
{
    // hook model_session_create_start.php
    $r = db_insert('session', $arr, $d);
    // hook model_session_create_end.php
    return $r;
}

function session_update($sid, $update, $d = NULL)
{
    // hook model_session_update_start.php
    $r = db_update('session', array('sid' => $sid), $update, $d);
    // hook model_session_update_end.php
    return $r;
}

function session_read($sid, $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_session_read_start.php
    $r = db_find_one('session', array('sid' => $sid), $orderby, $col, $d);
    // hook model_session_read_end.php
    return $r;
}

function session_delete($cond = array(), $d = NULL)
{
    // hook model_session_delete_start.php
    $r = db_delete('session', $cond, $d);
    // hook model_session_delete_end.php
    return $r;
}

function session_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = '', $col = array(), $d = NULL)
{
    // hook model_session_find_start.php
    $arrlist = db_find('session', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_session_find_end.php
    return $arrlist;
}

function session_count($cond = array(), $d = NULL)
{
    // hook model_session_count_start.php
    $n = db_count('session', $cond, $d);
    // hook model_session_count_end.php
    return $n;
}

function session_data_create($arr, $d = NULL)
{
    // hook model_session_data_create_start.php
    $r = db_insert('session_data', $arr, $d);
    // hook model_session_data_create_end.php
    return $r;
}

function session_data_update($sid, $update, $d = NULL)
{
    // hook model_session_data_update_start.php
    $r = db_update('session_data', array('sid' => $sid), $update, $d);
    // hook model_session_data_update_end.php
    return $r;
}

function session_data_read($sid, $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_session_data_read_start.php
    $r = db_find_one('session_data', array('sid' => $sid), $orderby, $col, $d);
    // hook model_session_data_read_end.php
    return $r;
}

function session_data_delete($cond = array(), $d = NULL)
{
    // hook model_session_data_delete_start.php
    $r = db_delete('session_data', $cond, $d);
    // hook model_session_data_delete_end.php
    return $r;
}

//--------------------------强相关--------------------------

function sess_open($save_path, $session_name)
{
    //echo "sess_open($save_path,$session_name) \r\n";
    return true;
}

// 关闭句柄，清理资源，这里 $sid 已经为空，
function sess_close()
{
    return true;
}

// 如果 cookie 中没有 sid, php 会自动生成 sid，作为参数
function sess_read($sid)
{
    global $g_session, $longip, $time;

    if (empty($sid)) {
        // 查找刚才是不是已经插入一条了？  如果相隔时间特别短，并且 data 为空，则删除。
        // 测试是否支持 cookie，如果不支持 cookie，则不生成 sid
        $sid = session_id();
        sess_new($sid);
        return '';
    }
    $arr = session_read($sid);
    if (empty($arr)) {
        sess_new($sid);
        return '';
    }
    if (1 == $arr['bigdata']) {
        $arr2 = session_data_read($sid);
        $arr['data'] = $arr2['data'];
    }
    $g_session = $arr;
    // 在 php 5.6.29 版本，需要返回 session_decode()
    //return $arr ? session_decode($arr['data']) : '';
    return $arr ? $arr['data'] : '';
}

function sess_new($sid)
{
    global $time, $longip, $conf, $g_session, $g_session_invalid;

    $agent = _SERVER('HTTP_USER_AGENT');

    // 干掉同 ip 的 sid，仅仅在遭受攻击的时候
    //session_delete(array('ip'=>$longip));

    $cookie_test = _COOKIE('cookie_test');
    if ($cookie_test) {
        $cookie_test_decode = xn_decrypt($cookie_test, $conf['auth_key']);
        $g_session_invalid = ($cookie_test_decode != md5($agent . $longip));
        setcookie('cookie_test', '', $time - 86400, '');
    } else {
        $cookie_test = xn_encrypt(md5($agent . $longip), $conf['auth_key']);
        setcookie('cookie_test', $cookie_test, $time + 86400, '');
        $g_session_invalid = FALSE;
        return;
    }

    // 可能会暴涨
    $url = _SERVER('REQUEST_URI');

    $arr = array(
        'sid' => $sid,
        'uid' => 0,
        'fid' => 0,
        'url' => $url,
        'last_date' => $time,
        'data' => '',
        'ip' => $longip,
        'useragent' => $agent,
        'bigdata' => 0,
    );
    $g_session = $arr;
    session_create($arr);
}

// 重新启动 session，降低并发写入数据的问题，这回抛弃前面的 _SESSION 数据
function sess_restart()
{
    global $sid;
    $data = sess_read($sid);
    session_decode($data); // 直接存入了 $_SESSION
}

// 将当前的 _SESSION 变量保存
function sess_save()
{
    global $sid;
    sess_write($sid, TRUE);
}

// 模拟加锁，如果发现写入的时候数据已经发生改变，则读取后，合并数据，重新写入（合并总比删除安全一点）。
function sess_write($sid, $data)
{
    global $g_session, $time, $longip, $g_session_invalid, $conf;

    $uid = _SESSION('uid');
    $fid = _SESSION('fid');
    unset($_SESSION['uid'], $_SESSION['fid']);

    $data and $data = session_encode();

    function_exists('chdir') and chdir(APP_PATH);

    $url = _SERVER('REQUEST_URI');
    $agent = _SERVER('HTTP_USER_AGENT');
    $arr = array(
        'uid' => $uid,
        'fid' => $fid,
        'url' => $url,
        'last_date' => $time,
        'data' => $data,
        'ip' => $longip,
        'useragent' => $agent,
        'bigdata' => 0,
    );

    // 开启 session 延迟更新，减轻压力，会导致不重要的数据(useragent,url)显示有些延迟，单位为秒。
    $session_delay_update_on = !empty($conf['session_delay_update']) && $time - $g_session['last_date'] < $conf['session_delay_update'];

    if ($session_delay_update_on) unset($arr['fid'], $arr['url'], $arr['last_date'], $arr['useragent']);

    // 判断数据是否超长
    $len = strlen($data);
    if ($len > 255 && 0 == $g_session['bigdata']) session_data_create(array('sid' => $sid));
    
    if ($len <= 255) {
        $update = array_diff_value($arr, $g_session);
        session_update($sid, $update);
        if (!empty($g_session) && 1 == $g_session['bigdata']) session_data_delete(array('sid' => $sid));
    } else {
        $arr['data'] = '';
        $arr['bigdata'] = 1;
        $update = array_diff_value($arr, $g_session);
        $update and session_update($sid, $update);
        $arr2 = array('data' => $data, 'last_date' => $time);
        if ($session_delay_update_on) unset($arr2['last_date']);
        $update2 = array_diff_value($arr2, $g_session);
        $update2 and session_data_update($sid, $update2);
    }
    return TRUE;
}

function sess_destroy($sid)
{
    session_delete(array('sid' => $sid));
    session_data_delete(array('sid' => $sid));
    return TRUE;
}

function sess_gc($maxlifetime)
{
    global $time;

    $expiry = $time - $maxlifetime;
    $arrlist = session_find(array('last_date' => array('<' => $expiry)), array(), 1, 10000, '', array('sid', 'bigdata', 'last_date'));
    if (!$arrlist) return TRUE;

    $expiry = $time - 21600; // 超6小时未提交丢弃上传图片和附件
    $sidarr = array();
    foreach ($arrlist as $val) {
        if ($val['last_date'] > $expiry && $val['bigdata']) continue;
        $sidarr[] = $val['sid'];
    }

    if (empty($sidarr)) return TRUE;

    session_delete(array('sid' => $sidarr));
    session_data_delete(array('sid' => $sidarr));

    return TRUE;
}

function sess_start()
{
    global $conf, $sid, $g_session;
    ini_set('session.name', $conf['cookie_pre'] . 'sid');
    ini_set('session.use_cookies', TRUE);
    ini_set('session.use_only_cookies', TRUE);
    ini_set('session.cookie_domain', $conf['cookie_domain']);
    // 为空则表示当前目录和子目录
    ini_set('session.cookie_path', $conf['cookie_path']);
    // 打开后只有通过 https 才有效
    ini_set('session.cookie_secure', FALSE);
    ini_set('session.cookie_lifetime', 8640000);
    // 打开后 js 获取不到 HTTP 设置的 cookie, 有效防止 XSS，对于安全很重要，除非有 BUG，否则不要关闭。
    ini_set('session.cookie_httponly', TRUE);
    // 活动时间
    ini_set('session.gc_maxlifetime', $conf['online_hold_time']);
    // 垃圾回收概率 = gc_probability/gc_divisor
    ini_set('session.gc_probability', 1);
    // 垃圾回收时间 5 秒，在线人数 * 10 / 每1000个请求回收一次垃圾
    ini_set('session.gc_divisor', 1000);

    session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy', 'sess_gc');

    // register_shutdown_function 会丢失当前目录，需要 chdir(APP_PATH)
    $conf['url_rewrite_on'] > 1 and function_exists('chdir') and chdir(APP_PATH);
    // 这个必须有，否则 ZEND 会提前释放 $db 资源
    register_shutdown_function('session_write_close');

    session_start();
    $sid = session_id();

    return $sid;
}

// 刷新页面清理附件缓存 废弃
function sess_clear_attach()
{
    global $sid, $time;
    $arr = session_read($sid);
    if (!$arr || 0 == $arr['bigdata']) return TRUE;

    session_update($sid, array('bigdata' => 0, 'last_date' => $time));
    session_data_delete(array('sid' => $sid));
    return TRUE;
}

function online_count()
{
    return session_count();
}

function online_list_cache()
{
    static $cache = array();
    $key = 'online_list';
    if (isset($cache[$key])) return $cache[$key];

    $cache[$key] = cache_get($key);
    if (NULL === $cache[$key]) {
        $cache[$key] = session_find(array('uid' => array('>' => 0)), array('last_date' => -1), 1, 1000);
        foreach ($cache[$key] as &$online) {
            $user = user_read_cache($online['uid']);
            $online['username'] = $user['username'];
            $online['gid'] = $user['gid'];
            $online['ip_fmt'] = safe_long2ip($online['ip']);
            $online['last_date_fmt'] = date('Y-n-j H:i', $online['last_date']);
        }
        cache_set('online_list', $cache[$key], 300);
    }
    return $cache[$key];
}

function online_user_list_cache()
{
    static $cache = array();
    $key = 'online_user_list';
    if (isset($cache[$key])) return $cache[$key];

    $cache[$key] = cache_get($key);
    if (NULL === $cache[$key]) {
        $cache[$key] = session_find(array('uid' => array('>' => 0)), array(), 1, 1000, 'uid', array('uid'));
        cache_set('online_user_list', $cache[$key], 300);
    }
    return $cache[$key];
}

// hook model_session_end.php

?>