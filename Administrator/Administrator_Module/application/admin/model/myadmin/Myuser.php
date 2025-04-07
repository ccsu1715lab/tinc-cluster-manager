<?php

namespace app\admin\model\myadmin;

use think\Model;
use think\Session;

class Myuser extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}