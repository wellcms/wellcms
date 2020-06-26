<?php

function cache_new($cacheconf) {
	// 缓存初始化，这里并不会产生连接！在真正使用的时候才连接。
	// 这里采用最笨拙的方式而不采用 new $classname 的方式，有利于 opcode 缓存。
	if($cacheconf && !empty($cacheconf['enable'])) {
		switch ($cacheconf['type']) {
			case 'redis': 	  $cache = new cache_redis($cacheconf['redis']); 	     break;
			case 'memcached': $cache = new cache_memcached($cacheconf['memcached']); break;
			case 'pdo_mysql': 	  
			case 'mysql': 	  
					$cache = new cache_mysql($cacheconf['mysql']); break;
			case 'xcache': 	  $cache = new cache_xcache($cacheconf['xcache']); 	break;
			case 'apc': 	  $cache = new cache_apc($cacheconf['apc']); 	break;
			case 'yac': 	  $cache = new cache_yac($cacheconf['yac']); 	break;
			default: return xn_error(-1, '不支持的 cache type:'.$cacheconf['type']);
		}
		return $cache;
	}
	return NULL;
}

function cache_get($k, $c = NULL) {
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if(!$c) return FALSE;

    $k = $c->cachepre.$k;
	strlen($k) > 32 AND $k = md5($k);
	
	$r = $c->get($k);
	return $r;
}

function cache_set($k, $v, $life = 0, $c = NULL) {
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if(!$c) return FALSE;

    $k = $c->cachepre.$k;
	strlen($k) > 32 AND $k = md5($k);
	
	$r = $c->set($k, $v, $life);
	return $r;
}

function cache_delete($k, $c = NULL) {
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if(!$c) return FALSE;

    $k = $c->cachepre.$k;
	strlen($k) > 32 AND $k = md5($k);
	
	$r = $c->delete($k);
	return $r;
}

// cache_update($key, $update = array('trash_threads' => 1));
// cache_update($key, $update = array('trash_threads+' => 1));
// cache_update($key, $update = array('trash_threads-' => 1));
function cache_update($key = NULL, $arr = array(), $life = 0)
{
    global $conf;
    if (empty($key) || empty($arr) || 'mysql' == $conf['cache']['type']) return NULL;

    $cache = cache_get($key);
    if (empty($cache)) return NULL;

    $cache = cache_merge($cache, $arr);
    cache_set($key, $cache, $life);

    return $cache;
}

// cache_merge($arr, $update = array('trash_threads' => 1));
// cache_merge($arr, $update = array('trash_threads+' => 1));
// cache_merge($arr, $update = array('trash_threads-' => 1));
function cache_merge($arr = array(), $update = array())
{
    if (empty($arr) || empty($update)) return TRUE;

    if (count($update) == count($update, 1)) {
        $arr = cache_merge_data($arr, $update);
    } else {
        foreach ($update as $k => $v) {
            !isset($arr[$k]) AND $arr[$k] = array();
            $arr = cache_merge_data($arr[$k], $v);
        }
    }
    return $arr;
}

// cache_merge_data($arr, $update = array('trash_threads' => 1));
// cache_merge_data($arr, $update = array('trash_threads+' => 1));
// cache_merge_data($arr, $update = array('trash_threads-' => 1));
function cache_merge_data($arr = array(), $update = array())
{
    if (empty($arr) || empty($update)) return TRUE;
    foreach ($update as $k => $v) {
        $op = substr($k, -1);
        if ('+' == $op || '-' == $op) {
            $k = substr($k, 0, -1);
            !isset($arr[$k]) AND $arr[$k] = 0;
            $v = '+' == $op ? ($arr[$k] + $v) : ($arr[$k] - $v);
        }
        $arr[$k] = $v;
    }
    return $arr;
}

// 尽量避免调用此方法，不会清理保存在 kv 中的数据，逐条 cache_delete() 比较保险
function cache_truncate($c = NULL) {
	$cache = $_SERVER['cache'];
	$c = $c ? $c : $cache;
	if(!$c) return FALSE;
	$r = $c->truncate();
	return $r;
}

function cookie_set($key, $value, $life = 8640000)
{
    global $conf, $time;
    is_array($value) AND $value = xn_json_encode($value);
    setcookie($conf['cookie_pre'] . $key, $value, ($time + $life), $conf['cookie_path'], $conf['cookie_domain'], '', TRUE);
}

// 清空内存缓存和Cookie
function cookie_cache_clear($key, $cookie = TRUE)
{
    global $conf, $time;
    TRUE == $cookie AND setcookie($key, '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain']);
    'mysql' != $conf['cache']['type'] AND cache_delete($key);
}

?>