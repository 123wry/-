<?php
namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\Queue;
use app\model\Examine;
use app\model\Lastmsg;
use app\model\Chat;
use think\facade\Db;
use think\Request;
use think\exception\ValidateException;
use app\validate\UserValidate;
use app\validate\ExamineValidate;
use app\validate\ChatValidate;

use Swoole\Coroutine\Http\Client;

class Index extends BaseController
{
    public $requset = null;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function addFriend()
    {
        $param = $this->request->param();
        try{
            validate(UserValidate::class)->scene("email")->check($param);
        } catch(ValidateException $e){
            $msg = $e->getError();
            $result = ['code'=>400,"message"=>$msg];
            return json($result);
        }
        $user = new User;
        $ret = $user->field("user_id")->where("email",$param['email'])->find();
        if(empty($ret)){
            $result = ['code'=>400,"message"=>'不存在此用户'];
            return json($result);
        }
        $user_id = $this->request->user_id;
        
        try{
            
            $examine = new Examine;
            // 已申请过添加聊天对象正在等对方审核中不能多次添加
            // if($user_id == $ret['user_id']){
            //     $userid = $examine->where('user_id',$user_id)->where('talk_id',$ret['user_id'])->where("status",1)->find();
            // } else {
            $userid = $examine->where('user_id',$user_id)->where('talk_id',$ret['user_id'])->where("status",0)->find();
            // }
            if(!empty($userid)){
                $result = ['code'=>400,"message"=>'已申请过添加聊天对象正在等对方审核中不能多次添加'];
                return json($result);
            }
            // 已经添加过在队列中不能重复添加
            $queue = new Queue();
            $queuedata = $queue->where('user_id='.$user_id.' and talk_id='.$ret['user_id'])->where("is_delete",0)->find();
            if(!empty($queuedata)){
                $result = ['code'=>400,"message"=>'聊天对象已在聊天队列里不能重复添加'];
                return json($result);
            }
            // 拒绝过重新添加
            $examine_id = $examine->field("examine_id")->where('user_id',$user_id)->where('talk_id',$ret['user_id'])->where("status",2)->find();

            if($user_id == $ret['user_id']){
                $status = 1;
            } else {
                $status = 0;
            }
            if(!empty($examine_id)){
                $ret = $examine->update([
                    'status'=>$status,
                    'create_time'=>time()
                ],['examine_id'=>$examine_id['examine_id']]);
                if(empty($ret)){
                    $result = ['code'=>500,"message"=>'发起申请存储失败'];
                    return json($result);
                }
                $result = ['code'=>200,"message"=>'发起申请成功'];
                return json($result);
            }
            // 没添加过添加
            if($user_id == $ret['user_id']){
                $queuedata = $queue->field("queue_id")->where('user_id='.$user_id.' and talk_id='.$ret['user_id'])->where("is_delete",1)->find();
                if(!empty($queuedata)){
                    $ret = $queue->update([
                        'is_delete'=>0
                    ],['queue_id'=>$queuedata["queue_id"]]);
                }else {
                    $qqid = time().rand(0,9).rand(0,9).rand(0,9).rand(0,9).$user_id.$ret['user_id'];
                    $ret = $queue->save([
                        'user_id'=>$user_id,
                        'talk_id'=>$ret['user_id'],
                        'create_time'=>time(),
                        'qqid'=>$qqid
                    ]);
                }
            }else {
                // unset($ret);
                $ret = $examine->save([
                    'user_id'=>$user_id,
                    'talk_id'=>$ret['user_id'],
                    'status'=>$status,
                    'create_time'=>time()
                ]);
            }
            if(empty($ret)){
                $result = ['code'=>500,"message"=>'发起申请存储失败'];
                return json($result);
            }
        }catch(\Exception $e){
            $result = ['code'=>500,"message"=>'发起申请失败'];
            return json($result);
        }
        $result = ['code'=>200,"message"=>'发起申请成功'];
        return json($result);
        
    }
    public function getFriend()
    {
        $uid = $this->request->user_id;
        $examine = new Examine;
        try{
            $ret = $examine
            ->alias("e")
            ->field("u.`email` as `email`,FROM_UNIXTIME(e.`create_time`) as `ctime`,e.`examine_id` as `examine_id`,e.`status`")
            ->leftJoin('t_user u','e.user_id=u.user_id')
            ->where("e.talk_id",$uid)
            ->select();
        } catch(\Exception $e){
            $result = ["code"=>500,"message"=>"获取数据失败".$e];
            return json($result);
        }
        $result = ["code"=>200,"message"=>"获取数据成功","data"=>$ret];
        return json($result);
    }
    public function friendStatus()
    {
        $param = $this->request->param();
        try{
            validate(ExamineValidate::class)->scene("status")->check($param);
        }catch(ValidateException $e){
            $message = $e->getError();
            $result = ["code"=>400,"message"=>$message];
            return json($result);
        }
        Db::startTrans();
        $examine = new Examine;
        try{
            $ret = $examine->update(["status"=>$param['status']],
                ["examine_id"=>$param['examine_id']]
            );
            if(empty($ret)){
                Db::rollback();
                $result = ["code"=>500,"message"=>'关系模型数据更新失败'];
                return json($result);
            }
            if($param['status'] == 2){
                Db::commit();
                $result = ["code"=>200,"message"=>'提交成功'];
                return json($result);
            }
            $data = $examine->field("user_id,talk_id")->where("examine_id",$param['examine_id'])->find();
            $queue = new Queue;
            $qqid = time().rand(0,9).rand(0,9).rand(0,9).rand(0,9).$data['user_id'].$data['talk_id'];
            $ret = $queue->save([
                'user_id'=>$data['user_id'],
                'talk_id'=>$data['talk_id'],
                'create_time'=>time(),
                'qqid'=>$qqid
            ]);
            
            if(empty($ret)){
                Db::rollback();
                $result = ["code"=>500,"message"=>'队列模型数据更新失败'];
                return json($result);
            }
            $queue = new Queue;
            $ret_reverse = $queue->save([
                'user_id'=>$data['talk_id'],
                'talk_id'=>$data['user_id'],
                'create_time'=>time(),
                'qqid'=>$qqid
            ]);
            if(empty($ret_reverse)){
                Db::rollback();
                $result = ["code"=>500,"message"=>'反队列模型数据更新失败'];
                return json($result);
            }
        }catch(\Exception $e){
            Db::rollback();
            $result = ["code"=>500,"message"=>'关系模型更新失败'.$e];
            return json($result);
        }
        Db::commit();
        $result = ["code"=>200,"message"=>'提交成功'];
        return json($result);
    }
    public function getStatus()
    {
        $uid = $this->request->user_id;
        $examine = new Examine();
        $user = new User;
        try{
            $count = $examine->where("talk_id",$uid)->where("status",0)->count();
            $data = $user->field("email")->where("user_id",$uid)->find();
            $data['count'] = $count;
        } catch(\Exception $e){
            $result = ["code"=>500,"message"=>"信息获取失败"];
            return json($result);
        }
        $result = ["code"=>200,"message"=>"获取成功","data"=>$data];
        return json($result);
    }
    public function queueList()
    {
        $uid = $this->request->user_id;
        $queue = new Queue;
        $user = new User;
        $lastmsg = new Lastmsg;
        //获取聊天用户名和用户id
        try{
            // $uids = $user->alias("u")->field("queue.queue_id,queue.talk_id,u.email")
            // ->rightJoin('(select talk_id,user_id,queue_id from t_queue where user_id='.$uid.') as queue','u.user_id=queue.talk_id')
            // ->select();
            $uids = $lastmsg->alias("l")->field("queue.queue_id,queue.user_id,queue.talk_id,l.msg,l.last_time,l.unread")
            ->rightJoin('(select talk_id,user_id,queue_id from t_queue where user_id='.$uid.') as queue','l.queue_id=queue.queue_id')
            ->order("l.unread desc,l.last_time desc")
            ->select();
        } catch(\Excepsion $e){
            $result = ["code"=>500,"message"=>"查询队列失败"];
            return json($result);
        }
        //拼接聊天队列id
        $uiditems = "";
        foreach($uids as $item){
            $uiditems .= $item['talk_id'].",";
        }
        $uiditems = trim($uiditems,",");
        //获取最后一句话数据
       
        if($uiditems == ''){
            $info = [];
        } else {
            $info = $user->field("user_id,email,status")->where("user_id in(".$uiditems.")")->select();
            
        }
        $infoitem = [];
        foreach($info as $item){
            $infoitem[$item['user_id']] = $item;
        }
        
        foreach($uids as $key=>$item){
            // $msg = isset($infoitem[$item['queue_id']]['msg'])?$infoitem[$item['queue_id']]['msg']:"";
            $last_time = $item['last_time'];
            // $uids[$key]['msg'] = $msg;
            if($last_time != ''){
                $today = strtotime(date("Y-m-d 00:00:00",time()));
                if($last_time>$today){
                    $uids[$key]['last_time'] = date("H:i",$last_time);
                } else {
                    $uids[$key]['last_time'] = date("m-d",$last_time);
                }
            } else {
                $uids[$key]['last_time'] = '';
            }
            $uids[$key]['email'] = isset($infoitem[$item['talk_id']]['email'])?$infoitem[$item['talk_id']]['email']:"";
            $status = 0;
            $uids[$key]['status'] = isset($infoitem[$item['talk_id']]['status'])?$infoitem[$item['talk_id']]['status']:0;
            // $uids[$key]['unread'] = isset($infoitem[$item['queue_id']]['unread'])?$infoitem[$item['queue_id']]['unread']:"0";
        }
        $result = ["code"=>200,'message'=>"查询成功","data"=>$uids];
        return json($result);
    }
    public function getMsg()
    {
        $param = $this->request->param();
        $uid = $this->request->user_id;
        try{
            validate(ChatValidate::class)->scene("queue_id")->check($param);
        }catch(ValidateException $e){
            $message = $e->getError();
            $result = ["code"=>400,"message"=>$message];
            return json($result);
        }

        try{
            $queue = new Queue;
            $qqid = $queue->field("qqid")->where("queue_id",$param['queue_id'])->find();
            $chat = new Chat;
            $data = $chat->field("msg,type,user_id")->where("qqid",$qqid['qqid'])->select();
        }catch(\Exception $e){
            $result = ["code"=>500,"message"=>"信息获取失败"];
            return json($result);
        }
        $res = [];
        $res['data'] = $data;
        $res['uid'] = $uid;
        $result = ["code"=>200,"message"=>"信息获取成功","data"=>$res];
        return json($result);
    }
    public function sendMsg()
    {
        $param = $this->request->param();
        $uid = $this->request->user_id;
        try{
            validate(ChatValidate::class)->scene("send")->check($param);
        }catch(ValidateException $e){
            $message = $e->getError();
            $result = ["code"=>400,"message"=>$message];
            return json($result);
        }
        Db::startTrans();
        $queue = new Queue;
        try{
            $unread = 0;
            $res = $queue->field("user_id,talk_id,qqid")->where("queue_id",$param["queue_id"])->find();
            if($res['user_id'] == $res['talk_id']){
                $unread = 1;
            }
            $chat = new Chat;
            $chat->save([
                'queue_id'=>$param['queue_id'],
                'msg'=>$param['msg'],
                'type'=>1,
                'user_id'=>$uid,
                'create_time'=>time(),
                'qqid'=>$res['qqid'],
                'unread'=>$unread
            ]);
        }catch(\Exception $e){
            Db::rollback();
            $result = ["code"=>500,"message"=>"发送信息失败"];
            return json($result);
        }
        
        try{
            
            $lastmsg = new Lastmsg();
            $lastmsg_res = $lastmsg->field("lastmsg_id")->where("queue_id",$param['queue_id'])->find();
            $time = time();
            if(!empty($lastmsg_res)){
                $lastmsg->update([
                    'msg'=>$param['msg'],
                    'last_time'=>$time
                ],['lastmsg_id'=>$lastmsg_res['lastmsg_id']]);
            }else {
                $lastmsg->save([
                    'msg'=>$param['msg'],
                    'last_time'=>$time,
                    'queue_id'=>$param['queue_id']
                ]);
            }
            $lastmsg = new Lastmsg();
            $queueid = $queue->field("queue_id")->where("user_id=".$res['talk_id']." and talk_id=".$res['user_id'])->find();
            if(!empty($queueid)){
                $lastmsg_res = $lastmsg->field("lastmsg_id,unread")->where("queue_id",$queueid['queue_id'])->find();
                $unread = 0;

                if(!empty($lastmsg_res)){
                    $unread = $lastmsg_res['unread']+1;
                    if($res['talk_id'] == $res['user_id']){
                        $unread = 0;
                    }
                    $lastmsg->update([
                        'msg'=>$param['msg'],
                        'last_time'=>$time,
                        'unread'=>$unread
                    ],['lastmsg_id'=>$lastmsg_res['lastmsg_id']]);
                }else {
                    $unread = 1;
                    if($res['talk_id'] == $res['user_id']){
                        $unread = 0;
                    }
                    $lastmsg->save([
                        'msg'=>$param['msg'],
                        'last_time'=>$time,
                        'queue_id'=>$queueid['queue_id'],
                        'unread'=>$unread
                    ]);
                }
                
            }

            $data = [];
            $data['msg'] = $param['msg'];
            
            $data['user_id'] = $res['user_id'];
            $data['talk_id'] = $res['talk_id'];
            if(!empty($queueid)){
                $data['queue_id'] = $queueid['queue_id'];
            }
            
        } catch(\Exception $e){
            Db::rollback();
            $result = ["code"=>500,"message"=>"获取发送信息失败".$e];
            return json($result);
        }
        Db::commit();
        $result = ["code"=>200,'message'=>"发送信息成功","data"=>$data];
        return json($result);
    }
    // 已读
    public function readed()
    {
        $param = $this->request->param();
        $uid = $this->request->user_id;
        try{
            validate(ChatValidate::class)->scene("queue_id")->check($param);
        }catch(ValidateException $e){
            $message = $e->getError();
            $result = ["code"=>400,"message"=>$message];
            return json($result);
        }
        $queue = new Queue;
        $lastmsg = new Lastmsg();
        try{
            $data = $queue->field("talk_id,qqid")->where("queue_id",$param['queue_id'])->find();
            
            $queuetmp = $queue->field("queue_id")->where("user_id",$data['talk_id'])->where("talk_id",$uid)->find();
        } catch (\Exception $e){
            $result = ['code'=>500,"message"=>'获取已读信息失败'];
            return json($result);
        }
        Db::startTrans();
        try{
            $chat = new Chat;
            $chat->update(['unread'=>1],
                ['user_id'=>$data['talk_id'],
                'qqid'=>$data['qqid']
            ]);
            
            $lastmsg->update(['unread'=>0],
            ['queue_id'=>$param['queue_id']]);
        } catch(\Exception $e){
            $result = ['code'=>500,"message"=>'更新未读信息失败'];
            return json($result);
        }
        Db::commit();
        $result = ['code'=>200,"message"=>'更新未读消息成功'];
        return json($result);

    }
    public function onConnect()
    {
        $uid = $this->request->user_id;
        $param = $this->request->param();
        try{
            validate(UserValidate::class)->scene("status")->check($param);
        }catch(\Exception $e){
            $msg = $e->getError();
            $result = ["code"=>400,"message"=>$e->$msg];
            return json($result);
        }
        $user = new User;
        try{
            $user->update(['status'=>$param['status']],['user_id'=>$uid]);
        }catch(\Exception $e){
            $result = ["code"=>500,"message"=>"更新连接失败"];
            return json($result);
        }
        $result = ["code"=>200,'message'=>"连接/关闭成功"];
        return json($result);
    }
    
}
