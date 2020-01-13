/*表单快捷键提交 CTRL+ENTER   / form quick submit*/
$('form').keyup(function (e) {
    var jthis = $(this);
    if ((e.ctrlKey && (e.which == 13 || e.which == 10)) || (e.altKey && e.which == 83)) {
        jthis.trigger('submit');
        return false;
    }
});

/*点击响应整行：方便手机浏览  / check response line*/
$('.tap').on('click', function (e) {
    var href = $(this).attr('href') || $(this).data('href');
    if (e.target.nodeName == 'INPUT') return true;
    if ($(window).width() > 992) return;
    if (e.ctrlKey) {
        window.open(href);
        return false;
    } else {
        window.location = href;
    }
});

/*点击响应整行：导航栏下拉菜单   / check response line*/
$('ul.nav > li').on('click', function (e) {
    var jthis = $(this);
    var href = jthis.children('a').attr('href');
    if (e.ctrlKey) {
        window.open(href);
        return false;
    }
});

/*点击响应整行：，但是不响应 checkbox 的点击  / check response line, without checkbox*/
$('.thread input[type="checkbox"]').parents('td').on('click', function (e) {
    e.stopPropagation();
})

/* 导航子菜单 鼠标悬浮移除移入*/
$(function () {
    var dropdown = $(".dropdown");
    dropdown.mouseover(function () {
        $(this).addClass("show");
        $('.dropdown-menu').addClass("show");
    });
    dropdown.mouseleave(function(){
        $(this).removeClass("show");
        $('.dropdown-menu').removeClass("show");
    });
});

/*菜单右至左滑出*/
$('.button-show').click(function () {
    $(this).css("display", "none");
    $(this).removeClass('d-lg-none position-fixed rounded-left bg-secondary d-flex align-items-center');
    var nav = $('#nav-show');
    nav.css({"top": "0", "bottom": "0", "z-index": "1020"});
    nav.removeClass('d-none d-lg-block');
    nav.find('.post-sticky-top').removeClass('sticky-top pt-2');
    nav.find('.post-sticky-top').addClass('pt-5 px-2');
    nav.addClass('position-fixed col-9 offset-3 h-100 bg-white p-0');
    nav.animate({right: ""}, 500);
    return false;
});

/*菜单左至右隐藏*/
$('.button-hide').click(function () {
    $(this).css("display", "none");
    var show = $('.button-show');
    show.addClass('d-lg-none position-fixed rounded-left bg-secondary d-flex align-items-center');
    show.css("display", "block");
    var nav = $('#nav-show');
    nav.removeClass("top", "bottom", "z-index");
    nav.removeClass('position-fixed col-9 offset-3 h-100 bg-white p-0');
    nav.find('.post-sticky-top').removeClass('pt-5 px-2');
    nav.find('.post-sticky-top').addClass('sticky-top pt-2');
    nav.addClass('d-none d-lg-block');
    nav.animate({left: ""}, 500);
    return false;
});

/*tag*/
$(function () {
    $(".tag-input").val("");
    function get_tag_val(obj) {
        var str = "";
        var token = $(obj).parents(".tags").find(".tags-token");
        if (token.length < 1) {
            $(obj).parents(".tags").find(".tags-val").val("");
            return false;
        }
        for (var i = 0; i < token.length; i++) {
            str += token.eq(i).text() + ",";
            $(obj).parents(".tags").find(".tags-val").val(str);
        }
    }

    $(document).on("keydown", ".tag-input", function (event) {
        $(this).next().hide();
        var v = $(this).val().replace(/\s+/g, "");
        var reg = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？%]", 'g');
        v = v.replace(reg, "");
        v = $.trim(v);
        var token = $(this).parents(".tags").find(".tags-token");
        if (v != '') {
            if (event.keyCode == 13 || event.keyCode == 108 || event.keyCode == 32) {
                n = xn.strpos($('input[name="tags"]').val(), v);
                if (n >= 0) {
                    $(this).val("");
                    return false;
                }
                $('<span class="border border-secondary tag btn-sm my-1 mr-3 tags-token">' + v + '</span>').insertBefore($(this).parents(".tags").find(".tag-wrap"));
                $(this).val("");
                get_tag_val(this);
                return false;
            }
        } else {
            if (event.keyCode == 8) {
                if (token.length >= 1) {
                    $(this).parents(".tags").find(".tags-token:last").remove();
                    get_tag_val(this);
                }
            }
        }
    });

    $(document).on("click", ".tags-token", function () {
        var token = $(this).parents(".tags").find(".tags-token");
        var it = $(this).parents(".tags");
        $(this).remove();
        var str = "";
        var token = it.find(".tags-token");
        if (token.length < 1) {
            it.find(".tags-val").val("");
            return false;
        }
        for (var i = 0; i < token.length; i++) {
            str += token.eq(i).text() + ",";
            it.find(".tags-val").val(str);
        }
    });
});

/*
    确定框 / confirm / GET / POST
    <a href="1.php" data-confirm-text="确定删除？" class="confirm">删除</a>
    <a href="1.php" data-method="post" data-confirm-text="确定删除？" class="confirm">删除</a>
*/
$('a.confirm').on('click', function () {
    var jthis = $(this);
    var text = jthis.data('confirm-text');
    $.confirm(text, function () {
        var method = xn.strtolower(jthis.data('method'));
        var href = jthis.data('href') || jthis.attr('href');
        if (method == 'post') {
            $.xpost(href, function (code, message) {
                if (code == 0) {
                    window.location.reload();
                } else {
                    alert(message);
                }
            });
        } else {
            window.location = jthis.attr('href');
        }
    })
    return false;
});

/*选中所有 / check all
<input class="checkall" data-target=".tid" />*/
$('input.checkall').on('click', function () {
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
$('body').on('click', '.post_reply', function () {
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
        var s = jmessage.val();
        jform.find('input[name="quotepid"]').val(pid);
        jadvanced_reply.attr('href', xn.url('post-create-' + tid + '-0-' + pid));
    }
    jmessage.focus();
    return false;
});

/* BBS 删除 / Delete post*/
$('body').on('click', '.post_delete', function () {
    var jthis = $(this);
    var href = jthis.data('href');
    var isfirst = jthis.attr('isfirst');
    if (window.confirm(lang.confirm_delete)) {
        $.xpost(href, function (code, message) {
            var isfirst = jthis.attr('isfirst');
            if (code == 0) {
                if (isfirst == '1') {
                    $.location('<?php echo url("forum-$fid");?>');
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

$('body').on('click', '.install, .uninstall', function () {
    var href = $(this).data('href');
    $.xpost(href, function (code, message) {
        if (code == 0) {
            $.alert(message).delay(1000).location();
        } else {
            $.alert(message);
        }
    });
    return false;
});