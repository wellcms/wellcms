<?php
/*
 * Copyright (C) www.wellcms.cn
 */
define('SKIP_ROUTE', TRUE);
define('ADMIN_PATH', dirname(__FILE__) . '/'); // __DIR__
define('MESSAGE_HTM_PATH', ADMIN_PATH . 'view/htm/message.htm');
define('OFFICIAL_URL', 'http://www.wellcms.cn/');

$url_access = TRUE;
include '../index.php';

$lang += include _include(APP_PATH . "lang/$conf[lang]/lang_admin.php");
$_SERVER['lang'] = $lang;
$_REQUEST = array_merge($_COOKIE, $_POST, $_GET);

include _include(ADMIN_PATH . 'admin.func.php');
$menu = include _include(ADMIN_PATH . 'menu.conf.php');
include _include(ADMIN_PATH . 'index.inc.php');
?>