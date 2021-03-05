<?php
// 如果环境支持，可以直接改为 redis get() set() 持久存储相关 API，提高速度。

// 无缓存
function kv__get($k)
{
    $arr = db_find_one('kv', array('k' => $k));
    return $arr ? xn_json_decode($arr['v']) : NULL;
}

function kv_get($k)
{
    static $static = array();
    strlen($k) > 32 AND $k = md5($k);
    if (!isset($static[$k])) {
        $static[$k] = kv__get($k);
    }
    return $static[$k];
}

function kv_set($k, $v)
{
    strlen($k) > 32 AND $k = md5($k);
    $arr = array(
        'k' => $k,
        'v' => xn_json_encode($v),
    );
    $r = db_replace('kv', $arr);
    return $r;
}

function kv_delete($k)
{
    strlen($k) > 32 AND $k = md5($k);
    $r = db_delete('kv', array('k' => $k));
    return $r;
}

// --------------------> kv + cache
function kv_cache_get($k)
{
    $r = cache_get($k);
    if (NULL === $r) {
        $r = kv_get($k);
        $r and cache_set($k, $r);
    }
    return $r;
}

function kv_cache_set($k, $v, $life = 0)
{
    cache_set($k, $v, $life);
    $r = kv_set($k, $v);
    return $r;
}

function kv_cache_delete($k)
{
    cache_delete($k);
    $r = kv_delete($k);
    return $r;
}

// ------------> kv + cache + setting
$g_setting = FALSE;
function setting_get($k)
{
    global $g_setting;
    FALSE === $g_setting AND $g_setting = kv_cache_get('setting');
    empty($g_setting) AND $g_setting = array();
    return array_value($g_setting, $k, NULL);
}

// 全站的设置，全局变量 $g_setting = array();
function setting_set($k, $v)
{
    global $g_setting;
    FALSE === $g_setting AND $g_setting = kv_cache_get('setting');
    empty($g_setting) AND $g_setting = array();
    $g_setting[$k] = $v;
    return kv_cache_set('setting', $g_setting);
}

function setting_delete($k)
{
    global $g_setting;
    FALSE === $g_setting AND $g_setting = kv_cache_get('setting');
    empty($g_setting) AND $g_setting = array();
    if (isset($g_setting[$k])) unset($g_setting[$k]);
    kv_cache_set('setting', $g_setting);
    return TRUE;
}

// ------------> kv + cache + website
$g_website = FALSE;
function website_get($k)
{
    global $g_website;
    FALSE === $g_website AND $g_website = kv_cache_get('website');
    empty($g_website) AND $g_website = array();
    return array_value($g_website, $k, NULL);
}

// 全局变量 $g_website = array();
function website_set($k, $v)
{
    global $g_website;
    FALSE === $g_website AND $g_website = kv_cache_get('website');
    empty($g_website) AND $g_website = array();
    $g_website[$k] = $v;
    return kv_cache_set('website', $g_website);
}

function website_delete($k)
{
    global $g_website;
    FALSE === $g_website AND $g_website = kv_cache_get('website');
    empty($g_website) AND $g_website = array();
    if (isset($g_website[$k])) unset($g_website[$k]);
    kv_cache_set('website', $g_website);
    return TRUE;
}

?>