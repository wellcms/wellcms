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
        if (event.keyCode == 13 || event.keyCode == 108 || event.keyCode == 188 || event.keyCode == 32) {
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
        if (str.indexOf(',') != -1 || str.indexOf('，') != -1 || str.indexOf(' ') != -1) {
            create_tag();
            return false;
        }
    });

    function create_tag() {
        var tag_input = $('.tag-input');
        var tag = tag_input.val().replace(/\s+/g, '');
        var reg = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？%]", 'g');
        tag = tag.replace(reg, '');
        if (tag.length > 0) {
            var tags = $('input[name="tags"]').val();
            var arr = tags.split(',');
            if (arr.indexOf(tag) > -1) {
                tag_input.val('');
                return false;
            }
            if (Object.count(arr) <= 5) {
                $('<span class="border border-secondary tag btn-sm my-1 mr-3 tags-token">' + tag + '</span>').insertBefore(tag_input.parents('.tags').find('.tag-wrap'));
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
            str = str.replace(/\s+/g, '');
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
 <a class="ajax" rel="nofollow" href="<?php echo url('comment-create'); ?>" data-method="get" aria-label="评论提交">提交</a>

 <a class="ajax" rel="nofollow" href="<?php echo url('comment-create'); ?>" data-method="post" data-json="{data:1}" aria-label="评论提交">提交</a>

 var list = document.getElementsByClassName('follow');
    for (var i in list) {
        list[i].onclick = function () {
            var jthis = this;
            var _uid = jthis.getAttribute('uid');
            var href = jthis.getAttribute('href');
            var method = jthis.getAttribute('data-method');
            $.xpost(href, {'uid': _uid}, function (code, data) {

            });
            console.log(_uid);
            return false;
        };
    }

array('url' => url('my-follow', array('type' => 1,'followuid' => $followuid)), 'text' => lang('well_unfollow'), 'data-method' => 'post', 'data-modal-title' => '')
 */
body.on('click', 'a.ajax', function () {
    var jthis = $(this);
    var method = xn.strtolower(jthis.data('method'));
    var href = jthis.data('href') || jthis.attr('href');
    if ('post' == method) {
        var postdata = jthis.data('json');
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
    var jform = $('#quick_reply_form');
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
调用方法
formId
format:0对象{'key':'value'} 1字符串key=value
console.log(well_serialize_form('form'));
console.log(well_serialize_form('form', 1));
*/
function well_serialize_form(formId, format) {
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

/* 获取指定form中的所有的<input>对象 */
function well_get_elements(formId) {
    let form = document.getElementById(formId);

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