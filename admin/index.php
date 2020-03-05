<?php
/*
 * Copyright (C) www.wellcms.cn
 */
define('ADMIN_PATH', dirname(__FILE__) . '/'); // __DIR__
define('MESSAGE_HTM_PATH', ADMIN_PATH . 'view/htm/message.htm');
define('SKIP_ROUTE', TRUE);
$_SERVER['REQUEST_URI'] = str_replace('/admin', '', $_SERVER['REQUEST_URI']);

$admin_access = TRUE;
include '../index.php';

$lang += include _include(APP_PATH . "lang/$conf[lang]/lang_admin.php");
$_SERVER['lang'] = $lang;

include _include(ADMIN_PATH . 'admin.func.php');
$menu = include _include(ADMIN_PATH . 'menu.conf.php');
include _include(ADMIN_PATH . 'index.inc.php');

?>