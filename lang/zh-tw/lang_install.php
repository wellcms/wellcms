<?php

return array(
	'installed_tips' => '程序已經安裝過了，如需重新安裝，請刪除 conf/conf.php ！',
	'please_set_conf_file_writable' => '請設置 conf/conf.php 文件為可寫！',
	'evn_not_support_php_mysql' => '當前 PHP 環境不支持 mysql 和 pdo_mysql，無法繼續安裝。',
	'dbhost_is_empty' => '數據庫主機不能為空',
	'dbname_is_empty' => '數據庫名不能為空',
	'dbuser_is_empty' => '用戶名不能為空',
	'adminuser_is_empty' => '管理員用戶名不能為空',
	'adminpass_is_empty' => '管理員密碼不能為空',
	'conguralation_installed' => '恭喜，安裝成功！為了安全請刪除 install 目錄。',
	'step_1_title' => '安裝環境檢測',
	'runtime_env_check' => '網站運行環境檢測',
	'required' => '需要',
	'current' => '當前',
	'check_result' => '檢測結果',
	'passed' => '通過',
	'not_passed' => '通過',
	'not_the_best' => '不是最理想的環境',
	'dir_writable_check' => '目錄 / 文件 權限檢測',
	'writable' => '可寫',
	'unwritable' => '不可寫',
	'check_again' => '重新檢測',
	'os' => '操作系統',
	'unix_like' => '類 UNIX',
	'php_version' => 'PHP 版本',
	'step_2_title' => '數據庫設置',
	'db_type' => '數據庫類型',
	'db_engine' => '數據庫引擎',
	'db_host' => '數據庫服務器',
	'db_name' => '數據庫名',
	'db_user' => '數據庫用戶名',
	'db_pass' => '數據庫密碼',
    'db_table_pre' => '表前綴',
	'step_3_title' => '三、管理員信息',
	'admin_email' => '管理員郵箱',
	'admin_username' => '管理員用戶名',
	'admin_pw' => '管理員密碼',
	'admin_pw_repeat' => '重複密碼',
	'installing_about_moment' => '正在安裝，大概需要壹分鐘左右',
    'license_title' => 'WellCMS',
	'license_content' => '安全稳定，链路完整，性能强劲',
    'license_version' => 'Version ' . $conf['version'],
    'license_date' => $conf['license_date'],
	'agree_license' => '已閱讀並同意 WellCMS <a style="font-weight: bold;" target="_blank" href="http://www.wellcms.cn/read-118.html"><u>許可協議</u></a>',
	'agree_license_to_continue' => '繼續安裝',
	'install_title' => 'WellCMS ' . $conf['version'] . ' 安装向导',
	'install_guide' => '安裝向導',
	'function_check' => '函數依賴檢查',
	'supported' => '支持',
	'not_supported' => '不支持',
	'function_glob_not_exists' => '後臺插件功能依賴該函數，請配置 php.ini，設置 disabled_functions = ; 去除對該函數的限制',
	'function_gzcompress_not_exists' => '後臺插件功能依賴該函數，Linux 主機請添加編譯參數 --with-zlib，Windows 主機請配置 php.ini 註釋掉  extension=php_zlib.dll',
	'function_mb_substr_not_exists' => '系統依賴該函數，Linux 主機請添加編譯參數 --with-mbstring，Windows 主機請配置 php.ini 註釋掉 extension=php_mbstring.dll',
    'already_installed' => '請刪除install目錄裡的install.lock再繼續安裝',

    // hook lang_zh_tw_install.php
);

?>