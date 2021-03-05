# 表结构

### 用户表 ###
DROP TABLE IF EXISTS `wellcms_user`;
CREATE TABLE `wellcms_user` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户编号',
  `gid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '用户组编号',	# 如果要屏蔽，调整用户组即可
  `email` char(40) NOT NULL DEFAULT '' COMMENT '邮箱',
  `username` char(32) NOT NULL DEFAULT '' COMMENT '用户名',	# 不可以重复
  `realname` char(16) NOT NULL DEFAULT '' COMMENT '用户名',	# 真实姓名，天朝预留
  `idnumber` char(19) NOT NULL DEFAULT '' COMMENT '用户名',	# 真实身份证号码，天朝预留
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `password_sms` char(16) NOT NULL DEFAULT '' COMMENT '密码',	# 预留，手机发送的 sms 验证码
  `salt` char(16) NOT NULL DEFAULT '' COMMENT '密码混杂',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',		# 预留，供二次开发扩展
  `qq` char(12) NOT NULL DEFAULT '' COMMENT 'QQ',			# 预留，供二次开发扩展，可以弹出QQ直接聊天
  `articles` int(11) NOT NULL DEFAULT '0' COMMENT '文章数', #
  `comments` int(11) NOT NULL DEFAULT '0' COMMENT '评论数', #
  `credits` int(11) NOT NULL DEFAULT '0' COMMENT '积分',		# 预留，供二次开发扩展
  `golds` int(11) NOT NULL DEFAULT '0' COMMENT '金币',		# 预留，虚拟币
  `money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '钱包',		# 预留，账户资金
  `create_ip` decimal(39,0) unsigned NOT NULL DEFAULT '0' COMMENT '创建时IP',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `login_ip` decimal(39,0) unsigned NOT NULL DEFAULT '0' COMMENT '登录时IP',
  `login_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `logins` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `avatar` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户最后更新图像时间',
  PRIMARY KEY (`uid`),
  UNIQUE KEY (`username`),
  UNIQUE KEY (`email`), # 升级的时候可能为空
  KEY gid (gid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
INSERT INTO `wellcms_user` SET uid=1, gid=1, email='admin@admin.com', username='admin',`password`='d98bb50e808918dd45a8d92feafc4fa3',salt='123456';

# 用户组
DROP TABLE IF EXISTS `wellcms_group`;
CREATE TABLE `wellcms_group` (
  `gid` smallint(6) unsigned NOT NULL,			#
  `name` varchar(20) NOT NULL default '',			# 用户组名称
  `creditsfrom` int(11) unsigned NOT NULL default '0',	# 积分从
  `creditsto` int(11) unsigned NOT NULL default '0',		# 积分到
  `allowread` tinyint(1) NOT NULL default '0',		# 允许访问
  `allowthread` tinyint(1) NOT NULL default '0',  # 允许发主题
  `allowpost` tinyint(1) NOT NULL default '0',		# 允许回复
  `allowattach` tinyint(1) NOT NULL default '0',  # 允许上传文件
  `allowdown` tinyint(1) NOT NULL default '0',		# 允许下载文件
  `allowtop` tinyint(1) NOT NULL default '0',     # 允许置顶
  `allowupdate` tinyint(1) NOT NULL default '0',  # 允许编辑
  `allowdelete` tinyint(1) NOT NULL default '0',  # 允许删除
  `allowmove` tinyint(1) NOT NULL default '0',		# 允许移动
  `allowbanuser` tinyint(1) NOT NULL default '0',		# 允许禁止用户
  `allowdeleteuser` tinyint(1) NOT NULL default '0',# 允许删除用户
  `allowviewip` tinyint(1) NOT NULL default '0',	# 允许查看用户敏感信息
  `allowuserdelete` tinyint(1) NOT NULL default '0', # 允许用户删除主题
  `allowpublish` tinyint(1) NOT NULL default '0',	# 前台允许投稿
  `publishverify` tinyint(1) NOT NULL default '0', # 投稿审核
  `commentverify` tinyint(1) NOT NULL default '0', # 评论审核
  `intoadmin` tinyint(1) NOT NULL default '0', # 进后台
  `managecontent` tinyint(1) NOT NULL default '0',  # 管理内容
  `managecreatethread` tinyint(1) NOT NULL default '0',	# 后台创建主题
  `manageupdatethread` tinyint(1) NOT NULL default '0',	# 后台编辑主题
  `managedeletethread` tinyint(1) NOT NULL default '0',	# 后台删除主题
  `managesticky` tinyint(1) NOT NULL default '0',	# 管理置顶
  `managecomment` tinyint(1) NOT NULL default '0',	# 管理评论
  `managepage` tinyint(1) NOT NULL default '0',	# 管理单页
  `manageforum` tinyint(1) NOT NULL default '0',	# 管理版块
  `managecategory` tinyint(1) NOT NULL default '0',	# 管理分类
  `manageuser` tinyint(1) NOT NULL default '0',	# 管理用户
  `managecreateuser` tinyint(1) NOT NULL default '0',	# 管理创建用户
  `manageupdateuser` tinyint(1) NOT NULL default '0',	# 管理编辑用户
  `managedeleteuser` tinyint(1) NOT NULL default '0',	# 管理删除用户
  `managegroup` tinyint(1) NOT NULL default '0',	# 管理用户组
  `manageupdategroup` tinyint(1) NOT NULL default '0',	# 管理编辑用户组
  `manageplugin` tinyint(1) NOT NULL default '0',	# 管理插件
  `manageother` tinyint(1) NOT NULL default '0',	# 管理其他
  `managesetting` tinyint(1) NOT NULL default '0',   # 系统设置

  #`manageorder` tinyint(1) NOT NULL default '0',	# 管理订单
  #`manageverify` tinyint(1) NOT NULL default '0',	# 管理审核
  #`allowagent` tinyint(1) NOT NULL default '0',  # 允许代理
  #`allowsell` tinyint(1) NOT NULL default '0',   # 允许分销
  #`allowmerchant` tinyint(1) NOT NULL default '0',	# 允许成为商户
  #`allowdelivery` tinyint(1) NOT NULL default '0',	# 允许发货
  # 信息message 通知notice 活动activity
  PRIMARY KEY (gid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `wellcms_group` (`gid`, `name`, `creditsfrom`, `creditsto`, `allowread`, `allowthread`, `allowpost`, `allowattach`, `allowdown`, `allowtop`, `allowupdate`, `allowdelete`, `allowmove`, `allowbanuser`, `allowdeleteuser`, `allowviewip`, `intoadmin`, `managesetting`, `managecontent`, `manageforum`, `managecategory`, `manageuser`, `manageplugin`, `manageother`, `managecreatethread`, `manageupdatethread`, `managedeletethread`, `managecomment`, `managepage`, `managesticky`, `managegroup`, `manageupdateuser`, `managecreateuser`, `manageupdategroup`, `managedeleteuser`, `allowuserdelete`, `allowpublish`, `publishverify`, `commentverify`) VALUES
(0, '游客组', 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1),
(1, '管理员组', 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0),
(2, '超级版主组', 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0),
(4, '版主组', 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0),
(5, '实习版主组', 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0),
(6, '待验证用户组', 0, 0, 1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1),
(7, '禁止用户组', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1),
(101, '一级用户组', 0, 50, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0),
(102, '二级用户组', 50, 200, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0),
(103, '三级用户组', 200, 1000, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0),
(104, '四级用户组', 1000, 10000, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0),
(105, '五级用户组', 10000, 10000000, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0);

# session 表
# 缓存到 runtime 表。 online_0 全局 online_fid 版块。提高遍历效率。
DROP TABLE IF EXISTS `wellcms_session`;
CREATE TABLE `wellcms_session` (
  `sid` char(32) NOT NULL default '0',  # 随机生成 id 不能重复 uniqueid() 13 位
  `uid` int(11) unsigned NOT NULL default '0',  # 用户id 未登录为 0，可以重复
  `fid` int(11) unsigned NOT NULL default '0', # 所在的版块
  `url` char(32) NOT NULL default '', # 当前访问 url
  `ip` decimal(39,0) unsigned NOT NULL default '0',		# 用户ip
  `useragent` char(128) NOT NULL default '',		# 用户浏览器信息
  `data` char(255) NOT NULL default '', # session 数据，超大数据存入大表。
  `bigdata` tinyint(1) NOT NULL default '0',  # 是否有大数据。
  `last_date` int(11) unsigned NOT NULL default '0',  # 上次活动时间
  PRIMARY KEY (`sid`),
  KEY `ip` (`ip`),
  KEY `last_date` (`last_date`),
  KEY `uid_last_date` (`uid`, `last_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_session_data`;
CREATE TABLE `wellcms_session_data` (
  `sid` char(32) NOT NULL default '0',			#
  `last_date` int(11) unsigned NOT NULL default '0',	# 上次活动时间
  `data` text NOT NULL,					# 存超大数据
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 持久的 key value 数据存储, ttserver, mysql
DROP TABLE IF EXISTS `wellcms_kv`;
CREATE TABLE `wellcms_kv` (
  `k` char(32) NOT NULL default '',
  `v` mediumtext NOT NULL,
  `expiry` int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT INTO `wellcms_kv` (`k`, `v`, `expiry`) VALUES ('setting', '{"conf":{"name":"WellCMS Oriental Lion","version":"2.1.02","official_version":"2.1.02","last_version":"0","version_date":"0","installed":0,"setting":{"website_mode":2,"tpl_mode":0,"map":"map","verify_thread":0,"verify_post":0,"verify_special":0,"thumbnail_on":1,"save_image_on":1},"picture_size":{"width":170,"height":113},"theme":"","shield":[],"index_stickys":0,"index_flags":"0","index_flagstr":""}}', 0);

# 缓存表 用来保存临时数据
DROP TABLE IF EXISTS `wellcms_cache`;
CREATE TABLE `wellcms_cache` (
  `k` char(32) NOT NULL default '',
  `v` mediumtext NOT NULL,
  `expiry` int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_forum`;
CREATE TABLE `wellcms_forum` (
  `fid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fup` int(11) unsigned NOT NULL DEFAULT '0',       # 上级栏目fid
  `son` int(11) NOT NULL DEFAULT '0',       # 子栏目数
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1',   # 分类 0论坛 1cms
  `model` tinyint(2) unsigned NOT NULL DEFAULT '0',  # 模型 0文章
  `category` tinyint(2) unsigned NOT NULL DEFAULT '0', # 版块分类 (0列表 1频道 2单页 3外链)
  `name` varchar(24) NOT NULL DEFAULT '',    # 版块名称
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0', # 显示，倒序，数字越大越靠前
  `threads` int(11) unsigned NOT NULL DEFAULT '0', # 主题数
  `tops` int(11) unsigned NOT NULL DEFAULT '0',    # 置顶主题数
  `todayposts` int(11) unsigned NOT NULL DEFAULT '0',  # 今日发帖，计划任务每日凌晨0点清空为0
  `todaythreads` int(11) unsigned NOT NULL DEFAULT '0',  # 今日发主题，计划任务每日凌晨0点清空为0
  `accesson` int(11) unsigned NOT NULL DEFAULT '0',    # 是否开启权限控制
  `orderby` tinyint(1) NOT NULL DEFAULT '0',  # BBS默认列表排序，0: 顶贴时间 last_date， 1: 发帖时间 tid
  `icon` int(11) unsigned NOT NULL DEFAULT '0',  # 板块是否有 icon，存放最后更新时间
  `display` tinyint(1) NOT NULL DEFAULT '0', # 首页和频道是否显示该栏目内容 1显示
  `nav_display` tinyint(1) NOT NULL DEFAULT '0', # 导航是否显示该栏目 1显示
  `index_new` tinyint(3) NOT NULL DEFAULT '0',  # 首页显示该栏目主题数量
  `channel_new` tinyint(3) NOT NULL DEFAULT '0',# 频道显示该栏目最新主题数量
  `comment` tinyint(1) NOT NULL DEFAULT '0',  # 评论开启 0关闭 1开启
  `pagesize` tinyint(3) NOT NULL DEFAULT '0', # 列表显示数量
  #`thread_rank` tinyint(1) NOT NULL DEFAULT '0',  # 主题排序
  `publish` tinyint(1) NOT NULL DEFAULT '0',  # 投稿
  `flags` int(11) unsigned NOT NULL DEFAULT '0', # 板块下属性数量
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 板块创建时间
  `flagstr` varchar(120) NOT NULL DEFAULT '', # 前台显示的属性 字串
  `thumbnail` varchar(32) NOT NULL DEFAULT '', # 缩略图像素
  `moduids` varchar(120) NOT NULL DEFAULT '',  # 每个版块有多个版主，最多10个： 10*12 = 120，删除用户的时候，如果是版主，则调整后再删除。逗号分隔
  `seo_title` varchar(64) NOT NULL DEFAULT '', # SEO 标题，如果设置会代替版块名称
  `seo_keywords` varchar(64) NOT NULL DEFAULT '',  # SEO keyword
  `brief` text NOT NULL, # 版块简介 允许HTML，SEO description
  `announcement` text NOT NULL, # 版块公告 允许HTML
  PRIMARY KEY (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_forum_access`;
CREATE TABLE `wellcms_forum_access` (
  `fid` int(11) unsigned NOT NULL DEFAULT '0',
  `gid` int(11) unsigned NOT NULL DEFAULT '0',
  `allowread` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allowthread` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allowpost` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allowattach` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allowdown` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`fid`,`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_attach`;
CREATE TABLE `wellcms_website_attach` (
  `aid` int(11) unsigned NOT NULL AUTO_INCREMENT, # 附件ID
  `tid` int(11) unsigned NOT NULL DEFAULT '0',  # 主题ID
  `pid` int(11) unsigned NOT NULL DEFAULT '0',  # 评论ID
  `uid` int(11) unsigned NOT NULL DEFAULT '0',  # 用户ID
  `filesize` int(8) unsigned NOT NULL DEFAULT '0',  # 文件尺寸 单位字节
  `width` mediumint(8) unsigned NOT NULL DEFAULT '0', # width > 0 则为图片
  `height` mediumint(8) unsigned NOT NULL DEFAULT '0',  # height
  `downloads` int(11) NOT NULL DEFAULT '0', # 下载次数 预留
  `credits` int(11) NOT NULL DEFAULT '0', # 下载需要积分 预留
  `golds` int(11) NOT NULL DEFAULT '0', # 下载需要金币 预留
  `money` int(11) NOT NULL DEFAULT '0', # 下载需要资金 预留
  `isimage` tinyint(1) NOT NULL DEFAULT '0',  # 是否为图片
  `attach_on` tinyint(1) NOT NULL DEFAULT '0',  # 0本地储存 1云储存 2图床 记录使用了哪种储存方式
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',  # 文件上传时间 UNIX 时间戳
  `filename` varchar(60) NOT NULL DEFAULT '', # 文件名称，会过滤，并且截断，保存后的文件名，不包含URL前缀 upload_url
  `orgfilename` varchar(80) NOT NULL DEFAULT '',  # 上传的原文件名
  `image_url` varchar(120) NOT NULL DEFAULT '', # 使用图床完整链接
  `filetype` char(7) NOT NULL DEFAULT '',  # 文件类型: image/txt/zip 小图标显示 <i class="icon filetype image"></i>
  `comment` varchar(100) NOT NULL DEFAULT '', # 文件注释 方便于搜索
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),  # 主题附件
  KEY `pid` (`pid`),  # 评论附件
  KEY `uid` (`uid`)   # 用户附件
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_data`;
CREATE TABLE `wellcms_website_data` (
  `tid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `doctype` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `message` longtext NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_flag`;
CREATE TABLE `wellcms_website_flag` (
  `flagid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(24) NOT NULL DEFAULT '',  # 属性名
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 0全站
  `rank` smallint(6) unsigned NOT NULL DEFAULT '0',  # 排序最大值排在最前面
  `number` int(11) unsigned NOT NULL DEFAULT '0',  # 前台显示主题数
  `count` int(11) NOT NULL DEFAULT '0', # 属性下主题统计
  `icon` int(11) unsigned NOT NULL DEFAULT '0',  # 图标 时间戳 flagid为图片名
  `display` tinyint(1) unsigned NOT NULL DEFAULT '0',  # 1显示
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 创建时间
  PRIMARY KEY (`flagid`),
  KEY `name` (`name`,`fid`),
  KEY `fid` (`fid`,`display`,`flagid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_flag_thread`;
CREATE TABLE `wellcms_website_flag_thread` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,  # 主键
  `flagid` int(11) unsigned NOT NULL DEFAULT '0', # flagid
  `fid` int(11) unsigned NOT NULL DEFAULT '0',  # 版块fid
  `tid` int(11) unsigned NOT NULL DEFAULT '0',  # 主题tid
  `type` tinyint(1) NOT NULL DEFAULT '0', # 1首页 2频道 3栏目
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',  # 创建时间
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`),
  KEY `flagid` (`flagid`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_operate`;
CREATE TABLE `wellcms_website_operate` (
  `logid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) NOT NULL DEFAULT '0', # 1删除 2移动 3置顶 4取消置顶 5禁止回复 6关闭 7打开 8操作人民币 9操作金币 10操作积分 11删除节点 12删除节点分类 13审核专题 14删除专题 15归类专题主题 16删除专题主题 17删除用户 18禁止用户 19编辑用户 20删除待审核主题 21删除退稿 22删除草稿 23删除待审核评论 24审核主题 25退稿 26审核评论
  `uid` int(11) unsigned NOT NULL DEFAULT '0', # 版主 uid
  `tid` int(11) unsigned NOT NULL DEFAULT '0', # 主题tid
  `pid` int(11) unsigned NOT NULL DEFAULT '0', # 评论pid
  `subject` varchar(32) NOT NULL DEFAULT '', # 主题
  `comment` varchar(64) NOT NULL DEFAULT '', # 版主评论
  `total` int(11) NOT NULL DEFAULT '0', # 加减人民币 金币 积分
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 时间
  PRIMARY KEY (`logid`),
  KEY `uid_logid` (`uid`,`logid`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_comment`;
CREATE TABLE `wellcms_website_comment` (
  `pid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fid` int(11) unsigned NOT NULL DEFAULT '0',
  `tid` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(2) NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `userip` decimal(39,0) unsigned NOT NULL DEFAULT '0',
  `doctype` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `quotepid` int(11) unsigned NOT NULL DEFAULT '0',
  `images` tinyint(2) NOT NULL DEFAULT '0', # 附件中包含的图片数
  `files` tinyint(2) NOT NULL DEFAULT '0',  # 附件中包含的文件数
  `message` longtext NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_comment_pid`;
CREATE TABLE `wellcms_website_comment_pid` (
  `pid` int(11) unsigned NOT NULL, # 回复pid
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 版块fid
  `tid` int(11) unsigned NOT NULL DEFAULT '0', # 主题tid
  `uid` int(11) unsigned NOT NULL DEFAULT '0', # 用户uid
  PRIMARY KEY (`pid`),
  KEY `tid_pid` (`tid`,`pid`),
  KEY `uid_pid` (`uid`,`pid`)  # 个人回复或个人微博
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_thread`;
CREATE TABLE `wellcms_website_thread` (
  `tid` int(11) unsigned NOT NULL AUTO_INCREMENT,  # 主题id
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 版块 id
  `type` tinyint(2) unsigned NOT NULL DEFAULT '0', # 主题类型:0默认内容 10外链 11单页
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',  # 置顶级别: 0: 普通主题, 1-3 置顶顺序
  `uid` int(11) unsigned NOT NULL DEFAULT '0',   # 用户uid
  `icon` int(11) unsigned NOT NULL DEFAULT '0',  # 缩略图 写入时间戳 图片名tid
  `userip` decimal(39,0) unsigned NOT NULL DEFAULT '0',# 发表ip ip2long()用来清理
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 发帖时间
  `views` int(11) NOT NULL DEFAULT '0', # 查看次数, 剥离出去，单独的服务，避免 cache 失效
  `posts` int(11) NOT NULL DEFAULT '0',     # 回复数
  `images` tinyint(3) NOT NULL DEFAULT '0', # 附件中包含的图片数
  `files` tinyint(3) NOT NULL DEFAULT '0',  # 附件中包含的文件数
  `mods` tinyint(3) NOT NULL DEFAULT '0',   # 预留：版主操作次数，如果 > 0, 则查询 operate，显示斑竹的评分
  `status` tinyint(2) NOT NULL DEFAULT '0', # 0:通过 1~9审核:1待审核 2草稿 10~19:10退稿 11逻辑删除
  `closed` tinyint(1) unsigned NOT NULL DEFAULT '0', # 1关闭回复 2关闭主题不能回复、编辑
  `lastuid` int(11) unsigned NOT NULL DEFAULT '0',   # 最近参与的 uid
  `last_date` int(11) unsigned NOT NULL DEFAULT '0', # 最后回复时间
  `attach_on` tinyint(1) unsigned NOT NULL DEFAULT '0',  # 0本地储存 1云储存 2图床
  `flags` tinyint(2) NOT NULL DEFAULT '0',  # 主题绑定flag数量
  `subject` varchar(128) NOT NULL DEFAULT '', # 主题
  `tag` varchar(120) NOT NULL DEFAULT '',  # 标签 json {tgaid:name}
  `brief` varchar(120) NOT NULL DEFAULT '', # 简介
  `keyword` varchar(64) NOT NULL DEFAULT '', # SEO keyword
  `description` varchar(120) NOT NULL DEFAULT '', # SEO description 外链接写这里 直接跳出去了
  `image_url` varchar(120) NOT NULL DEFAULT '', # 图床文件网址
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_thread_tid`;
CREATE TABLE `wellcms_website_thread_tid` (
  `tid` int(11) unsigned NOT NULL,
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 版块fid
  `uid` int(11) unsigned NOT NULL DEFAULT '0', # 用户uid
  #`rank` smallint(6) unsigned NOT NULL DEFAULT '0', # 主题排序 插件
  #`lastpid` int(11) unsigned NOT NULL DEFAULT '0', # 最后回复pid
  `verify_date` int(11) unsigned NOT NULL DEFAULT '0', # 审核时间
  PRIMARY KEY (`tid`),
  KEY `fid_tid` (`fid`,`tid`),  # 版块下主题 发布时间排序
  #KEY `fid_lastpid` (`fid`,`lastpid`),  # 回复时间排序主题
  #KEY `fid_rank` (`fid`,`rank`), # 插件形式出现
  KEY `uid_tid` (`uid`,`tid`) # 用户主题 & 清理
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 单页
DROP TABLE IF EXISTS `wellcms_website_page`;
CREATE TABLE `wellcms_website_page` (
  `tid` int(11) unsigned NOT NULL AUTO_INCREMENT, # 主题tid
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0', # 主题排序
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 版块fid
  PRIMARY KEY (`tid`),
  KEY `fid_rank` (`fid`,`rank`) # 主题排序
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_thread_sticky`;
CREATE TABLE `wellcms_website_thread_sticky` (
  `tid` int(11) unsigned NOT NULL DEFAULT '0', # 主题tid
  `fid` int(11) unsigned NOT NULL DEFAULT '0', # 查找板块置顶
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',  # sticky:置顶 1版块 2频道 3全局
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 创建时间
  PRIMARY KEY (`tid`),
  KEY `sticky_tid` (`sticky`,`tid`),
  KEY `fid_sticky` (`fid`,`sticky`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_tag`;
CREATE TABLE `wellcms_website_tag` (
  `tagid` int(11) unsigned NOT NULL AUTO_INCREMENT,  # 标签ID
  `name` char(32) NOT NULL DEFAULT '',  # 标签名
  `count` int(11) NOT NULL DEFAULT '0', # 标签下主题数 允许负数，方便查bug
  `icon` int(11) unsigned NOT NULL DEFAULT '0',  # 标签缩略图 时间戳 tagid为图片名
  PRIMARY KEY (`tagid`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `wellcms_website_tag_thread`;
CREATE TABLE `wellcms_website_tag_thread` (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`tagid` int(11) unsigned NOT NULL DEFAULT '0',
`tid` int(11) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
KEY `tid` (`tid`),
KEY `tagid_id` (`tagid`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

# 友情链接
DROP TABLE IF EXISTS `wellcms_website_link`;
CREATE TABLE `wellcms_website_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0', # 排序
  `name` varchar(24) NOT NULL DEFAULT '',  # 网站名
  `url` varchar(120) NOT NULL DEFAULT '',  # URL
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', # 创建时间
  PRIMARY KEY (`id`),
  KEY `rank` (`rank`) # 排序
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;