var w;
var h;
var dw;
var dh;
var resizefunc = [];

$(document).ready(function(){
	FastClick.attach(document.body);
	resizefunc.push("initscrolls");
	resizefunc.push("changeptype");

	$(".open-left").click(function(e){
		e.stopPropagation();
	    $("#wrapper").toggleClass("enlarged");
	    $("#wrapper").addClass("forced");

	    if($("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left")){
	    	$("body").removeClass("fixed-left").addClass("fixed-left-void");
	    }else if(!$("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left-void")){
	    	$("body").removeClass("fixed-left-void").addClass("fixed-left");
	    }
	    if($("#wrapper").hasClass("enlarged")){
	    	$(".left ul").removeAttr("style");
	    }else{
	    	$(".subdrop").siblings("ul:first").show();
	    }
	    toggle_slimscroll(".slimscrollleft");
	    $("body").trigger("resize");
	});

	// LEFT SIDE MAIN NAVIGATION
	$("#sidebar-menu a").on('click',function(e){
		if(!$("#wrapper").hasClass("enlarged")){

		    if($(this).parent().hasClass("has_sub")) {
		    	if ($(this).parent().find("ul").length>0) {
		    		e.preventDefault();
		    	} else {
		    		$('.active').removeClass('active');
		            $(this).addClass('active');
		    	}
		    }

		    if(!$(this).hasClass("subdrop")) {
		    	// hide any open menus and remove all other classes
		    	$("ul",$(this).parents("ul:first")).slideUp(350);
		    	$("a",$(this).parents("ul:first")).removeClass("subdrop");
		    	$("#sidebar-menu .pull-right i").removeClass("fa-angle-up").addClass("fa-angle-down");

		    	// open our new menu and add the open class
		    	$(this).next("ul").slideDown(350);
		    	$(this).addClass("subdrop");
		    	$(".pull-right i",$(this).parents(".has_sub:last")).removeClass("fa-angle-down").addClass("fa-angle-up");
		    	$(".pull-right i",$(this).siblings("ul")).removeClass("fa-angle-up").addClass("fa-angle-down");
		    }else if($(this).hasClass("subdrop")) {
		    	$(this).removeClass("subdrop");
		    	$(this).next("ul").slideUp(350);
		    	$(".pull-right i",$(this).parent()).removeClass("fa-angle-up").addClass("fa-angle-down");
		    	//$(".pull-right i",$(this).parents("ul:eq(1)")).removeClass("fa-chevron-down").addClass("fa-chevron-left");
		    }
		}
	});

	// NAVIGATION HIGHLIGHT & OPEN PARENT
	$("#sidebar-menu ul li.has_sub a.active").parents("li:last").children("a:first").addClass("active").trigger("click");

	// 导航点击事件
    $('.b-nav-li').click(function(event) {
        $('.active').removeClass('active');
        var ulObj=$(this).parents('.b-has-child').eq(0);
        $(this).addClass('active');
        // alert(2);
        if(ulObj.length!=0){
            $(this).parents('.b-has-child').eq(0).addClass('active');
        }
    });

    //NAVIGATION HIGHLIGHT & OPEN PARENT
    $("#sidebar-menu ul li.has_sub ul li a").click(function(e){
    	$("#sidebar-menu ul li.has_sub a").removeClass("active");
    	$(this).addClass("active");
    	$(this).parents(".has_sub").find("a:first").addClass("active");
    });

  //RUN RESIZE ITEMS
	$(window).resize(debounce(resizeitems,100));
	$("body").trigger("resize");

//	iFrameHeight();
//	window.setInterval("iFrameHeight()", 100);

	// 退出登录点击事件
    $('.logout-button').click(function(event) {
    	var ref = $(this).attr("ref");
    	//询问框
    	layer.confirm('你确定要退出会员系统吗？', {
    	    btn: ['确认','取消'] //按钮
    	}, function(index){
    		self.location = ref;
          	layer.close(index);
    	}, function(index){
    		layer.close(index);
    	});
    });
});

function iFrameHeight() {
	var ifm= document.getElementById("main-frame");
	var subWeb = document.frames ? document.frames["main-frame"].document : ifm.contentDocument;
	if(ifm != null && subWeb != null) {
		var availHeight = document.documentElement.clientHeight -95;
		
		if(subWeb.body != null){
			ifm.height = subWeb.body.scrollHeight>availHeight ?subWeb.body.scrollHeight : availHeight;
		}
	}
}

var changeptype = function(){
    w = $(window).width();
    h = $(window).height();
    dw = $(document).width();
    dh = $(document).height();

    if(jQuery.browser.mobile === true){
      	$("body").addClass("mobile").removeClass("fixed-left");
    }

    if(!$("#wrapper").hasClass("forced")){
	    if(w > 990){
	    	$("body").removeClass("smallscreen").addClass("widescreen");
	        $("#wrapper").removeClass("enlarged");
	    }else{
	    	$("body").removeClass("widescreen").addClass("smallscreen");
	    	$("#wrapper").addClass("enlarged");
	    	$(".left ul").removeAttr("style");
	    }
	    if($("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left")){
	    	$("body").removeClass("fixed-left").addClass("fixed-left-void");
	    }else if(!$("#wrapper").hasClass("enlarged") && $("body").hasClass("fixed-left-void")){
	    	$("body").removeClass("fixed-left-void").addClass("fixed-left");
	    }

	}
	toggle_slimscroll(".slimscrollleft");
}

var debounce = function(func, wait, immediate) {
	var timeout, result;
	return function() {
	    var context = this, args = arguments;
	    var later = function() {
	      timeout = null;
	      if (!immediate) result = func.apply(context, args);
	    };
	    var callNow = immediate && !timeout;
	    clearTimeout(timeout);
	    timeout = setTimeout(later, wait);
	    if (callNow) result = func.apply(context, args);
	    return result;
	};
}

function resizeitems(){
	if($.isArray(resizefunc)){
	    for (i = 0; i < resizefunc.length; i++) {
	        window[resizefunc[i]]();
	    }
	}
}

function initscrolls(){
    if(jQuery.browser.mobile !== true){
	    $('.slimscrollleft').slimScroll({
	        height: 'auto',
	        position: 'left',
	        size: "5px",
	        color: '#7A868F'
	    });
	}
}

function toggle_slimscroll(item){
    if($("#wrapper").hasClass("enlarged")){
    	$(item).css("overflow","inherit").parent().css("overflow","inherit");
    	$(item). siblings(".slimScrollBar").css("visibility","hidden");
    }else{
    	$(item).css("overflow","hidden").parent().css("overflow","hidden");
    	$(item). siblings(".slimScrollBar").css("visibility","visible");
    }
}

function toggle_fullscreen(){
    var fullscreenEnabled = document.fullscreenEnabled || document.mozFullScreenEnabled || document.webkitFullscreenEnabled;
    if(fullscreenEnabled){
    	if(!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
    		launchIntoFullscreen(document.documentElement);
    	}else{
    		exitFullscreen();
    	}
    }
}

function launchIntoFullscreen(element) {
	if(element.requestFullscreen) {
		element.requestFullscreen();
	} else if(element.mozRequestFullScreen) {
		element.mozRequestFullScreen();
	} else if(element.webkitRequestFullscreen) {
		element.webkitRequestFullscreen();
	} else if(element.msRequestFullscreen) {
		element.msRequestFullscreen();
	}
}

function exitFullscreen() {
	if(document.exitFullscreen) {
		document.exitFullscreen();
	} else if(document.mozCancelFullScreen) {
		document.mozCancelFullScreen();
	} else if(document.webkitExitFullscreen) {
		document.webkitExitFullscreen();
	}
}