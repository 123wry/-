<html>
<head>
	<?php require_once "public/header.php"?>
</head>
<body>
	<div class="login_tab">
		<h4 class="login_title"><div></div>H&nbsp;W</h4>
		<div class="login_title"><span>登录</span></div>
		<input class="input_class user_class" required id="user_name"/>
		<span class="user_class">请输入用户名</span>
		<input class="input_class user_class" type="password" required id="password"/>
		<span class="user_class">请输入密码</span>
		<input class="input_class email" required id="email"/>
		<span>请输入邮箱</span>
		<div class="send"><button id="sendEmail">发送</button></div>
		
		<input class="input_class valid" required id="vaild"/>
		<span>请输入验证码</span>
		<input class="input_class forget_class" type="password" required id="password1"/>
		<span class="forget_class">请输入密码</span>
		<input class="input_class forget_class" type="password" required id="password2"/>
		<span class="forget_class">请再次输入密码</span>

		<p class="warn"></p>
		<p class="beizhu"><span id="forget">忘记密码</span><span id="regist">注册</span><span id="login">登录</span></p>
		<div class="regist_btn"><button id="regist_btn" type="button">注册</button></div>
		<div class="login_btn"><button id="login_btn" type="button">登录</button></div>
		<div class="forget_btn"><button id="forget_btn" type="button">确认</button></div>
	</div>
</body>
<link rel='stylesheet' href="css/login.css" />
<script src="js/login.js"></script>
</html>