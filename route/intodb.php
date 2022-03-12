<?php
/*
 * Copyright (C) www.wellcms.cn
 * intodb-uid-password-fid.html
*/
!defined('DEBUG') and exit('Access Denied.');

$uid = param(1, 0);
$user = user_read_cache($uid);
if (empty($uid) || empty($user)) exit(lang('user_not_exists'));
$gid = $user['gid'];

// hook intodb_start.php

$password = param(2);
empty($password) and exit(lang('password_incorrect'));

$check = (md5(md5($password) . $user['salt']) == $user['password']);
empty($check) and exit(lang('password_incorrect'));

$_fid = param(3, 0);

// hook intodb_after.php

if ('GET' == $method) {

    // hook intodb_get_after.php

    // 返回CMS栏目数据(仅列表)
    $columnlist = category_list($forumlist);

    // hook intodb_get_after.php

    $s = '';
    foreach ($columnlist as $_forum) {
        $s .= "<option value=\"$_forum[fid]\">$_forum[name]</option>";
    }

    // hook intodb_get_end.php

    header('Content-Type:text/html;charset=utf-8');
    echo "<select class=\"custom-select mr-1 w-auto\" name=\"fid\">$s</select>";

} elseif ('POST' == $method) {

    FALSE === group_access($gid, 'managecreatethread') and exit(lang('user_group_insufficient_privilege'));

    // hook intodb_post_start.php

    // 统一更新主题数据
    $thread_update = array();
    // 统一更新用户数据
    $user_update = array();

    // hook intodb_post_forum_before.php

    $fid = param('fid', $_fid);
    $forum = array_value($forumlist, $fid);
    empty($forum) and exit(lang('forum_not_exists'));

    $subject = param('subject');
    $subject = filter_all_html($subject);
    empty($subject) and exit(lang('please_input_subject'));

    // 截取128个字符
    $subject = xn_substr($subject, 0, 128);
    // 过滤标题 关键词

    $link = param('link', 0);
    $type = $link ? 10 : 0;
    $closed = param('closed', 0);
    $setting = array_value($config, 'setting');
    $thumbnail = isset($_GET['thumbnail']) ? param('thumbnail', 0) : array_value($setting, 'thumbnail_on', 0);
    $save_image = isset($_GET['save_image']) ? param('save_image', 0) : array_value($setting, 'save_image_on', 0);
    $delete_pic = param('delete_pic', 0);
    $brief_auto = param('brief_auto', 0);
    $doctype = param('doctype', 0);
    $doctype > 10 and exit(lang('doc_type_not_supported'));

    // hook intodb_post_before.php

    $message = $_message = '';
    if (0 == $link) {
        $message = param('message', '', FALSE);
        $message = trim($message);

        // hook intodb_post_message.php

        empty($message) ? exit(lang('please_input_message')) : xn_strlen($message) > 2028000 and exit(lang('message_too_long'));

        $_message = htmlspecialchars(filter_all_html($message), ENT_QUOTES);
    }

    $brief = param('brief');
    if ($brief) {
        xn_strlen($brief) > 120 and $brief = xn_substr($brief, 0, 120);
    } else {
        $brief = ($brief_auto and $_message) ? xn_html_safe(xn_substr($_message, 0, 120)) : '';
    }
    $brief and $brief = filter_all_html($brief);

    // hook intodb_post_center.php

    $keyword = param('keyword');
    // 超出则截取
    xn_strlen($keyword) > 64 and $keyword = xn_substr($keyword, 0, 64);

    $description = param('description');
    // 超出则截取
    xn_strlen($description) > 120 and $description = xn_substr($description, 0, 120);

    $tags = param('tags', '', FALSE);
    $tags = xn_html_safe(filter_all_html($tags));

    // hook intodb_post_middle.php

    // 首页flag
    $flag_index = param('index');
    $flag_index_arr = explode(',', $flag_index);
    $flag_index_arr = array_filter($flag_index_arr);
    // 频道flag
    $flag_cate = param('category');
    $flag_cate_arr = explode(',', $flag_cate);
    $flag_cate_arr = array_filter($flag_cate_arr);
    // 栏目flag
    $flag_forum = param('forum');
    $flag_forum_arr = explode(',', $flag_forum);
    $flag_forum_arr = array_filter($flag_forum_arr);
    // 统计主题绑定flag数量
    $flags = count($flag_index_arr) + count($flag_cate_arr) + count($flag_forum_arr);

    // hook intodb_post_after.php

    $thread = array('fid' => $fid, 'type' => $type, 'doctype' => $doctype, 'subject' => $subject, 'brief' => $brief, 'keyword' => $keyword, 'description' => $description, 'closed' => $closed, 'flags' => $flags, 'thumbnail' => $thumbnail, 'save_image' => $save_image, 'delete_pic' => $delete_pic, 'message' => $message, 'time' => $time, 'longip' => $longip, 'gid' => $gid, 'uid' => $uid, 'conf' => $conf,);

    group_access($gid, 'publishverify') && 1 != $gid and $thread['status'] = 1;

    // hook intodb_post_thread_create.php

    $result = thread_create_handle($thread);
    FALSE === $result and message(-1, lang('create_thread_failed'));
    unset($thread);
    $tid = $result['tid'];
    $result['icon'] and $thread_update['icon'] = $result['icon'];
    $result['images'] and $thread_update['images'] = $result['images'];
    $result['files'] and $thread_update['files'] = $result['files'];

    !empty($result['user_update']) and $user_update += $result['user_update'];

    $tag_json = well_tag_post($tid, $fid, $tags);
    if (xn_strlen($tag_json) >= 120) {
        $s = xn_substr($tag_json, -1, NULL);
        if ('}' != $s) {
            $len = mb_strripos($tag_json, ',', 0, 'UTF-8');
            $tag_json = $len ? xn_substr($tag_json, 0, $len) . '}' : '';
        }
    }
    !empty($tag_json) and $thread_update['tag'] = $tag_json;

    // hook intodb_post_tag_after.php

    // 首页flag
    !empty($flag_index_arr) and FALSE === flag_create_thread(0, 1, $tid, $flag_index_arr) and exit(lang('create_failed'));

    // 频道flag
    $forum['fup'] and !empty($flag_cate_arr) and FALSE === flag_create_thread($forum['fup'], 2, $tid, $flag_cate_arr) and exit(lang('create_failed'));

    // 栏目flag
    !empty($flag_forum_arr) and FALSE === flag_create_thread($fid, 3, $tid, $flag_forum_arr) and exit(lang('create_failed'));

    // hook intodb_post_flag_after.php

    !empty($thread_update) && FALSE === well_thread_update($tid, $thread_update) and message(-1, lang('update_thread_failed'));

    !empty($user_update) && FALSE === user_update($uid, $user_update) and message(-1, lang('update_failed'));

    // hook intodb_post_end.php

    exit('success');
}

?>