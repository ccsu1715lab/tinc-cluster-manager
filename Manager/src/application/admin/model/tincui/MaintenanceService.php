<?php
namespace app\admin\model\tincui;

use think\Model;
use think\Db;
use think\Log;
use think\Cache;

/**
 * 类名：MaintenanceService
 * 功能：系统维护服务，包括定期清理旧数据等
 */
class MaintenanceService extends Model
{
    // 数据表名
    protected $name = 'maintenance_log';
    
    /**
     * 清理旧记录
     * @param int $days 清理多少天前的记录，默认为15天
     * @return array 清理结果
     */
    public function cleanOldRecords($days = 15)
    {
        try {
            // 计算截止日期
            $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            // 需要清理的表和对应的日期字段
            $tables = [
                'fa_network_recovery_log' => 'recovery_time',
                'fa_log_operations' => 'occurrence_time',
                'fa_log_nodes' => 'create_time',
                'fa_event' => 'occurrence_time'
            ];
            
            $totalCount = 0;
            $details = [];
            
            // 逐表清理
            foreach ($tables as $table => $dateField) {
                $count = Db::table($table)
                    ->where($dateField, '<', $date)
                    ->delete();
                
                $totalCount += $count;
                $details[$table] = $count;
                
                Log::write("已从{$table}表中删除{$count}条过期记录", 'info');
            }
            
            // 记录本次清理操作
            $logData = [
                'operation' => '系统自动清理',
                'details' => "已删除{$days}天前的记录，共{$totalCount}条",
                'execute_time' => date('Y-m-d H:i:s')
            ];
            
            $this->save($logData);
            
            // 更新最后清理时间缓存
            Cache::set('last_records_clean_time', time());
            
            return [
                'status' => true,
                'message' => "清理成功，共删除{$totalCount}条记录",
                'data' => [
                    'total' => $totalCount,
                    'details' => $details
                ]
            ];
        } catch (\Exception $e) {
            Log::write('清理旧记录失败: ' . $e->getMessage(), 'error');
            
            return [
                'status' => false,
                'message' => '清理失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 检查是否需要清理
     * @return bool 是否需要清理
     */
    public function shouldCleanRecords()
    {
        // 获取上次清理时间
        $lastCleanTime = Cache::get('last_records_clean_time');
        
        // 如果从未清理过，或者距离上次清理已经过去30天
        if (!$lastCleanTime || (time() - $lastCleanTime) > (30 * 24 * 3600)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 自动执行定期清理
     * 可以在系统某个常用接口中调用此方法
     */
    public function autoCleanIfNeeded()
    {
        if ($this->shouldCleanRecords()) {
            return $this->cleanOldRecords();
        }
        
        return [
            'status' => true,
            'message' => '无需清理',
            'data' => null
        ];
    }
} 