<?php

require_once "vendor/topthink/framework/src/think/Env.php";
use think\Env;

$server = new Swoole\WebSocket\Server("0.0.0.0", 9060);

$server->set([
    'worker_num'=>1,
    'heartbeat_check_interval'=>30,
    'heartbeat_idle_time'=>62
]);

$server->on('open', function (Swoole\WebSocket\Server $server, $request) {

    
    $env = new Env();
    $env->load(".env");
    
    echo "server: handshake success with fd{$request->fd}\n";
   
    $redis = new Redis();
    $redis->connect($env->get('redis.hostname'));
    $redis->auth($env->get('redis.password'));
    // $redis->del("fd");
    // $redis->del("id");
    $GLOBALS['REDIS'] = $redis;

    $mysql = mysqli_connect($env->get("database.hostname"),$env->get("database.username"),$env->get("database.password"),$env->get("database.database"));
    $GLOBALS['mysql'] = $mysql;

    
    
});

$server->on('message', function (Swoole\WebSocket\Server $server, $frame) {
    $data = $frame->data;
    $type = json_decode($data,true);

    $redis = $GLOBALS['REDIS'];    

    if($type['type'] == 2){
        $qid = $type['talk_id'];
        $fd = $redis->hget('fd',$qid);
        echo "发送到{$fd}";
        foreach($server->connections as $conn){
            if($fd == $conn){
                $server->push($fd, $frame->data);
            }
        }
       
    } elseif($type['type'] == 'ping'){
        
        foreach($server->connections as $conn){
            $data = json_encode(['type'=>3,"id"=>$type['id']]);
            $server->push($conn,$data);
        }
    } elseif($type['type'] == '4'){
        echo "{$frame->fd} close";
        foreach($server->connections as $conn){
            $data = json_encode(['type'=>4,"id"=>$type['id']]);
            $server->push($conn,$data);
        }
    } else {
        echo "连接到{$type['id']}";
        $redis->hset("fd",$type['id'],$frame->fd);
        $redis->hset("id",$frame->fd,$type['id']);
        foreach($server->connections as $conn){
            $data = json_encode(['type'=>3,"id"=>$type['id']]);
            $server->push($conn,$data);
        }
    }
    
});

$server->on('close', function ($server, $fd) {
    echo "client {$fd} closed\n";
    $mysql = $GLOBALS['mysql'];
    $redis = $GLOBALS['REDIS'];
    $uid = $redis->hget('id',$fd);
    $sql = "UPDATE t_user SET status=0 WHERE user_id=".$uid;
    $res = mysqli_query($mysql,$sql);
    foreach($server->connections as $conn){
        $data = json_encode(['type'=>4,"id"=>$uid]);
        $server->push($conn,$data);
    }
});
$server->start();
