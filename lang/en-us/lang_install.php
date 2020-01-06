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

    'step_1_title' => '1. Environmental Check',
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

    'step_2_title' => '2. Database settings',
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
    'installing_about_moment' => 'Installing, it takes about a minute or so',
    'license_title' => 'WellCMS 2.0 License Agreement',
    'license_content' => 'Thank you for choosing WellCMS X 2.0.0, which is a mobile content management product with security, efficiency, stability, speed and load, especially in the case of large data volume. At the same time, a lot of optimizations have been done in SEO. Based on the excellent XiunoPHP development, the author once again enhanced the core, the purpose is to extract the full computing performance of the hardware.

The X in the WellCMS X version is taken from "Extreme", meaning to the extreme; meaning the ultimate; meaning free flying, requires a very light body; meaning to maximize the performance of the program.

WellCMS X 2.0.0 has only 22 tables, the source code is about 1M after compression, and it runs very fast, processing a single request at the 0.01 second level. Distributed server design, each table can create a separate DB server group and CACHE server (group), single table can withstand up to 100 million data, easy to deploy and maintain, is a very good cornerstone of secondary development.
	
WellCMS X (Content Management System) 2.0.0 using bootstrap 4 + jQuery 3 as a front-end library, full support for mobile browser; the back-end XiunoPHP support NoSQL way to operate a variety of databases, this version is a great leap forward.

WellCMS X 2.0.0 using the MIT agreement, you can freely modify, derived version, commercial without fear of any legal risks (the original copyright information should be retained after the modification).
	',
    'license_date' => 'Release date: January 6, 2020',
    'agree_license_to_continue' => 'Agree to continue to install the agreement',
    'install_title' => 'WellCMS X 2.0.0 Installation wizard',
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