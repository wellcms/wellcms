<?php
/*
 * Copyright (C) www.wellcms.cn
 */
return array(
    // hook admin_menu_conf_start.php
    'content' => array(
        'url' => url('content-list'),
        'text' => lang('manage_content'),
        'icon' => 'icon-pencil-square',
        'tab' => array(
            // hook admin_menu_conf_content_start.php
            'content' => array('url' => url('content-list'), 'text' => lang('content_list')),
            // hook admin_menu_conf_content_before.php
            'sticky' => array('url' => url('sticky-list'), 'text' => lang('sticky_list')),
            // hook admin_menu_conf_content_center.php
            'comment' => array('url' => url('comment-list'), 'text' => lang('comment_list')),
            // hook admin_menu_conf_content_after.php
            'page' => array('url' => url('page-list'), 'text' => lang('single__page')),
            /*审核主题插在此处*/
            // hook admin_menu_conf_content_end.php
        )
    ),
    // hook admin_menu_conf_column_before.php
    'forum' => array(
        'url' => url('column-list'),
        'text' => lang('manage_forum'),
        'icon' => 'icon-columns',
        'tab' => array(
            // hook admin_menu_conf_column_start.php
            'website' => array('url' => url('column-list'), 'text' => lang('website')),
            // hook admin_menu_conf_column_end.php
        )
    ),
    // hook admin_menu_conf_category_before.php
    'category' => array(
        'url' => url('column-list'),
        'text' => lang('manage_category'),
        'icon' => 'icon-sort-alpha-asc',
        'tab' => array(
            // hook admin_menu_conf_category_start.php
            'flag' => array('url' => url('flag-list'), 'text' => lang('customize')),
            // hook admin_menu_conf_category_end.php
        )
    ),
    // hook admin_menu_conf_user_before.php
    'user' => array(
        'url' => url('user-list'),
        'text' => lang('manage_user'),
        'icon' => 'icon-user',
        'tab' => array(
            // hook admin_menu_conf_user_start.php
            'list' => array('url' => url('user-list'), 'text' => lang('user_list')),
            // hook admin_menu_conf_user_center.php
            'group' => array('url' => url('group-list'), 'text' => lang('admin_user_group')),
            // hook admin_menu_conf_user_after.php
            'create' => array('url' => url('user-create'), 'text' => lang('admin_user_create')),
            // hook admin_menu_conf_user_end.php
        )
    ),
    // hook admin_menu_conf_plugin_before.php
    'plugin' => array(
        'url' => url('plugin'),
        'text' => lang('manage_warehouse'),
        'icon' => 'icon-cogs',
        'tab' => array(
            // hook admin_menu_conf_plugin_local_before.php
            'list' => array('url' => url('plugin-list'), 'text' => lang('local_plugin')),
            // hook admin_menu_conf_plugin_local_after.php
            'theme' => array('url' => url('plugin-theme'), 'text' => lang('local_theme')),
            // hook admin_menu_conf_theme_local_after.php
            'store' => array('url' => url('plugin-store'), 'text' => lang('official_store')),
            // hook admin_menu_conf_store_after.php
        )
    ),
    // hook admin_menu_conf_other_before.php
    'other' => array(
        'url' => url('other'),
        'text' => lang('other_function'),
        'icon' => 'icon-wrench',
        'tab' => array(
            // hook admin_menu_conf_other_map_before.php
            'map' => array('url' => url('other-map'), 'text' => lang('map')),
            // hook admin_menu_conf_other_increase_before.php
            'increase' => array('url' => url('other-increase'), 'text' => lang('increase_thread')),
            // hook admin_menu_conf_other_chain_before.php
            'link' => array('url' => url('other-link'), 'text' => lang('friends__link')),
            // hook admin_menu_conf_other_cache_before.php
            'cache' => array('url' => url('other-cache'), 'text' => lang('cache')),
            // hook admin_menu_conf_other_after.php
        )
    ),
    // hook admin_menu_conf_setting_before.php
    'setting' => array(
        'url' => url('setting-base'),
        'text' => lang('system_setting'),
        'icon' => 'icon-cog',
        'tab' => array(
            // hook admin_menu_conf_setting_base_before.php
            'setting-website' => array('url' => url('setting-website'), 'text' => lang('admin_site_setting')),
            // hook admin_menu_conf_setting_base_after.php
            'base' => array('url' => url('setting-base'), 'text' => lang('admin_setting_base')),
            // hook admin_menu_conf_setting_system_after.php
            'smtp' => array('url' => url('setting-smtp'), 'text' => lang('admin_setting_smtp')),
            // hook admin_menu_conf_setting_smtp_after.php
        )
    ),
    // hook admin_menu_conf_end.php
);

?>