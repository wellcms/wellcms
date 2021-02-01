<?php
/*
 * Copyright (C) www.wellcms.cn
*/
// hook model__tag_start.php
// ------------> 最原生的 CURD，无关联其他数据。
function well_tag__create($arr = array(), $d = NULL)
{
    // hook model__tag__create_start.php
    $r = db_insert('website_tag', $arr, $d);
    // hook model__tag__create_end.php
    return $r;
}

function well_tag__update($cond = array(), $update = array(), $d = NULL)
{
    // hook model__tag__update_start.php
    $r = db_update('website_tag', $cond, $update, $d);
    // hook model__tag__update_end.php
    return $r;
}

function well_tag__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    // hook model__tag__read_start.php
    $r = db_find_one('website_tag', $cond, $orderby, $col, $d);
    // hook model__tag__read_end.php
    return $r;
}

function well_tag__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tagid', $col = array(), $d = NULL)
{
    // hook model__tag__find_start.php
    $arr = db_find('website_tag', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model__tag__find_end.php
    return $arr;
}

function well_tag__delete($tagid, $d = NULL)
{
    // hook model__tag_delete_start.php
    $r = db_delete('website_tag', array('tagid' => $tagid), $d);
    // hook model__tag_delete_end.php
    return $r;
}

function well_tag__count($cond = array(), $d = NULL)
{
    // hook model__tag_count_start.php
    $n = db_count('website_tag', $cond, $d);
    // hook model__tag_count_end.php
    return $n;
}

function tag_max_tagid($col = 'tagid', $cond = array(), $d = NULL)
{
    // hook model_tag_max_tagid_start.php
    $tagid = db_maxid('website_tag', $col, $cond, $d);
    // hook model_tag_max_tagid_end.php
    return $tagid;
}

function tag_big_insert($arr = array(), $d = NULL)
{
    // hook model_tag_big_insert_start.php
    $r = db_big_insert('website_tag', $arr, $d);
    // hook model_tag_big_insert_end.php
    return $r;
}

function tag_big_update($cond = array(), $update = array(), $d = NULL)
{
    // hook model_tag_big_update_start.php
    $r = db_big_update('website_tag', $cond, $update, $d);
    // hook model_tag_big_update_end.php
    return $r;
}

//--------------------------强相关--------------------------
function well_tag_create($arr)
{
    if (empty($arr)) return FALSE;
    // hook model__tag_create_start.php
    $r = well_tag__create($arr);
    // hook model__tag_create_end.php
    return $r;
}

// 标签名查询
function well_tag_read_name($name)
{
    // hook model__tag_read_name_start.php
    $r = well_tag__read(array('name' => $name));
    $r and well_tag_format($r);
    // hook model__tag_read_name_end.php
    return $r;
}

// 标签tagid查询
function well_tag_read_tagid($tagid)
{
    // hook model__tag_read_tagid_start.php
    $r = well_tag__read(array('tagid' => $tagid));
    $r and well_tag_format($r);
    // hook model__tag_read_tagid_end.php
    return $r;
}

function well_tag_update($tagid, $update)
{
    global $conf;
    if (empty($tagid) || empty($update)) return FALSE;

    // hook model__tag_update_start.php

    $r = well_tag__update(array('tagid' => $tagid), $update);
    if (FALSE === $r) return FALSE;

    // hook model__tag_update_before.php

    if ('mysql' != $conf['cache']['type']) {
        if (is_array($tagid)) {
            foreach ($tagid as $_tagid) {
                cache_delete('web_tag_' . $_tagid);
            }
        } else {
            cache_delete('web_tag_' . $tagid);
        }
    }

    // hook model__tag_update_end.php

    return $r;
}

function well_tag_find($page, $pagesize, $desc = TRUE)
{
    // hook model__tag_find_start.php
    $orderby = TRUE == $desc ? -1 : 1;
    // hook model__tag_find_before.php
    $arrlist = well_tag__find(array(), array('tagid' => $orderby), $page, $pagesize);
    if (empty($arrlist)) return NULL;
    $i = 0;
    foreach ($arrlist as &$val) {
        ++$i;
        $val['i'] = $i;
        well_tag_format($val);
        // hook model__tag_find_after.php
    }
    // hook model__tag_find_end.php
    return $arrlist;
}

function well_tag_find_by_tagids($tagids, $page, $pagesize)
{
    // hook model__tag_find_by_tagids_start.php
    $arrlist = well_tag__find(array('tagid' => $tagids), array('tagid' => 1), $page, $pagesize);
    if (empty($arrlist)) return NULL;
    $i = 0;
    foreach ($arrlist as &$val) {
        ++$i;
        $val['i'] = $i;
        well_tag_format($val);
        // hook model__tag_find_by_tagids_after.php
    }
    // hook model__tag_find_by_tagids_end.php
    return $arrlist;
}

function well_tag_count()
{
    // hook model__tag_count_start.php
    $n = well_tag__count();
    // hook model__tag_count_end.php
    return $n;
}

function well_tag_delete($tagid)
{
    global $conf;
    if (empty($tagid)) return FALSE;
    // hook model__tag_delete_start.php

    $read = well_tag_read_tagid($tagid);
    if (empty($read)) return FALSE;

    // hook model__tag_delete_before.php

    $r = well_tag__delete($tagid);
    if (FALSE === $r) return FALSE;

    // hook model__tag_delete_after.php

    if ('mysql' != $conf['cache']['type']) {
        $key = 'web_tag_' . $tagid;
        cache_delete($key);
        $key = 'web_tag_' . md5($read['name']);
        cache_delete($key);
    }

    // hook model__tag_delete_end.php

    return $r;
}

function well_tag_format(&$val)
{
    global $conf;
    if (empty($val)) return;
    // hook model__tag_format_start.php
    $val['icon_fmt'] = $val['icon'] ? file_path() . 'website_tag/' . $val['tagid'] . '.png?' . $val['icon'] : '';
    $val['url'] = url('tag-' . $val['tagid'], '', FALSE);
    // hook model__tag_format_end.php
}

//--------------------------cache--------------------------

function well_tag_read_by_tagid_cache($tagid)
{
    global $conf;
    // hook model__tag_read_by_tagid_cache_start.php
    $key = 'web_tag_' . $tagid;
    // hook model__tag_read_by_tagid_cache_before.php
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = well_tag_read_tagid($tagid);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = well_tag_read_tagid($tagid);
            $r and cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    // hook model__tag_read_by_tagid_cache_end.php
    return $cache[$key];
}

function well_tag_read_by_name_cache($name)
{
    global $conf;
    // hook model__tag_read_by_name_cache_start.php
    if (empty($name)) return NULL;
    $key = 'web_tag_' . md5($name);
    // hook model__tag_read_by_name_cache_before.php
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = well_tag_read_name($name);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = well_tag_read_name($name);
            $r and cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    // hook model__tag_read_by_name_cache_end.php
    return $cache[$key];
}

//--------------其他方法-------------

// 标签预处理 一般出入的是数组
function well_tag_post($tid, $fid, $str)
{
    // hook model_tag_post_start.php

    if (empty($str)) return '';

    $arr = explode(',', $str);
    $arr = array_filter($arr);
    if (empty($arr)) return '';
    $arr = array_unique($arr);

    // hook model_tag_post_end.php

    // $tags中的tagid和帖子tid入库 创建帖子时json入库主题附表
    return well_tag_process($tid, $fid, $arr);
}

// 修改内容标签预处理 $newtag数组, $oldtag旧的json数据
function well_tag_post_update($tid, $fid, $newtag, $oldtag)
{
    // hook model_tag_post_update_start.php

    // 如果旧的tag为空 直接创建新标签
    if (empty($oldtag)) return well_tag_post($tid, $fid, $newtag);

    // hook model_tag_post_update_before.php

    // json旧标签
    if (!is_array($oldtag)) {
        $oldtag = xn_json_decode($oldtag);
        is_array($oldtag) || $oldtag = array();
    }

    // hook model_tag_post_update_center.php

    $newtag = explode(',', $newtag);
    $newtag = array_filter($newtag);
    $newtag = array_unique($newtag);

    // hook model_tag_post_update_middle.php

    //if (empty($newtag)) return '';

    $create_tag = array();
    $tagarr = array();
    if (!empty($newtag)) {
        foreach ($newtag as $tagname) {
            // 搜索数组键值，并返回对应的键名
            $tagname = filter_all_html($tagname);
            $key = array_search($tagname, $oldtag);
            if (FALSE === $key) {
                // 创建新数组$new_tags
                $create_tag[] = $tagname;
            } else {
                // 保留的旧标签
                $tagarr[$key] = $tagname;
                // 销毁旧数组保留的标签 余下为需要删除的标签
                unset($oldtag[$key]);
            }
        }
    }
    // hook model_tag_post_update_after.php

    if (!empty($oldtag)) {
        $tagids = array();
        foreach ($oldtag as $tagid => $tagname) {
            $tagids[] = $tagid;
        }

        well_oldtag_delete($tagids, $tid);
    }

    $r = well_tag_process($tid, $fid, $create_tag, $tagarr);

    // hook model_tag_post_update_end.php

    return $r;
}

// 删除标签和绑定的主题
function well_oldtag_delete($tagids, $tid)
{
    // hook model_tag_oldtag_delete_start.php

    $pagesize = count($tagids);
    $arrlist = well_tag_find_by_tagids($tagids, 1, $pagesize);

    // hook model_tag_oldtag_delete_before.php

    $delete_tagids = array(); // 删除
    $tagids = array();
    $n = 0;
    foreach ($arrlist as $val) {
        ++$n;
        // hook model_tag_oldtag_delete_foreach_start.php
        if (1 == $val['count']) {
            // 只有一个主题
            $delete_tagids[] = $val['tagid'];
            // hook model_tag_oldtag_delete_foreach_after.php
        } else {
            $tagids[] = $val['tagid'];
        }
        // hook model_tag_oldtag_delete_foreach_end.php
    }

    // hook model_tag_oldtag_delete_after.php

    !empty($delete_tagids) and well_tag_delete($delete_tagids);

    $arlist = well_tag_thread_find_by_tid($tid, 1, $n);
    if ($arlist) {
        $ids = array();
        foreach ($arlist as $val) $ids[] = $val['id'];

        well_tag_thread_delete($ids);
    }

    !empty($tagids) and well_tag_update($tagids, array('count-' => 1));

    // hook model_tag_oldtag_delete_end.php
}

// 标签数据处理 $arr=新提交的数组 $tagarr=保留的旧标签
function well_tag_process($tid, $fid, $new_tags = array(), $tagarr = array())
{
    if (empty($tid)) return '';

    // hook model_tag_process_start.php

    // 新标签处理入库
    if ($new_tags) {

        // hook model_tag_process_foreach_start.php

        $threadarr = array();
        $tagids = array();
        $i = 0;
        $size = 5;
        $n = count($tagarr);
        $n = $n > $size ? $size : $size - $n;

        // hook model_tag_process_foreach_before.php

        foreach ($new_tags as $name) {
            ++$i;
            $name = strip_tags(trim($name));
            $name = htmlspecialchars($name);
            if ($name && $i <= $n) {
                // hook model_tag_process_read_name_before.php
                // 查询标签
                $read = well_tag_read_name($name);
                // hook model_tag_process_read_name_after.php
                if ($read) {
                    // 存在 count+1
                    $tagids[] = $read['tagid'];
                    // hook model_tag_process_read_name.php
                } else {
                    // 入库
                    $arr = array('name' => $name, 'count' => 1);
                    // hook model_tag_process_create.php
                    $tagid = well_tag_create($arr);
                    FALSE === $tagid and message(-1, lang('create_failed'));
                    $read = array('tagid' => $tagid, 'name' => $name);
                    // hook model_tag_process_create_after.php
                }

                $tag_thread = array('tagid' => $read['tagid'], 'tid' => $tid);

                // hook model_tag_process_before.php

                $threadarr[] = $tag_thread;

                // hook model_tag_process_center.php

                $tagarr[$read['tagid']] = $read['name'];
            }
        }

        // hook model_tag_process_middle.php

        !empty($threadarr) and tag_thread_big_insert($threadarr);

        !empty($tagids) and well_tag_update($tagids, array('count+' => 1));
    }

    // hook model_tag_process_after.php

    $json = empty($tagarr) ? '' : xn_json_encode($tagarr);

    // hook model_tag_process_end.php

    return $json;
}

// hook model__tag_end.php

?>