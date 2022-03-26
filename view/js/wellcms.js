var body = $('body');

/*
 ajax 推出登陆 绑定id="user-logout"
 <a class="nav-link" rel="nofollow" id="user-logout" href="<?php echo url('user-logout');?>"><i class="icon-sign-out"></i>&nbsp;<?php echo lang('logout');?></a>
 */
body.on('click', '#user-logout', function () {
    var href = $(this).attr('href') || $(this).data('href');
    $.xpost(href, function (code, message) {
        if (code == 0) {
            $.alert(message).delay(1000).location();
        } else {
            alert(message);
        }
    });
    return false;
});

/* 搜索使用 */
body.on('submit', '#form-search', function () {
    var jthis = $(this);
    var range = jthis.find('input[name="range"]').val();
    var keyword = jthis.find('input[name="keyword"]').val();
    window.location = xn.url('operate-search-' + xn.urlencode(keyword) + '-' + range);
    return false;
});

/*表单快捷键提交 CTRL+ENTER   / form quick submit*/
body.on('keyup', 'form', function (e) {
    var jthis = $(this);
    if ((e.ctrlKey && (e.which == 13 || e.which == 10)) || (e.altKey && e.which == 83)) {
        jthis.trigger('submit');
        return false;
    }
});

/*点击响应整行：方便手机浏览  / check response line*/
body.on('click', '.tap', function (e) {
    var href = $(this).attr('href') || $(this).data('href');
    if (e.target.nodeName == 'LABEL' || e.target.nodeName == 'INPUT') return true;
    if ($(window).width() > 992) return;
    if (e.ctrlKey) {
        window.open(href);
        return false;
    } else {
        window.location = href;
    }
});

/*点击响应整行：，但是不响应 checkbox 的点击  / check response line, without checkbox*/
$('.thread input[type="checkbox"]').parents('td').on('click', function (e) {
    e.stopPropagation();
});

/*点击响应整行：导航栏下拉菜单   / check response line*/
body.on('click', 'ul.nav > li', function (e) {
    var jthis = $(this);
    var href = jthis.children('a').attr('href');
    if (e.ctrlKey) {
        window.open(href);
        return false;
    }
});

/*管理用户组*/
body.on('click', '.admin-manage-user', function () {
    var href = $(this).data('href');
    $.xpost(href, function (code, message) {
        if (code == 0) {
            $.alert(message).delay(1000).location();
        } else {
            $.alert(message).delay(2000).location();
        }
    });
    return false;
});

$(function () {
    var nav = $('#nav-show');
    var remove = 'd-lg-none position-fixed rounded-left bg-secondary d-flex align-items-center';
    var remove1 = 'd-none d-lg-block';
    var remove2 = 'sticky-top pt-2';
    var add = 'shadow col-8 col-md-4 bg-white px-0';
    var add1 = 'px-2';
    /*菜单侧边滑出 .nav-block 控制在左右 */
    $('.button-show').click(function () {
        var jthis = $(this);
        var left = jthis.offset().left;
        add += left ? ' offset-4 offset-md-8' : '';
        jthis.css('display', 'none');
        nav.before('<div id="menu-wrap" style="overflow-x:hidden;overflow-y:auto;position:fixed;top:0;left:0;width:100%;height:100%;z-index:1031;background-color:#3a3b4566;"></div>');
        jthis.removeClass(remove);
        /*nav.css({"position": "fixed", "top": "0", "bottom": "0", "right": "0", "margin-top": "3.625rem", "z-index": "1032"});*/
        nav.removeClass(remove1).addClass(add);
        nav.find('.post-sticky-top').removeClass(remove2).addClass(add1);
        /*nav.animate({right: ''}, 500);*/
        return false;
    });

    /*菜单侧边收起弹出菜单*/
    $('.button-hide').click(function () {
        var jthis = $(this);
        var left = jthis.offset().left;
        add += left ? ' offset-3' : '';
        jthis.css('display', 'none');
        var button_show = $('.button-show');
        button_show.addClass(remove);
        button_show.css('display', 'block');
        $('#menu-wrap').remove();
        nav.removeClass(add).addClass(remove1);
        nav.find('.post-sticky-top').removeClass(add1).addClass(remove2);
        /*nav.animate({left: ''}, 500);*/
        return false;
    });
});

/*tag*/
$(function () {
    var tag_input = $('.tag-input');
    tag_input.val('');

    $(document).on('keydown', '.tag-input', function (event) {
        var tag_input = $(this);
        var token = tag_input.parents('.tags').find('.tags-token');
        /* event.keyCode == 32 */
        if (event.keyCode == 13 || event.keyCode == 108 || event.keyCode == 188) {
            create_tag();
            return false;
        }
        var str = tag_input.val().replace(/\s+/g, '');
        if (str.length == 0 && event.keyCode == 8) {
            if (token.length >= 1) {
                tag_input.parents('.tags').find('.tags-token:last').remove();
                get_tag_val(tag_input);
                return false;
            }
        }
    });

    $(document).on('click', '.tags-token', function () {
        var it = $(this).parents('.tags');
        $(this).remove();
        var str = '';
        var token = it.find('.tags-token');
        if (token.length < 1) {
            it.find('.tags-val').val('');
            return false;
        }
        for (var i = 0; i < token.length; i++) {
            str += token.eq(i).text() + ',';
            it.find('.tags-val').val(str);
        }
    });

    tag_input.bind("input propertychange", function () {
        var str = $(this).val();
        /* || str.indexOf(' ') != -1 */
        if (str.indexOf(',') != -1 || str.indexOf('，') != -1) {
            create_tag();
            return false;
        }
    });

    function create_tag() {
        var tag_input = $('.tag-input');
        /*var tag = tag_input.val().replace(/\s+/g, '');*/
        var tag = tag_input.val();
        var reg = new RegExp("[`~!@#$^&*()=|{}:;,\\[\\].<>/?！￥…（）—【】‘；：”“。，、？%]", 'g');
        tag = tag.replace(reg, '');
        tag = tag.replace(/(^\s*)|(\s*$)/g, '');
        if (tag.length > 0) {
            var tags = $('input[name="tags"]').val();
            var arr = tags.split(',');
            if (arr.indexOf(tag) > -1) {
                tag_input.val('');
                return false;
            }
            if (Object.count(arr) <= 5) {
                $('<span class="tag tags-token" style="margin-right: 1rem;margin-bottom: .25rem;margin-top: .25rem;padding: .25rem .5rem;border: 1px solid #dddfeb;font-size: .8575rem;line-height: 1.5;border-radius: .2rem;">' + tag + '</span>').insertBefore(tag_input.parents('.tags').find('.tag-wrap'));
            }
            tag_input.val('');
            get_tag_val(tag_input);
        }
    }

    function get_tag_val(obj) {
        var str = '';
        var token = $(obj).parents('.tags').find('.tags-token');
        if (token.length < 1) {
            $(obj).parents('.tags').find('.tags-val').val('');
            return false;
        }
        for (var i = 0; i < token.length; i++) {
            str += token.eq(i).text() + ',';
            /*str = str.replace(/\s+/g, '');*/
            var reg = new RegExp("[`~!@#$^&*()=|{}:;\\[\\].<>/?！￥…（）—【】‘；：”“。，、？%]", 'g');
            str = str.replace(reg, '');
            str = str.replace(/(^\s*)|(\s*$)/g, '');
            $(obj).parents('.tags').find('.tags-val').val(str);
        }
    }
});

/*
 确定框 / confirm / GET / POST
 <a href="1.php" data-confirm-text="确定删除？" class="confirm">删除</a>
 <a href="1.php" data-method="post" data-confirm-text="确定删除？" class="confirm">删除</a>
 */
body.on('click', 'a.confirm', function () {
    var jthis = $(this);
    var text = jthis.data('confirm-text');
    $.confirm(text, function () {
        var method = xn.strtolower(jthis.data('method'));
        var href = jthis.data('href') || jthis.attr('href');
        if ('post' == method) {
            $.xpost(href, function (code, message) {
                if (0 == code) {
                    window.location.reload();
                } else {
                    $.alert(message);
                }
            });
        } else {
            window.location = jthis.attr('href');
        }
    });
    return false;
});

/*
 <a class="ajax" rel="nofollow" href="<?php echo url('comment-create'); ?>" data-method="get" data-confirm-text="删除" aria-label="评论提交">提交</a>

 <a class="ajax" rel="nofollow" href="<?php echo url('comment-create'); ?>" data-method="post" data-json='{"safe_token":"<?php echo $safe_token;?>","type":"1"}' data-confirm-text="删除" aria-label="评论提交">提交</a>

	let list = document.getElementsByClassName('follow');
	let listLen = list.length;
	for (var i = 0; i < listLen; ++i) {
		list[i].onclick = function () {
			let jthis = this;
			let _uid = jthis.getAttribute('uid');
			let href = jthis.getAttribute('href');
			let method = jthis.getAttribute('data-method');
			$.xpost(href, {'uid': _uid}, function (code, data) {

			});
			console.log(_uid);
			return false;
		};
	}

array('url' => url('my-follow', array('type' => 1,'followuid' => $followuid)), 'text' => lang('well_unfollow'), 'data-method' => 'post', 'data-modal-title' => '')
 */
body.on('click', 'a.ajax', function () {
    let jthis = $(this);
    let text = jthis.data('confirm-text') || '';

    if (text) {
        $.confirm(text, function () {
            well_click_ajax()
        });
    } else {
        well_click_ajax();
    }

    function well_click_ajax() {
        let method = xn.strtolower(jthis.data('method'));
        let href = jthis.data('href') || jthis.attr('href');
        if ('post' == method) {
            let postdata = jthis.data('json');
            $.xpost(href, postdata, function (code, message) {
                if (0 == code) {
                    if (undefined == message.text) {
                        window.location.reload();
                    } else {
                        jthis.html(message.text);
                        if (message.url) jthis.attr('href', message.url); /*url*/
                        if (message.method) jthis.attr('data-method', message.method); /*data-method*/
                        if (message.modal) jthis.attr('data-method', message.modal); /*data-modal-title*/
                    }
                } else if ('url' == code) {
                    window.location = message;
                } else {
                    $.alert(message);
                }
            });
        } else {
            window.location = jthis.attr('href');
        }
    }

    return false;
});

/*选中所有 / check all
 <input class="checkall" data-target=".tid" />*/
body.on('click', 'input.checkall', function () {
    var jthis = $(this);
    var target = jthis.data('target');
    jtarget = $(target);
    jtarget.prop('checked', this.checked);
});

/*
 jmobile_collapsing_bavbar = $('#mobile_collapsing_bavbar');
 jmobile_collapsing_bavbar.on('touchstart', function(e) {
 //var h = $(window).height() - 120;
 var h = 350;
 jmobile_collapsing_bavbar.css('overflow-y', 'auto').css('max-height', h+'px');
 e.stopPropagation();
 });
 jmobile_collapsing_bavbar.on('touchmove', function(e) {
 //e.stopPropagation();
 //e.stopImmediatePropagation();
 });*/

/*引用 / Quote*/
body.on('click', '.well_reply', function () {
    var jthis = $(this);
    var tid = jthis.data('tid');
    var pid = jthis.data('pid');
    var jmessage = $('#message');
    var jli = jthis.closest('.post');
    var jpostlist = jli.closest('.postlist');
    var jadvanced_reply = $('#advanced_reply');
    var jform = $('#form');
    if (jli.hasClass('quote')) {
        jli.removeClass('quote');
        jform.find('input[name="quotepid"]').val(0);
        jadvanced_reply.attr('href', xn.url('comment-create-' + tid));
    } else {
        jpostlist.find('.post').removeClass('quote');
        jli.addClass('quote');
        jform.find('input[name="quotepid"]').val(pid);
        jadvanced_reply.attr('href', xn.url('comment-create-' + tid + '-' + pid));
    }
    jmessage.focus();
    return false;
});

/*引用 / Quote*/
body.on('click', '.post_reply', function () {
    var jthis = $(this);
    var tid = jthis.data('tid');
    var pid = jthis.data('pid');
    var jmessage = $('#message');
    var jli = jthis.closest('.post');
    var jpostlist = jli.closest('.postlist');
    var jadvanced_reply = $('#advanced_reply');
    var jform = $('#quick_reply_form');
    if (jli.hasClass('quote')) {
        jli.removeClass('quote');
        jform.find('input[name="quotepid"]').val(0);
        jadvanced_reply.attr('href', xn.url('post-create-' + tid));
    } else {
        jpostlist.find('.post').removeClass('quote');
        jli.addClass('quote');
        jform.find('input[name="quotepid"]').val(pid);
        jadvanced_reply.attr('href', xn.url('post-create-' + tid + '-0-' + pid));
    }
    jmessage.focus();
    return false;
});

/* 删除 / Delete post*/
body.on('click', '.post_delete', function () {
    var jthis = $(this);
    var href = jthis.data('href');
    if (window.confirm(lang.confirm_delete)) {
        $.xpost(href, {safe_token: safe_token}, function (code, message) {
            var isfirst = jthis.attr('isfirst');
            if (code == 0) {
                if (isfirst == 1) {
                    window.location = jthis.attr('forum-url');
                } else {
                    // 删掉楼层
                    jthis.parents('.post').remove();
                    // 回复数 -1
                    var jposts = $('.posts');
                    jposts.html(xn.intval(jposts.html()) - 1);
                }
            } else {
                $.alert(message);
            }
        });
    }
    return false;
});

body.on('click', '.install, .uninstall', function () {
    var jthis = $(this);
    var href = jthis.data('href') || jthis.attr('href');
    $.xpost(href, function (code, message) {
        if (code == 0) {
            $.alert(message).delay(1000).location();
        } else {
            $.alert(message);
        }
    });
    return false;
});

$(function () {
    var body = $('body');
    body.on('click', '#but-sidebar-toggle', function () {
        var toggle = $('#sidebar-toggle');
        toggle.toggleClass('position-fixed d-none d-lg-block');
        toggle.collapse('hide');
        toggle.css('z-index', '999');
    });

    var scroll_top = function (scroll_distance) {
        if (scroll_distance > 100) {
            $('.scroll-to-top').fadeIn();
            $('.scroll-to-bottom').fadeOut();
        } else {
            $('.scroll-to-top').fadeOut();
            $('.scroll-to-bottom').fadeIn();
        }
    };

    /* Scroll to top button appear */
    var wrapper = $('#content-wrapper');
    if (wrapper.length > 0) {
        wrapper.on('scroll', function () {
            scroll_top($(this).scrollTop());
        });
    } else {
        $(document).on('scroll', function () {
            scroll_top($(this).scrollTop());
        });
    }

    /* scroll to top */
    body.on('click', 'a.scroll-to-top', function (e) {
        $('html, body, #content-wrapper').animate({scrollTop: 0}, 500);
        e.preventDefault();
    });

    /* scroll to bottom */
    body.on('click', 'a.scroll-to-bottom', function (e) {
        var height = $('#body').height() || $('body').height();
        $('html, body, #content-wrapper').animate({scrollTop: height}, 500);
        e.preventDefault();
    });
});

/* post 数组格式化为 get 请求参数 */
function well_params_fmt(data) {
    var arr = [];
    for (var name in data) {
        arr.push(encodeURIComponent(name) + "=" + encodeURIComponent(data[name]));
    }
    arr.push(("v=" + Math.random()).replace(".", ""));
    return arr.join("&");
}

/*
滚动到窗口可视区域元素位置中间下方
well_set_top('id', Element)
*/
function well_set_top(Type, Element) {
    let scrollTop = document.documentElement.scrollTop;
    let scrollHeight = document.body.scrollHeight;
    let innerHeight = window.innerHeight;
    let from = 'id' === Type ? document.getElementById(Element) : document.getElementsByClassName(Element);
    /* 距离顶部距离 */
    let top = from.getBoundingClientRect().top;
    /* 元素高度 */
    let height = from.getBoundingClientRect().height;
    _height = top - innerHeight / 2 - height;
    if (top > innerHeight) {
        _height = innerHeight / 2;
    }

    let x = from.offsetTop + _height;

    /* 判断是否在移动端打开 */
    /*let u = navigator.userAgent;
    if (u.match(/AppleWebKit.*Mobile.*!/)) {
        x = form.offsetTop + _height;
    }*/
    let timer = setInterval(function () {
        document.documentElement.scrollTop += _height;
        if (document.documentElement.scrollTop >= x) {
            clearInterval(timer);
        }
    }, 50);

    let timer_1 = setInterval(function () {
        window.pageYOffset += _height;
        if (window.pageYOffset >= x) {
            clearInterval(timer_1);
        }
    }, 50);

    let timer_2 = setInterval(function () {
        document.body.scrollTop += _height;
        if (document.body.scrollTop >= x) {
            clearInterval(timer_2);
        }
    }, 50);
}

/*
获取表单值 调用方法
formId
format:0对象{'key':'value'} 1字符串key=value
console.log(well_serialize_form('form'));
console.log(well_serialize_form('form', 1));
*/
function well_serialize_form(formId, format) {

    let form = document.getElementById(formId);
    if (form && 'FORM' != form.tagName) {
        let parent = form.parentNode;
        while ('FORM' != parent.tagName) {
            parent = parent.parentNode;
        }
        if (!parent) '';
        formId = parent.id;
    } else {
        formId = form.id;
        if (!formId) return '';
    }

    format = format || 0;
    let elements = well_get_elements(formId);
    let queryComponents = new Array();
    let length = elements.length;
    for (let i = 0; i < length; ++i) {
        let queryComponent = well_serialize_element(elements[i], format);
        if (queryComponent) queryComponents.push(queryComponent);
    }

    if (format) return queryComponents.join('&');

    let ojb = {}
    let len = queryComponents.length;
    if (!len) return ojb;

    for (let i = 0; i < len; ++i) {
        ojb[queryComponents[i][0]] = queryComponents[i][1];
    }

    return ojb;
}

/*
获取指定form中的所有的<input>对象
暂时不支持表单数组name="a[]"
*/
function well_get_elements(formId) {
    let form = document.getElementById(formId);
    if (!form) return '';
    let elements = new Array();
    let tagInputs = form.getElementsByTagName('input');
    for (let i = 0; i < tagInputs.length; ++i) {
        elements.push(tagInputs[i]);
    }

    let tagSelects = form.getElementsByTagName('select');
    for (let i = 0; i < tagSelects.length; ++i) {
        elements.push(tagSelects[i]);
    }

    let tagTextareas = form.getElementsByTagName('textarea');
    for (let i = 0; i < tagTextareas.length; ++i) {
        elements.push(tagTextareas[i]);
    }

    return elements;
}

/* 组合URL 0数组'key':'value' 1字符串key=value */
function well_serialize_element(element, format) {
    format = format || 0;
    let method = element.tagName.toLowerCase();
    let parameter;

    if ('select' == method) parameter = [element.name, element.value];

    switch (element.type.toLowerCase()) {
        case 'submit':
        case 'hidden':
        case 'password':
        case 'text':
        case 'date':
        case 'textarea':
            parameter = [element.name, element.value];
            break;
        case 'checkbox':
        case 'radio':
            if (element.checked) {
                parameter = [element.name, element.value];
            }
            break;
    }

    if (parameter) {
        let key = encodeURIComponent(parameter[0]);
        if (0 == key.length) return;

        if (parameter[1].constructor != Array) parameter[1] = [parameter[1]];

        let results = new Array();
        let values = parameter[1];
        let length = values.length;
        for (let i = 0; i < length; ++i) {
            if (format) {
                results.push(key + '=' + encodeURIComponent(values[i]));
            } else {
                results = [key, values[i]];
            }
        }

        if (format) {
            return results.join('&');
        } else {
            return results;
        }
    }
}

/*
* body = Element
* options = {'title': 'title', 'timeout': '1', 'size': '', 'width': '550px', 'fixed': 'bottom', 'bg': 'white', 'screen': 'black'};
*
* title 标题
* timeout x秒关闭 0点击关闭 -1自行使用代码关闭
* size 模态框大小CSS 定义的class / bootstrap 可以使用 modal-dialog modal-md
* width 限制模态框宽度 size和width同时存在时，使用width 550px
* fixed 默认居中center 从底部弹出bottom
* screen 弹窗全屏背景 默认透明 black 黑色60%透明度
* bg 弹窗背景 默认黑色60%透明度 white or black
* rounded 边框角度，默认0.25rem 圆角
* */
$.modal = function (body, options) {

    let w_modal = document.getElementById('w-modal');
    if (w_modal) w_modal.parentNode.removeChild(w_modal);

    options = options || {'title': '', 'timeout': '1', 'size': '', 'width': '550px', 'fixed': 'center', 'screen': '', 'bg': 'rgb(0 0 0 / 60%)', 'rounded': '0.25rem'};
    if (options.size && options.width) options.size = '';

    if ('white' == options.bg) {
        options.bg = '#FFFFFF';
        font_bg = 'rgb(0 0 0 / 100%)';
    } else if ('black' == options.bg) {
        options.bg = 'rgb(0 0 0 / 60%)';
        font_bg = '#FFFFFF';
    } else {
        options.bg = 'rgb(0 0 0 / 60%)';
        font_bg = '#FFFFFF';
    }

    let styleCode = '';
    let header = '';
    if (options.title || 0 == options.timeout) {
        let title = '&nbsp;';
        if (options.title) {
            title = '<div id="w-title" style="position: relative;margin: .5rem .5rem;line-height: 1.3;font-weight: bold;font-size: 1.05rem;color: '+font_bg+';">' + options.title + '</div>';
        }

        let close = '';
        if (0 == options.timeout) {
            close = '<span id="w-modal-close" style="position: relative;padding: .5rem .5rem;float: right;font-size: 1.5rem;font-weight: 700;cursor:pointer;color: '+font_bg+';">&times;</span>';
        }

        header = '\
        <div id="w-modal-header" style="display: flex;position: relative;width: 100%;align-items: flex-start;justify-content: space-between;line-height: .8;padding: 0.5rem 0;">\
            ' + title + '\
            ' + close + '\
		</div>';
    }

    if (!options.fixed) options.fixed = 'center';
    if (!options.rounded) options.rounded = '0.25rem';

    if ('top' == options.fixed) {
        fixed = 'position:fixed;top:0;left:0;visibility:visible;animation: modal-fadein .5s;';
        radius = 'border-bottom-left-radius:'+options.rounded+';border-bottom-right-radius:'+options.rounded+';';
        options.width = '100%';
        styleCode += '@keyframes modal-fadein { from{opacity:0;top:0;} to{opacity:1;top:0;}}';
    } else if ('center' == options.fixed) {
        /*let Width = window.screen.availWidth;
        if (Width > 800) {
            maxWidth = 'calc(100% - 30px)';
        } else {
            maxWidth = '100%';
        }*/
        let maxWidth = 'calc(100% - 30px)';
        fixed = 'position: relative;top:50%;left:50%;max-height:calc(100% - 30px);max-width:'+maxWidth+';transform:translate(-50%,-50%);';
        radius = 'border-radius: '+options.rounded+';';
    } else if ('bottom' == options.fixed) {
        fixed = 'position:fixed;bottom:0;left:0;visibility:visible;animation: modal-fadein .5s;';
        radius = 'border-top-left-radius:'+options.rounded+';border-top-right-radius:'+options.rounded+';';
        options.width = '100%';
        styleCode += '@keyframes modal-fadein { from{opacity:0;bottom:0;} to{opacity:1;bottom:0;}}';
    }

    let style = '<style>' + styleCode + '</style>';

    let screen = '';
    if (options.screen && 'black' == options.screen) {
        screen = 'background-color: rgb(0 0 0 / 60%);';
    }

    const s = '\
    ' + style + '\
    <div style="display: block;overflow-x: hidden;overflow-y: hidden;position: fixed;top: 0;left: 0;z-index: 1050;width: 100%;height: 100%;'+screen+'">\
        <div id="w-modal-dialog" style="flex-direction: column;overflow-x: hidden;overflow-y: hidden;margin:0 !important;width: 100%;' + fixed + '">\
            <div id="w-wrap" class="' + options.size + '" style="position: relative;margin: 0 auto;max-width:' + options.width + ';font-size: 1.2rem;background-color: ' + options.bg + ';color: '+font_bg+';pointer-events: auto !important;overflow-x: hidden;overflow-y: hidden;' + radius + '">\
            <div id="w-modal-content" style="display: block;position: relative;display: -ms-flexbox;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;padding: 0 .5rem;overflow-x: hidden;overflow-y: auto;width: 100%;">\
                ' + header + '\
                <div id = "w-modal-body" style = "display: block;display: -ms-flexbox;display: flex;position: relative;-ms-flex-direction: column;flex-direction: column;word-wrap: break-word;-ms-flex: 1 1 auto;flex: 1 1 auto;width: 100%;" >' + body + '</div>\
            </div>\
        </div>\
    </div>';

    let modal = document.createElement("div");
    modal.id = 'w-modal';
    modal.innerHTML = s;
    let jmodal = document.body.insertBefore(modal, document.body.lastElementChild);
    if (typeof options.timeout) {
        w_modal = document.getElementById('w-modal');
        console.log(w_modal)
        if (options.timeout > 0) {
            setTimeout(function () {
                w_modal.parentNode.removeChild(w_modal);
            }, options.timeout * 1000);
        } else if (0 == options.timeout) {
            w_close = document.getElementById('w-modal-close');
            if (w_close) {
                w_close.addEventListener('click', function (e) {
                    w_modal.parentNode.removeChild(w_modal);
                    e.stopPropagation();
                });
            }
        }
    }

    return jmodal;
};

/*
options = {'title': '标题可空', 'timeout': 0, 'size': '定义的class', 'width': '550px', 'fixed': 'center or bottom', 'screen': 'black 黑色背景', 'rounded': '0.25rem 圆角', 'bg': 'white or black 默认黑色60%透明度'}
*/
$.ajaxModal = function (url, callback, arg, options) {
    options = options || {'title': '.', 'timeout': 0, 'size': '', 'width': '550px', 'fixed': 'center', 'screen': '', 'rounded': ''};
    if (0 != options.timeout) options.timeout = 0;
    if (!options.size && !options.width) options.width = '550px';

    let jmodal = $.modal('<div style="text-align: center;padding-bottom: 1.5rem;padding-top: .5rem;">Loading...</div>', options);

    jmodal.querySelector('[id="w-title"]').innerHTML = options.title;

    /*ajax 加载内容*/
    $.xget(url, function (code, message) {
        /*对页面 html 进行解析*/
        if (code == -101) {
            var r = xn.get_title_body_script_css(message);
            jmodal.querySelector('[id="w-modal-body"]').innerHTML = r.body;
        } else {
            jmodal.querySelector('[id="w-modal-body"]').innerHTML = '<div style="text-align: center;padding-bottom: 1.5rem;padding-top: .5rem;">' + message + '</div>';
            return;
        }
        /*eval script, css*/
        xn.eval_stylesheet(r.stylesheet_links);
        jmodal.script_sections = r.script_sections;
        if (r.script_srcs.length > 0) {
            $.require(r.script_srcs, function () {
                xn.eval_script(r.script_sections, {'jmodal': jmodal, 'callback': callback, 'arg': arg});
            });
        } else {
            xn.eval_script(r.script_sections, {'jmodal': jmodal, 'callback': callback, 'arg': arg});
        }
    });

    return jmodal;
};

/*
modal-width 和 modal-size 同时存在，优先使用 modal-width

<button id="button1" class="w-ajax-modal btn btn-primary" modal-url="user-login.htm" modal-title="用户登录" modal-arg="xxx" modal-callback="login_success_callback" modal-width="550px" modal-size="md" modal-fixed="bottom" modal-bg="white" modal-rounded="1rem" modal-screen="black">登陆</button>

<a class="w-ajax-modal nav-link" rel="nofollow" modal-title="<?php echo lang('login');?>" modal-arg="xxx" modal-callback="login_success_callback" modal-width="550px" modal-size="md" modal-fixed="bottom" modal-bg="white" modal-screen="black" modal-rounded="1rem" href="<?php echo url('user-login');?>"><i class="icon-user"></i>&nbsp;<?php echo lang('login');?></a>
*/
$(function () {
    var modalList = document.getElementsByClassName('w-ajax-modal');
    var length = modalList.length;
    for (var i = 0; i < length; ++i) {
        modalList[i].onclick = function (e) {
            let jthis = this;
            let url = jthis.getAttribute('modal-url') || jthis.getAttribute('href');
            let title = jthis.getAttribute('modal-title');
            if (!title) title = '';
            let arg = jthis.getAttribute('modal-arg');
            if (!arg) arg = '';
            let callback_str = jthis.getAttribute('modal-callback');
            let callback = callback_str ? window[callback_str] : '';
            let width = jthis.getAttribute('modal-width');
            if (!width) width = '';
            let size = jthis.getAttribute('modal-size');
            if (!size) size = '';
            let fixed = jthis.getAttribute('modal-fixed');
            if (!fixed) fixed = '';
            let bg = jthis.getAttribute('modal-bg');
            if (!bg) bg = '';
            let screen = jthis.getAttribute('modal-screen');
            if (!screen) screen = '';
            let rounded = jthis.getAttribute('modal-rounded');
            if (!rounded) rounded = '';
            let options = {'title': title, 'timeout': 0, 'size': size, 'width': width, 'fixed': fixed, 'screen': screen, 'bg': bg, 'rounded': rounded}
            $.ajaxModal(url, callback, arg, options);
            e.stopPropagation();
            return false;
        }
    }
});

/*二位数组 依据 key 排序
* asc false升序 true降序
* */
arrListMultiSort = function (arrList, asc) {

    let newKeys = Object.keys(arrList).sort(function (a, b) {
        return parseInt(arrList[a].num) - parseInt(arrList[b].num)
    });

    if (asc) newKeys.reverse();

    var arr = []
    for (let i in newKeys) {
        arr.push(arrList[newKeys[i]]);
    }

    /*console.log(arr);*/
    return arr;
}

/**
 * number_format
 * @param number 传进来的数,
 * @param bit 保留的小数位,默认保留两位小数,
 * @param sign 为整数位间隔符号,默认为空格
 * @param gapnum 为整数位每几位间隔,默认为3位一隔
 * @type arguments的作用：arguments[0] == number(之一)
 */
number_format = function (number, bit, sign, gapnum) {
    /*设置接收参数的默认值*/
    bit = arguments[1] ? arguments[1] : 2;
    sign = arguments[2] ? arguments[2] : '';
    gapnum = arguments[3] ? arguments[3] : 3;
    var str = '';

    number = number.toFixed(bit);/*格式化*/
    realnum = number.split('.')[0];/*整数位(使用小数点分割整数和小数部分)*/
    decimal = number.split('.')[1];/*小数位*/
    realnumarr = realnum.split('');/*将整数位逐位放进数组 ["1", "2", "3", "4", "5", "6"]*/

    /*把整数部分从右往左拼接，每bit位添加一个sign符号*/
    for (var i = 1; i <= realnumarr.length; i++) {
        str = realnumarr[realnumarr.length - i] + str;
        if (i % gapnum == 0) {
            str = sign + str;/*每隔gapnum位前面加指定符号*/
        }
    }

    /*当遇到 gapnum 的倍数的时候，会出现比如 ",123",这种情况，所以要去掉最前面的 sign*/
    str = (realnum.length % gapnum == 0) ? str.substr(1) : str;
    /*重新拼接实数部分和小数位*/
    realnum = str + '.' + decimal;
    return realnum;
}

format_number = function (number) {
    number = parseInt(number);
    return number > 1000 ? (number > 1100 ? number_format((number / 1000), 1) : parseInt($number / 1000))+'K+' : number;
}

/**
 * 获取客户端信息
 */
get_device = function () {
    var userAgent = navigator.userAgent;
    var Agents = new Array('Android', 'iPhone', 'SymbianOS', 'Windows Phone', 'iPad', 'iPod');
    var agentinfo = null;
    for (var i = 0; i < Agents.length; i++) {
        if (userAgent.indexOf(Agents[i]) > 0) {
            agentinfo = userAgent;
            break;
        }
    }
    if (agentinfo) {
        return agentinfo;
    } else {
        return 'PC';
    }
}

//基本的使用实例
/*$.well_ajax({
    url:"http://server-name/login",
    type:'POST',
    data:{
        username:'username',
        password:'password'
    },
    dataType:'json',
    timeout:10000,
    contentType:"application/json",
    success:function(data){
        /!*服务器返回响应*!/
    },
    /!*异常处理*!/
    error:function(e){
        console.log(e);
    }
});*/

/*
$.well_post = function (url, postdata, callback, progress_callback) {
    postdata = postdata || null;
    $.well_ajax({
        type: 'POST',
        url: url,
        data: postdata,
        dataType: 'text',
        timeout: 6000000,
        progress: function (e) {
            if (e.lengthComputable) {
                if (progress_callback) progress_callback(e.loaded / e.total * 100);
            }
        },
        success: function (r) {
            if (!r) return callback(-1, 'Server Response Empty!');
            var s = xn.json_decode(r);
            if (!s || s.code === undefined) return callback(-1, 'Server Response Not JSON：' + r);
            if (s.code == 0) {
                return callback(0, s.message);
            } else if (s.code < 0) {
                return callback(s.code, s.message);
            } else {
                return callback(s.code, s.message);
            }
        },
        error: function (xhr, type) {
            if (type != 'abort' && type != 'error' || xhr.status == 403) {
                return callback(-1000, "xhr.responseText:" + xhr.responseText + ', type:' + type);
            } else {
                return callback(-1001, "xhr.responseText:" + xhr.responseText + ', type:' + type);
                console.log("xhr.responseText:" + xhr.responseText + ', type:' + type);
            }
        }
    });
};

$.well_ajax = function (options) {
    options = options ||{};
    options.type=(options.type || 'GET').toUpperCase();
    /!* 响应数据格式，默认json *!/
    options.dataType = options.dataType || 'json';
    /!* options.data请求的数据 *!/
    options.postdata = well_params_fmt(options.postdata);
    options.timeout = options.timeout || 6000000;
    options.contentType = options.contentType || 'application/json';
    var xhr;

    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else if (window.ActiveObject) {
        /!*兼容IE6以下版本*!/
        xhr = new ActiveXobject('Microsoft.XMLHTTP');
    }

    if ('GET' == options.type) {
        xhr.open('GET', options.url + "?" + options.postdata, true);
        xhr.send(null);
    } else if ('POST' == options.type) {
        xhr.open('POST', options.url, true);
        /!*设置表单提交时的内容类型Content-type数据请求的格式*!/
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send(options.postdata);
    }

    /!* 设置有效时间 *!/
    setTimeout(function () {
        if (xhr.readySate != 4) {
            xhr.abort();
        }
    }, options.timeout);

    /!*
    options.success成功之后的回调函数  options.error失败后的回调函数
    xhr.responseText,xhr.responseXML  获得字符串形式的响应数据或者XML形式的响应数据
    *!/
    xhr.onreadystatechange = function () {
        if (4 == xhr.readyState) {
            var status = xhr.status;
            if (status >= 200 && status < 300 ||  304 == status) {
                options.success && options.success(xhr.responseText, xhr.responseXML);
            } else {
                options.error && options.error(status);
            }
        }
    }
};*/