<?php

function get_env(&$env, &$write)
{
    $env['os']['name'] = lang('os');
    $env['os']['must'] = TRUE;
    $env['os']['current'] = PHP_OS;
    $env['os']['need'] = lang('unix_like');
    $env['os']['status'] = 1;

    $env['php_version']['name'] = lang('php_version');
    $env['php_version']['must'] = TRUE;
    $env['php_version']['current'] = PHP_VERSION;
    $env['php_version']['need'] = '5.0';
    $env['php_version']['status'] = version_compare(PHP_VERSION, '5') > 0;

    // 目录可写
    $writedir = array(
        '../conf/',
        '../log/',
        '../tmp/',
        '../upload/',
        '../plugin/',
        '../view/template/'
    );

    $write = array();
    foreach ($writedir as &$dir) {
        $write[$dir] = xn_is_writable('./' . $dir);
    }
}

function install_sql_file($tablepre, $sqlfile)
{
    global $errno, $errstr;
    $s = file_get_contents($sqlfile);
    $s = str_replace(";\r\n", ";\n", $s);
    $s = str_replace("`wellcms_", "`$tablepre", $s);
    $arr = explode(";\n", $s);
    foreach ($arr as $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;
        db_exec($sql) === FALSE AND message(-1, "sql: $sql, errno: $errno, errstr: $errstr");
    }
}

?>