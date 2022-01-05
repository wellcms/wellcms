<?php
define('SKIP_ROUTE', 1);
include './index.php';
include _include(APP_PATH . 'model/db_check.func.php');
set_time_limit(0);

$next = param('next', 0);

if (0 == $next) {

    if (!db_find_field($db->tablepre . 'website_comment', 'attach_on')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_comment` ADD `attach_on` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `quotepid`";
        $r = db_exec($sql);
    }

    message(0, jump('Next', '?next=1', 1));

} elseif (1 == $next) {

    if (!db_find_field($db->tablepre . 'website_data', 'attach_on')) {
        $sql = "ALTER TABLE `{$db->tablepre}website_data` ADD `attach_on` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `doctype`";
        $r = db_exec($sql);
    }

    message(0, jump('Next', '?next=2', 1));

} elseif (2 == $next) {

    $replace = array();
    $replace['version'] = '2.2.0';
    $replace['static_version'] = '?2.2.0';
    file_replace_var(APP_PATH . 'conf/conf.php', $replace);

    $config['name'] = 'WellCMS Oriental Lion';
    $config['version'] = '2.2.0';
    $config['official_version'] = '2.2.0';
    $config['version_date'] = '1640966400';
    $config['upgrade'] = 0;
    setting_set('conf', $config);

    rmdir_recusive($conf['tmp_path'], 1);

    message(0, lang('update_successfully'));
}

?>