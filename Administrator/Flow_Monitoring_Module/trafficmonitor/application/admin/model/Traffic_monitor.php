<?php
namespace app\admin\model;

use think\Model;

class Traffic_monitor extends Model
{
    // 开启自动写入时间戳功能
    protected $autoWriteTimestamp = 'datetime';

    // 自定义创建时间字段名
    protected $createTime = 'record_time';

    // 关闭更新时间自动写入
    protected $updateTime = false;

    // 指定表名
    protected $name = 'traffic_monitor';

    // 新增日期格式配置
    protected $dateFormat = 'Y-m-d H:i:s';

    // 或者使用类型转换
    protected $type = [
        'record_time' => 'string',
    ];
}