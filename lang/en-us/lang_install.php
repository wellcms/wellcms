<?php

return array(
    'installed_tips' => 'You have been installed, and if you need to re install, please delete the conf/conf.php!',
    'please_set_conf_file_writable' => 'Please set the conf/conf.php file to write!',
    'evn_not_support_php_mysql' => 'The current PHP environment does not support mysql and pdo_mysql driver, can not continue to install.',
    'dbhost_is_empty' => 'Database host cannot be empty',
    'dbname_is_empty' => 'Database name cannot be empty',
    'dbuser_is_empty' => 'User name cannot be empty',
    'adminuser_is_empty' => 'Administrator user name can not be empty',
    'adminpass_is_empty' => 'Administrator password can not be empty',
    'conguralation_installed' => 'Congratulations, installation success, please remove install directory for security.',
    'step_1_title' => 'Environmental Check',
    'runtime_env_check' => 'Runtime environment detection',
    'required' => 'Required',
    'current' => 'Current',
    'check_result' => 'Check Result',
    'passed' => 'Passed',
    'not_passed' => 'Not Passed',
    'not_the_best' => 'Not the ideal environment',
    'dir_writable_check' => 'Directory / file permissions',
    'writable' => 'Writable',
    'unwritable' => 'Unwritable',
    'check_again' => 'Check Again',
    'os' => 'OS',
    'unix_like' => 'UNIX Like',
    'php_version' => 'PHP Version',
    'step_2_title' => 'Database settings',
    'db_type' => 'Database type',
    'db_engine' => 'Database Engine',
    'db_host' => 'Database Host',
    'db_name' => 'Database Name',
    'db_user' => 'Database User',
    'db_pass' => 'Database Password',
    'db_table_pre' => 'Table pre',
    'step_3_title' => '3. Administrator information',
    'admin_email' => 'Administrator Email',
    'admin_username' => 'Administrator Username',
    'admin_pw' => 'Administrator Password',
    'admin_pw_repeat' => 'Repeat password',
    'installing_about_moment' => 'Installing, it takes about a minute or so',
    'license_title' => 'WellCMS 2.2 License Agreement',
    'license_content' => 'Thank you for choosing WellCMS 2.2, which is a mobile-oriented content management product. The front and back ends support various operations on the mobile terminal. WellCMS has the characteristics of safety, efficiency, stability, fast speed, and super load, especially under the large data volume, its excellence is even more outstanding.
<br><br>
WellCMS 2.2 is adaptive to mobile phones, tablets, and PCs. It can also be set to load templates on each end separately, and the URL remains unchanged. It has a very convenient plug-in and template development mechanism. Some pages at the front desk are equipped with APIs, which can return the data requested by AJAX through JSON, which is convenient for APP development.
<br><br>
WellCMS 2.2 has only 22 tables. After the source code is compressed, it is about 1M. It runs very fast. It handles a single request at the level of 0.01 seconds. The front-end and back-end code are separated, supporting various NoSQL operations, supporting the separation of the entire station\'s attachments, and supporting the separation of one master, multiple slaves, read and write. Distributed server design, each table can create a separate DB server group and CACHE server group, a single table can carry more than 100 million data, easy to deploy and maintain, is a very good cornerstone of secondary development.
<br><br>
Bootstrap 4 + JQuery 3 is used as the front-end class library, which fully supports mobile browsers; the back-end is based on PHP7 MySQL and supports various databases such as XCache/Yac/Redis/Memcached to operate various databases.
<br><br>
WellCMS 2.2 is released under the MIT agreement. You can freely modify, derive the version, and use it commercially without worrying about any legal risks (the original copyright information should be retained after the modification).',
    'license_date' => 'Release date: December 31, 2021',
    'agree_license_to_continue' => 'Agree to continue to install the agreement',
    'install_title' => 'WellCMS 2.2 Installation wizard',
    'install_guide' => 'Installation Wizard',

    'function_check' => 'Function dependency check',
    'supported' => 'Supported',
    'not_supported' => 'Not Supported',
    'function_glob_not_exists' => 'Plugin install dependent on it, please setting php.ini, set disabled_functions = ; Lifting restrictions on this function',
    'function_gzcompress_not_exists' => 'Plugin install dependent on it, on Linux server, add compile argument: --with-zlib, on Windows Server, please setting php.ini open extension=php_zlib.dll',
    'function_mb_substr_not_exists' => 'System dependent on it, on Linux server, add compile argument: --with-mbstring, on Windows Server, please setting php.ini open extension=php_mbstring.dll',
    'already_installed' => 'Please remove the install.lock in the installation directory and continue to install',

    // hook lang_en_us_install.php
);

?>