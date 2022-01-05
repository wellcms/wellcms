<?php

// hook admin_func_start.php

// 有部分用户
define('XN_ADMIN_BIND_IP', array_value($conf, 'admin_bind_ip'));

function admin_token_check()
{
    global $longip, $time, $useragent, $conf;

    $useragent_md5 = md5($useragent);
    $key = md5((XN_ADMIN_BIND_IP ? $longip : '') . $useragent_md5 . xn_key());

    // hook admin_token_check_start.php

    $admin_token = param($conf['cookie_pre'] . 'admin_token');
    if (empty($admin_token)) {
        $_REQUEST[0] = 'index';
        $_REQUEST[1] = 'login';
    } else {
        $s = xn_decrypt($admin_token, $key);
        if (empty($s)) {
            setcookie($conf['cookie_pre'] . 'admin_token', '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
            message(-1, lang('admin_token_expiry'));
        }
        list($_ip, $_time) = explode("\t", $s);

        // 后台超过 3600 自动退出。
        // Background / more than 3600 automatic withdrawal.
        if ((XN_ADMIN_BIND_IP && $_ip != $longip || !XN_ADMIN_BIND_IP) && $time - $_time > 3600) {
            setcookie($conf['cookie_pre'] . 'admin_token', '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
            message(-1, lang('admin_token_expiry'));
            //jump(lang('admin_token_expiry'), url('index-login', '', TRUE), 1);
        }

        // 超过半小时，重新发新令牌，防止过期
        // More than half an hour, reset a new token, prevent expired
        $time - $_time > 1800 AND admin_token_set();
    }
    // hook admin_token_check_end.php
}

function admin_token_set()
{
    global $longip, $time, $useragent, $conf;
    $useragent_md5 = md5($useragent);
    $key = md5((XN_ADMIN_BIND_IP ? $longip : '') . $useragent_md5 . xn_key());
    // hook admin_token_set_start.php
    $s = "$longip	$time";
    $admin_token = xn_encrypt($s, $key);
    setcookie($conf['cookie_pre'] . 'admin_token', $admin_token, $time + 3600, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
    // hook admin_token_set_end.php
}

function admin_token_clean()
{
    global $time, $conf;
    setcookie($conf['cookie_pre'] . 'admin_token', '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
    // hook admin_token_clean_start.php
}

// bootstrap style
function admin_tab_active($arr, $active)
{
    // hook admin_tab_active_start.php
    $s = '';
    foreach ($arr as $k => $v) {
        $s .= '<a role="button" class="btn btn-secondary' . ($k == $active ? ' active' : '') . '" href="' . $v['url'] . '">' . $v['text'] . '</a>';
    }
    // hook admin_tab_active_end.php
    return $s;
}

// hook admin_func_end.php

?>