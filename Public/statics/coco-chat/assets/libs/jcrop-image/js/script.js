/**
 *
 * HTML5 Image uploader with Jcrop
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * Copyright 2012, Script Tutorials
 * http://www.script-tutorials.com/
 */

// convert bytes into friendly format
function bytesToSize(bytes) {
    var sizes = ['Bytes', 'KB', 'MB'];
    if (bytes == 0)
        return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
}

// check for selected crop region
function checkForm() {
	var filedim = $('#filedim').val();
	if (filedim == ''){
		return true;
	}
	var filedimsplit = filedim.split('x');
	//验证像素相等
	if (parseInt(filedimsplit[0]) == parseInt(filedimsplit[1])) {
		$('#iscrop').val('notcrop');
		return true;
	} else {
		if (parseInt($('#w').val())){
			$('#iscrop').val('crop');
			return true;
		}else{
			return false;
		}
	}
}

// update info by cropping (onChange and onSelect events handler)
function updateInfo(e) {
    $('#x1').val(e.x);
    $('#y1').val(e.y);
    $('#w').val(e.w);
    $('#h').val(e.h);

    $('.crop_preview').css('display','block');
    if(parseInt(e.w) > 0){
        //计算预览区域图片缩放的比例，通过计算显示区域的宽度(与高度)与剪裁的宽度(与高度)之比得到
        var rx = $("#preview_box").width() / e.w; 
        var ry = $("#preview_box").height() / e.h;
        //通过比例值控制图片的样式与显示
        $("#crop_preview").css({
            width:Math.round(rx * $("#preview").width()) + "px",   //预览图片宽度为计算比例值与原图片宽度的乘积
            height:Math.round(rx * $("#preview").height()) + "px", //预览图片高度为计算比例值与原图片高度的乘积
            marginLeft:"-" + Math.round(rx * e.x) + "px",
            marginTop:"-" + Math.round(ry * e.y) + "px"
        });
    }
}

// clear info by cropping (onRelease event handler)
function clearInfo() {
    $('.info #w').val('');
    $('.info #h').val('');
    $("#preview").attr("src",'');
}

// Create variables (in this scope) to hold the Jcrop API and image size
var jcrop_api, boundx, boundy;

function fileSelectHandler() {

    // get selected file
    var oFile = $('#image_file')[0].files[0];

    // check for image type (jpg and png are allowed)
    var rFilter = /^(image\/jpeg|image\/png|image\/jpg)$/i;
    if (!rFilter.test(oFile.type)) {
        window.parent.layer.msg('请选择jpg、jpeg或png格式的图片');
        notify('error','请选择jpg、jpeg或png格式的图片');
        return;
    }

    // check for file size
    if (oFile.size > 5*1024*1024) {
        window.parent.layer.msg('请上传小于5M的图片');
        notify('error','请上传小于5M的图片');
        return;
    }

    // preview element
    var oImage = document.getElementById('preview');
    var crop_preview = document.getElementById('crop_preview');

    // prepare HTML5 FileReader
    var oReader = new FileReader();
    oReader.onload = function(e) {

        // e.target.result contains the DataURL which we can use as a source of the image
        oImage.src = e.target.result;
        crop_preview.src = oImage.src;
       
        oImage.onload = function() { // onload event handler

            $('#flag').val('edit');

            // display step 2
            $('.step2').fadeIn(500);

            // display some basic image info
            var sResultFileSize = bytesToSize(oFile.size);
            $('#filesize').val(sResultFileSize);
            $('#filetype').val(oFile.type);
            $('#filedim').val(oImage.naturalWidth + ' x ' + oImage.naturalHeight);

            // destroy Jcrop if it is existed
            if (typeof jcrop_api != 'undefined')
                jcrop_api.destroy();

            if(oImage.naturalWidth < 400 || oImage.naturalHeight < 400){
                window.parent.layer.msg('图片的宽度或高度不能小于400像素');
                notify('error','图片的宽度或高度不能小于400像素');
                $(oImage).hide();
                $('.crop_preview').hide();
                $('#uploadtip').html('');
               return false;
            }

            if(oImage.naturalWidth/oImage.naturalHeight != 1){
                $('#uploadtip').html('图片尺寸不是1:1，请进行裁剪');
            }

            // initialize Jcrop
            $('#preview').Jcrop({
                minSize: [400, 400], // min crop size
                aspectRatio: 1, // keep aspect ratio 1:1
                bgFade: true, // use fade effect
                bgOpacity: .3, // fade opacity
                boxWidth:600,
                boxHeight:600,
                onChange: updateInfo,
                onSelect: updateInfo,
                onRelease: clearInfo
            }, function() {
            	// use the Jcrop API to get the real image size
                var bounds = this.getBounds();
                boundx = bounds[0];
                boundy = bounds[1];

                // Store the Jcrop API in the jcrop_api variable
                jcrop_api = this;
            });

            //加载提交按钮
            //$('#uploadsubmit').css('display','block');
        };
    };

    // read selected file as DataURL
    oReader.readAsDataURL(oFile);
}