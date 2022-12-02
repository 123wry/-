<?php
namespace app\controller;

use app\BaseController;
use app\Validate\UserValidate;
use think\exception\ValidateException;

use think\Request;
use  app\model\User;



class Login extends BaseController
{
    public $request = null;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function send()
    {
        $to = $this->request->param("email");
        try{
            validate(UserValidate::class)->scene("send")->check([
                'email'=>$to
            ]);
        }catch(ValidateException $e){
            $msg = $e->getError();
            $result = ['code'=>400,"message"=>$msg];
            return json($result);
        }
        $num = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);

        try{
            $redis = connect_redis();
            $redis->set("send:email:".$to,$num);
            $redis->expire("send:email:".$to,600);
            $redis->close();
        } catch(\Exception $e) {
            $result = ["code"=>500,'message'=>'redis连接失败'];
            return json($result);
        }
        $subjuct = '[HW]验证码';
        $body = '<p>欢迎您使用HW</p><p>本次使用验证码:'.$num."</p>";
        $result = $this->sendEmail($subjuct,$body,$to);
        return json($result);
    }
    public function login()
    {
        $param = $this->request->param();

        try{
            validate(UserValidate::class)->scene("login")->check($param);
        }catch(ValidateException $e){
            $msg = $e->getError();
            $result = ['code'=>400,"message"=>$msg];
            return json($result);
        }
        $redis = connect_redis();
        try{
            $user = new User();
            $res  = $user->where("user_name",$param['user_name'])->field("password,email,user_id")->find();
            if($res['email'] != $param['email']){
                $result = ['code'=>400,'message'=>'邮箱错误'];
                return json($result);
            }
            $num = $redis->get("send:email:".$param['email']);
            if(empty($num)){
                $result = ['code'=>400,'message'=>'验证码错误(发送验证码后十分钟过期)'];
                return json($result);
            }
            if($param['vaild'] != $num){
                $result = ['code'=>400,'message'=>'验证码错误'];
                return json($result);
            }
            $password = md5(md5($param['password']));
            if($password != $res['password']){
                $result = ['code'=>400,'message'=>'用户名或密码错误'];
                return json($result);
            }
            $uid = time().$res['user_id'].rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
            $redis->hset($uid,'email',$res['email']);
            $redis->hset($uid,'user_id',$res['user_id']);
            $redis->expire($uid,86400);
            $redis->close();
        }catch(\Exception $e){
            $result = ['code'=>500,'message'=>'用户名或密码传输错误'];
            return json($result);
        }
       
        $result = ['code'=>200,'message'=>'登录成功','data'=>['uid'=>$uid]];
        return json($result);
    }
    public function regist()
    {
        $param = $this->request->param();
        try{
            validate(UserValidate::class)->scene("login")->check($param);
        }catch(ValidateException $e){
            $msg = $e->getError();
            $result = ['code'=>400,"message"=>$msg];
            return json($result);
        }
        $redis = connect_redis();
        try{
            $num = $redis->get("send:email:".$param['email']);
            $redis->close();
        }catch(\Exception $e){
            $result = ['code'=>400,'message'=>'邮箱错误'];
            return json($result);
        }
        if(empty($num)){
            $result = ['code'=>400,'message'=>'验证码错误(发送验证码后十分钟过期)'];
            return json($result);
        }
        if($param['vaild'] != $num){
            $result = ['code'=>400,'message'=>'验证码错误'];
            return json($result);
        }
        try{
            $password = md5(md5($param['password']));
            $user = new User();
            $res  = $user->where("user_name",$param['user_name'])->field("password")->find();
            if(!empty($res)){
                $result = ['code'=>400,'message'=>'用户名已存在'];
                return json($result);
            }
            $res  = $user->where("email",$param['email'])->field("password")->find();
            if(!empty($res)){
                $result = ['code'=>400,'message'=>'邮箱已存在'];
                return json($result);
            }
            $res = $user->save([
                "user_name"=>$param['user_name'],
                "password"=>$password,
                "email"=>$param['email']
            ]);
            if(empty($res)){
                $result = ['code'=>500,'message'=>'新增失败'];
                return json($result);
            }
        }catch(\Exception $e){
            $result = ['code'=>500,'message'=>'用户名或密码传输错误'];
            return json($result);
        }
        
        $result = ['code'=>200,'message'=>'注册成功'];
        return json($result);
    }
    public function forget()
    {
        $param = $this->request->param();
        try{
            validate(UserValidate::class)->scene("forget")->check($param);
        }catch(ValidateException $e){
            $msg = $e->getError();
            $result = ['code'=>400,"message"=>$msg];
            return json($result);
        }
        $redis = connect_redis();
        try{
            $user = new User();
            $res  = $user->where("email",$param['email'])->field("user_id")->find();
            
            $num = $redis->get("send:email:".$param['email']);
            $redis->close();
            if(empty($num)){
                $result = ['code'=>400,'message'=>'验证码错误(发送验证码后十分钟过期)'];
                return json($result);
            }
            if($param['vaild'] != $num){
                $result = ['code'=>400,'message'=>'验证码错误'];
                return json($result);
            }
            $password = md5(md5($param['password1']));
            if($param['password1'] != $param['password2']){
                $result = ['code'=>400,'message'=>'两次密码不一致'];
                return json($result);
            }
            $res = $user->update(['password'=>$password],['user_id'=>$res['user_id']]);
            if(empty($res)){
                $result = ['code'=>500,'message'=>'更新失败,密码不能和之前一致'];
                return json($result);
            }
        }catch(\Exception $e){
            $result = ['code'=>500,'message'=>'用户名或密码传输错误'.$e];
            return json($result);
        }
        
        $result = ['code'=>200,'message'=>'修改成功'];
        return json($result);
    }
}
