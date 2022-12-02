<?php
// 应用公共文件
use think\Exception;
function connect_redis()
{
    try{
        $redis = new \Redis();
        $redis->connect(env('redis.hostname'));
        $redis->auth(env('redis.password'));
    } catch(\Exception $e) {
        return null;
    }
    return $redis;
}