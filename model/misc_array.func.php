<?php
/*
 * Copyright (C) www.wellcms.cn
 */

// hook model_misc_array_start.php

// 对二维数组排序 $col排序列 $key索引键
function array_multisort_key($arrlist, $col, $asc = TRUE, $key = NULL)
{
    if (empty($arrlist)) return array();

    $colarr = array();
    foreach ($arrlist as $k => $v) {
        if (!isset($v[$col])) continue;
        $colarr[$k] = $v[$col];
    }

    if (empty($colarr)) return $arrlist;
    
    $asc = $asc ? SORT_ASC : SORT_DESC;
    array_multisort($colarr, $asc, $arrlist);
    unset($colarr);

    $key AND $arrlist = array_change_key($arrlist, $key);

    return $arrlist;
}

// 更改二维数组key
function array_change_key($arrlist, $key = NULL)
{
    if (empty($arrlist) || empty($key)) return $arrlist;
    $arr = array();
    foreach ($arrlist as $k => $v) {
        $arr[$v[$key]] = $v;
    }
    return $arr;
}

// 二维数组分页，对排序的整个数组分页获取数据
function array_pagination($arrlist, $page = 1, $pagesize = 20)
{
    if (empty($arrlist)) return array();

    $page = intval($page);
    $pagesize = intval($pagesize);

    // 输出开始位置 第二页开始 +1
    $start = ($page - 1) * $pagesize + ($page > 1 ? 1 : 0);
    // 输出结束位置 当前页数*每页数量
    $end = $page * $pagesize;

    $arr = array();
    $i = 0;
    foreach ($arrlist as $key => $val) {
        ++$i;
        if ($i >= $start && $i <= $end) {
            $arr[$key] = $val;
        }
    }

    return $arr;
}

// 倒叙 二维关联数组整理一维关联数组 col排序列 关联key=>value
function array_rank_key($arr = array(), $col = NULL, $key = NULL, $value = NULL)
{
    if (!empty($arr) && $col && $key && $value) {
        $arr = arrlist_multisort($arr, $col, FALSE);
        $arr = arrlist_key_values($arr, $key, $value);
    }
    return $arr;
}

// 移除二维数组中的重复的值，并返回结果数组。
function unique_array($array2D, $stkeep = FALSE, $ndformat = TRUE)
{
    // 判断是否保留一级数组键 (一级数组键可以为非数字)
    $starr = $stkeep ? array_keys($array2D) : array();
    // 判断是否保留二级数组键 (所有二级数组键必须相同)
    $ndarr = $ndformat ? array_keys(end($array2D)) : array();
    // 降维,也可以用implode,将一维数组转换为用逗号连接的字符串
    $temp = array();
    foreach ($array2D as $v) {
        $v = implode(",", $v);
        $temp[] = $v;
    }
    // 去掉重复的字符串,也就是重复的一维数组
    $temp = array_unique($temp);
    // 再将拆开的数组重新组装
    $output = array();
    foreach ($temp as $k => $v) {
        if ($stkeep) $k = $starr[$k];
        if ($ndformat) {
            $temparr = explode(",", $v);
            foreach ($temparr as $ndkey => $ndval) $output[$k][$ndarr[$ndkey]] = $ndval;
        } else $output[$k] = explode(",", $v);
    }
    return $output;
}

// 合并二维数组 如重复 值以第一个数组值为准
function array2_merge($array1, $array2, $key = '')
{
    if (empty($array1) || empty($array2)) return NULL;
    $arr = array();
    foreach ($array1 as $k => $v) {
        isset($v[$key]) ? $arr[$v[$key]] = array_merge($v, $array2[$k]) : $arr[] = array_merge($v, $array2[$k]);
    }
    return $arr;
}

/*
 * 对二维数组排序 两个数组必须有一个相同的键值
 * $array1 需要排序数组
 * $array2 按照该数组key排序
 * */
function array2_sort_key($array1, $array2, $key = '')
{
    if (empty($array1) || empty($array2)) return NULL;
    $arr = array();
    foreach ($array2 as $k => $v) {
        if (isset($v[$key]) && $v[$key] == $array1[$v[$key]][$key]) {
            $arr[$v[$key]] = $array1[$v[$key]];
        } else {
            $arr[] = $v;
        }
    }

    return $arr;
}

// hook model_misc_array_end.php

?>