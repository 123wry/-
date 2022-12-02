<?php
namespace app\validate;
use think\Validate;
class ExamineValidate extends Validate{
    protected $rule = [
        'status'=>'require|number',
        'examine_id'=>'require|number'
    ];
    protected $message = [
        'status.require'=>'状态不能为空',
        'status.number'=>'状态不能非数字',
        'examine_id.require'=>'未选择合适关系',
        'examine_id.number'=>'关系id不能非数字'
    ];
    protected $scene = [
        'status'=>['status','examine_id']
    ];
}
