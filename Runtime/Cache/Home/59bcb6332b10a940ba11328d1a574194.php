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
					<p class="text-center logintitle">忘记密码</p>
					<form role="form" id='vcode-form' action="<?php echo U('Login/forgetPass');?>">
					    <div class="form-group login-input">
					        <i class="fa fa-user overlay"></i>
			                <input type="text" class="form-control text-input" placeholder="用户名" name="user_no" id="user_no">
			            </div>
			            <div class="form-group login-input">
			            	<?php if($isforget == 'message'): ?><i class="fa fa-phone overlay"></i>
			            		<input type="text" name="phone" class="form-control text-input" placeholder="手机号" id="phone" />
			            	<?php else: ?>
			            	    <i class="icon-mail-2 overlay"></i>
			            	    <input type="text" name="email" class="form-control text-input" placeholder="email" id="email" /><?php endif; ?>
			            </div>
			            <div class="form-group login-input">
			            	<div class="verify_input">
			            		<input type="text" id='code' class="form-control" name="code" placeholder="验证码" />
			            	</div>
			            	<div class="verify_code">
			            		<a href="javascript:void(null)">
					                <img src='<?php echo U("Login/verify");?>' class="imgvcode" alt="CAPTCHA" onclick = "imgchange(this)"  title="点击切换" />
					            </a>
			            	</div>
			            	<div class="clear"></div>
			            </div>
			            <div class="form-group">
							<button type="submit" class="btn btn-success btn-block">下一步</button>
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
	<script src="/tpl/Home/Public/js/common.js"></script>
	
	<script>

	function imgchange(img){
       img.src = '<?php echo U("Login/verify");?>?'+Math.random();
	}

	$(function(){
	
		$('#vcode-form').bootstrapValidator({
	        message: '验证不通过',
	        trigger: 'blur',
	        submitHandler: function(validator, form, submitButton) {
	        	// 用ajax提交表单
		      	$.post(form.attr('action'), form.serialize(), function(response) {
					if(response.status){
						window.location.href = "<?php echo U('Login/confirmVerify');?>?user_no="+response.user_no;
					}else{
						$('.imgvcode').attr('src','<?php echo U("Login/verify");?>?'+Math.random());
						//弹出提示
					    layer.msg(response.message);
						notify("error",response.message);
						
					}
		        }, 'json');
	        },
	        fields: {
	        	user_no: {
	                validators: {
	                    notEmpty: {
	                        message: '不允许为空'
	                    }
	                }
	            },
	            phone: {
	                validators: {
	                    notEmpty: {
	                        message: '不允许为空'
	                    }
	                }
	            },
	            email: {
	                validators: {
	                    notEmpty: {
	                        message: '不允许为空'
	                    }
	                }
	            },
	            code: {
	                validators: {
	                    notEmpty: {
	                        message: '不允许为空'
	                    }
	                }
	            }
	        }
	    });
	});
	</script>
	</body>
</html>