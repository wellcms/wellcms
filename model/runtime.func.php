<?php

// hook model_runtime_start.php

function runtime_init()
{
    global $conf;
    // hook model_runtime_init_start.php
    // 实时运行的数据，初始化！
    $runtime = $conf['cache']['type'] == 'mysql' ? website_get('runtime') : cache_get('runtime');

    if ($runtime === NULL || empty($runtime['users'])) {
        $runtime = array();
        $runtime['users'] = user_count();

        $runtime['articles'] = well_thread_count();
        $runtime['comments'] = comment_pid__count();
        $runtime['todayarticles'] = 0;
        $runtime['todaycomments'] = 0;
        $runtime['todaythreads'] = 0;
        $runtime['onlines'] = max(1, online_count());
        $runtime['cron_1_last_date'] = 0;
        $runtime['cron_2_last_date'] = 0;

        // hook model_runtime_init_before.php

        $conf['cache']['type'] == 'mysql' ? website_set('runtime', $runtime) : cache_set('runtime', $runtime);
    }
    // hook model_runtime_init_end.php
    return $runtime;
}

function runtime_get($k)
{
    global $runtime;
    // hook model_runtime_get_start.php
    // hook model_runtime_get_end.php
    return array_value($runtime, $k, NULL);
}

function runtime_set($k, $v)
{
    global $conf, $runtime;
    // hook model_runtime_set_start.php
    $op = substr($k, -1);
    if ($op == '+' || $op == '-') {
        $k = substr($k, 0, -1);
        isset($runtime[$k]) || $runtime[$k] = 0;
        $v = $op == '+' ? ($runtime[$k] + $v) : ($runtime[$k] - $v);
    }

    $runtime[$k] = $v;
    // hook model_runtime_set_end.php
    return TRUE;
}

function runtime_delete($k)
{
    global $conf, $runtime;
    // hook model_runtime_delete_start.php
    unset($runtime[$k]);
    runtime_save();
    // hook model_runtime_delete_end.php
    return TRUE;
}

function runtime_save()
{
    global $conf, $runtime;
    // hook model_runtime_save_start.php

    function_exists('chdir') AND chdir(APP_PATH);

    $r = $conf['cache']['type'] == 'mysql' ? website_set('runtime', $runtime) : cache_set('runtime', $runtime);

    // hook model_runtime_save_end.php
}

function runtime_truncate()
{
    global $conf;
    // hook model_runtime_truncate_start.php
    $conf['cache']['type'] == 'mysql' ? website_set('runtime', '') : cache_delete('runtime');
    // hook model_runtime_truncate_end.php
}

register_shutdown_function('runtime_save');

// hook model_runtime_end.php

?>