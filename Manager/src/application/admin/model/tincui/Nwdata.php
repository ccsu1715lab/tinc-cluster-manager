<?php
namespace app\admin\model\tincui;

use think\Model;
use think\Db;

class Nwdata extends Model
{
    protected $name = 'node_status';
    
    // 获取网络状态统计数据
    public function getNetworkStats()
    {
        // 获取总网络数量
        $totalNetworks = Db::table('fa_net')->count();
        
        // 获取在线网络数量
        $onlineNetworks = Db::table('fa_net')
            ->where('status', '在线')
            ->count();
            
        // 计算在线率
        $onlineRate = ($totalNetworks > 0) ? round(($onlineNetworks / $totalNetworks) * 100, 2) : 0;
    
        // 计算平均故障恢复时间（从日志中获取）
        $avgRecoveryTime = $this->calculateAvgRecoveryTime();
        
        return [
            'total_networks' => $totalNetworks,
            'online_networks' => $onlineNetworks,
            'online_rate' => $onlineRate,
            'avg_recovery_time' => $avgRecoveryTime
        ];
    }
    
    // 计算平均故障恢复时间（分钟）
    protected function calculateAvgRecoveryTime()
    {
        // 计算7天前的日期时间
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 day'));

        // 使用单次查询获取记录数和总持续时间
        $result = Db::table('fa_network_recovery_log')
            ->where('create_time', '>=', $sevenDaysAgo)
            ->field('COUNT(*) as count, SUM(duration) as total_duration')
            ->find();

        // 安全计算平均值
        if ($result['count'] > 0) {
            // 可以选择四舍五入到两位小数
            $avgRecoveryTime = round($result['total_duration'] / $result['count'], 2);
        } else {
            $avgRecoveryTime = 0;
        }

        return $avgRecoveryTime;
    }
    
    // 获取网络健康分数趋势（过去7天）
    public function getHealthScoreTrend()
    {
        // 在实际项目中，应从数据库获取历史健康分数数据
        // 这里使用模拟数据
        $scores = [92, 95, 89, 94, 96, 91, 93];
        
        return [
            'scores' => $scores,
            'max' => max($scores),
            'min' => min($scores),
            'avg' => round(array_sum($scores) / count($scores), 1)
        ];
    }
    
    // 获取网络中断次数趋势（过去7天）
    public function getDisruptionTrend()
    {
        // 获取最近7天的网络中断次数，按天统计
        $disruptions = [];
        for ($i = 0; $i < 7; $i++) {
            $startTime = date('Y-m-d 00:00:00', strtotime("-" . (6 - $i) . " day")); // 从6天前到今天
            $endTime = date('Y-m-d 23:59:59', strtotime("-" . (6 - $i) . " day"));
            
            $count = Db::table('fa_network_disruption_log')
                ->where('offline_time', '>=', $startTime)
                ->where('offline_time', '<=', $endTime)
                ->count();
            
            $disruptions[$i] = $count; // i号位置对应第i+1天的中断次数
        }
        
        return [
            'disruptions' => $disruptions,
            'max' => max($disruptions),
            'min' => min($disruptions),
            'freq' => round(array_sum($disruptions) / count($disruptions), 1)
        ];
    }
    
    // 计算网络健康分数
    public function calculateNetworkHealthScore($netId)
    {
        // 在实际项目中，根据多个因素计算健康分数：
        // 1. 连接稳定性 (40%)
        // 2. 响应时间 (30%)
        // 3. 数据质量 (30%)
        
        // 这里使用模拟数据
        return rand(85, 98);
    }
}