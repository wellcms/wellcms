<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model_data_start.php

// ------------> 原生CURD，无关联其他数据。
function data__create($arr = array(), $d = NULL)
{
    // hook model_data__create_start.php
    $r = db_insert('website_data', $arr, $d);
    // hook model_data__create_end.php
    return $r;
}

function data__update($tid, $update = array(), $d = NULL)
{
    // hook model_data__update_start.php
    $r = db_update('website_data', array('tid' => $tid), $update, $d);
    // hook model_data__update_end.php
    return $r;
}

function data__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_data__read_start.php
    $r = db_find_one('website_data', $cond, $orderby, $col, $d);
    // hook model_data__read_end.php
    return $r;
}

function data__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tid', $col = array(), $d = NULL)
{
    // hook model_data__find_start.php
    $arr = db_find('website_data', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_data__find_end.php
    return $arr;
}

function data__delete($tid, $d = NULL)
{
    // hook model_data__delete_start.php
    $r = db_delete('website_data', array('tid' => $tid), $d);
    // hook model_data__delete_end.php
    return $r;
}

//--------------------------强相关--------------------------
// $arr = array('tid' => $tid, 'gid' => $gid, 'message' => $arr['message'], 'doctype' => $doctype);
function data_create($arr)
{
    // hook model_data_create_start.php

    if (empty($arr)) return FALSE;

    // hook model_data_create_before.php

    data_message_format($arr);

    // hook model_data_create_after.php

    $r = data__create($arr);

    // hook model_data_create_end.php

    return $r;
}

// 更新 $update = array('tid' => $tid, 'gid' => $gid, 'message' => $arr['message'], 'doctype' => $doctype);
function data_update($tid, $update)
{
    global $conf;
    // hook model_data_update_start.php

    if (empty($tid) || empty($update)) return FALSE;

    // hook model_data_update_before.php

    data_message_format($update);

    // hook model_data_update_center.php

    $r = data__update($tid, $update);

    // hook model_data_update_after.php

    $r AND 'mysql' != $conf['cache']['type'] AND cache_delete('website_data_' . $tid);
    // hook model_data_update_end.php

    return $r;
}

// 单次查询 tid
function data_read($tid)
{
    // hook model_data_read_by_tid_start.php
    $r = data__read(array('tid' => $tid));
    $r AND data_format($r);
    // hook model_data_read_by_tid_end.php
    return $r;
}

function data_find($tid, $pagesize = 20)
{
    // hook model_data_find_start.php
    $arrlist = data__find(array('tid' => $tid), array('tid' => -1), 1, $pagesize);
    // hook model_data_find_end.php
    return $arrlist;
}

// 主键删除
function data_delete($tid)
{
    global $conf;
    // hook model_data_delete_start.php
    if (empty($tid)) return FALSE;
    // hook model_data_delete_before.php
    $r = data__delete($tid);
    $r AND 'mysql' != $conf['cache']['type'] AND cache_delete('website_data_' . $tid);
    // hook model_data_delete_end.php
    return $r;
}

function data_format(&$val)
{
    global $conf;
    // hook model_data_format_start.php

    if (empty($val)) return;

    // 使用云储存
    1 == $conf['attach_on'] || 0 == $conf['attach_on'] AND $val['message'] = str_replace('="upload/', '="' . file_path(), $val['message']);

    // 使用图床
    if (2 == $conf['attach_on']) {

        list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($val['tid']);

        foreach ($imagelist as $key => $attach) {

            $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];

            // 替换成图床
            $val['message'] = FALSE !== strpos($val['message'], $url) && $attach['image_url'] ? str_replace($url, $attach['image_url'], $val['message']) : $val['message'];
        }
    }
    //$val['message'] = stripslashes(htmlspecialchars_decode($val['message']));

    // hook model_data_format_end.php
}

// 把内容中使用了云储存的附件链接替换掉
function data_message_replace_url($tid, $message)
{
    global $conf;

    // hook model_data_message_replace_url_start.php

    if (0 == $conf['attach_on']) {
        $message = FALSE !== strpos($message, '="../upload/') ? str_replace('="../upload/', '="upload/', $message) : $message;
    } elseif (1 == $conf['attach_on']) {
        // 使用云储存
        $message = str_replace('="' . $conf['cloud_url'] . 'upload/', '="upload/', $message);
    } elseif (2 == $conf['attach_on']) {

        // 使用图床
        list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($tid);

        foreach ($imagelist as $key => $attach) {
            $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];
            // 替换回相对链接
            $message = $attach['image_url'] && FALSE !== strpos($message, $attach['image_url']) ? str_replace($attach['image_url'], $url, $message) : $message;
        }
    }

    // hook model_data_message_replace_url_end.php

    return $message;
}

// 写入时格式化
function data_message_format(&$post)
{
    // hook model_data_message_format_start.php

    // 超长内容截取
    $post['message'] = xn_substr($post['message'], 0, 2028000);

    // hook model_data_message_format_beofre.php

    // 格式转换: 类型，0: html, 1: txt; 2: markdown; 3: ubb
    $message = htmlspecialchars($post['message'], ENT_QUOTES); // html标签全部转换

    // 入库过滤 非管理员全部过滤
    0 == $post['doctype'] && $message = ((isset($post['gid']) AND 1 == $post['gid']) ? $post['message'] : xn_html_safe($post['message']));
    1 == $post['doctype'] && $message = xn_txt_to_html($post['message']);

    // hook model_data_message_format_after.php

    $post['message'] = $message;
    unset($post['gid']);

    // 对引用进行处理
    !empty($post['quotepid']) && $post['quotepid'] > 0 && $post['message'] = comment_quote($post['quotepid']) . $post['message'];

    // hook model_data_message_format_end.php
}

/*公用的附件模板，采用函数，效率比 include 高
 * @param $filelist 附件列表
 * @param bool $include_delete 删除
 * @param bool $access TRUE编辑时附件路径
 * @param bool $path TRUE后台编辑时附件路径
 * @return string
 */
function data_file_list_html($filelist, $include_delete = FALSE, $access = FALSE, $path = FALSE)
{
    global $conf;
    if (empty($filelist)) return '';

    // hook model_data_file_list_html_start.php

    if (FALSE != $path) {
        if ($conf['url_rewrite_on'] > 1) {
            $path = $conf['path'];
        } else {
            $path = '../';
        }
    } else {
        $path = '';
    }

    $s = '<fieldset class="fieldset m-0 p-0">' . "\r\n";
    $s .= '<legend>' . lang('uploaded_attach') . '：</legend>' . "\r\n";
    $s .= '<ul class="list-unstyled attachlist nowrap">' . "\r\n";
    foreach ($filelist as &$attach) {
        $s .= '<li aid="' . $attach['aid'] . '" class="p-1">' . "\r\n";
        $s .= '		<div class="w-75"><a href="' . (FALSE != $access ? $path . $attach['url'] : url('attach-download-' . $attach['aid'])) . '" target="_blank" class="d-block ellipsis">' . "\r\n";
        $s .= '			<i class="icon filetype ' . $attach['filetype'] . '"></i>' . "\r\n";
        $s .= '			' . $attach['orgfilename'] . "\r\n";
        $s .= '		</a></div>' . "\r\n";
        // hook model_post_file_list_html_delete_before.php
        $include_delete AND $s .= '		<a href="javascript:void(0)" class="w-25 position-absolute text-center delete" style="margin-top:-21px;right:0px;"><i class="icon-remove"></i> ' . lang('delete') . '</a>' . "\r\n";
        // hook model_post_file_list_html_delete_after.php
        $s .= '</li>' . "\r\n";
    };
    $s .= '</ul>' . "\r\n";
    $s .= '</fieldset>' . "\r\n";

    // hook model_data_file_list_html_end.php

    return $s;
}

//--------------------------cache--------------------------
// 从缓存中读取，避免重复从数据库取数据
function data_read_cache($tid)
{
    global $conf;
    // hook model_data_read_cache_start.php
    $key = 'website_data_' . $tid;
    static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，要跨进程，可以再加一层缓存： memcached/xcache/apc/
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = data_read($tid);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = data_read($tid);
            $r AND cache_set($key, $r, 3600); // 60分钟
        }
    }
    $cache[$key] = $r ? $r : NULL;
    // hook model_data_read_cache_end.php
    return $cache[$key];
}

// hook model_data_end.php
?>