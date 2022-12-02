<?php
namespace app\validate;
use think\Validate;
class UserValidate extends Validate{
    protected $rule = [
        'user_name'=>'require|max:50',
        'password'=>'require',
        'email'=>'require|email',
        'vaild'=>'require|number',
        'password1'=>'require',
        'password2'=>'require',
        'status'=>'require|number'
    ];
    protected $message = [
        'user_name.require'=>"用户名不能为空",
        'user_name.max'=>"用户名不能超过50个字符",
        'password.require'=>"密码不能为空",
        'email.require'=>'邮箱不能为空',
        'email.email'=>'不符合邮箱规则',
        'vaild.number'=>"验证码错误",
        'vaild.require'=>"验证码不能为空",
        'password1.require'=>"密码不能为空",
        'password2.require'=>"再次输入密码不能为空",
        'status.require'=>'状态不能为空',
        'status.number'=>"状态类型错误"
    ];

    protected $scene = ['send'=>["email"],
    'login'=>['user_name','password','email','vaild'],
    'forget'=>['email','vaild','password1','password2'],
    'email'=>['email'],
    'status'=>['status']
];
}