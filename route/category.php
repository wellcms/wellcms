<?php
/*
 * Copyright (C) www.wellcms.cn
*/
!defined('DEBUG') AND exit('Access Denied.');

// hook category_start.php

$fid = param(1, 0);
empty($fid) AND message(1, lang('data_malformation'));

$extra = array(); // 插件预留

// hook category_extra.php

$forum = array_value($forumlist_show, $fid);
// hook category_read.php
empty($forum) AND message(-1, lang('forum_not_exists'));

// 管理时使用
(forum_access_mod($fid, $gid, 'allowdelete') OR forum_access_mod($fid, $gid, 'allowtop')) AND $extra['fid'] = $fid;

// hook category_before.php

// 不是频道
empty($forum['category']) AND message(1, lang('data_malformation'));

$website_setting = $config['setting'];
// website_mode
$website_mode = $website_setting['website_mode'];
// tpl_mode
$tpl_mode = $website_setting['tpl_mode'];

// hook category_after.php

switch ($forum['model']) {
    case '0':
        // 频道下显示的版块和主题 频道置顶
        // hook category_article_start.php

        // 从默认的地方读取主题列表
        $thread_list_from_default = 1;

        // hook category_article_mode_before.php

        if (0 == $website_mode || 1 == $website_mode) {
            // 门户模式 portal

            // hook category_article_portal_start.php

            /*
             * $arrlist[list]对应每个需要显示的栏目;
             * // 栏目按照rank被重新排序所以键已经不是fid
             * $arrlist[list][0]['name']栏目名;
             * $arrlist[list][0]['url']栏目路径;
             * $arrlist[list][0]['news']栏目下显示的主题二维数组;
             * $arrlist[flag]首页需要显示的主题二维数组;
             * $arrlist[sticky]首页置顶需要显示的主题二维数组;
             * */
            $arrlist = portal_channel_thread_cache($fid);

            // 轮播凑整 双列排版 防止错版 单一列注释该代码
            $slide = array_value($arrlist, 'sticky');
            /*if ($slide) {
                if (0 != count($arrlist['sticky']) % 2) {
                    $i = 0;
                    foreach ($arrlist['sticky'] as $key => &$_thread) {
                        if (1 == ++$i) {
                            $slide[] = $_thread;
                        }
                    }
                }
            }*/

            $first_flag = NULL;
            $flaglist = NULL;
            if (isset($arrlist['flaglist'])) {
                $flaglist = isset($arrlist['flaglist']);
                $first_flag = reset($arrlist['flaglist']);
            }

            // hook category_article_portal_end.php

        } elseif (2 == $website_mode) {
            // 扁平模式

            // hook category_article_flat_start.php

            $page = param(2, 1);
            $pagesize = empty($forum['pagesize']) ? $conf['pagesize'] : $forum['pagesize'];

            $threadlist = NULL;
            $tidlist = NULL;
            $threads = 0;
            $fids = array();

            // hook category_article_flat_after.php

            if ($thread_list_from_default) {
                $fids = array();
                $threads = 0;
                if ($forumlist_show) {
                    foreach ($forumlist_show as $key => $val) {
                        if ($val['fup'] == $fid && 1 == $val['type'] && 0 == $val['category']) {
                            $fids[] = $val['fid'];
                            $threads += $val['threads'];
                        }
                    }
                }

                // hook index_flat_thread_find_tid_before.php

                $tidlist = thread_tid_find_by_fid($fids, $page, $pagesize, TRUE);
            }

            // hook category_article_flat_center.php

            // 置顶
            $stickylist = 1 == $page ? sticky_index_thread() : array();

            // hook category_article_flat_sticky_after.php

            $arr = array('tidlist' => $tidlist, 'stickylist' => $stickylist);

            // hook category_article_flat_unified_pull_before.php

            $arrlist = thread_unified_pull($arr);
            $threadlist = $arrlist['threadlist'];
            $flaglist = $arrlist['flaglist'];

            // hook category_article_flat_after.php

            $page_url = url('category-' . $fid . '-{page}', $extra);
            $num = $threads > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $threads;

            // hook category_article_flat_pagination_before.php

            $pagination = pagination($page_url, $num, $page, $pagesize);

            // hook category_article_flat_end.php
        }

        // hook category_article_after.php

        // SEO
        $seo_title = $forum['seo_title'] ? $forum['seo_title'] : $forum['name'] . '-' . $conf['sitename'];
        $header['title'] = strip_tags($seo_title);
        $header['mobile_title'] = '';
        $header['mobile_link'] = url('category-' . $fid);
        $seo_keywords = $forum['seo_keywords'] ? $forum['seo_keywords'] : $forum['name'];
        $header['keywords'] = strip_tags($seo_keywords);
        $header['description'] = strip_tags($forum['brief']);
        $_SESSION['fid'] = $fid;
        $active = 'default';

        // hook category_article_end.php

        if ($ajax) {
            $conf['api_on'] ? message(0, $arrlist) : message(0, lang('closed'));
        } else {
            include _include(theme_load('category', $fid));
        }
        break;
    // hook category_model_case_after.php
    default:
        message(-1, lang('data_malformation'));
        break;
}

// hook category_end.php

?>