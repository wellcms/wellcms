<?php
/*
 * Copyright (C) 2018 www.wellcms.cn
 */
// ------------> 最原生的 CURD，无关联其他数据。

// hook model_operate_start.php

function operate__create($arr, $d = NULL)
{
    // hook model_operate__create_start.php
    $r = db_insert('website_operate', $arr, $d);
    // hook model_operate__create_end.php
    return $r;
}

function operate__update($logid, $arr, $d = NULL)
{
    // hook model_operate__update_start.php
    $r = db_update('website_operate', array('logid' => $logid), $arr, $d);
    // hook model_operate__update_end.php
    return $r;
}

function operate__read($logid, $orderby = array(), $col = array(), $d = NULL)
{
    // hook model_operate__read_start.php
    $operate = db_find_one('website_operate', array('logid' => $logid), $orderby, $col, $d);
    // hook model_operate__read_end.php
    return $operate;
}

function operate__delete($logid, $d = NULL)
{
    // hook model_operate__delete_start.php
    $r = db_delete('website_operate', array('logid' => $logid), $d);
    // hook model_operate__delete_end.php
    return $r;
}

function operate__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'id', $col = array(), $d = NULL)
{
    // hook model_operate__find_start.php
    $operatelist = db_find('website_operate', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    // hook model_operate__find_end.php
    return $operatelist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function operate_create($arr)
{
    // hook model_operate_create_start.php
    $r = operate__create($arr);
    // hook model_operate_create_end.php
    return $r;
}

function operate_update($logid, $arr)
{
    if (empty($logid)) return FALSE;
    // hook model_operate_update_start.php
    $r = operate__update($logid, $arr);
    // hook model_operate_update_end.php
    return $r;
}

function operate_read($logid)
{
    // hook model_operate_read_start.php
    $operate = operate__read($logid);
    $operate AND operate_format($operate);
    // hook model_operate_read_end.php
    return $operate;
}

function operate_delete($logid)
{
    if (empty($logid)) return FALSE;
    // hook model_operate_delete_start.php
    $r = operate__delete($logid);
    // hook model_operate_delete_end.php
    return $r;
}

function operate_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20)
{
    // hook model_operate_find_start.php
    $operatelist = operate__find($cond, $orderby, $page, $pagesize);
    if ($operatelist) {
        $i = 0;
        foreach ($operatelist as &$operate) {
            ++$i;
            $v['i'] = $i;
            operate_format($operate);
        }
    }
    // hook model_operate_find_end.php
    return $operatelist;
}

// ----------------> 其他方法

function operate_format(&$operate)
{
    global $conf;
    // hook model_operate_format_start.php
    $operate['create_date_fmt'] = date('Y-n-j', $operate['create_date']);
    // hook model_operate_format_end.php
}

function operate_count($cond = array())
{
    // hook model_operate_count_start.php
    $n = db_count('website_operate', $cond);
    // hook model_operate_count_end.php
    return $n;
}

function operate_maxid()
{
    // hook model_operate_maxid_start.php
    $n = db_maxid('website_operate', 'logid');
    // hook model_operate_maxid_end.php
    return $n;
}

// hook model_operate_end.php

?>