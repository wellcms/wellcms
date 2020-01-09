<?php
define('SKIP_ROUTE', 1);
include './index.php';

$next = param('next', 0);

if ($next == 0) {
    // conf.php添加变量
    $confarr = array();
    $confarr['attach_on'] = 0;
    $confarr['cloud_url'] = '';
    $confarr['attach_delete'] = 0;
    $confarr['upload_quick'] = 0;
    $confarr['path'] = './';
    //$confarr['admin_path'] = '../';
    $confarr['listsize'] = 100;
    $confarr['comment_pagesize'] = 20;
    $confarr['linksize'] = 20;
    $confarr['cookie_lifetime'] = 8640000;
    $confarr['api_on'] = 0;
    $confarr['tagsize'] = 60;
    $confarr['logo_mobile_url'] = 'img/logo.png';
    $confarr['logo_pc_url'] = 'img/logo.png';
    $confarr['logo_water_url'] = 'img/water-small.png';
    $confarr['random_n'] = 1000;
    $confarr['static_version'] = '?2.0.05';
    file_replace_var(APP_PATH . 'conf/conf.php', $confarr);

    message(0, jump('conf update successfully', '?next=1', 1));

} elseif ($next == 1) {

    // 更改了缩略图文件夹名
    $path = APP_PATH . 'upload/website_mainpic';
    is_dir($path) AND rename($path, APP_PATH . 'upload/thumbnail');

    message(0, jump('update website_mainpic to thumbnail successfully', '?next=2', 1));

} elseif ($next == 2) {

    //升级数据表
    include _include(APP_PATH . 'model/db_check.func.php');

    if (db_find_index($db->tablepre . 'website_comment_pid', 'fid_pid')) {
        $sql = "ALTER TABLE {$db->tablepre}website_comment_pid DROP INDEX fid_pid;";
        $r = db_exec($sql);
    }

    if (!db_find_table($db->tablepre . 'website_link')) {
        $sql = "CREATE TABLE `{$db->tablepre}website_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0', # 排序
  `name` varchar(12) NOT NULL DEFAULT '',  # 网站名
  `url` varchar(120) NOT NULL DEFAULT '',  # URL
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 创建时间
  PRIMARY KEY (`id`),
  KEY `rank` (`rank`) # 排序
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        $r = db_exec($sql);
    }

    //$conf['version'] == '2.0.0' AND message(0, jump('database updated successfully', '?next=3', 1));

    if (db_find_field($db->tablepre . 'forum', 'well_thread_rank')) {
        // 删除字段
        $sql = "ALTER TABLE {$db->tablepre}forum DROP `well_thread_rank`;";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_list_news')) {
        // 删除字段
        $sql = "ALTER TABLE {$db->tablepre}forum DROP `well_list_news`;";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'list_news')) {
        // 删除字段
        $sql = "ALTER TABLE {$db->tablepre}forum DROP `list_news`;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'forum', 'tops')) {
        // threads后增加字段tops
        $sql = "ALTER TABLE  {$db->tablepre}forum ADD  `tops` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `threads`;";
        $r = db_exec($sql);
    }

    if (db_find_table($db->tablepre . 'website_post')) {
        // 修改表名
        $sql = "RENAME TABLE  {$db->tablepre}website_post TO  {$db->tablepre}website_comment;";
        $r = db_exec($sql);
    }

    if (db_find_table($db->tablepre . 'website_post_pid')) {
        // 修改表名
        $sql = "RENAME TABLE  {$db->tablepre}website_post_pid TO  {$db->tablepre}website_comment_pid;";
        $r = db_exec($sql);
    }

    if (db_find_table($db->tablepre . 'website_thread_top')) {
        // 修改表名置顶表thread_top
        $sql = "RENAME TABLE  `{$db->tablepre}website_thread_top` TO  `{$db->tablepre}website_thread_sticky` ;";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'website_thread_sticky', 'top')) {
        // 修改置顶表 字段
        $sql = "ALTER TABLE  `{$db->tablepre}website_thread_sticky` CHANGE  `top`  `sticky` TINYINT( 1 ) NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);
    }

    if (db_find_index($db->tablepre . 'website_thread_sticky', 'top')) {
        // 删除置顶表索引
        $sql = "ALTER TABLE {$db->tablepre}website_thread_sticky DROP INDEX top;";
        $r = db_exec($sql);
    }

    if (db_find_index($db->tablepre . 'website_thread_sticky', 'fid')) {
        // 删除置顶表索引
        $sql = "ALTER TABLE {$db->tablepre}website_thread_sticky DROP INDEX fid;";
        $r = db_exec($sql);
    }

    if (!db_find_index($db->tablepre . 'website_thread_sticky', 'sticky_tid')) {
        // 创建置顶表索引
        $sql = "ALTER TABLE  `{$db->tablepre}website_thread_sticky` ADD INDEX  `sticky_tid` (  `sticky` ,  `tid` );";
        $r = db_exec($sql);
    }

    if (!db_find_index($db->tablepre . 'website_thread_sticky', 'fid_sticky')) {
        // 创建置顶表索引
        $sql = "ALTER TABLE  `{$db->tablepre}website_thread_sticky` ADD INDEX  `fid_sticky` (  `fid` ,  `sticky` );";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_thread_sticky', 'create_date')) {
        // 置顶表增加字段 create_date
        $sql = "ALTER TABLE  `{$db->tablepre}website_thread_sticky` ADD  `create_date` INT( 11 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'website_thread', 'top')) {
        // 修改主题表字段top
        $sql = "ALTER TABLE  `{$db->tablepre}website_thread` CHANGE  `top`  `sticky` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    // 修改主键
    $sql = "ALTER TABLE  {$db->tablepre}website_comment_pid DROP PRIMARY KEY ,ADD PRIMARY KEY (  `pid` );";
    $r = db_exec($sql);

    if (!db_find_index($db->tablepre . 'website_comment_pid', 'tid_pid')) {
        // 创建索引
        $sql = "ALTER TABLE  {$db->tablepre}website_comment_pid ADD UNIQUE  `tid_pid` (  `tid` ,  `pid` );";
        $r = db_exec($sql);
    }

    if (db_find_index($db->tablepre . 'website_comment_pid', 'uid')) {
        // 改索引名
        $sql = "ALTER TABLE  {$db->tablepre}website_comment_pid DROP INDEX  `uid` ,ADD INDEX  `uid_pid` (  `uid` ,  `pid` );";
        $r = db_exec($sql);
    }

    if (db_find_index($db->tablepre . 'website_comment_pid', 'fid')) {
        // 改索引名
        $sql = "ALTER TABLE  {$db->tablepre}website_comment_pid DROP INDEX  `fid` ,ADD INDEX  `fid_pid` (  `fid` ,  `pid` );";
        $r = db_exec($sql);
    }

    if (db_find_table($db->tablepre . 'website_modelog')) {
        if (db_find_index($db->tablepre . 'website_modelog', 'uid')) {
            // 改索引名
            $sql = "ALTER TABLE  {$db->tablepre}website_modelog DROP INDEX  `uid` ,ADD INDEX  `uid_logid` (  `uid` ,  `logid` );";
            $r = db_exec($sql);
        }

        if (db_find_table($db->tablepre . 'website_modelog')) {
            // 数据表改名 well_website_modelog
            $sql = "RENAME TABLE  `{$db->tablepre}website_modelog` TO  `{$db->tablepre}website_operate` ;";
            $r = db_exec($sql);
        }
    }

    // 修改字段类型
    //$sql = "ALTER TABLE  {$db->tablepre}website_thread_top CHANGE  `top`  `top` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
    //$r = db_exec($sql);


    if (!db_find_field($db->tablepre . 'website_attach', 'image_url')) {
        // 附件表增加字段
        $sql = "ALTER TABLE  {$db->tablepre}website_attach ADD  `image_url` CHAR( 120 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `orgfilename`;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_attach', 'attach_on')) {
        $sql = "ALTER TABLE  {$db->tablepre}website_attach ADD  `attach_on` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_thread', 'image_url')) {
        // 主题表增加字段
        $sql = "ALTER TABLE  {$db->tablepre}website_thread ADD  `image_url` VARCHAR( 120 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_thread', 'attach_on')) {
        $sql = "ALTER TABLE  {$db->tablepre}website_thread ADD  `attach_on` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'website_thread_tid', 'rank')) {
        // 主题小表删除字段
        $sql = "ALTER TABLE {$db->tablepre}website_thread_tid DROP `rank`;";
        $r = db_exec($sql);
    }

    if (db_find_index($db->tablepre . 'website_thread_tid', 'fid_rank')) {
        // 主题小表删除索引
        $sql = "ALTER TABLE {$db->tablepre}website_thread_tid DROP INDEX fid_rank;";
        $r = db_exec($sql);
    }

##砍掉##主题小表增加字段 lastpid
#$sql = "ALTER TABLE  `{$db->tablepre}website_thread_tid` ADD  `lastpid` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `uid`;";
#$r = db_exec($sql);
##砍掉##主题小表增加索引 评论正序倒序 或 BBS 使用
#$sql = "ALTER TABLE  `{$db->tablepre}website_thread_tid` ADD INDEX  `fid_lastpid` (  `fid` ,  `lastpid` );";
#$r = db_exec($sql);

    $sql = "ALTER TABLE  {$db->tablepre}website_tag_thread CHANGE  `tagid`  `tagid` INT( 11 ) NOT NULL DEFAULT  '0';";
    $r = db_exec($sql);


    if (db_find_field($db->tablepre . 'forum', 'well_display')) {
        // 修改字段名 well_display
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_display`  `display` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_nav_display')) {
        // 修改字段名 well_nav_display
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_nav_display`  `nav_display` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_news')) {
        // 修改字段名 well_news
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_news`  `index_new` TINYINT( 3 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    // 修改字段名 well_list_news
    //$sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_list_news`  `list_new` TINYINT( 3 ) NOT NULL DEFAULT  '0';";
    //$r = db_exec($sql);

    if (db_find_field($db->tablepre . 'forum', 'well_channel_news')) {
        // 修改字段名 well_channel_news
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_channel_news`  `channel_new` TINYINT( 3 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_fup')) {
        // 修改字段名 well_fup
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_fup`  `fup` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_comment')) {
        // 修改字段名 well_comment
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_comment`  `comment` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_son')) {
        // 修改字段名 well_son
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_son`  `son` INT( 11 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_type')) {
        // 修改字段名 well_type
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_type`  `type` TINYINT( 1 ) NOT NULL DEFAULT  '1';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_model')) {
        // 修改字段名 well_model
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_model`  `model` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_forum_type')) {
        // 修改字段名 well_forum_type
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_forum_type`  `category` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'forum_type')) {
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `forum_type`  `category` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_pagesize')) {
        // 修改字段名 well_pagesize
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_pagesize`  `pagesize` TINYINT( 3 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_flag')) {
        // 修改字段名 well_flag
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_flag`  `flagstr` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'well_picture_size')) {
        // 修改字段名 well_picture_size
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `well_picture_size`  `thumbnail` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'seo_description')) {
        // 删除字段 seo_description
        $sql = "ALTER TABLE `{$db->tablepre}forum` DROP `seo_description`;";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'forum_type')) {
        // 修改forum表字段forum_type
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `forum_type`  `category` TINYINT( 1 ) NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'forum', 'flags')) {
        // forum表增加字段flags
        $sql = "ALTER TABLE  `{$db->tablepre}forum` ADD  `flags` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);
    }

    if (db_find_field($db->tablepre . 'forum', 'flag')) {
        // 修改forum表字段flag为flagstr
        $sql = "ALTER TABLE  `{$db->tablepre}forum` CHANGE  `flag`  `flagstr` CHAR( 120 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' ;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'website_thread', 'flags')) {
        // TODO 升级的时候需要统计flag绑定主题数量，更新到对应的主题
        // 主题表增加统计字段flags
        $sql = "ALTER TABLE  `{$db->tablepre}website_thread` ADD  `flags` TINYINT( 2 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);
    }

    // 增加单页
    if (!db_find_table($db->tablepre . 'website_page')) {
        $sql = "CREATE TABLE `{$db->tablepre}website_page` (
  `tid` int(11) unsigned NOT NULL AUTO_INCREMENT, # 主题tid
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0', # 主题排序
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 版块fid
  PRIMARY KEY (`tid`),
  KEY `fid_rank` (`fid`,`rank`) # 主题排序
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'intoadmin')) {
        // 增加允许进后台
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `intoadmin` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员进后台权限
        $sql = "UPDATE `{$db->tablepre}group` SET `intoadmin` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managesetting')) {
        // 增加允许系统设置
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managesetting` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员系统设置
        $sql = "UPDATE `{$db->tablepre}group` SET `managesetting` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managecontent')) {
        // 增加允许管理内容
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecontent` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理内容
        $sql = "UPDATE `{$db->tablepre}group` SET `managecontent` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageforum')) {
        // 增加允许管理版块
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageforum` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理版块
        $sql = "UPDATE `{$db->tablepre}group` SET `manageforum` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managecategory')) {
        // 增加允许管理分类
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecategory` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理分类
        $sql = "UPDATE `{$db->tablepre}group` SET `managecategory` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageuser')) {
        // 增加允许管理用户
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageuser` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理用户
        $sql = "UPDATE `{$db->tablepre}group` SET `manageuser` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managecreateuser')) {
        // 增加允许后台管理创建用户
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecreateuser` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理创建用户
        $sql = "UPDATE `{$db->tablepre}group` SET `managecreateuser` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageupdateuser')) {
        // 增加允许后台管理编辑用户
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageupdateuser` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理编辑用户
        $sql = "UPDATE `{$db->tablepre}group` SET `manageupdateuser` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managedeleteuser')) {
        // 增加允许后台管理删除用户
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managedeleteuser` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理删除用户
        $sql = "UPDATE `{$db->tablepre}group` SET `managedeleteuser` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managegroup')) {
        // 增加允许后台管理用户组
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managegroup` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理用户组
        $sql = "UPDATE `{$db->tablepre}group` SET `managegroup` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managecreategroup')) {
        // 增加允许后台管理创建用户组
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecreategroup` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理创建用户组
        $sql = "UPDATE `{$db->tablepre}group` SET `managecreategroup` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageupdategroup')) {
        // 增加允许后台管理编辑用户组
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageupdategroup` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理编辑用户组
        $sql = "UPDATE `{$db->tablepre}group` SET `manageupdategroup` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managedeletegroup')) {
        // 增加允许后台管理删除用户组
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managedeletegroup` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理删除用户组
        $sql = "UPDATE `{$db->tablepre}group` SET `managedeletegroup` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageplugin')) {
        // 增加允许后台管理插件
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageplugin` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理插件
        $sql = "UPDATE `{$db->tablepre}group` SET `manageplugin` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageother')) {
        // 增加允许管理用户
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageother` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许管理用户
        $sql = "UPDATE `{$db->tablepre}group` SET `manageother` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managecreatethread')) {
        // 增加允许后台创建内容
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecreatethread` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员后台创建内容
        $sql = "UPDATE `{$db->tablepre}group` SET `managecreatethread` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'manageupdatethread')) {
        // 增加允许后台编辑内容
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageupdatethread` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许后台编辑内容
        $sql = "UPDATE `{$db->tablepre}group` SET `manageupdatethread` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managedeletethread')) {
        // 增加允许后台删除内容
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managedeletethread` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许后台删除内容
        $sql = "UPDATE `{$db->tablepre}group` SET `managedeletethread` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managesticky')) {
        // 增加允许后台管理置顶
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managesticky` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许后台管理置顶
        $sql = "UPDATE `{$db->tablepre}group` SET `managesticky` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managecomment')) {
        // 增加允许后台管理评论
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecomment` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许后台管理评论
        $sql = "UPDATE `{$db->tablepre}group` SET `managecomment` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'managepage')) {
        // 增加允许后台管理单页
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managepage` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加管理员允许后台管理单页
        $sql = "UPDATE `{$db->tablepre}group` SET `managepage` = '1' WHERE `gid` =1;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'allowuserdelete')) {
        // 增加允许用户删除主题
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `allowuserdelete` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
        $r = db_exec($sql);

        // 增加允许用户删除主题权限
        $sql = "UPDATE `{$db->tablepre}group` SET `allowuserdelete` = '1' WHERE `gid` in(1,2,3,4,5,101,102,103,104,105);";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'user', 'articles')) {
        // user表增加字段articles
        $sql = "ALTER TABLE `{$db->tablepre}user` ADD  `articles` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'user', 'comments')) {
        // user表增加字段comments
        $sql = "ALTER TABLE `{$db->tablepre}user` ADD  `comments` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'forum', 'publish')) {
        // forum表增加投稿字段
        $sql = "ALTER TABLE  `{$db->tablepre}forum` ADD  `publish` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'allowpublish')) {
        // group增加允许投稿字段
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `allowpublish` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);

        // 增加管理员允许投稿
        $sql = "UPDATE `{$db->tablepre}group` SET `allowpublish` = '1' WHERE `gid` in(1,2,3,4,5);";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'publishverify')) {
        // group增加允许投稿审核字段
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `publishverify` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);

        // 普通用户增加投稿审核权限
        $sql = "UPDATE `{$db->tablepre}group` SET `publishverify` = '1' WHERE `gid` in(0,5,6,7,101,102,103,104,105);";
        $r = db_exec($sql);
    }

    if (!db_find_field($db->tablepre . 'group', 'commentverify')) {
        // group增加评论审核字段
        $sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `commentverify` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' ;";
        $r = db_exec($sql);

        // 普通用户增加评论审核权限
        $sql = "UPDATE `{$db->tablepre}group` SET `commentverify` = '1' WHERE `gid` in(0,6,7);";
        $r = db_exec($sql);
    }

    message(0, jump('database updated successfully', '?next=3', 1));

} elseif ($next == 3) {

    include _include(APP_PATH . 'model/db_check.func.php');
    // 统计主题数更新
    $count = user_count();
    if ($count) {
        $_count = ceil($count / 100);
        for ($i = 0; $i <= $_count; ++$i) {
            $userlist = user_find(array(), array(), $i, 100);
            foreach ($userlist as $val) {

                $threads = 0;
                if (db_find_table($db->tablepre . 'mythread')) {
                    $threads = db_count('mythread', array('uid' => $val['uid']));
                    $threads AND $arr['threads'] = $threads;
                    $posts = db_count('post', array('uid' => $val['uid']));
                    $posts AND $arr['posts'] = $posts;
                }

                //$articles = thread_uid_count($val['uid']);
                $comments = comment_pid_count_by_uid($val['uid']);
                $arr = array('articles' => ($threads ? ($val['threads'] - $threads) : $val['threads']), 'comments' => $comments);

                user_update($val['uid'], $arr);
            }
        }
    }

    message(0, jump('user update successfully', '?next=4', 2));

} elseif ($next == 4) {

    include _include(APP_PATH . 'model/db_check.func.php');
    // 没安装BBS删除字段
    if (!db_find_table($db->tablepre . 'thread')) {
        $sql = "ALTER TABLE `{$db->tablepre}user` DROP `threads`, DROP `posts`;";
        $r = db_exec($sql);
    }

    message(0, jump('BBS update successfully', '?next=5', 1));

} elseif ($next == 5) {

    // 增加全局置顶统计
    $toplist = sticky_thread__find(array(), array('tid' => -1), 1, 1000);

    $index_stickys = 0;
    $fidarr = array();
    $tidarr = array();
    if (!empty($toplist)) {

        foreach ($toplist as $val) {
            $tidarr[] = $val['tid'];
        }

        $_tidarr = array();
        $threadlist = well_thread__find(array('tid' => $tidarr), array('tid' => 1), 1, count($tidarr));
        foreach ($threadlist as $_thread) {
            $_thread['sticky'] == 3 AND $index_stickys += 1; // 全站置顶
            $_thread['sticky'] > 0 AND $fidarr[$_thread['tid']] = $_thread['fid'];
            $_thread['sticky'] == 0 AND $_tidarr[] = $_thread['tid'];
        }

        // 删除脏数据
        !empty($_tidarr) AND sticky_thread__delete($_tidarr);

        cache_delete('sticky_thread_list');
    }

    $arr = setting_get('well_website_conf');
    empty($arr) AND $arr = setting_get('conf');
    $arr['theme'] = isset($arr['style']) ? $arr['style'] : (isset($arr['theme']) ? $arr['theme'] : ''); // 模板切换
    unset($arr['style']);
    $arr['index_stickys'] = $index_stickys; // 统计全局置顶更新
    $arr['version'] = '2.0.0';
    $arr['official_version'] = '2.0.0'; // 官方版本
    $arr['last_version'] = 1578294915; // 最后获取版本时间
    $arr['version_date'] = 1578294915; // 版本时间戳
    $arr['upgrade'] = 0; // 0无更新 1有更新
    $arr['index_flags'] = empty($arr['index_flags']) ? 0 : $arr['index_flags']; // 首页flag统计
    $arr['index_flagstr'] = empty($arr['index_flagstr']) ? '' : $arr['index_flagstr']; // 首页显示的flag字串1,2,3
    $arr['setting']['thumbnail_on'] = 1; // 生成主图
    $arr['setting']['save_image_on'] = 1; // 图片本地化
    setting_set('conf', $arr);
    setting_delete('well_website_conf');

    if (!empty($fidarr)) {
        $fidarr = array_count_values($fidarr);
        foreach ($fidarr as $_fid => $n) {
            forum_update($_fid, array('tops' => $n));
        }
    }

    message(0, jump('cache update successfully', '?next=6', 1));

} elseif ($next == 6) {

    // 修改主题缩略图名
    function update_thread_thumbnail($page, $pagesize)
    {
        global $conf;
        $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');
        $arrlist = well_thread__find(array(), array(), $page, $pagesize);
        foreach ($arrlist as $val) {
            if ($val['icon']) {
                $day = date($attach_dir_save_rule, $val['icon']);
                $path = $conf['upload_path'] . 'thumbnail/' . $day;
                $destfile = $path . '/' . $val['tid'] . '.jpeg';
                if (is_file($destfile)) {
                    $new_file = $path . '/' . $val['uid'] . '_' . $val['tid'] . '_' . $val['icon'] . '.jpeg';
                    rename($destfile, $new_file);
                }
            }
        }
    }

    $n = well_thread_count();

    if ($n < 1000) {
        $count = ceil($n / 100);
        for ($i = 0; $i <= $count; ++$i) {
            update_thread_thumbnail($i, 100);
        }
    } else {

        $page = param('page', 0);
        $page += 1;

        update_thread_thumbnail($page, 500);

        $count = ceil($n / 500);
        $page != $count AND message(0, jump('升级缩略图 第' . $page . '次 请耐心等待，程序自动刷新页面', '?next=6&page=' . $page, 2));
    }

    message(0, jump('thumbnail update successfully', '?next=7', 2));

} elseif ($next == 7) {

    // 统计版块flag数量，写入forum
    $arrlist = flag__find(array(), array(), 1, 1000);
    $arr = array();
    $index = array();
    $indexflags = '';
    foreach ($arrlist as $val) {
        if ($val['fid']) {
            isset($arr[$val['fid']]) ? $arr[$val['fid']] += 1 : $arr[$val['fid']] = 1;
        } else {
            isset($index[$val['fid']]) ? $index[$val['fid']] += 1 : $index[$val['fid']] = 1;
            if ($val['display']) $indexflags .= $val['flagid'] . ',';
        }
    }

    foreach ($arr as $_fid => $n) {
        forum__update($_fid, array('flags' => $n));
    }

    $arr = setting_get('conf');
    $arr['index_flags'] = empty($index[0]) ? 0 : $index[0];
    $arr['index_flagstr'] = empty($indexflags) ? '' : trim($indexflags, ',');
    setting_set('conf', $arr);

    message(0, jump('flag update successfully', '?next=8', 2));

} elseif ($next == 8) {

    // 更新频道子栏目数量
    $fup = array();
    foreach ($forumlist as $_forum) {
        if ($_forum['fup']) {
            isset($fup[$_forum['fup']]) ? $fup[$_forum['fup']] += 1 : $fup[$_forum['fup']] = 1;
        }
    }

    if (!empty($fup)) {
        foreach ($fup as $_fid => $n) {
            forum_update($_fid, array('son' => $n));
        }
    }

    message(0, jump('forum update successfully', '?next=9', 1));

} elseif ($next == 9) {

    // 更新主题 flags绑定数量
    function update_thread_flas($page, $pagesize)
    {
        $arrlist = flag_thread__find(array(), array(), $page, $pagesize);
        if ($arrlist) {
            $arr = array();
            foreach ($arrlist as $val) {
                isset($arr[$val['tid']]) ? $arr[$val['tid']] += 1 : $arr[$val['tid']] = 1;
            }

            if (empty($arr)) return;

            foreach ($arr as $tid => $n) {
                well_thread__update($tid, array('flags+' => $n));
            }
        }
    }

    $flag_count = flag__count();
    $n = flag_thread__count();

    //$n = well_thread_count();
    if ($flag_count && $n) {

        $page = param('page', 0);
        $page += 1;

        $page == 1 AND db_exec("UPDATE `{$db->tablepre}website_thread` SET flags=0");

        if ($n < 1000) {
            $count = ceil($n / 200);
            for ($i = 0; $i <= $count; ++$i) {
                update_thread_flas($i, 200);
            }
        } else {

            update_thread_flas($page, 200);

            $count = ceil($n / 200);
            $page != $count AND message(0, jump('升级主题数据 第' . $page . '次 请耐心等待，程序自动刷新页面', '?next=9&page=' . $page, 2));
        }
    }

    message(0, jump('thread flags update successfully', '?next=10', 1));

} elseif ($next == 10) {

    cache_truncate();
    $g_website = kv_cache_get('website');
    $g_website['forumlist'] = '';
    $g_website['flag'] = '';
    kv_cache_set('website', $g_website);
    rmdir_recusive($conf['tmp_path'], 1);

    unlink(APP_PATH . 'upgrade.php');

    message(0, 'upgrade successfully');
}

?>