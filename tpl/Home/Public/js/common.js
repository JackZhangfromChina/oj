$(document).ready(function(){
	window.parent.$("html,body").animate({"scrollTop": "0px"}, 100);
});

//通知
function notify(style, text, position) {
	if(style == "error"){
		icon = "fa fa-exclamation";
	}else if(style == "warning"){
		icon = "fa fa-warning";
	}else if(style == "success"){
		icon = "fa fa-check";
	}else if(style == "info"){
		icon = "fa fa-question";
	}else{
		icon = "fa fa-circle-o";
	}
    $.notify({
        text: text,
        image: "<i class='"+icon+"'></i>"
    }, {
        style: 'metro',
        className: style,
        globalPosition: position,
        showAnimation: "show",
        showDuration: 0,
        hideDuration: 10,
        autoHide: true,
        clickToHide: true
    });
}