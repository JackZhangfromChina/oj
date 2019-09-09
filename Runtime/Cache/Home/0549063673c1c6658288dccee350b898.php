<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo ($title); ?></title>   
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="description" content="<?php echo ($description); ?>">
        <meta name="keywords" content="<?php echo ($keywords); ?>">

        <!-- Base Css Files -->
        <link href="/Public/statics/coco-chat/assets/libs/jqueryui/ui-lightness/jquery-ui-1.10.4.custom.min.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/fontello/css/fontello.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/animate-css/animate.min.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/nifty-modal/css/component.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/magnific-popup/magnific-popup.css" rel="stylesheet" /> 
        <link href="/Public/statics/coco-chat/assets/libs/jquery-notifyjs/styles/metro/notify-metro.css" rel="stylesheet" type="text/css" />
        <link href="/Public/statics/coco-chat/assets/libs/pace/pace.css" rel="stylesheet" />
        
        <!-- Extra CSS Files -->
        <link href="/Public/statics/coco-chat/assets/libs/layer/skin/default/layer.css" rel="stylesheet" />
        <link href="/Public/statics/coco-chat/assets/libs/jquery-icheck/skins/all.css" rel="stylesheet" />
      	<link href="/Public/statics/coco-chat/assets/libs/bootstrap-validator/css/bootstrapValidator.min.css" rel="stylesheet"/>
      	<link href="/Public/statics/coco-chat/assets/libs/bootstrap-select/bootstrap-select.min.css" rel="stylesheet" type="text/css" />
        
        
        <!-- Custom Css Files -->
        <link href="/tpl/Public/css/reset.css" rel="stylesheet"  />
        <link href="/Public/statics/coco-chat/assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="/Public/statics/coco-chat/assets/css/style-responsive.css" rel="stylesheet" />
        <link href="/tpl/Home/Public/css/index.css"  rel="stylesheet" />

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="fixed-left login-page">
	<!-- Begin page -->
	<div class="container">
		<div class="full-content-center">
			<p class="text-center"><a href="#"><img src='<?php if(empty($logo)): ?>/tpl/Public/images/logo.png<?php else: echo ($logo); endif; ?>' alt="Logo" class="logo-img"></a></p>
			<div class="login-wrap animated flipInX">
				<div class="login-block">
					<img src='<?php if(empty($head_portrait)): ?>/tpl/Public/images/default-user.png<?php else: echo ($head_portrait); endif; ?>' class="img-circle not-logged-avatar">
					<form role="form" id='login-form' action="<?php echo U('Login/login');?>">
						<div class="form-group login-input">
							<i class="fa fa-user overlay"></i>
							<?php if(!empty($remember)): ?><input type="text" name='username' value="<?php echo ($remember); ?>" class="form-control text-input" placeholder="会员编号">
							<?php else: ?>
							    <input type="text" name='username' class="form-control text-input" placeholder="会员编号"><?php endif; ?>
						</div>
						<div class="form-group login-input">
							<i class="fa fa-key overlay"></i>
							<input type="password" name='password' class="form-control text-input" placeholder="********">
						</div>
						
						<!-- <div class="form-group">
							<select class="form-control selectpicker" name="lang_type" id="language_type">
			                  	<option value="zh_cn" <?php if($lang_type == 'zh_cn'): ?>selected<?php endif; ?>>简体中文（中国）</option>
			                  	<option value="zh_tw" <?php if($lang_type == 'zh_tw'): ?>selected<?php endif; ?>>繁体中文（台湾）</option>
			                  	<option value="en" <?php if($lang_type == 'en'): ?>selected<?php endif; ?>>英语（英国）</option>
			                </select>
						</div> -->
						
						
						<div class="form-group" style="line-height:22px">
                    		<input type="checkbox" id="chkRemember" name="remb" checked="checked" >
							<label for="chkRemember"></label>
	                      	 记住我
	                      	<?php if($isforget != 'ignore'): ?><a style="float: right;" target="_blank" href="<?php echo U('Login/forgetPass');?>">忘记密码？</a><?php endif; ?>
						</div>
								  
						<div class="form-group">
							<button type="submit" class="btn btn-success btn-block">登 录</button>
						</div>
					</form>
				</div>
			</div>
			
		</div>
	</div>

	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="/Public/statics/coco-chat/assets/libs/jquery/jquery-1.11.1.min.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/bootstrap/js/bootstrap.min.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/jqueryui/jquery-ui-1.10.4.custom.min.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/jquery-ui-touch/jquery.ui.touch-punch.min.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/jquery-detectmobile/detect.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/jquery-notifyjs/notify.min.js"></script>
    <script src="/Public/statics/coco-chat/assets/libs/jquery-notifyjs/styles/metro/notify-metro.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/pace/pace.min.js"></script>
	
	<!-- Extra Js Files -->
	<script src="/Public/statics/coco-chat/assets/libs/layer/layer.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/jquery-icheck/icheck.min.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/bootstrap-validator/js/bootstrapValidator.min.js"></script>
	<script src="/Public/statics/coco-chat/assets/libs/bootstrap-select/bootstrap-select.min.js"></script>
    <script src="/Public/statics/coco-chat/assets/libs/bootstrap-select2/select2.min.js"></script>
	
	<!-- Custom Js Files -->
	<script src="/tpl/Public/js/sha1.js"></script>
	<script src="/tpl/Home/Public/js/common.js"></script>
	
	<script>
	$(function(){
		//ICHECK
		$('input').iCheck({
		  	checkboxClass: 'icheckbox_square-aero',
		  	radioClass: 'iradio_square-aero',
		  	increaseArea: '20%' // optional
		});
		
		//SELECT
		$('.selectpicker').selectpicker();
		
		$('#login-form').bootstrapValidator({
	        message: '验证不通过',
	        trigger: 'blur',
	        submitHandler: function(validator, form, submitButton) {
	        	var f_data = $('form').serializeArray();
				var data = {};
				$.each(f_data, function(i, field){
					if(field.name=='password') {
						data[field.name] = hex_sha1(field.value);
					} else {
						data[field.name] = field.value;
					}
			  	});
	        	// 用ajax提交表单
		      	$.post(form.attr('action'), data, function(response) {
		      		//弹出提示
					layer.msg(response.message);
					
					if(response.status){
						notify("success",response.message);
						window.parent.location.href = "<?php echo U('Index/index');?>";
					}else{
						notify("error",response.message);
					}
		        }, 'json');
	        },
	        fields: {
	        	username: {
	                validators: {
	                    notEmpty: {
	                        message: '不允许为空'
	                    }
	                }
	            },
	            password: {
	                validators: {
	                    notEmpty: {
	                        message: '不允许为空'
	                    }
	                }
	            }
	        }
	    });
		
		$('#language_type').change(function(){
      		var lang_type =  $('#language_type').val();
      		
      		$.post("<?php echo U('Login/setLang');?>", 'lang_type='+lang_type, function(response) {
	      		//弹出提示
				layer.msg(response.message);
				if(response.status){
					notify("success",response.message);
					window.location.reload();
				}else{
					notify("error",response.message);
				}
	        }, 'json');
      	});
	});
	</script>
	</body>
</html>