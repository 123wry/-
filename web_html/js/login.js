$("#regist").click(function(){
    $(".regist_btn").css("display","flex");
    $(".login_btn").css("display","none");
    $(".login_title span").html("注册");
    $("#regist").css("display","none");
    $("#login").css("display","block");
    $(".forget_class").css("display","none");
    $(".user_class").css("display","block");
    $(".forget_btn").css("display","none");
});

$("#login").click(function(){
    $(".regist_btn").css("display","none");
    $(".login_btn").css("display","flex");
    $(".login_title span").html("登录");
    $("#regist").css("display","block");
    $("#login").css("display","none");
    $(".forget_class").css("display","none");
    $(".user_class").css("display","block");
    $(".forget_btn").css("display","none");
});

$("#forget").click(function(){
    $(".user_class").css("display","none");
    $(".forget_class").css("display","block");
    $(".login_title span").html("忘记密码");
    $(".forget_btn").css("display","flex");
    $(".regist_btn").css("display","none");
    $(".login_btn").css("display","none");
});

$("#sendEmail").click(function(){
    $(".warn").html('') ;
    var email = $("#email").val();
    $.ajax({
        method:"post",
        url:domain+"/Login/send",
        data:{email:email},
        success:function(ret){
            if(ret.code !=200){
                $(".warn").html(ret.message) ;
            } else {
                $(".warn").html("邮件发送成功") ;
            }
        },
        error:function(e){
            $(".warn").html("网络异常");
        }
    });
});
$("#login_btn").click(function(){
    $(".warn").html('') ;
    var user_name = $("#user_name").val();
    var password = $("#password").val();
    var email = $("#email").val();
    var vaild = $("#vaild").val();
    $.ajax({
        url:domain+"/Login/login",
        method:"post",
        data:{user_name:user_name,password:password,email:email,vaild:vaild},
        success:function(ret){
            if(ret.code != 200){
                $(".warn").html(ret.message) ;
            } else {
                localStorage.setItem("headermustneed",ret['data']['uid']);
                window.location.href='index.php';
            }
        },
        error:function(e){
            $(".warn").html("网络异常");
        }
    });
});
$("#regist_btn").click(function(){
    $(".warn").html('') ;
    var user_name = $("#user_name").val();
    var password = $("#password").val();
    var email = $("#email").val();
    var vaild = $("#vaild").val();
    $.ajax({
        url:domain+"/Login/regist",
        method:"post",
        data:{user_name:user_name,password:password,email:email,vaild:vaild},
        success:function(ret){
            if(ret.code != 200){
                $(".warn").html(ret.message) ;
            } else {
                $("#login").trigger("click");
                $("#user_name").val("");
                $("#password").val("");
                $("#email").val("");
                $("#vaild").val("");
            }
        },
        error:function(e){
            $(".warn").html("网络异常");
        }
    });
});
$("#forget_btn").click(function(){
    $(".warn").html('') ;
    var email = $("#email").val();
    var vaild = $("#vaild").val();
    var password1 = $("#password1").val();
    var password2 = $("#password2").val();
    if(password1 != password2){
        $(".warn").html("两次密码不一致");
        return false;
    }
    $.ajax({
        url:domain+"/Login/forget",
        method:"post",
        data:{email:email,vaild:vaild,password1:password1,password2:password2},
        success:function(ret){
            if(ret.code != 200){
                $(".warn").html(ret.message) ;
            } else {
                $("#login").trigger("click");
                $("#user_name").val("");
                $("#password").val("");
                $("#email").val("");
                $("#vaild").val("");
            }
        },
        error:function(e){
            $(".warn").html("网络异常");
        }
    });
});