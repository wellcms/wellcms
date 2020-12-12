<?php
define('SKIP_ROUTE', 1);
include './index.php';
include _include(APP_PATH . 'model/db_check.func.php');
set_time_limit(0);

$next = param('next', 0);

if (0 == $next) {

    // 数据表改名
    if (!db_find_table($db->tablepre . 'website_tag_thread_backup') && db_find_table($db->tablepre . 'website_tag_thread')) {
        db_exec("RENAME TABLE `{$db->tablepre}website_tag_thread` TO `{$db->tablepre}website_tag_thread_backup`;");
    } else {
        db_exec("TRUNCATE `{$db->tablepre}website_tag_thread`");
    }

    message(0, jump('Next', '?next=1', 1));

} elseif (1 == $next) {

    // 创建新表
    if (!db_find_table($db->tablepre . 'website_tag_thread')) {
        $sql = "CREATE TABLE `{$db->tablepre}website_tag_thread` (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`tagid` int(11) unsigned NOT NULL DEFAULT '0',
`tid` int(11) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
KEY `tid` (`tid`),
KEY `tagid_id` (`tagid`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        $r = db_exec($sql);
    }

    message(0, jump('Next', '?next=2', 1));

} elseif (2 == $next) {

    $page = param('page', 0);
    $page += 1;
    $pagesize = 10000;

    $count = db_count('website_tag_thread_backup', array());

    $total_page = ceil($count / $pagesize);

    // 转移数据
    $arrlist = db_find('website_tag_thread_backup', array(), array(), $page, $pagesize);

    if (!$arrlist) message(0, jump('Next', '?next=3', 1));

    $arr = array();
    foreach ($arrlist as $val) {
        $arr[] = array('tagid' => $val['tagid'], 'tid' => $val['tid']);
    }

    !empty($arr) and tag_thread_big_insert($arr);

    $page != $total_page and message(0, jump('共需执行 ' . $total_page . ' 次，重建数据 第' . $page . '次 请耐心等待', '?next=2&page=' . $page, 1));

    message(0, jump('Next……', '?next=3', 1));

} elseif (3 == $next) {

    $sql = "ALTER TABLE `{$db->tablepre}user` CHANGE `create_ip` `create_ip` DECIMAL(39,0) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时IP';";
    $r = db_exec($sql);

    $sql = "ALTER TABLE `{$db->tablepre}user` CHANGE `login_ip` `login_ip` DECIMAL(39,0) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时IP';";
    $r = db_exec($sql);

    $sql = "ALTER TABLE `{$db->tablepre}session` CHANGE `ip` `ip` DECIMAL(39,0) UNSIGNED NOT NULL DEFAULT '0';";
    $r = db_exec($sql);

    $sql = "ALTER TABLE `{$db->tablepre}website_comment` CHANGE `userip` `userip` DECIMAL(39,0) UNSIGNED NOT NULL DEFAULT '0';";
    $r = db_exec($sql);

    // 升级成功
    if (db_find_field($db->tablepre . 'user', 'rmbs')) {
        $sql = "ALTER TABLE `{$db->tablepre}user` CHANGE `rmbs` `money` DECIMAL(11,2) NOT NULL DEFAULT '0.00';";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_attach', 'pid')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_attach` ADD `pid` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `tid`";
        $r = db_exec($sql);
    }

    if (db_find_index($db->tablepre . 'website_attach', 'pid_2')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_attach` DROP INDEX `pid_2`";
        $r = db_exec($sql);
    }

    if (!db_find_index($db->tablepre . 'website_attach', 'pid')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_attach` ADD INDEX `pid` (`pid`)";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'website_attach', 'rmbs')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_attach` CHANGE `rmbs` `money` INT(11) NOT NULL DEFAULT '0';";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_comment', 'files')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_comment` ADD `files` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' AFTER `quotepid`";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_comment', 'images')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_comment` ADD `images` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' AFTER `quotepid`";
        $r = db_exec($sql);
    }

    $replace = array();
    $replace['version'] = '2.1.0';
    $replace['static_version'] = '?2.1.0';
    file_replace_var(APP_PATH . 'conf/conf.php', $replace);

    $config['name'] = 'WellCMS Oriental Lion';
    $config['version'] = '2.1.0';
    $config['official_version'] = '2.1.0';
    $config['version_date'] = '1607702400';
    $config['upgrade'] = 0;
    setting_set('conf', $config);

    $count = db_count('website_tag_thread', array());
    $_count = db_count('website_tag_thread_backup', array());
    $count != $count and message(0, 'tag_thread 表数据不正常，请重新执行升级文件');

    // 主题表升级放到后面 以免出错
    $sql = "ALTER TABLE `{$db->tablepre}website_thread` CHANGE `userip` `userip` DECIMAL(39,0) UNSIGNED NOT NULL DEFAULT '0';";
    $r = db_exec($sql);

    rmdir_recusive($conf['tmp_path'], 1);

    message(0, lang('update_successfully').'请测试 tag 主题列表是否正常，一切正常请手动删除 tag_thread_backup 表');
}

?>