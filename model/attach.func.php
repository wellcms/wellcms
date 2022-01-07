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

function well_attach__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'aid', $col = array(), $d = NULL)
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

function well_attach_max_aid($col = 'aid', $cond = array(), $d = NULL)
{
    // hook model_well_attach_max_aid_start.php
    $id = db_maxid('website_attach', $col, $cond, $d);
    // hook model_well_attach_max_aid_end.php
    return $id;
}

function attach_big_insert($arr = array(), $d = NULL)
{
    // hook model_attach_big_insert_start.php
    $r = db_big_insert('website_attach', $arr, $d);
    // hook model_attach_big_insert_end.php
    return $r;
}

function attach_big_update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_attach_big_update_start.php
    $r = db_big_update('website_attach', $cond, $update, $d);
    // hook model_attach_big_update_end.php
    return $r;
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

    $attach and well_attach_format($attach);

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
    is_file($path) and unlink($path);

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

// 获取主题附件和图片 $filelist $imagelist
function well_attach_find_by_tid($tid)
{
    $imagelist = array();
    $filelist = array();

    // hook model__attach_find_by_tid_start.php

    $attachlist = well_attach__find(array('tid' => $tid), array(), 1, 100, 'aid');
    if (empty($attachlist)) return array($attachlist, $imagelist, $filelist);

    // hook model__attach_find_by_tid_before.php

    foreach ($attachlist as $key => $attach) {
        if ($attach['pid']) continue;
        well_attach_format($attach);
        $attach['isimage'] ? $imagelist[$attach['aid']] = $attach : $filelist[$attach['aid']] = $attach;
    }

    // hook model__attach_find_by_tid_end.php

    return array($attachlist, $imagelist, $filelist);
}

function well_attach_delete_by_tid($tid)
{
    global $conf;
    // hook model_attach_delete_by_tid_start.php

    list($attachlist, $imagelist, $filelist) = well_attach_find_by_tid($tid);

    // hook model_attach_delete_by_tid_before.php
    if (empty($attachlist)) return FALSE;

    $aids = array();
    foreach ($attachlist as $attach) {
        $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
        is_file($path) and unlink($path);
        $aids[] = $attach['aid'];

        // hook model_attach_delete_by_tid_center.php
    }

    // hook model_attach_delete_by_tid_after.php

    well_attach__delete($aids);

    // hook model_attach_delete_by_tid_end.php

    return count($attachlist);
}

/*
 * @param $tids 主题tid 数组array(1,2,3)
 * @param $n 图片和附件总数量
 * @return int 返回清理数量
 */
function well_attach_delete_by_tids($tids, $n)
{
    global $conf;

    // hook model_attach_delete_by_tids_start.php

    $attachlist = well_attach__find(array('tid' => $tids), array('aid' => 1), 1, $n);
    if (!$attachlist) return 0;

    // hook model_attach_delete_by_tids_before.php

    $aids = array();
    foreach ($attachlist as $attach) {
        if (!$attach['filename']) continue;
        $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
        is_file($path) and unlink($path);
        $aids[] = $attach['aid'];

        // hook model_attach_delete_by_tids_center.php
    }

    // hook model_attach_delete_by_tids_after.php

    well_attach__delete($aids);

    // hook model_attach_delete_by_tids_end.php

    return count($aids);
}

// 获取 $filelist $imagelist
function well_attach_find_by_pid($pid)
{
    $imagelist = array();
    $filelist = array();

    // hook model__attach_find_by_pid_start.php

    $attachlist = well_attach__find(array('pid' => $pid), array(), 1, 100, 'aid');
    if (empty($attachlist)) return array($attachlist, $imagelist, $filelist);

    // hook model__attach_find_by_pid_before.php

    foreach ($attachlist as $attach) {
        well_attach_format($attach);
        $attach['isimage'] ? $imagelist[$attach['aid']] = $attach : $filelist[$attach['aid']] = $attach;
    }

    // hook model__attach_find_by_pid_end.php

    return array($attachlist, $imagelist, $filelist);
}

// 删除评论附件和图片
function well_attach_delete_by_pid($pid)
{
    global $conf;
    // hook model_attach_delete_by_pid_start.php

    list($attachlist, $imagelist, $filelist) = well_attach_find_by_pid($pid);

    // hook model_attach_delete_by_pid_before.php
    if (empty($attachlist)) return FALSE;

    $aids = array();
    foreach ($attachlist as $attach) {
        $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
        is_file($path) and unlink($path);
        $aids[] = $attach['aid'];
        // hook model_attach_delete_by_pid_center.php
    }

    // hook model_attach_delete_by_pid_after.php

    well_attach__delete($aids);

    // hook model_attach_delete_by_pid_end.php

    return count($attachlist);
}

function well_attach_delete_by_uid($uid)
{
    global $conf;
    // hook model_attach_delete_by_uid_start.php

    $attachlist = well_attach__find(array('uid' => $uid), array(), 1, 2000);

    if (empty($attachlist)) return;

    // hook model_attach_delete_by_uid_before.php

    $aids = array();
    foreach ($attachlist as $attach) {
        $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
        is_file($path) and unlink($path);
        $aids[] = $attach['aid'];
        // hook model_attach_delete_by_uid_center.php
    }

    // hook model_attach_delete_by_uid_after.php

    well_attach__delete($aids);

    // hook model_attach_delete_by_uid_end.php
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
        if ('all' == $type) continue;
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
            $time - filemtime($file) > 86400 and unlink($file);
        }
    }
    // hook model_attach_gc_end.php
}

// 已放弃使用该函数 关联 session 中的临时文件，并不会重新统计 images, files
function well_attach_assoc_post($arr = array())
{
    if (empty($arr)) return FALSE;
    // hook model__attach_assoc_post_start.php
    $assoc = array_value($arr, 'assoc');
    // hook model__attach_assoc_post_before.php
    $arr['sess_tmp_files'] = well_attach_assoc_type($assoc);
    // hook model__attach_assoc_post_center.php
    switch ($assoc) {
        case 'thumbnail': // 主图缩略图
            // hook model__attach_assoc_post_thumbnail_start.php
            if (empty($arr['sess_tmp_files'])) return FALSE;
            // hook model__attach_assoc_post_thumbnail_before.php
            well_attach_assoc_thumbnail($arr);
            // hook model__attach_assoc_post_thumbnail_end.php
            break;
        case 'post': // 内容附件和图片
            // hook model__attach_assoc_post_file_start.php
            return well_attach_assoc_file($arr);
            // hook model__attach_assoc_post_file_end.php
            break;
        // hook model__attach_assoc_post_case.php
        default:
            message(-1, lang('data_malformation'));
            break;
    }
    // hook model__attach_assoc_post_end.php
    return TRUE;
}

// 关联上传主题图
function well_attach_assoc_thumbnail($arr = array())
{
    global $conf, $time;

    // hook model_attach_assoc_thumbnail_start.php

    $tid = array_value($arr, 'tid');
    $uid = array_value($arr, 'uid');
    $sess_tmp_files = $_SESSION['tmp_thumbnail'];
    if (empty($sess_tmp_files)) return FALSE;

    // hook model_attach_assoc_thumbnail_before.php

    // 获取文件后缀
    $ext = strtolower(file_ext($sess_tmp_files['url']));

    if (!in_array($ext, array('gif', 'jpg', 'jpeg', 'png'), TRUE)) {
        unlink($sess_tmp_files['path']);
        return TRUE;
    }

    // 默认位置存图
    $thumbnail_save_default = 1;

    // hook model_attach_assoc_thumbnail_center.php

    if (1 == $thumbnail_save_default) {
        $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

        $day = date($attach_dir_save_rule, $time);
        $path = $conf['upload_path'] . 'thumbnail/' . $day;

        is_dir($path) || mkdir($path, 0777, TRUE);

        // 主题ID.后缀
        $destfile = $path . '/' . $uid . '_' . $tid . '_' . $time . '.' . $ext;
    }

    // hook model_attach_assoc_thumbnail_middle.php

    if (empty($destfile)) {
        unlink($sess_tmp_files['path']);
        return TRUE;
    }

    copy($sess_tmp_files['path'], $destfile);

    // 按照$destfile文件路径，上传至云储存或图床，返回数据.附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。

    // hook model_attach_assoc_thumbnail_after.php

    if (is_file($destfile) && filesize($destfile) == filesize($sess_tmp_files['path'])) unlink($sess_tmp_files['path']);

    // 清空 session
    $_SESSION['tmp_thumbnail'] = array();
    clearstatcache();

    // hook model_attach_assoc_thumbnail_end.php

    return TRUE;
}

/*
 * 附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。
 * */
// 关联内容中图片和文件
// 传参 $arr = array('uid' => $uid, 'gid' => $gid, 'tid' => $tid, 'fid' => $fid, 'time' => $time, 'conf' => $conf, 'message' => $message, 'thumbnail' => $thumbnail, 'save_image' => $save_image, 'sess_file' => 1);
// 返回 array('tid' => $tid, 'pid' => $pid, 'icon' => $icon, 'message' => $message, 'images' => $images, 'files' => $files);
function well_attach_assoc_handle($arr = array())
{
    if (empty($arr['tid']) && empty($arr['pid']) || empty($arr['conf'])) return FALSE;

    // hook model_attach_assoc_image_start.php

    $uid = array_value($arr, 'uid', 0); // 用户uid
    $gid = array_value($arr, 'gid', 0); // 用户gid
    $tid = array_value($arr, 'tid', 0); // 创建成功的主题tid
    $pid = array_value($arr, 'pid', 0); // 评论内容的pid，非主题内容时必传
    $message = array_value($arr, 'message'); // 内容
    $conf = array_value($arr, 'conf'); // 程序配置文件
    $time = array_value($arr, 'time'); // 时间戳
    $fid = array_value($arr, 'fid', 0); // 当前版块fid
    $forumlist = forum_list_cache();
    $thumbnail = array_value($arr, 'thumbnail', 0); // 从内容中获取主图
    $save_image = array_value($arr, 'save_image', 0); // 本地化
    $sess_file = array_value($arr, 'sess_file', 0); // 关联上传文件

    $return = array(); // 返回数据

    // hook model_attach_assoc_image_before.php

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');
    $day = date($attach_dir_save_rule, $time);
    $upload_path = $conf['upload_path'] . 'website_attach/' . $day;
    $upload_url = $conf['upload_url'] . 'website_attach/' . $day;
    is_dir($upload_path) || mkdir($upload_path, 0777, TRUE);

    // hook model_attach_assoc_image_message_before.php

    $message = urldecode($message);
    //$message = htmlspecialchars_decode($message);
    preg_match_all('#<img[^>]+src="(.*?)"#i', $message, $match);

    $localurlarr = array(
        'http://' . $_SERVER['SERVER_NAME'] . '/',
        'https://' . $_SERVER['SERVER_NAME'] . '/',
    );

    // 跳过云储存
    $conf['cloud_url'] and $localurlarr[] = $conf['cloud_url'];

    // hook model_attach_assoc_image_match_before.php

    /*$match[1]
            Array
            (
                [0] => https://nimg.ws.126.net/?url=http://dingyue.ws.126.net/2021/1109/d3321a58j00r29xq20013c000hk00h4g.jpg&thumbnail=650x2147483647&quality=80&type=jpg
                [1] => https://nimg.ws.126.net/?url=http://dingyue.ws.126.net/2021/1109/e4974b87j00r29xq30013c000fi00i3g.jpg&thumbnail=650x2147483647&quality=80&type=jpg
                [2] => upload/tmp/1_KAUXKFMJHFTUMNS.jpg
                [3] => https://nimg.ws.126.net/?url=http://dingyue.ws.126.net/2021/1109/5a7be552j00r29xq3001qc000hs00hvg.jpg&thumbnail=650x2147483647&quality=80&type=jpg
            )*/
    $attach = array();
    $thumbnail_tmp = '';
    $uploadlist = array(); // 上传
    $imagelist = array(); // 远程
    $i = 0;
    if (!empty($match[1])) {
        foreach ($match[1] as $_url) {

            foreach ($localurlarr as $localurl) {
                if ($localurl == substr($_url, 0, strlen($localurl))) continue 2;
            }

            ++$i;

            if (substr($_url, 0, 7) == 'http://' || substr($_url, 0, 8) == 'https://') {
                $imagelist[] = $_url;

                1 == $i and $thumbnail_tmp = array(
                    'type' => 'http',
                    'url' => htmlspecialchars_decode($_url)
                );

            } elseif (substr($_url, 0, 11) == 'upload/tmp/') {
                $uploadlist[] = $_url;

                1 == $i and $thumbnail_tmp = array(
                    'type' => 'file',
                    'url' => $conf['upload_path'] . substr($_url, 7, strlen($_url))
                );
            } elseif (substr($_url, 0, 12) == '/upload/tmp/') {
                $_url = str_replace('/upload/tmp/', 'upload/tmp/', $_url);
                $uploadlist[] = $_url;

                1 == $i and $thumbnail_tmp = array(
                    'type' => 'file',
                    'url' => $conf['upload_path'] . substr($_url, 7, strlen($_url))
                );
            } elseif (substr($_url, 0, 14) == '../upload/tmp/') {

                $_url = str_replace('../upload/', 'upload/', $_url);
                $uploadlist[] = $_url;

                1 == $i and $thumbnail_tmp = array(
                    'type' => 'file',
                    'url' => $conf['upload_path'] . substr($_url, 7, strlen($_url))
                );
            }
        }
    }

    // hook model_attach_assoc_image_match_after.php

    $icon = 0; // 缩略图
    if ($thumbnail && $thumbnail_tmp) {

        $forum = array_value($forumlist, $fid);
        $picture = $forum['thumbnail'];
        $pic_width = $picture['width'];
        $pic_height = $picture['height'];

        $thumbnail_path = $conf['upload_path'] . 'thumbnail/' . $day . '/';
        is_dir($thumbnail_path) || mkdir($thumbnail_path, 0777, TRUE);

        // hook model_attach_assoc_image_thumbnail_start.php

        $thumbnail_file = $thumbnail_path . $uid . '_' . $tid . '_' . $time . '.jpeg';
        $thumbnail_url = $conf['upload_url'] . 'thumbnail/' . $day . '/' . $uid . '_' . $tid . '_' . $time . '.jpeg';

        // hook model_attach_assoc_image_thumbnail_before.php

        $icon = $time;
        $delete = FALSE;
        if ('http' == $thumbnail_tmp['type']) {
            $imgdata = https_request($thumbnail_tmp['url']);
            $filename = $uid . '_' . xn_rand(16);
            $destpath = $upload_path . $filename;
            file_put_contents_try($destpath, $imgdata);

            $delete = TRUE;
        } else {
            $destpath = $thumbnail_tmp['url'];
        }

        $getimgsize = getimagesize($destpath);

        // 裁切保存到缩略图目录
        'clip' == array_value($conf, 'upload_resize', 'clip') ? well_image_clip_thumb($destpath, $thumbnail_file, $pic_width, $pic_height, $getimgsize) : well_image_thumb($destpath, $thumbnail_file, $pic_width, $pic_height, $getimgsize);

        // 按照$destpath文件路径，上传至云储存或图床，返回数据.附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。

        // hook model_attach_assoc_image_thumbnail_after.php

        $delete && is_file($destpath) and unlink($destpath);
    }

    // hook model_attach_assoc_image_thumbnail_end.php

    // 处理需要本地化的图片
    if ($save_image && !empty($imagelist)) {

        // hook model_attach_assoc_file_save_before.php

        foreach ($imagelist as $image_url) {

            $full_url = htmlspecialchars_decode($image_url);
            $message = str_replace($image_url, $full_url, $message);
            $getimgsize = getimagesize($full_url);
            if (FALSE === $getimgsize) continue; // 非图片跳出

            // hook model_attach_assoc_file_save_filename.php

            $filename = $uid . '_' . xn_rand(16);
            if (1 == $getimgsize[2]) {
                $filename .= '.gif';
                $destpath = $upload_path . '/' . $filename;
            } elseif (in_array($getimgsize[2], array(2, 3, 15, 18))) {
                $filename .= '.jpeg';
                $destpath = $upload_path . '/' . $filename;
            } else {
                continue; // 非常见图片格式跳出
            }

            $desturl = $upload_url . '/' . $filename;

            // hook model_attach_assoc_file_save_filename_after.php

            // 本地化
            if ($save_image) {
                $imgdata = https_request($full_url);
                file_put_contents_try($destpath, $imgdata);

                // hook model_attach_assoc_file_save_file_put.php

                $filesize = strlen($imgdata);
                $attach_arr = array('tid' => $tid, 'uid' => $uid, 'filesize' => $filesize, 'width' => $getimgsize[0], 'height' => $getimgsize[1], 'filename' => "$day/$filename", 'orgfilename' => $filename, 'filetype' => 'image', 'create_date' => $time, 'downloads' => 0, 'isimage' => 1);

                // 按照$destpath文件路径，上传至云储存或图床，返回数据.附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。

                // hook model_attach_assoc_file_save_attach.php

                $attach[] = $attach_arr;
            }

            // hook model_attach_assoc_file_save_message_before.php

            $message = str_replace($full_url, $desturl, $message);
            $message = preg_replace('#(<img.*?)(class=.+?[\'|\"])|(data-src=.+?[\'|"])|(data-type=.+?[\'|"])|(data-ratio=.+?[\'|"])|(data-s=.+?[\'|"])|(data-fail=.+?[\'|"])|(crossorigin=.+?[\'|"])|((data-w)=[\'"]+[0-9]+[\'"]+)|(_width=.+?[\'|"]+)|(_height=.+?[\'|"]+)|(style=.+?[\'|"])|((width)=[\'"]+[0-9]+[\'"]+)|((height)=[\'"]+[0-9]+[\'"]+)#i', '$1', $message);

            // hook model_attach_assoc_file_save_message_after.php
        }

        // hook model_attach_assoc_file_save_end.php
    }

    // hook model_attach_assoc_image_upload_start.php

    if ($sess_file) {

        // hook model_attach_assoc_image_upload_before.php

        // session 存在
        if (!empty($_SESSION['tmp_website_files'])) {

            // hook model_attach_assoc_session_start.php

            foreach ($_SESSION['tmp_website_files'] as $file) {

                if (!is_file($file['path'])) continue;

                // hook model_attach_assoc_file_foreach_start.php

                // 后台提交的内容需要替换掉../
                $file['url'] = str_replace(array('../upload/', '/upload/'), 'upload/', $file['url']);

                // 过滤非内容图，不包括附件
                if (!in_array($file['url'], $uploadlist) && 0 != $file['isimage']) {
                    unlink($file['path']);
                    continue;
                }

                // 没有附件权限
                if (0 == $file['isimage']) {
                    if (($fid && !forum_access_user($fid, $gid, 'allowattach')) && !group_access($gid, 'allowattach')) {
                        unlink($file);
                        continue;
                    }
                }

                // 内容附件 将文件移动到 upload/website_attach 目录
                $filename = file_name($file['url']);

                // hook model_attach_assoc_file_foreach_before.php

                // 绝对路径
                $destpath = $upload_path . '/' . $filename;
                // 相对路径
                $desturl = $upload_url . '/' . $filename;
                $desturl = str_replace('/upload/', 'upload/', $desturl);
                // 复制
                copy($file['path'], $destpath);

                // hook model_attach_assoc_file_copy_after.php

                if (is_file($destpath) && filesize($destpath) == filesize($file['path'])) unlink($file['path']);

                // hook model_attach_assoc_file_arr_before.php

                $attacharr = array(
                    /*'tid' => $tid,
                    'pid' => $pid,*/
                    'uid' => $uid,
                    'filesize' => $file['filesize'],
                    'width' => $file['width'],
                    'height' => $file['height'],
                    'filename' => $day . '/' . $filename,
                    'orgfilename' => $file['orgfilename'],
                    //'image_url' => '', // 图床文件完整网址
                    'filetype' => $file['filetype'],
                    'create_date' => $time,
                    'isimage' => $file['isimage']
                );

                // 按照$destpath文件路径，上传至云储存或图床，返回数据.附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表、内容表、评论表字段 attach_on 为 1或2。关联附件上传云储存，有可能导致超时。

                // hook model_attach_assoc_file_create_attach.php

                $tid and $attacharr += $pid ? array('pid' => $pid) : array('tid' => $tid);

                // hook model_attach_assoc_file_create_before.php

                $attach[] = $attacharr;

                $message = str_replace(array('="../upload/', '="/upload/'), '="upload/', $message);
                $message = str_replace($file['url'], $desturl, $message);

                // hook model_attach_assoc_file_foreach_end.php
            }

            // hook model_attach_assoc_session_end.php

        } else {

            // session 丢失，则只关联图片，忽略附件
            $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] : '';

            // hook model_attach_assoc_image_path_start.php

            foreach ($uploadlist as $file) {

                $filename = file_name($file);
                // 绝对路径
                $file = $conf['upload_path'] . 'tmp/' . $filename;

                if (!is_file($file)) continue;

                // 没有附件权限
                if (($fid && !forum_access_user($fid, $gid, 'allowattach')) && !group_access($gid, 'allowattach')) {
                    unlink($file);
                    continue;
                }

                // hook model_attach_assoc_image_path_before.php

                // 内容附件 将文件移动到 upload/website_attach 目录
                // 绝对路径
                $destfile = $upload_path . '/' . $filename;
                // 相对路径 upload/website_attach/1_D34JFMJTW3NXSZR.jpeg
                $desturl = $upload_url . '/' . $filename;
                $desturl = str_replace('/upload/', 'upload/', $desturl);
                // 内容中图片缓存路径 upload/tmp/1_D34JFMJTW3NXSZR.jpeg
                $tmpurl = $conf['upload_url'] . 'tmp/' . $filename;

                // hook model_attach_assoc_image_path_copy.php

                copy($file, $destfile);

                unlink($file);

                // 按照$destfile文件路径，上传至云储存或图床，返回数据。附件分离最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url，根据tid更新主题表、内容表、评论表字段 attach_on 为 1或2。关联附件上传云储存，有可能导致超时。

                $getimgsize = getimagesize($file);
                $attach_arr = array('uid' => $uid, 'filesize' => filesize($file), 'width' => $getimgsize['width'], 'height' => $getimgsize['height'], 'filename' => $day . '/' . $filename, 'orgfilename' => '', 'filetype' => $getimgsize['filetype'], 'create_date' => $time, 'isimage' => 1);

                $tid and $attach_arr += $pid ? array('pid' => $pid) : array('tid' => $tid);

                // hook model_attach_assoc_image_path_attach.php

                $attach[] = $attach_arr;

                // hook model_attach_assoc_image_path_message_before.php
                $message = str_replace(array('="../upload/', '="/upload/'), '="upload/', $message);
                $message = str_replace($tmpurl, $desturl, $message);

                // hook model_attach_assoc_image_path_message_after.php
            }

            // hook model_attach_assoc_image_path_end.php
        }

        // hook model_attach_assoc_image_upload_after.php
    }

    // hook model_attach_assoc_image_upload_end.php

    !empty($attach) and attach_big_insert($attach);

    // hook model_attach_assoc_image_filter_start.php

    $attachlist = $imagelist = $filelist = '';
    $images = 0;
    $files = 0;
    // 处理不在 message 中的图片，删除掉没有插入的附件
    if ($message) {
        // 只有评论会传pid
        list($attachlist, $imagelist, $filelist) = $pid ? well_attach_find_by_pid($pid) : well_attach_find_by_tid($tid);

        // hook model_attach_assoc_image_filter_before.php

        if (!empty($imagelist)) {
            $aids = array();
            foreach ($imagelist as $key => $attach) {

                $image_url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];

                // hook model_attach_assoc_image_filter_delete_before.php

                if (FALSE === strpos($message, $image_url)) {

                    unset($attachlist[$attach['aid']], $imagelist[$attach['aid']]);

                    $aids[] = $attach['aid'];

                    $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
                    is_file($path) and unlink($path);

                    // hook model_attach_assoc_file_filter_delete.php

                    // 删除云储存文件
                }

                // hook model_attach_assoc_image_filter_center.php
            }

            !empty($aids) and well_attach__delete($aids);

            // hook model_attach_assoc_image_filter_middle.php
        }

        $images = count($imagelist);
        $files = count($filelist);

        // hook model_attach_assoc_image_filter_after.php
    }

    // hook model_attach_assoc_image_filter_end.php

    $return += array('tid' => $tid, 'pid' => $pid, 'icon' => $icon, 'message' => $message, 'images' => $images, 'files' => $files);

    // hook model_attach_assoc_image_end.php

    return $return;
}

// 关联内容中的文件，逐渐放弃使用该函数，使用 well_attach_assoc_handle()
function well_attach_assoc_file($arr = array())
{
    global $conf, $time;

    // hook model_attach_assoc_file_start.php

    $uid = array_value($arr, 'uid', 0);
    $tid = array_value($arr, 'tid', 0);
    $post_create = array_value($arr, 'post_create', 0); // 创建回复
    $pid = array_value($arr, 'pid', 0);
    $images = array_value($arr, 'images', 0);
    $files = array_value($arr, 'files', 0);
    $message = array_value($arr, 'message');

    if (!$tid && !$pid) return $message;

    // hook model_attach_assoc_file_before.php

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');
    $day = date($attach_dir_save_rule, $time);
    $path = $conf['upload_path'] . 'website_attach/' . $day;
    $url = $conf['upload_url'] . 'website_attach/' . $day;
    is_dir($path) || mkdir($path, 0777, TRUE);

    if (!empty($arr['sess_tmp_files'])) {

        $message = urldecode($message);
        preg_match_all('#<img[^>]+src="(.*?)"#i', $message, $match);

        $localurlarr = array(
            'http://' . $_SERVER['SERVER_NAME'] . '/',
            'https://' . $_SERVER['SERVER_NAME'] . '/',
        );

        // 跳过云储存
        $conf['cloud_url'] and $localurlarr[] = $conf['cloud_url'];

        // hook model_attach_assoc_file_localurl.php

        /*$match[1]
            Array
            (
                [0] => https://nimg.ws.126.net/?url=http://dingyue.ws.126.net/2021/1109/d3321a58j00r29xq20013c000hk00h4g.jpg&thumbnail=650x2147483647&quality=80&type=jpg
                [1] => https://nimg.ws.126.net/?url=http://dingyue.ws.126.net/2021/1109/e4974b87j00r29xq30013c000fi00i3g.jpg&thumbnail=650x2147483647&quality=80&type=jpg
                [2] => upload/tmp/1_KAUXKFMJHFTUMNS.jpg
                [3] => https://nimg.ws.126.net/?url=http://dingyue.ws.126.net/2021/1109/5a7be552j00r29xq3001qc000hs00hvg.jpg&thumbnail=650x2147483647&quality=80&type=jpg
            )*/
        $upload_arr = array();
        $http_arr = array();
        if (!empty($match[1])) {
            foreach ($match[1] as $_url) {

                foreach ($localurlarr as $localurl) {
                    if ($localurl == substr($_url, 0, strlen($localurl))) continue 2;
                }

                if (substr($_url, 0, 7) == 'http://' || substr($_url, 0, 8) == 'https://') {
                    $http_arr[] = $_url;
                } elseif (substr($_url, 0, 11) == 'upload/tmp/' || substr($_url, 0, 12) == '/upload/tmp/' || substr($_url, 0, 14) == '../upload/tmp/') {
                    $upload_arr[] = $_url;
                }
            }
        }

        // hook model_attach_assoc_file_center.php

        $attach = array();
        foreach ($arr['sess_tmp_files'] as $file) {

            // 过滤非内容图，不包括附件
            if (!in_array($file['url'], $upload_arr) && 0 != $file['isimage']) {
                unlink($file['path']);
                continue;
            }

            // hook model_attach_assoc_file_foreach_start.php

            // 后台提交的内容需要替换掉../
            $file['url'] = $file['backstage'] ? str_replace('../upload/', 'upload/', $file['url']) : str_replace('/upload/', 'upload/', $file['url']);

            // hook model_attach_assoc_file_foreach_before.php

            // 内容附件 将文件移动到 upload/website_attach 目录
            $filename = file_name($file['url']);

            // hook model_attach_assoc_file_path_after.php

            // 绝对路径
            $destfile = $path . '/' . $filename;
            // 相对路径
            $desturl = $url . '/' . $filename;
            // 复制
            xn_copy($file['path'], $destfile) || xn_log("xn_copy($file[path]), $destfile) failed, tid:$tid, pid:$pid", 'php_error');

            // hook model_attach_assoc_file_copy_after.php

            if (is_file($destfile) && filesize($destfile) == filesize($file['path'])) unlink($file['path']);

            // 按照$destfile文件路径，上传至云储存或图床，返回数据.附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url自动，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。

            // hook model_attach_assoc_file_arr_before.php

            $attacharr = array(
                /*'tid' => $tid,
                'pid' => $pid,*/
                'uid' => $uid,
                'filesize' => $file['filesize'],
                'width' => $file['width'],
                'height' => $file['height'],
                'filename' => $day . '/' . $filename,
                'orgfilename' => $file['orgfilename'],
                //'image_url' => '', // 图床文件完整网址
                'filetype' => $file['filetype'],
                'create_date' => $time,
                'isimage' => $file['isimage']
            );

            $tid and $attacharr += $pid ? array('pid' => $pid) : array('tid' => $tid);

            // hook model_attach_assoc_file_create_before.php

            $attach[] = $attacharr;

            // 关联内容再入库
            //$aid = well_attach_create($attach);

            $file['backstage'] and $message = str_replace('../upload/', 'upload/', $message);
            $message = str_replace($file['url'], $desturl, $message);

            // hook model_attach_assoc_file_foreach_end.php
        }

        !empty($attach) and attach_big_insert($attach);

        // hook model_attach_assoc_file_middle.php

        // 清空 session
        $_SESSION['tmp_website_files'] = array();
    }

    // hook model_attach_assoc_file_filter_start.php

    // 更新附件数
    $update = array();
    $_images = 0;
    $_files = 0;
    // 处理不在 message 中的图片，删除掉没有插入的图片附件
    if ($message) {

        // 只有评论会传pid
        list($attachlist, $imagelist, $filelist) = $pid ? well_attach_find_by_pid($pid) : well_attach_find_by_tid($tid);

        // hook model_attach_assoc_file_filter_before.php

        if (!empty($imagelist)) {
            $aids = array();
            foreach ($imagelist as $key => $attach) {

                $url = $conf['upload_url'] . 'website_attach/' . $attach['filename'];

                // hook model_attach_assoc_file_filter_delete_before.php

                if (FALSE === strpos($message, $url)) {
                    unset($attachlist[$attach['aid']], $imagelist[$attach['aid']]);

                    $aids[] = $attach['aid'];

                    $path = $conf['upload_path'] . 'website_attach/' . $attach['filename'];
                    is_file($path) and unlink($path);

                    // hook model_attach_assoc_file_filter_delete.php

                    // 删除云储存文件
                }

                // hook model_attach_assoc_file_filter_center.php
            }

            !empty($aids) and well_attach__delete($aids);

            // hook model_attach_assoc_file_filter_middle.php
        }

        $_images = count($imagelist);
        $images != $_images and $update['images'] = $_images;

        $_files = count($filelist);
        $files != $_files and $update['files'] = $_files;

        // hook model_attach_assoc_file_filter_after.php
    }

    // hook model_attach_assoc_file_filter_end.php

    if (empty($update)) return $pid ? array($message, $_images, $_files) : $message;

    if ($pid) {
        if ($post_create) {
            $update['message'] = $message;
            comment__update($pid, $update);
        } else {
            // 编辑回复返回的数据
            return array($message, $_images, $_files);
        }
    } else {
        well_thread_update($tid, $update);
    }

    // hook model_attach_assoc_file_end.php

    return $message;
}

// thumbnail:主题主图 post:内容图片或附件
function well_attach_assoc_type($type)
{
    // hook model__attach_assoc_type_start.php
    switch ($type) {
        case 'thumbnail':
            $k = 'tmp_thumbnail';
            break;
        case 'post':
            $k = 'tmp_website_files';
            break;
        // hook model__attach_assoc_case_end.php
        default:
            return NULL;
            break;
    }
    $sess_tmp_files = _SESSION($k);
    // 如果session中没有，从数据库中获取储存的session
    //if (empty($sess_tmp_files) && preg_match('#' . $k . '\|(a\:1\:\{.*\})#', _SESSION('data'), $matches)) $sess_tmp_files = unserialize(str_replace(array('+', '='), array('_', '.'), $matches['1']));
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

    $attachlist = well_attach_assoc_type('post');
    if (empty($attachlist)) return;

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

    $day = date($attach_dir_save_rule, $time);
    $path = $conf['upload_path'] . 'thumbnail/' . $day;
    is_dir($path) || mkdir($path, 0777, TRUE);

    $tmp_file = $conf['upload_path'] . 'tmp/' . $uid . '_' . $tid . '_' . $time . '.jpeg';

    $i = 0;
    foreach ($attachlist as $val) {
        ++$i;
        if (1 == $val['isimage'] && 1 == $i) {
            'clip' == array_value($conf, 'upload_resize', 'clip') ? well_image_clip_thumb($val['path'], $tmp_file, $pic_width, $pic_height) : well_image_thumb($val['path'], $tmp_file, $pic_width, $pic_height);
            break;
        }
    }
    $destfile = $path . '/' . $uid . '_' . $tid . '_' . $time . '.jpeg';
    xn_copy($tmp_file, $destfile) || xn_log("xn_copy($tmp_file), $destfile) failed, tid:$tid", 'php_error');
}

function well_save_remote_image($arr)
{
    global $conf, $time, $forumlist, $config;

    // hook model_save_remote_image_start.php

    $message = array_value($arr, 'message');
    $tid = array_value($arr, 'tid', 0);
    $fid = array_value($arr, 'fid', 0);
    $uid = array_value($arr, 'uid', 0);
    $thumbnail = array_value($arr, 'thumbnail', 0);
    $save_image = array_value($arr, 'save_image', 0);

    $attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');

    $day = date($attach_dir_save_rule, $time);
    $attach_dir = $conf['upload_path'] . 'website_attach/' . $day . '/';
    $attach_url = $conf['upload_url'] . 'website_attach/' . $day . '/';
    is_dir($attach_dir) || mkdir($attach_dir, 0777, TRUE);

    // hook model_save_remote_image_before.php

    if ($thumbnail) {

        $picture = $config['picture_size'];
        $forum = array_value($forumlist, $fid);
        $picture = isset($forum['thumbnail']) ? $forum['thumbnail'] : $picture['picture_size'];
        $pic_width = $picture['width'];
        $pic_height = $picture['height'];

        $thumbnail_path = $conf['upload_path'] . 'thumbnail/' . $day . '/';
        is_dir($thumbnail_path) || mkdir($thumbnail_path, 0777, TRUE);

        $tmp_file = $thumbnail_path . $uid . '_' . $tid . '_' . $time . '.jpeg';
    }

    $localurlarr = array(
        'http://' . $_SERVER['SERVER_NAME'] . '/',
        'https://' . $_SERVER['SERVER_NAME'] . '/',
    );
    // 跳过云储存
    $conf['cloud_url'] and $localurlarr[] = $conf['cloud_url'];

    // hook model_save_remote_image_center.php

    //$save_image_quality = array_value($conf, 'save_image_quality', 0);
    $save_image_quality = 0;

    $message = urldecode($message);
    //$message = str_replace('&amp;', '&', $message);
    //$message = htmlspecialchars_decode($message);
    preg_match_all('#<img[^>]+src="(http.*?)"#i', $message, $match);

    // hook model_save_remote_image_middle.php

    if (!empty($match[1])) {
        $n = 0;
        $i = 0;
        foreach ($match[1] as $url) {

            foreach ($localurlarr as $localurl) {
                if ($localurl == substr($url, 0, strlen($localurl))) continue 2;
            }

            $full_url = htmlspecialchars_decode($url);
            $message = str_replace($url, $full_url, $message);
            $getimgsize = getimagesize($full_url);
            if (FALSE === $getimgsize) continue; // 非图片跳出

            $filename = $uid . '_' . xn_rand(16);
            if (1 == $getimgsize[2]) {
                $filename .= '.gif';
                $destpath = $attach_dir . $filename;
            } elseif (in_array($getimgsize[2], array(2, 3, 15, 18))) {
                $filename .= '.jpeg';
                $destpath = $attach_dir . $filename;
            } else {
                continue; // 非常见图片格式跳出
            }

            $desturl = $attach_url . $filename;
            $_message = str_replace($full_url, $desturl, $message);

            if ($message != $_message) {

                if ($save_image) {
                    if (0 == $save_image_quality) {
                        $imgdata = https_request($full_url);
                        //$destpath = $attach_dir . $filename;
                        file_put_contents_try($destpath, $imgdata);
                    } else {
                        // 图片压缩 GD 库效率低下 ImageMagick 需要额外安装扩展
                        switch ($getimgsize[2]) {
                            case 1: // GIF
                                $imgdata = imagecreatefromgif($full_url);
                                break;
                            case 2: // JPG
                                $imgdata = imagecreatefromjpeg($full_url);
                                break;
                            case 3: // PNG
                                $imgdata = imagecreatefrompng($full_url);
                                break;
                            case 15: // WBMP
                                $imgdata = imagecreatefromwbmp($full_url);
                                break;
                            case 18: // WEBP
                                $imgdata = imagecreatefromwebp($full_url);
                                break;
                        }
                        imagejpeg($imgdata, $destpath, $save_image_quality);
                        imagedestroy($imgdata);
                    }
                }

                // 创建缩略图
                if ($thumbnail) {

                    if (1 == ++$i) {

                        if (empty($save_image)) {
                            $imgdata = https_request($full_url);
                            file_put_contents_try($destpath, $imgdata);
                        }

                        // 裁切保存到缩略图目录
                        'clip' == array_value($conf, 'upload_resize', 'clip') ? well_image_clip_thumb($destpath, $tmp_file, $pic_width, $pic_height, $getimgsize) : well_image_thumb($destpath, $tmp_file, $pic_width, $pic_height, $getimgsize);
                        well_thread_update($tid, array('icon' => $time));
                    }

                    if (empty($save_image)) {
                        is_file($destpath) and unlink($destpath);
                        continue;
                    }
                }

                $filesize = strlen($imgdata);
                $attach = array('tid' => $tid, 'uid' => $uid, 'filesize' => $filesize, 'width' => $getimgsize[0], 'height' => $getimgsize[1], 'filename' => "$day/$filename", 'orgfilename' => $filename, 'filetype' => 'image', 'create_date' => $time, 'downloads' => 0, 'isimage' => 1);
                $aid = well_attach_create($attach);
                $n++;
            }

            $message = preg_replace('#(<img.*?)(class=.+?[\'|\"])|(data-src=.+?[\'|"])|(data-type=.+?[\'|"])|(data-ratio=.+?[\'|"])|(data-s=.+?[\'|"])|(data-fail=.+?[\'|"])|(crossorigin=.+?[\'|"])|((data-w)=[\'"]+[0-9]+[\'"]+)|(_width=.+?[\'|"]+)|(_height=.+?[\'|"]+)|(style=.+?[\'|"])|((width)=[\'"]+[0-9]+[\'"]+)|((height)=[\'"]+[0-9]+[\'"]+)#i', '$1', $_message);
        }
        // hook model_attach_save_remote_image_after.php
        $n and well_thread_update($tid, array('images+' => $n));
    }

    // hook model_save_remote_image_end.php
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