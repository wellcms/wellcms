<?php
/*
 * Copyright (C) www.wellcms.cn
 * intodb-uid-password-fid.html
*/
!defined('DEBUG') AND exit('Access Denied.');

$uid = param(1, 0);
$user = user_read_cache($uid);
if (empty($uid) || empty($user)) exit(lang('user_not_exists'));
$gid = $user['gid'];

$password = param(2);
empty($password) AND exit(lang('password_incorrect'));

$check = (md5(md5($password) . $user['salt']) == $user['password']);
empty($check) AND exit(lang('password_incorrect'));

$_fid = param(3, 0);

if ('GET' == $method) {

    // 返回CMS栏目数据(仅列表)
    $columnlist = category_list($forumlist);

    $s = '';
    foreach ($columnlist as $_forum) {
        $s .= "<option value=\"$_forum[fid]\">$_forum[name]</option>";
    }

    header('Content-Type:text/html;charset=utf-8');
    echo "<select class=\"custom-select mr-1 w-auto\" name=\"fid\">$s</select>";

} elseif ('POST' == $method) {

    $fid = param('fid', $_fid);
    $forum = array_value($forumlist, $fid);
    empty($forum) AND exit(lang('forum_not_exists'));

    $r = forum_access_user($fid, $user['gid'], 'allowthread');
    empty($r) AND exit(lang('user_group_insufficient_privilege'));

    $subject = param('subject');
    $subject = filter_all_html($subject);
    empty($subject) AND exit(lang('please_input_subject'));

    // 截取128个字符
    $subject = xn_substr($subject, 0, 128);
    // 过滤标题 关键词

    $link = param('link', 0);
    $type = $link ? 10 : 0;
    $closed = param('closed', 0);
    $setting = array_value($config, 'setting');
    $thumbnail = array_value($setting, 'thumbnail_on', 0);
    $save_image = array_value($setting, 'save_image_on', 0);
    $delete_pic = param('delete_pic', 0);
    $brief_auto = param('brief_auto', 0);
    $doctype = param('doctype', 0);
    $doctype > 10 AND exit(lang('doc_type_not_supported'));

    $message = $_message = '';
    if (0 == $link) {
        $message = param('message', '', FALSE);
        $message = trim($message);
        empty($message) ? exit(lang('please_input_message')) : xn_strlen($message) > 2028000 AND exit(lang('message_too_long'));
        $_message = filter_all_html($message);
    }

    $brief = param('brief');
    if ($brief) {
        xn_strlen($brief) > 120 AND $brief = xn_substr($brief, 0, 120);
    } else {
        $brief = ($brief_auto AND $_message) ? xn_html_safe(xn_substr($_message, 0, 120)) : '';
    }

    $keyword = param('keyword');
    // 超出则截取
    xn_strlen($keyword) > 64 AND $keyword = xn_substr($keyword, 0, 64);

    $description = param('description');
    // 超出则截取
    xn_strlen($description) > 120 AND $description = xn_substr($description, 0, 120);

    $tags = param('tags', '', FALSE);
    $tags = xn_html_safe(filter_all_html($tags));

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

    $thread = array('fid' => $fid, 'type' => $type, 'doctype' => $doctype, 'subject' => $subject, 'brief' => $brief, 'keyword' => $keyword, 'description' => $description, 'closed' => $closed, 'flags' => $flags, 'thumbnail' => $thumbnail, 'save_image' => $save_image, 'delete_pic' => $delete_pic, 'message' => $message);

    $tid = well_thread_create($thread);
    FALSE === $tid AND exit(lang('create_failed'));
    unset($thread);

    $tag_json = well_tag_post($tid, $fid, $tags);
    if (xn_strlen($subject) >= 120) {
        $s = xn_substr($tag_json, -1, NULL);
        if ('}' != $s) {
            $len = mb_strripos($tag_json, ',', 0, 'UTF-8');
            $tag_json = $len ? xn_substr($tag_json, 0, $len) . '}' : '';
        }
    }
    $tag_json AND well_thread_update($tid, array('tag' => $tag_json));

    // 首页flag
    !empty($flag_index_arr) AND FALSE === flag_create_thread(0, 1, $tid, $flag_index_arr) AND exit(lang('create_failed'));

    // 频道flag
    $forum['fup'] AND !empty($flag_cate_arr) AND FALSE === flag_create_thread($forum['fup'], 2, $tid, $flag_cate_arr) AND exit(lang('create_failed'));

    // 栏目flag
    !empty($flag_forum_arr) AND FALSE === flag_create_thread($fid, 3, $tid, $flag_forum_arr) AND exit(lang('create_failed'));

    exit('success');
}

?>