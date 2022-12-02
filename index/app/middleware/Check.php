<?php
namespace app\middleware;

class Check{
    public function handle($request,\Closure $next)
    {
        $newstr = trim($request->request()['s'],"/");
        $end = strpos($newstr,"/");
        $controller = substr($newstr,0,$end);

        $redis = connect_redis();
        $uid = $request->header("uid");
        $info = $redis->hgetall($uid);

        if($controller != 'Login' && empty($info) ){
            return json(['code'=>401,'message'=>'未登录']);
        }
        if(!empty($info)){
            $redis->hset($uid,'email',$info['email']);
            $redis->hset($uid,'user_id',$info['user_id']);
            $redis->expire($uid,86400);

            $request->user_id = $info['user_id'];
            $request->email = $info['email'];
        }
        return $next($request);
    }
}