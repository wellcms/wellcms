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
function theme_load($type = 0, $id = 0, $dir = '')
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
        if ($tpl_mode == 2 && $detect == 2) {
            $pre = 'pad.'; // 平板端 pad.list.htm
        } else {
            $pre = 'm.'; // 移动端 m.list.htm
        }
    }

    switch ($type) {
        case '0':
            // 首页
            $pre .= $default_pre .= theme_mode_pre();
            break;
        case '1':
            // 列表
            $pre .= $default_pre .= 'list.htm';
            break;
        case '2':
            // 频道
            $pre .= $default_pre .= theme_mode_pre(2);
            break;
        case '3':
            // 详情
            $pre .= $default_pre .= 'read.htm';
            break;
        case '4':
            // tag 分类列表
            $pre .= $default_pre .= 'tag_list.htm';
            break;
        case '5':
            // tag 主题列表
            $pre .= $default_pre .= 'tag.htm';
            break;
        case '6':
            // flag 主题列表
            $pre .= $default_pre .= 'flag.htm';
            break;
        case '7':
            // my 个人中心
            $pre .= $default_pre .= 'my.htm';
            break;
        case '8':
            // password 修改密码
            $pre .= $default_pre .= 'my_password.htm';
            break;
        case '9':
            // user 用户中心
            $pre .= $default_pre .= 'user.htm';
            break;
        case '10':
            // 登录
            $pre .= $default_pre .= 'user_login.htm';
            break;
        case '11':
            // 注册
            $pre .= $default_pre .= 'user_create.htm';
            break;
        case '12':
            // 密码找回
            $pre .= $default_pre .= 'user_resetpw.htm';
            break;
        case '13':
            // 重置密码
            $pre .= $default_pre .= 'user_resetpw_complete';
            break;
        case '14':
            // 我的首页
            $pre .= $default_pre .= 'home.htm';
            break;
        case '15':
            // 单页
            $pre .= $default_pre .= 'single_page.htm';
            break;
        case '16':
            // 我的首页评论
            $pre .= $default_pre .= 'home_comment.htm';
            break;
        case '17':
            // 我的首页评论
            $pre .= $default_pre .= 'user_comment.htm';
            break;
        case '18':
            // 搜索
            $pre .= $default_pre .= 'search.htm';
            break;
        case '19':
            // 我的头像
            $pre .= $default_pre .= 'my_avatar.htm';
            break;
        // hook theme_load_case_end.php
        default:
            // 首页
            $pre .= $default_pre .= theme_mode_pre();
            // hook theme_load_case_default.php
            break;
    }

    if ($config['theme']) {
        $conffile = 'view/template/' . $config['theme'] . '/conf.json';
        $json = is_file($conffile) ? xn_json_decode(file_get_contents($conffile)) : array();
    }

    // 加载安装风格
    !empty($json['installed']) AND $path_file = APP_PATH . 'view/template/' . $config['theme'] . '/htm/' . ($id ? $id . '_' : '') . $pre;

    // 风格不存在加载适配端
    (empty($path_file) || !is_file($path_file)) AND $path_file = APP_PATH . ($dir ? 'plugin/' . $dir . '/view/htm/' : 'view/htm/') . $default_pre;

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
    if ($mode == 1) {
        // 门户模式
        $pre .= $type == 2 ? 'portal_category.htm' : 'portal.htm';
    } elseif ($mode == 2) {
        // 扁平模式
        $pre .= $type == 2 ? 'flat_category.htm' : 'flat.htm';
    } else {
        // 自定义模式
        $pre .= $type == 2 ? 'index_category.htm' : 'index.htm';
    }
    return $pre;
}

?>