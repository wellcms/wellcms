<?php
/*
 * Copyright (C) www.wellcms.cn
 */
// hook model__attach_start.php

// ------------> 最原生的 CURD，无关联其他数据

function well_attach__create($arr, $d = NULL)
{
    // hook model__attach__create_start.php
    $r = db_insert('website_attach', $arr, $d);
    // hook model__attach__create_end.php
    return $r;
}

function well_attach__update($aid, $arr, $d = NULL)
{
    // hook model__attach__update_start.php
    $r = db_update('website_attach', array('aid' => $aid), $arr, $d);
    // hook model__attach__update_end.php
    return $r;
}

function well_attach__read($aid, $orderby = array(), $col = array(), $d = NULL)
{
    // hook model__attach__read_start.php
    $attach = db_find_one('website_attach', array('aid' => $aid), $orderby, $col, $d);
    // hook model__attach__read_end.php
    return $attach;
}

function well_attach__delete($aid, $d = NULL)
{
    // hook model__attach__delete_start.php
    $r = db_delete('website_attach', array('aid' => $aid), $d);
    // hook model__attach__delete_end.php
    return $r;
}

function well_attach__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = '', $col = array(), $d = NULL)
{
    // hook model__attach__find_start.php
    $attachlist = db_find('website_attach', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model__attach__find_end.php
    return $attachlist;
}

function well_attach_count($cond = array(), $d = NULL)
{
    // hook model__attach_count_start.php
    $n = db_count('website_attach', $cond, $d);
    // hook model__attach_count_end.php
    return $n;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理
function well_attach_create($arr)
{
    if (empty($arr)) return FALSE;
    // hook model__attach_create_start.php
    $r = well_attach__create($arr);
    // hook model__attach_create_end.php
    return $r;
}

function well_attach_update($aid, $update)
{
    if (empty($aid) || empty($update)) return FALSE;
    // hook model__attach_update_start.php
    $r = well_attach__update($aid, $update);
    // hook model__attach_update_end.php
    return $r;
}

function well_attach_read($aid)
{
    // hook model__attach_read_start.php

    $attach = well_attach__read($aid);

    $attach AND well_attach_format($attach);

    // hook model__attach_read_end.php

    return $attach;
}

function well_attach_delete($aid)
{
    global $conf;
    if (empty($aid)) return FALSE;
    // hook model__attach_delete_start.php

    $attach = well_attach_read($aid);
    if (empty($attach)) return FALSE;

    $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
    is_file($path) AND unlink($path);

    // hook model__attach_delete_after.php

    $r = well_attach__delete($aid);

    // hook model__attach_delete_end.php
    return $r;
}

function well_attach_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20)
{
    // hook model__attach_find_start.php

    $attachlist = well_attach__find($cond, $orderby, $page, $pagesize);
    if (empty($attachlist)) return NULL;

    // hook model__attach_find_before.php

    foreach ($attachlist as &$attach) well_attach_format($attach);
    // hook model__attach_find_end.php

    return $attachlist;
}

// 获取 $filelist $imagelist
function well_attach_find_by_tid($tid)
{
    $imagelist = array();
    $filelist = array();

    // hook model__attach_find_by_tid_start.php

    $attachlist = well_attach__find(array('tid' => $tid), array(), 1, 1000);
    if (empty($attachlist)) return array($attachlist, $imagelist, $filelist);

    // hook model__attach_find_by_tid_before.php

    foreach ($attachlist as $attach) {
        well_attach_format($attach);
        $attach['isimage'] ? $imagelist[] = $attach : $filelist[] = $attach;
    }

    // hook model__attach_find_by_tid_end.php

    return array($attachlist, $imagelist, $filelist);
}

function well_attach_delete_by_tid($tid)
{
    global $conf;
    // hook model__attach_delete_by_tid_start.php

    list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($tid);

    // hook model__attach_delete_by_tid_before.php
    if (empty($attachlist)) return FALSE;

    foreach ($attachlist as $attach) {
        $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
        is_file($path) AND unlink($path);
        well_attach__delete($attach['aid']);
    }

    // hook model__attach_delete_by_tid_end.php

    return count($attachlist);
}

function well_attach_delete_by_uid($uid)
{
    global $conf;
    // hook model__attach_delete_by_uid_start.php

    $attachlist = well_attach__find(array('uid' => $uid), array(), 1, 9000);

    if (empty($attachlist)) return;

    // hook model__attach_delete_by_uid_before.php

    foreach ($attachlist as $attach) {
        $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
        is_file($path) AND unlink($path);
        well_attach__delete($attach['aid']);
        // hook model__attach_delete_by_uid_after.php
    }

    // hook model__attach_delete_by_uid_end.php
}

// ------------> 其他方法
function well_attach_format(&$attach)
{
    global $conf;
    // hook model__attach_format_start.php
    if (empty($attach)) return;
    // hook model__attach_format_before.php
    $attach['create_date_fmt'] = date('Y-n-j', $attach['create_date']);
    $attach['url'] = $conf['upload_url'] . 'website_attach/' . $attach['filename'];
    // hook model__attach_format_end.php
}

function attach_type($name, $types)
{
    // hook model_attach_type_start.php
    $ext = file_ext($name);
    foreach ($types as $type => $exts) {
        if ($type == 'all') continue;
        if (in_array($ext, $exts)) return $type;
    }
    // hook model_attach_type_end.php
    return 'other';
}

// 扫描垃圾的附件，每24小时清理一次
function attach_gc()
{
    global $conf, $time;
    // hook model_attach_gc_start.php
    $tmpfiles = glob($conf['upload_path'] . 'tmp/*.*');
    if (is_array($tmpfiles)) {
        foreach ($tmpfiles as $file) {
            // 清理超过一天还没处理的临时文件
            $time - filemtime($file) > 86400 AND unlink($file);
        }
    }
    // hook model_attach_gc_end.php
}

/*
 * 附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。
 * */
// 关联 session 中的临时文件，并不会重新统计 images, files
// type 0主题主图 1:内容图片或附件 8:节点主图 9:节点tag主图
// 关联 session 中的临时文件，并不会重新统计 images, files
function well_attach_assoc_post($arr = array())
{
    if (empty($arr)) return FALSE;
    $message = TRUE;
    // hook model__attach_assoc_post_start.php
    $type = array_value($arr, 'type', 0);
    // hook model__attach_assoc_post_before.php
    $arr['sess_tmp_files'] = well_attach_assoc_type($type);
    // hook model__attach_assoc_post_center.php
    if ($type == 0) {
        // 主图缩略图
        // hook model__attach_assoc_post_thumbnail_start.php
        if (empty($arr['sess_tmp_files'])) return FALSE;
        // hook model__attach_assoc_post_thumbnail_before.php
        well_attach_assoc_thumbnail($arr);
        // hook model__attach_assoc_post_thumbnail_end.php
    } elseif ($type == 1) {
        // hook model__attach_assoc_post_file_start.php
        $message = well_attach_assoc_file($arr);
        // hook model__attach_assoc_post_file_end.php
    }
    // hook model__attach_assoc_post_end.php
    return $message;
}

// 主题缩略图
function well_attach_assoc_thumbnail($arr = array())
{
    global $conf, $time;

    // hook model_attach_assoc_thumbnail_start.php

    $sess_tmp_files = array_value($arr, 'sess_tmp_files');
    $tid = array_value($arr, 'tid');
    $uid = array_value($arr, 'uid');

    // hook model_attach_assoc_thumbnail_before.php

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

    $thumbnail_path = $conf['upload_path'] . 'thumbnail';
    is_dir($thumbnail_path) || mkdir($thumbnail_path, 0777, TRUE);

    $day = date($attach_dir_save_rule, $time);
    $path = $conf['upload_path'] . 'thumbnail/' . $day;
    is_dir($path) || mkdir($path, 0777, TRUE);

    // hook model_attach_assoc_thumbnail_center.php

    // 获取文件后缀
    $ext = file_ext($sess_tmp_files['url']);
    if (!in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) {
        unlink($sess_tmp_files['path']);
        return;
    }
    // hook model_attach_assoc_thumbnail_middle.php

    // 主题ID.后缀
    $destfile = $path . '/' . $uid . '_' . $tid . '_' . $time . '.' . $ext;
    xn_copy($sess_tmp_files['path'], $destfile) || xn_log("xn_copy($sess_tmp_files[path]), $destfile) failed, tid:$tid, name:$time", 'php_error');

    // hook model_attach_assoc_thumbnail_after.php

    if (is_file($destfile) && filesize($destfile) == filesize($sess_tmp_files['path'])) unlink($sess_tmp_files['path']);

    // 清空 session
    $_SESSION['tmp_thumbnail'] = array();
    clearstatcache();

    // 按照$destfile文件路径，上传至云储存或图床，返回数据。附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on，如果使用了图床则需要更新主题表image_url图床文件完整网站。上传云储存，有可能导致超时。

    // hook model_attach_assoc_thumbnail_end.php
}

// 关联内容的文件
function well_attach_assoc_file($arr = array())
{
    global $conf, $time;

    // hook model_attach_assoc_file_start.php

    //$sess_tmp_files = array_value($arr, 'sess_tmp_files');
    $uid = array_value($arr, 'uid');
    $tid = array_value($arr, 'tid');
    //$message = array_value($arr, 'message');
    $images = array_value($arr, 'images');
    $files = array_value($arr, 'files');

    // hook model_attach_assoc_file_before.php

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

    if (!empty($arr['sess_tmp_files'])) {

        // hook model_attach_assoc_file_center.php

        $upload_path = $conf['upload_path'] . 'website_attach';
        is_dir($upload_path) || mkdir($upload_path, 0777, TRUE);

        foreach ($arr['sess_tmp_files'] as $file) {

            // hook model_attach_assoc_file_foreach_start.php

            // 后台提交的内容需要替换掉../
            $file['url'] = $file['backstage'] && strpos($file['url'], '../upload/') !== FALSE ? str_replace('../upload/', 'upload/', $file['url']) : $file['url'];

            // hook model_attach_assoc_file_foreach_before.php

            // 内容附件 将文件移动到 upload/website_attach 目录
            $filename = file_name($file['url']);
            $day = date($attach_dir_save_rule, $time);
            $path = $conf['upload_path'] . 'website_attach/' . $day;
            $url = $conf['upload_url'] . 'website_attach/' . $day;
            is_dir($path) || mkdir($path, 0777, TRUE);

            // hook model_attach_assoc_file_path_after.php

            // 复制 删除
            $destfile = $path . '/' . $filename;
            // 相对路径
            $desturl = $url . '/' . $filename;

            xn_copy($file['path'], $destfile) || xn_log("xn_copy($file[path]), $destfile) failed, tid:$tid", 'php_error');

            // hook model_attach_assoc_file_copy_after.php

            if (is_file($destfile) && filesize($destfile) == filesize($file['path'])) unlink($file['path']);

            // 按照$destfile文件路径，上传至云储存或图床，返回数据.附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。

            // hook model_attach_assoc_file_arr_before.php

            $attach = array(
                'tid' => $tid,
                'uid' => $uid,
                'filesize' => $file['filesize'],
                'width' => $file['width'],
                'height' => $file['height'],
                'filename' => $day . '/' . $filename,
                'orgfilename' => $file['orgfilename'],
                //'image_url' => '', // 图床文件完整网址
                'filetype' => $file['filetype'],
                'create_date' => $time,
                'isimage' => $file['isimage'],
                'attach_on' => $conf['attach_on']
            );

            // hook model_attach_assoc_file_create_before.php

            // 关联内容再入库
            $aid = well_attach_create($attach);

            $file['backstage'] AND $arr['message'] = str_replace('../upload/', 'upload/', $arr['message']);
            $arr['message'] = str_replace($file['url'], $desturl, $arr['message']);

            // hook model_attach_assoc_file_foreach_end.php
        }

        // hook model_attach_assoc_file_middle.php

        // 清空 session
        $_SESSION['tmp_website_files'] = array();
    }

    // hook model_attach_assoc_file_filter_start.php

    // 更新附件数
    $thread = array();

    // 处理不在 message 中的图片，删除掉没有插入的图片附件
    if ($arr['message']) {

        list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($tid);

        // hook model_attach_assoc_file_filter_before.php

        if (!empty($imagelist)) {
            foreach ($imagelist as $key => $attach) {

                $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];

                // hook model_attach_assoc_file_filter_delete_before.php

                if (strpos($arr['message'], $url) === FALSE) {
                    unset($imagelist[$key]);
                    well_attach_delete($attach['aid']);
                    // hook model_attach_assoc_file_filter_delete.php
                }

                //$conf['attach_delete'] == 1 开启云储存后删除本地附件
                /*$path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
                if ($conf['attach_delete'] == 1 && is_file($path)) unlink($path);*/

                // hook model_attach_assoc_file_filter_center.php
            }

            // hook model_attach_assoc_file_filter_middle.php
        }

        $_images = count($imagelist);
        $images != $_images AND $thread['images'] = $_images;

        $_files = count($filelist);
        $files != $_files AND $thread['files'] = $_files;

        // hook model_attach_assoc_file_filter_end.php
    }

    // hook model_attach_assoc_file_filter_end.php

    if (empty($thread)) return $arr['message'];

    well_thread_update($tid, $thread);

    // hook model_attach_assoc_file_end.php

    return $arr['message'];
}

// type 0内容主图 1:内容图片或附件 8:节点主图 9:节点tag主图 教练套课主图
function well_attach_assoc_type($type)
{
    // hook model__attach_assoc_type_start.php
    switch ($type) {
        case '0':
            $k = 'tmp_thumbnail';
            break;
        case '1':
            $k = 'tmp_website_files';
            break;
        // hook model__attach_assoc_case_end.php
        default:
            $k = 'tmp_thumbnail';
            break;
    }
    $sess_tmp_files = _SESSION($k);
    // 如果session中没有，从数据库中获取储存的session
    if (empty($sess_tmp_files) && preg_match('#' . $k . '\|(a\:1\:\{.*\})#', _SESSION('data'), $matches)) $sess_tmp_files = unserialize(str_replace(array('+', '='), array('_', '.'), $matches['1']));
    // hook model__attach_assoc_type_end.php
    return $sess_tmp_files;
}

// Create thumbnail
function well_attach_create_thumbnail($arr)
{
    global $conf, $time, $forumlist, $config;

    $uid = array_value($arr, 'uid', 0);
    $tid = array_value($arr, 'tid', 0);
    $fid = array_value($arr, 'fid', 0);
    $forum = array_value($forumlist, $fid);

    $picture = $config['picture_size'];
    $picture = isset($forum['thumbnail']) ? $forum['thumbnail'] : $picture['picture_size'];
    $pic_width = $picture['width'];
    $pic_height = $picture['height'];

    $attachlist = well_attach_assoc_type(1);
    if (empty($attachlist)) return;

    $website_path = $conf['upload_path'] . 'thumbnail';
    is_dir($website_path) || mkdir($website_path, 0777, TRUE);

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

    $day = date($attach_dir_save_rule, $time);
    $path = $conf['upload_path'] . 'thumbnail/' . $day;
    is_dir($path) || mkdir($path, 0777, TRUE);

    $tmp_file = $conf['upload_path'] . 'tmp/' . $uid . '_' . $tid . '_' . $time . '.jpeg';

    $i = 0;
    foreach ($attachlist as $val) {
        ++$i;
        if ($val['isimage'] == 1 && $i == 1) {
            well_image_clip_thumb($val['path'], $tmp_file, $pic_width, $pic_height);
            break;
        }
    }
    $destfile = $path . '/' . $uid . '_' . $tid . '_' . $time . '.jpeg';
    xn_copy($tmp_file, $destfile) || xn_log("xn_copy($tmp_file), $destfile) failed, tid:$tid", 'php_error');
}

function well_save_remote_image($arr)
{
    global $conf, $time, $forumlist, $config;

    // hook model_attach_save_remote_image_start.php

    $message = array_value($arr, 'message');
    $tid = array_value($arr, 'tid', 0);
    $fid = array_value($arr, 'fid', 0);
    $uid = array_value($arr, 'uid', 0);
    $thumbnail = array_value($arr, 'thumbnail', 0);
    $save_image = array_value($arr, 'save_image', 0);

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

    $website_path = $conf['upload_path'] . 'website_attach';
    is_dir($website_path) || mkdir($website_path, 0777, TRUE);

    $day = date($attach_dir_save_rule, $time);
    $attach_dir = $conf['upload_path'] . 'website_attach/' . $day;
    $attach_url = $conf['upload_url'] . 'website_attach/' . $day;
    is_dir($attach_dir) || mkdir($attach_dir, 0777, TRUE);

    if ($thumbnail) {

        $picture = $config['picture_size'];
        $forum = array_value($forumlist, $fid);
        $picture = isset($forum['thumbnail']) ? $forum['thumbnail'] : $picture['picture_size'];
        $pic_width = $picture['width'];
        $pic_height = $picture['height'];

        $thumbnail_path = $conf['upload_path'] . 'thumbnail';
        is_dir($thumbnail_path) || mkdir($thumbnail_path, 0777, TRUE);
        $thumbnail_path = $thumbnail_path . '/' . $day;
        is_dir($thumbnail_path) || mkdir($thumbnail_path, 0777, TRUE);

        $tmp_file = $thumbnail_path . '/' . $uid . '_' . $tid . '_' . $time . '.jpeg';
    }

    $localurlarr = array(
        'http://' . $_SERVER['SERVER_NAME'] . '/',
        'https://' . $_SERVER['SERVER_NAME'] . '/',
    );

    // hook model_attach_save_remote_image_before.php

    preg_match_all('#<img[^>]+src="(http.*?)"#i', $message, $match);

    if (!empty($match[1])) {
        $n = 0;
        $i = 0;
        foreach ($match[1] as $url) {
            foreach ($localurlarr as $localurl) {
                if ($localurl == substr($url, 0, strlen($localurl))) continue 2;
            }
            // hook model_attach_save_remote_image_imageurl_before.php
            $imageurl = well_get_image_url($url);
            $ext = $imageurl ? file_ext($imageurl) : '';
            // hook model_attach_save_remote_image_center.php
            $filename = $uid . '_' . xn_rand(16) . '.' . ($ext ? $ext : 'jpeg');
            $destpath = $attach_dir . '/' . $filename;
            $desturl = $attach_url . '/' . $filename;
            $_message = str_replace($url, $desturl, $message);
            if ($message != $_message) {
                $imgdata = https_request($url);
                $filesize = strlen($imgdata);
                if ($filesize < 10) continue;
                // hook model_attach_save_remote_image_put_before.php
                if (empty($ext) || $ext == 'webp') {
                    $tmpfile = $conf['upload_path'] . 'tmp/' . $filename;
                    file_put_contents_try($tmpfile, $imgdata);
                    $img = imagecreatefromwebp($tmpfile);
                    imagejpeg($img, $destpath, 70);
                    imagedestroy($img);
                    is_file($tmpfile) AND unlink($tmpfile);
                } else {
                    if (!in_array($ext, array('gif', 'jpg', 'jpeg', 'png'))) continue;
                    file_put_contents_try($destpath, $imgdata);
                }

                if ($thumbnail) {
                    ++$i;
                    if ($i == 1) {
                        // 裁切保存到缩略图目录
                        well_image_clip_thumb($destpath, $tmp_file, $pic_width, $pic_height);
                        well_thread_update($tid, array('icon' => $time));
                        if (empty($save_image)) continue;
                    }
                }
                // hook model_attach_save_remote_image_put_after.php
                list($width, $height) = getimagesize($destpath);
                // hook model_attach_save_remote_image_middle.php
                $attach = array(
                    'tid' => $tid,
                    'uid' => $uid,
                    'filesize' => $filesize,
                    'width' => $width,
                    'height' => $height,
                    'filename' => "$day/$filename",
                    'orgfilename' => $filename,
                    'filetype' => 'image',
                    'create_date' => $time,
                    'comment' => '',
                    'downloads' => 0,
                    'isimage' => 1
                );
                $aid = well_attach_create($attach);
                $n++;
            }
            
            $message = preg_replace('#(<img.*?)(class=.+?[\'|\"])|(data-src=.+?[\'|"])|(data-type=.+?[\'|"])|(data-ratio=.+?[\'|"])|(data-s=.+?[\'|"])|(data-fail=.+?[\'|"])|(crossorigin=.+?[\'|"])|((data-w)=[\'"]+[0-9]+[\'"]+)|(_width=.+?[\'|"]+)|(_height=.+?[\'|"]+)|(style=.+?[\'|"])|((width)=[\'"]+[0-9]+[\'"]+)|((height)=[\'"]+[0-9]+[\'"]+)#i', '$1', $_message);
        }
        // hook model_attach_save_remote_image_after.php
        $n AND well_thread_update($tid, array('images+' => $n));
    }
    // hook model_attach_save_remote_image_end.php
    return $message;
}

function well_get_image_url($url)
{
    if ($n = strpos($url, '.jpg')) {
        $_n = $n + 4;
    } elseif ($n = strpos($url, '.jpeg')) {
        $_n = $n + 5;
    } elseif ($n = strpos($url, '.png')) {
        $_n = $n + 4;
    } elseif ($n = strpos($url, '.gif')) {
        $_n = $n + 4;
    } elseif ($n = strpos($url, '.bmp')) {
        $_n = $n + 4;
    }

    $url = $n ? mb_substr($url, 0, $_n, 'UTF-8') : NULL;

    return $url;
}

// hook model__attach_end.php

?>