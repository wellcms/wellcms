# WellCMS 2.0

#### 介绍
WellCMS X是一款开源、倾向移动端的轻量级、高负载的CMS，是大数据量、高并发访问网站最佳选择的轻CMS。WellCMS具有安全、高效、稳定、速度快、负载超强的特点。

WellCMS 2.0 只有22张表，运行速度非常快，处理单次请求在 0.01 秒级别，支持各种NoSQL操作，支持附件分离，支持mysql读写分离。分布式服务器设计，每张表都可创建单独的DB服务器群和CACHE服务器(群)，单表可承载高达亿级以上的数据，方便部署和维护，大量的运算放到了客户端，并发问题尽量由客户端控制，是一个二次开发非常好的基石。

前端基于 BootStrap 4.4、JQuery 3，后端基于 PHP/7 MySQL XCache/Yac/Redis/Memcached...

自适应手机、平板、PC，也可以设置各端单独模板，并且URL保持不变，系统自动加载各端模板，有着非常方便的插件机制，还是一个良好的二次开发平台。

#### 安装教程

1. 确认您的主机支持 PHP，并且已经开通并且配置好了 MySQL。
3. 设置如下目录和文件为可写(Linux: 目录权限为 0777，Windows 设置用户 everyone 可读写）
	./upload
	./plugin
	./tmp
	./log
	./conf
4. 上传所有文件到你的网站根目录
5. 访问 http://www.domain.com/install/, 根据提示安装。
6. 删除 install 目录

#### 使用说明

1.  xxxx
2.  xxxx
3.  xxxx

#### 参与贡献

1.  Fork 本仓库
2.  新建 Feat_xxx 分支
3.  提交代码
4.  新建 Pull Request
