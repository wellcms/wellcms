<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

if('download' == $action) {
	
	$type = param(2, 'chrome');
	if('chrome' == $type) {
		http_location('http://down.tech.sina.com.cn/download/d_load.php?d_id=40975&down_id=9&ip=8.8.8.8');
	} elseif('firefox' == $type) {
		http_location('http://download.firefox.com.cn/releases/stub/official/zh-CN/Firefox-latest.exe');
	} elseif('ie' == $type) {
		http_location('http://windows.microsoft.com/zh-cn/internet-explorer/ie-10-worldwide-languages/');
	}
	
} else {

	include './view/htm/browser.htm';
}

?>