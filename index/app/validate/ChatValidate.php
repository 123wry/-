<?php
namespace app\validate;
use think\Validate;
class ChatValidate extends Validate{
    protected $rule = [
        'queue_id'=>'require|number',
        'msg'=>'require|max:200'
    ];
    protected $message = [
        'queue_id.require'=>"队列id不能为空",
        'queue_id.number'=>"队列id不能为非数字",
        'msg.require'=>'消息不能为空',
        'msg.max'=>'消息不能超过200字符'
    ];
    protected $scene = [
        'queue_id'=>['queue_id'],
        'send'=>['queue_id','msg']
    ];
}