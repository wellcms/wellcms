<?php
/*
 * Copyright (C) www.wellcms.cn
 */

/*
 * $mode = 0 自定义模式 1门户模式 2扁平模式
 * 1.先搜索绑定的ID模板文件，存在则区分电脑端/平板端/移动端模板，如果平板和移动端没有模板，则只加载电脑端；
 * 2.没有绑定ID，则加载当前风格默认模板；
 * 3.当前风格默认没有相对应的模板，则加载官方默认模板；
 * $type 区分页面
 * $id 需绑定的ID，如fid，tagid，flagid
 * $dir = 插件名，即目录 well_bbs
 * */
function theme_load($type = '', $id = 0, $dir = '')
{
    global $config;

    // 0:self-adaption 1:PC/Pad 2:PC/Pad/Mobile
    $tpl_mode = $config['setting']['tpl_mode'];

    isset($tpl_mode) || $tpl_mode = 0;

    // 0:pc 1:wechat 2:pad 3:mobile
    $detect = get_device();

    // 自动追加前缀 $pre适配端 $default_pre自适应或PC
    $pre = $default_pre = '';

    if ($tpl_mode && $detect) {
        if (2 == $tpl_mode && 2 == $detect) {
            $pre = 'pad.'; // 平板端 pad.list.htm
        } else {
            $pre = 'm.'; // 移动端 m.list.htm
        }
    }

    switch ($type) {
        case 'index':
            // 首页
            $pre .= $default_pre .= theme_mode_pre();
            break;
        case 'list':
            // 列表
            $pre .= $default_pre .= 'list.htm';
            break;
        case 'category':
            // 频道
            $pre .= $default_pre .= theme_mode_pre(2);
            break;
        case 'read':
            // 详情
            $pre .= $default_pre .= 'read.htm';
            break;
        case 'comment':
            // 高级评论
            $pre .= $default_pre .= 'comment.htm';
            break;
        case 'comment_list.inc':
            // 评论列表公用文件
            $pre .= $default_pre .= 'comment_list.inc.htm';
            break;
        case 'message':
            $pre .= $default_pre .= 'message.htm';
            break;
        case 'tag_list':
            // tag 分类列表
            $pre .= $default_pre .= 'tag_list.htm';
            break;
        case 'tag':
            // tag 主题列表
            $pre .= $default_pre .= 'tag.htm';
            break;
        case 'flag':
            // flag 主题列表
            $pre .= $default_pre .= 'flag.htm';
            break;
        case 'my':
            // my 个人中心
            $pre .= $default_pre .= 'my.htm';
            break;
        case 'my_password':
            // password 修改密码
            $pre .= $default_pre .= 'my_password.htm';
            break;
        case 'my_bind':
            // 绑定第三方登录
            $pre .= $default_pre .= 'my_bind.htm';
            break;
        case 'my_avatar':
            // 我的头像
            $pre .= $default_pre .= 'my_avatar.htm';
            break;
        case 'home_article':
            // 我的文章
            $pre .= $default_pre .= 'home_article.htm';
            break;
        case 'home_comment':
            // 我的评论
            $pre .= $default_pre .= 'home_comment.htm';
            break;
        case 'user':
            // user 用户中心
            $pre .= $default_pre .= 'user.htm';
            break;
        case 'user_login':
            // 登录
            $pre .= $default_pre .= 'user_login.htm';
            break;
        case 'user_create':
            // 注册
            $pre .= $default_pre .= 'user_create.htm';
            break;
        case 'user_resetpw':
            // 密码找回
            $pre .= $default_pre .= 'user_resetpw.htm';
            break;
        case 'user_resetpw_complete':
            // 重置密码
            $pre .= $default_pre .= 'user_resetpw_complete.htm';
            break;
        case 'user_comment':
            // 我的首页评论
            $pre .= $default_pre .= 'user_comment.htm';
            break;
        case 'single_page':
            // 单页
            $pre .= $default_pre .= 'single_page.htm';
            break;
        case 'search':
            // 搜索
            $pre .= $default_pre .= 'search.htm';
            break;
        case 'operate_sticky':
            // 置顶
            $pre .= $default_pre .= 'operate_sticky.htm';
            break;
        case 'operate_close':
            // 关闭
            $pre .= $default_pre .= 'operate_close.htm';
            break;
        case 'operate_delete':
            // 删除
            $pre .= $default_pre .= 'operate_delete.htm';
            break;
        case 'operate_move':
            // 移动
            $pre .= $default_pre .= 'operate_move.htm';
            break;
        case '404':
            $pre .= $default_pre .= '404.htm';
            break;
        case 'read_404':
            $pre .= $default_pre .= 'read_404.htm';
            break;
        case 'list_404':
            $pre .= $default_pre .= 'list_404.htm';
            break;
        // hook theme_load_case_end.php
        default:
            // 首页
            $pre .= $default_pre .= theme_mode_pre();
            // hook theme_load_case_default.php
            break;
    }

    if ($config['theme']) {
        $conffile = APP_PATH . 'view/template/' . $config['theme'] . '/conf.json';
        $json = is_file($conffile) ? xn_json_decode(file_get_contents($conffile)) : array();
    }

    // 加载绑定ID安装风格
    !empty($json['installed']) and $path_file = APP_PATH . 'view/template/' . $config['theme'] . '/htm/' . ($id ? $id . '_' : '') . $pre;

    // 加载安装风格
    (empty($path_file) || !is_file($path_file)) and $path_file = APP_PATH . 'view/template/' . $config['theme'] . '/htm/' . $pre;

    // 主风格下可安装多个子风格
    if (!empty($config['theme_child']) && is_array($config['theme_child'])) {
        foreach ($config['theme_child'] as $theme) {
            if (empty($theme) || is_array($theme)) continue;

            // 加载绑定ID安装风格
            $path_file = APP_PATH . 'view/template/' . $theme . '/htm/' . ($id ? $id . '_' : '') . $pre;

            // 加载安装风格
            !is_file($path_file) and $path_file = APP_PATH . 'view/template/' . $theme . '/htm/' . $pre;
        }
    }

    // 风格不存在加载适配端
    !is_file($path_file) and $path_file = APP_PATH . ($dir ? 'plugin/' . $dir . '/view/htm/' : 'view/htm/') . $default_pre;

    // hook theme_load_end.php

    return $path_file;
}

// 依据模式返回适配文件
function theme_mode_pre($type = 0)
{
    global $config;

    // 网站模式
    $mode = $config['setting']['website_mode'];
    $pre = '';

    // 首页文件前缀
    if (1 == $mode) {
        // 门户模式
        $pre .= 2 == $type ? 'portal_category.htm' : 'portal.htm';
    } elseif (2 == $mode) {
        // 扁平模式
        $pre .= 2 == $type ? 'flat_category.htm' : 'flat.htm';
    } else {
        // 自定义模式
        $pre .= 2 == $type ? 'index_category.htm' : 'index.htm';
    }
    return $pre;
}

?>