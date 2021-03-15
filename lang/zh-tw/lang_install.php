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
    'license_title' => 'WellCMS 2.0 版授權協議',
	'license_content' => '感謝您選擇 WellCMS 2.0，它是一款傾向移動端的內容管理產品，前後台支持移動端各種操作。 WellCMS 具有安全、高效、穩定、速度快、負載超強的特點，尤其在大數據量下，它的優秀才更顯卓越。
<br><br>
WellCMS 2.0 自適應手機、平板、PC，也可設置單獨加載各端模板，並且URL保持不變，有著非常方便的插件和模板開發機制。前台部分頁面配備API，可通過JSON返回AJAX請求的數據，方便 APP 開發。
<br><br>
WellCMS 2.0 只有 22 張表，源代碼壓縮後 1M 左右，運行速度非常快，處理單次請求在 0.01 秒級別。前後端代碼分離，支持各種 NoSQL 操作、支持整站附件分離，支持一主多從讀寫分離。分佈式服務器設計，每張表都可創建單獨的 DB 服務器群和 CACHE 服務器群，單表可承載億級以上數據，方便部署和維護，是一個二次開發非常好的基石。
<br><br>
採用 Bootstrap 4 + JQuery 3 作為前端類庫，全面支持移動端瀏覽器；後端基於 PHP7 MySQL，支持XCache/Yac/Redis/Memcached等 NoSQL 的方式操作各種數據庫。
<br><br>
WellCMS 2.0 採用 MIT 協議發布，您可以自由修改、派生版本、商用而不用擔心任何法律風險（修改後應保留原來的版權信息）。
<br><br>
wellcms主程序為免費提供使用，不提供任何形式的免費服務。使用者不得將本系統應用於任何形式的非法用途，由此產生的一切法律風險，需由使用者自行承擔，與本站和開發者無關。一旦下載、安裝、使用wellcms，表示您即承認您已閱讀、理解並同意受此條款的約束，並遵守所有相應法律和法規。如果您不同意此類條款，請不要使用本程序。
<br><br>
警告：按照我國法律，在未取得相關資源（包含但不限于影片、動畫、圖書、音樂等）授權的情況下，請勿傳播任何形式的相關資源（包含但不限於資源數據文件、種子文件、網盤文件、FTP 文件等）。',
	'license_date' => '發布時間：2020年1月6日',
	'agree_license_to_continue' => '同意協議繼續安裝',
	'install_title' => 'WellCMS 2.0 安装向导',
	'install_guide' => '安裝向導',
	'function_check' => '函數依賴檢查',
	'supported' => '支持',
	'not_supported' => '不支持',
	'function_glob_not_exists' => '後臺插件功能依賴該函數，請配置 php.ini，設置 disabled_functions = ; 去除對該函數的限制',
	'function_gzcompress_not_exists' => '後臺插件功能依賴該函數，Linux 主機請添加編譯參數 --with-zlib，Windows 主機請配置 php.ini 註釋掉  extension=php_zlib.dll',
	'function_mb_substr_not_exists' => '系統依賴該函數，Linux 主機請添加編譯參數 --with-mbstring，Windows 主機請配置 php.ini 註釋掉 extension=php_mbstring.dll',
    'already_installed' => '請刪除install目錄裡的install.lock再繼續安裝',

	// hook lang_zh_tw_admin.php
);

?>