<?php
define('DEBUG', 2);
define('APP_PATH', realpath(dirname(__FILE__) . '/../') . '/');
define('INSTALL_PATH', dirname(__FILE__) . '/');
define('MESSAGE_HTM_PATH', INSTALL_PATH . 'view/htm/message.htm');

// 切换到上一级目录，操作很方便
$conf = (include APP_PATH . 'conf/conf.default.php');
$conf['log_path'] = APP_PATH . $conf['log_path'];
$conf['tmp_path'] = APP_PATH . $conf['tmp_path'];

include APP_PATH . 'xiunophp/xiunophp.php';
include APP_PATH . 'model/misc.func.php';
include APP_PATH . 'model/plugin.func.php';
include APP_PATH . 'model/user.func.php';
include APP_PATH . 'model/form.func.php';

// 从 cookie 中获取数据，默认为中文
$_lang = param('lang', 'zh-cn');
$conf['lang'] = $_lang;
$lang = include APP_PATH . "lang/$conf[lang]/lang.php";
$lang += include APP_PATH . "lang/$conf[lang]/lang_install.php";
include INSTALL_PATH . 'install.func.php';
$_SERVER['lang'] = $lang;
$version = $conf['version'];

is_file(APP_PATH . 'install/install.lock') AND message(0, jump(lang('already_installed'), '../'));

$action = param('action');

// 安装初始化检测,放这里
is_file(APP_PATH . 'conf/conf.php') AND message(0, jump(lang('installed_tips'), '../'));

//exit(lang('license_content'));
// 第一步，阅读
if (empty($action)) {
    if ($method == 'GET') {
        $input = array();
        $input['lang'] = form_select('lang', array('zh-cn' => '简体中文', 'zh-tw' => '正體中文', 'en-us' => 'English'), $conf['lang'], TRUE, FALSE, TRUE);

        // 修改 conf.php
        include INSTALL_PATH . "view/htm/index.htm";
    } else {

        $_lang = param('lang');

        !in_array($_lang, array('zh-cn', 'zh-tw', 'en-us')) AND $_lang = 'zh-cn';
        setcookie('lang', $_lang);

        http_location('index.php?action=license');
    }

} elseif ($action == 'license') {

    // 设置到 cookie
    include INSTALL_PATH . "view/htm/license.htm";

} elseif ($action == 'env') {

    $agree = param('agree', 0);
    if (1 != $agree) http_location('index.php?action=license');

    if ($method == 'GET') {
        $succeed = 1;
        $env = $write = array();
        get_env($env, $write);
        include INSTALL_PATH . "view/htm/env.htm";
    }

} elseif ($action == 'db') {

    if ($method == 'GET') {

        $agree = param('agree', 0);
        if (1 != $agree) http_location('index.php?action=license');

        $succeed = 1;
        //$mysql_support = function_exists('mysql_connect');
        $pdo_mysql_support = extension_loaded('pdo_mysql');
        $myisam_support = extension_loaded('pdo_mysql');
        $innodb_support = extension_loaded('pdo_mysql');

        //(!$mysql_support && !$pdo_mysql_support) AND message(-1, lang('evn_not_support_php_mysql'));
        !$pdo_mysql_support AND message(-1, lang('evn_not_support_php_mysql'));

        include INSTALL_PATH . "view/htm/db.htm";

    } else {

        //$type = param('type', 'pdo_mysql');
        $type = 'pdo_mysql';
        $engine = param('engine');
        $host = param('host');
        $name = param('name');
        $user = param('user');
        $password = param('password');
        $tablepre = param('tablepre');
        $force = param('force');
        $adminemail = param('adminemail');
        $adminuser = param('adminuser');
        $adminpass = param('adminpass');
        $adminpassrepeat = param('adminpassrepeat');
        
        empty($host) AND message('host', lang('dbhost_is_empty'));
        empty($name) AND message('name', lang('dbname_is_empty'));
        empty($user) AND message('user', lang('dbuser_is_empty'));
        empty($adminpass) AND message('adminpass', lang('adminuser_is_empty'));
        $adminpassrepeat != $adminpass and message('adminpassrepeat', lang('password_incorrect'));
        
        empty($adminemail) AND message('adminemail', lang('adminpass_is_empty'));

        // 设置超时尽量短一些
        //set_time_limit(60);
        ini_set('mysql.connect_timeout', 5);
        ini_set('default_socket_timeout', 5);

        $conf['db']['type'] = $type;
        $conf['db']['mysql']['master']['host'] = $host;
        $conf['db']['mysql']['master']['name'] = $name;
        $conf['db']['mysql']['master']['user'] = $user;
        $conf['db']['mysql']['master']['password'] = $password;
        $conf['db']['mysql']['master']['tablepre'] = $tablepre;
        $conf['db']['mysql']['master']['engine'] = $engine;
        $conf['db']['pdo_mysql']['master']['host'] = $host;
        $conf['db']['pdo_mysql']['master']['name'] = $name;
        $conf['db']['pdo_mysql']['master']['user'] = $user;
        $conf['db']['pdo_mysql']['master']['password'] = $password;
        $conf['db']['pdo_mysql']['master']['tablepre'] = $tablepre;
        $conf['db']['pdo_mysql']['master']['engine'] = $engine;
        $pre = $_SERVER['HTTP_HOST'].'_';
        $conf['cache']['memcached']['cachepre'] = $pre;
        $conf['cache']['redis']['cachepre'] = $pre;
        $conf['cache']['xcache']['cachepre'] = $pre;
        $conf['cache']['yac']['cachepre'] = $pre;
        $conf['cache']['apc']['cachepre'] = $pre;
        $conf['cache']['mysql']['cachepre'] = $pre;
        $_SERVER['db'] = $db = db_new($conf['db']);

        // 此处可能报错
        $r = db_connect($db);
        if (FALSE === $r) {
            if (1049 == $errno || 1045 == $errno) {
                if ($type == 'mysql') {
                    mysql_query("CREATE DATABASE $name");
                    $r = db_connect($db);
                } elseif ('pdo_mysql' == $type) {
                    if (FALSE !== strpos(':', $host)) {
                        $arr = explode(':', $host);
                        $host = $arr[0];
                        $port = $arr[1];
                    } else {
                        $port = 3306;
                    }
                    try {
                        $attr = array(PDO::ATTR_TIMEOUT => 5,);
                        $link = new PDO("mysql:host=$host;port=$port", $user, $password, $attr);
                        $r = $link->exec("CREATE DATABASE `$name`");
                        if ($r === FALSE) {
                            $error = $link->errorInfo();
                            $errno = $error[1];
                            $errstr = $error[2];
                        }
                    } catch (PDOException $e) {
                        $errno = $e->getCode();
                        $errstr = $e->getMessage();
                    }
                }
            }
            if (FALSE === $r) {
                message(-1, "$errstr (errno: $errno)");
            }
        }

        // 初始化
        copy(APP_PATH . 'conf/conf.default.php', APP_PATH . 'conf/conf.php');

        $replace = array();
        $replace['db'] = $conf['db'];
        $replace['cache'] = $conf['cache'];
        $replace['cookie_pre'] = $tablepre;
        $rand = xn_rand(64);
        $replace['auth_key'] = $rand;
        $replace['installed'] = 1;
        $replace['lang'] = $_lang;
        file_replace_var(APP_PATH . 'conf/conf.php', $replace);

        $conf['cache']['mysql']['db'] = $db; // 这里直接传 $db，复用 $db；如果传配置文件，会产生新链接。
        $_SERVER['cache'] = $cache = !empty($conf['cache']) ? cache_new($conf['cache']) : NULL;

        // 设置引擎的类型
        if ($engine == 'innodb') {
            $db->innodb_first = TRUE;
        } else {
            $db->innodb_first = FALSE;
        }

        // 连接成功以后，开始建表，导数据。
        install_sql_file($tablepre, INSTALL_PATH . 'install.sql');

        // 管理员密码
        $salt = xn_rand(16);
        $password = md5(md5($adminpass) . $salt);
        $update = array('username' => $adminuser, 'email' => $adminemail, 'password' => $password, 'salt' => $salt, 'create_date' => $time, 'create_ip' => $longip);
        db_update('user', array('uid' => 1), $update);

        if (filter_var(ip(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {$post = array('type' => 1, 'sitename' => xn_urlencode(_SERVER('HTTP_HOST')), 'domain' => xn_urlencode(_SERVER('HTTP_HOST')), 'app_url' => '', 'siteid' => md5($rand . _SERVER('SERVER_ADDR')), 'version' => $version, 'version_date' => time());$json = https_request('http://www.wellcms.cn/version.html', $post, '', 500, 1);}

        xn_mkdir(APP_PATH . 'upload/tmp', 0777);
        xn_mkdir(APP_PATH . 'upload/website_attach', 0777);
        xn_mkdir(APP_PATH . 'upload/thumbnail', 0777);
        xn_mkdir(APP_PATH . 'upload/avatar', 0777);
        xn_mkdir(APP_PATH . 'view/template', 0777);

        file_put_contents(APP_PATH . 'install/install.lock', $version.'|'.date('Y-m-d H:i:s'));

        message(0, jump(lang('conguralation_installed'), '../'));
    }
}

?>