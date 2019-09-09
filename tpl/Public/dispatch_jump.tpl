<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>跳转提示</title>
    <link href="__PUBLIC_CSS__/reset.css" rel="stylesheet" />
	<link href="__PUBLIC__/statics/coco-chat/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
	<link href="__PUBLIC__/statics/coco-chat/assets/libs/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
	<link href="__PUBLIC__/statics/coco-chat/assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="__PUBLIC_CSS__/base.css" rel="stylesheet" />
</head>
<body>
<!-- Begin page -->
<div class="container">
	<div class="full-content-center animated flipInX">
		<p><a href="#"><img src="__PUBLIC_IMAGES__/logo.png" alt="Logo" style="max-width:200px;"></a></p>
		<h3>{$message}{$error}</h3>
		<h4 class="text-lightblue-2">页面将在<b id="wait">{$waitSecond}</b>秒后<a id="href" href="{$jumpUrl}">跳转</a></h4>
	</div>
</div>
<script type="text/javascript">
	(function(){
	    var wait = document.getElementById('wait'),href = document.getElementById('href').href;
	    var interval = setInterval(function(){
	        var time = --wait.innerHTML;
	        if(time <= 0) {
	            location.href = href;
	            clearInterval(interval);
	        };
	    }, 1000);
	})();
</script>
</body>
</html>