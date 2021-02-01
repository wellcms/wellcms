<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') and exit('Access Denied.');

$action = param(1);
$t = param('t', 0); // 0 CMS上传/1 BBS上传
/*
 * 附件分离，最优方案是redis队列，单独写上传云储存php文件，nohup后台运行，将队列数据上传云储存，然后根据aid更新附件表attach_on、image_url，根据tid更新主题表attach_on。关联附件上传云储存，有可能导致超时。
 *
 * 秒传在上传时完成，不创建session数据
 *
 * 关联->复制文件到目录->上传云储存->创建或更新附件表和主题表->检查message中是否有未上传云储存的图片->上传云储存->创建或更新附件表和主题表->替换message中图片为相对路径->删除不在内容中的图片
 *
 * 1.创建附件，保存本地，不上传云储存；
 * 2.入库时上传云储存，之后完成一系列业务逻辑；
 * 如果一个主题中即含有分离的图片和本地图片地址，在编辑时，会将本地的删除
 * */

// hook attach_start.php

switch ($action) {
    // hook attach_case_start.php
    case 'create':

        user_login_check();

        // 验证token
        if (array_value($conf, 'upload_token', 0)) {
            $safe_token = param('safe_token');
            FALSE === well_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));
        }

        $fid = param('fid', 0);
        if ($fid) {
            $allowattach = forum_access_user($fid, $gid, 'allowattach');
            empty($allowattach) && $gid != 1 and message(-1, lang('user_group_insufficient_privilege'));
        } else {
            empty($group['allowattach']) && $gid != 1 and message(-1, lang('user_group_insufficient_privilege'));
        }

        // hook attach_create_start.php

        $backstage = param(2, 0); // 0前台/1后台
        $width = param('width', 0);
        $height = param('height', 0);
        $is_image = param('is_image', 0); // 图片
        $name = param('name');
        $data = param_base64('data');
        $mode = param('mode'); // 上传类型 0内容图片和附件 1主图
        $n = param('n', 0); // 对应主图赋值

        switch ($mode) {
            case '1':
                $key = 'tmp_thumbnail';
                break;
            // hook attach_assoc_case_after.php
            default:
                if (empty($t)) {
                    $key = 'tmp_website_files';
                }
                // hook attach_create_after.php
                break;
        }

        sess_restart();

        !isset($_SESSION[$key]) and $_SESSION[$key] = array();
        
        // 超过30则删除之前上传的所有附件
        if (count($_SESSION[$key]) > 50) {
            foreach ($_SESSION[$key] as $_file) is_file($_file['path']) and unlink($_file['path']);
            $_SESSION[$key] = array();
        }

        // hook attach_create_before.php

        empty($data) and message(1, lang('data_is_empty'));
        $size = strlen($data);
        $size > 20480000 and message(1, lang('filesize_too_large', array('maxsize' => '20M', 'size' => $size)));

        // hook attach_create_center.php

        $ext = strtolower(file_ext($name, 7));
        $filetypes = include APP_PATH . 'conf/attach.conf.php';

        if (1 == $mode && !in_array($ext, $filetypes['image'], TRUE)) message(1, lang('data_malformation'));

        // hook attach_create_file_ext_before.php

        // 主图为图片 附件文件后缀不在规定范围内 改变后缀名
        1 == $mode ? $ext = 'jpeg' : (!in_array($ext, $filetypes['all'], TRUE) and $ext = '_' . $ext);

        // hook attach_create_file_ext_after.php

        $tmpanme = $uid . '_' . xn_rand(15) . '.' . $ext;
        $tmpfile = $conf['upload_path'] . 'tmp/' . $tmpanme;
        $tmpurl = $conf['upload_url'] . 'tmp/' . $tmpanme;

        // hook attach_create_tmpurl_after.php

        $filetype = attach_type($name, $filetypes);

        // hook attach_create_save_before.php

        file_put_contents($tmpfile, $data) or message(1, lang('write_to_file_failed'));

        // hook attach_create_save_after.php
    
        // $mode = 0内容图片和附件按照SESSION数组附件数量统计，1主图按照传入的n数值
        empty($mode) and $n = count($_SESSION[$key]);

        $attach = array(
            'backstage' => $backstage, // 0前台 1后台
            'url' => $backstage ? '../' . $tmpurl : ($conf['url_rewrite_on'] > 1 ? $conf['path'] : '') . $tmpurl,
            'path' => $tmpfile,
            'orgfilename' => $name,
            'filetype' => $filetype,
            'filesize' => filesize($tmpfile),
            'width' => $width,
            'height' => $height,
            'isimage' => $is_image,
            'downloads' => 0,
            'aid' => '_' . $n
        );

        // hook attach_create_array_after.php

        if (empty($mode)) {
            // 内容图和附件
            // hook attach_create_website_files_beofre.php
            $_SESSION[$key][$n] = $attach;
            // hook attach_create_website_files_after.php
        } elseif (1 == $mode) {
            // 缩略图 / 主图
            $thumbnail = _SESSION($key);
            isset($thumbnail['path']) and is_file($thumbnail['path']) and unlink($thumbnail['path']);
            // hook attach_create_thumbnail_beofre.php
            $_SESSION[$key] = $attach;
            // hook attach_create_thumbnail_after.php
        }

        // hook attach_create_session_after.php

        unset($attach['path']);

        // hook attach_create_end.php

        message(0, $attach);
        break;
    case 'delete':
        user_login_check();

        // hook attach_delete_start.php

        $aid = param(2);

        // hook attach_delete_before.php

        // 临时的文件 id / temp attach id : _0 _1 _2 _3 ...
        if ('_' == substr($aid, 0, 1)) {
            $key = intval(substr($aid, 1));
            if (empty($t)) {
                $tmp_files = _SESSION('tmp_website_files');
            }
            // hook attach_delete_after.php
            isset($tmp_files[$key]) || message(1, lang('item_not_exists', array('item' => $key)));

            $attach = $tmp_files[$key];
            is_file($attach['path']) ? unlink($attach['path']) : message(1, lang('file_not_exists'));

            if (empty($t)) {
                unset($_SESSION['tmp_website_files'][$key]);
            }
            // hook attach_delete_center.php
        } else {
            $aid = intval($aid);
            if (empty($t)) {
                $attach = well_attach_read($aid);
                $thread = well_thread_read($attach['tid']);
            }
            // hook attach_delete_middle.php
            empty($attach) and message(-1, lang('attach_not_exists'));
            empty($thread) and message(-1, lang('thread_not_exists'));
            // hook attach_delete_thread_after.php
            $allowdelete = forum_access_mod($thread['fid'], $gid, 'allowdelete');
            $attach['uid'] != $uid && !$allowdelete and message(0, lang('insufficient_privilege'));
            if (empty($t)) {
                FALSE === well_attach_delete($aid) and message(-1, lang('delete_failed'));
                well_thread_update($thread['tid'], array('files-' => 1));
            }
            // hook attach_delete_aid_after.php
        }

        // hook attach_delete_end.php

        message(0, 'delete_successfully');
        break;
    case 'download':
        // hook attach_download_start.php

        // 判断权限
        $aid = param(2, 0);

        if (empty($t)) {
            $attach = well_attach_read($aid);
            empty($attach) and message(-1, lang('attach_not_exists'));
            $thread = well_thread_read($attach['tid']);
            $path = 'website_attach/';
        }
        // hook attach_download_before.php
        $allowdown = forum_access_user($thread['fid'], $gid, 'allowdown');
        empty($allowdown) and message(2, lang('insufficient_privilege_to_download'));

        // hook attach_output_before.php

        if (1 == $conf['attach_on']) {
            $attachurl = $conf['cloud_url'] . $conf['upload_url'] . $path . $attach['filename'];
        } elseif (2 == $conf['attach_on']) {
            $attachurl = empty($attach['image_url']) ? $conf['upload_url'] . $path . $attach['filename'] : $attach['image_url'];
        } else {
            $attachpath = $conf['upload_path'] . $path . $attach['filename'];
            is_file($attachpath) || message(1, lang('attach_not_exists'));
            $attachurl = $conf['upload_url'] . $path . $attach['filename'];
        }

        $type = 'php';

        // hook attach_output_after.php

        // php 输出
        if ('php' == $type) {
            // hook attach_download_update_before.php
            if (empty($t)) {
                well_attach_update($aid, array('downloads+' => 1));
            }
            // hook attach_download_update_after.php
            if (FALSE !== stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE') || FALSE !== stripos($_SERVER["HTTP_USER_AGENT"], 'Edge') || FALSE !== stripos($_SERVER["HTTP_USER_AGENT"], 'Trident')) {
                $attach['orgfilename'] = urlencode($attach['orgfilename']);
                $attach['orgfilename'] = str_replace("+", "%20", $attach['orgfilename']);
            }
            $timefmt = date('D, d M Y H:i:s', $time) . ' GMT';
            header('Date: ' . $timefmt);
            header('Last-Modified: ' . $timefmt);
            header('Expires: ' . $timefmt);
            header('Cache-control: max-age=86400');
            header('Content-Transfer-Encoding: binary');
            header("Pragma: public");
            header('Content-Disposition: attachment; filename="' . $attach['orgfilename'] . '"');
            header('Content-Type: application/octet-stream');
            // hook attach_download_readfile_before.php
            readfile($attachpath);
            exit;
        } else {
            // hook attach_download_location_before.php
            http_location($attachurl);
        }
        break;
    // hook attach_case_end.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook attach_end.php

?>