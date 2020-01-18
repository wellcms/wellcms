<?php
/*
 * Copyright (C) www.wellcms.cn
 */
!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

include _include(APP_PATH . 'model/smtp.func.php');
$smtplist = smtp_init(APP_PATH . 'conf/smtp.conf.php');
// hook admin_setting_start.php

if ($action == 'base') {

    // hook admin_setting_base_get_post.php

    if ($method == 'GET') {

        // hook admin_setting_base_get_start.php

        $input = array();
        $input['sitename'] = form_text('sitename', $conf['sitename']);
        $input['sitebrief'] = form_textarea('sitebrief', $conf['sitebrief'], '', '100%', 100);
        $input['runlevel'] = form_radio('runlevel', array(0 => lang('runlevel_0'), 1 => lang('runlevel_1'), 2 => lang('runlevel_2'), 3 => lang('runlevel_3'), 4 => lang('runlevel_4'), 5 => lang('runlevel_5')), $conf['runlevel']);
        $input['user_create_on'] = form_radio_yes_no('user_create_on', $conf['user_create_on']);
        $input['user_create_email_on'] = form_radio_yes_no('user_create_email_on', $conf['user_create_email_on']);
        $input['user_resetpw_on'] = form_radio_yes_no('user_resetpw_on', $conf['user_resetpw_on']);
        $input['lang'] = form_select('lang', array('zh-cn' => lang('lang_zh_cn'), 'zh-tw' => lang('lang_zh_tw'), 'en-us' => lang('lang_en_us')), $conf['lang']);

        $header['title'] = lang('admin_setting_base');
        $header['mobile_title'] = lang('admin_setting_base');

        // hook admin_setting_base_get_end.php

        include _include(ADMIN_PATH . 'view/htm/setting_base.htm');

    } else {

        group_access($gid, 'managesetting') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

        $sitebrief = param('sitebrief', '', FALSE);
        $sitename = param('sitename', '', FALSE);
        $runlevel = param('runlevel', 0);
        $user_create_on = param('user_create_on', 0);
        $user_create_email_on = param('user_create_email_on', 0);
        $user_resetpw_on = param('user_resetpw_on', 0);

        $_lang = param('lang');

        // hook admin_setting_base_post_start.php

        $replace = array();
        $replace['sitename'] = xn_html_safe(filter_all_html($sitename));
        $replace['sitebrief'] = xn_html_safe($sitebrief);
        $replace['runlevel'] = $runlevel;
        $replace['user_create_on'] = $user_create_on;
        $replace['user_create_email_on'] = $user_create_email_on;
        $replace['user_resetpw_on'] = $user_resetpw_on;
        $replace['lang'] = $_lang;

        file_replace_var(APP_PATH . 'conf/conf.php', $replace);

        // hook admin_setting_base_post_end.php

        message(0, lang('modify_successfully'));
    }

} elseif ($action == 'smtp') {

    // hook admin_setting_smtp_get_post.php

    if ($method == 'GET') {

        // hook admin_setting_smtp_get_start.php

        $header['title'] = lang('admin_setting_smtp');
        $header['mobile_title'] = lang('admin_setting_smtp');

        $smtplist = smtp_find();
        $maxid = smtp_maxid();

        // hook admin_setting_smtp_get_end.php

        include _include(ADMIN_PATH . "view/htm/setting_smtp.htm");

    } else {

        group_access($gid, 'managesetting') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

        // hook admin_setting_smtp_post_start.php

        $email = param('email', array(''));
        $host = param('host', array(''));
        $port = param('port', array(0));
        $user = param('user', array(''));
        $pass = param('pass', array(''));

        $smtplist = array();
        foreach ($email as $k => $v) {
            $smtplist[$k] = array(
                'email' => $email[$k],
                'host' => $host[$k],
                'port' => $port[$k],
                'user' => $user[$k],
                'pass' => $pass[$k],
            );
        }
        $r = file_put_contents_try(APP_PATH . 'conf/smtp.conf.php', "<?php\r\nreturn " . var_export($smtplist, true) . ";\r\n?>");
        !$r AND message(-1, lang('conf/smtp.conf.php', array('file' => 'conf/smtp.conf.php')));

        // hook admin_setting_smtp_post_end.php

        message(0, lang('save_successfully'));
    }
} elseif ($action == 'website') {

    // hook admin_setting_website_start.php

    $setting = array_value($config, 'setting');

    if ($method == 'GET') {

        $header['title'] = lang('admin_site_setting');
        $header['mobile_title'] = lang('admin_site_setting');
        $header['mobile_link'] = url('system-setting');

        // hook admin_setting_website_get_start.php

        $input = array();
        $website_modearr = array('0' => lang('custom'), '1' => lang('portal'), '2' => lang('flat'));
        // hook admin_setting_website_get_before.php
        $input['website_mode'] = form_radio('website_mode', $website_modearr, array_value($setting, 'website_mode', 0));

        $tpl_modearr = array('0' => lang('mode_0'), '1' => lang('mode_1'), '2' => lang('mode_2'));
        // hook admin_setting_website_get_center.php
        $input['tpl_mode'] = form_radio('tpl_mode', $tpl_modearr, array_value($setting, 'tpl_mode', 0));
        $input['thumbnail_on'] = form_radio_yes_no('thumbnail_on', array_value($setting, 'thumbnail_on', 0));
        $input['save_image_on'] = form_radio_yes_no('save_image_on', array_value($setting, 'save_image_on', 0));

        // hook admin_setting_website_get_end.php

        include _include(ADMIN_PATH . 'view/htm/setting_website.htm');

    } elseif ($method == 'POST') {

        group_access($gid, 'managesetting') == FALSE AND message(1, lang('user_group_insufficient_privilege'));

        // hook admin_setting_website_post_start.php

        $website_mode = param('website_mode', 0);
        $tpl_mode = param('tpl_mode', 0);
        $thumbnail_on = param('thumbnail_on', 0);
        $save_image_on = param('save_image_on', 0);

        // hook admin_setting_website_post_before.php
        $setting['website_mode'] = $website_mode;
        $setting['tpl_mode'] = $tpl_mode;
        $setting['thumbnail_on'] = $thumbnail_on;
        $setting['save_image_on'] = $save_image_on;

        // hook admin_setting_website_post_center.php

        // 模式更改删除缓存
        array_value($setting, 'website_mode', 0) != $website_mode AND cache_delete('portal_index_thread');

        // hook admin_setting_website_post_middle.php

        $config['setting'] = $setting;

        // hook admin_setting_website_post_after.php

        setting_set('conf', $config);

        // hook admin_setting_website_post_end.php

        message(0, lang('modify_successfully'));
    }

}

// hook admin_setting_end.php

?>