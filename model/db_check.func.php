<?php
/*
 * Copyright (C) www.wellcms.cn
 */
 
// 检查表
function db_find_table($table)
{
    if ($table) {
        // 查询表的详细信息
        //$arr = db_sql_find_one("SHOW TABLE STATUS LIKE '$table'");
        // 模糊搜索表
        $arr = db_sql_find_one("SHOW TABLES LIKE '$table'");
        if (!empty($arr)) {
            foreach ($arr as $v) {
                if ($v == $table) return TRUE;
            }
        }
    }
    return FALSE;
}

// 检查字段 $table表 $field字段
function db_find_field($table, $field)
{
    if ($table && $field) {
        $r = db_sql_find_one("DESCRIBE " . $table . " `{$field}`");
        if (!empty($r) && $r['Field'] == $field) return TRUE;
    }
    return FALSE;
}

// 检查索引 $table表 $index索引
function db_find_index($table, $index)
{
    if ($table && $index) {
        $arr = db_sql_find("SHOW INDEX FROM " . $table);
        if (!empty($arr)) {
            foreach ($arr as $v) {
                if ($v['Key_name'] == $index) return TRUE;
            }
        }
    }
    return FALSE;
}

?>