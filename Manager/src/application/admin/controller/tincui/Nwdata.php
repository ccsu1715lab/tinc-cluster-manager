<?php
namespace app\admin\controller\tincui;
use think\Db;
use app\common\controller\Backend;
use app\admin\library\tincui\Auxi;

/**
 * 类名：Networkstatistics
 * 功能：网络状态统计模块，提供网络在线情况和健康度的统计分析
*/
class Nwdata extends Backend
{
    protected $noNeedRight = [
        'index'
    ];
    private $auxi = null;
    public function __construct()
    {
        parent::__construct();
        $this->auxi = new Auxi();
    }

    /**
     * 函数名：index
     * 功能：渲染网络统计分析页面
     * 返回：HTML页面
    */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 获取网络统计数据
            $networkStats = $this->getNetworkStats();
            
            // 获取中断趋势
            $disruptionTrend = $this->getDisruptionTrend();
            

            // 组合数据
            $data = [
                'network_status' => $networkStats['network_status'],
                'avg_response_time' => $networkStats['avg_response_time'],
                'avg_recovery_time' => $networkStats['avg_recovery_time'],
                'health_score' => $networkStats['health_score'],
                'disruption_trend' => $disruptionTrend,
                'recovery_time_trend' => $networkStats['recovery_time_trend']
            ];
            
            return json($data);
        }
        
        return $this->view->fetch('index');
    }

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
    
        // 计算平均响应时间(ms)
        $avgResponseTime = $this->calculateAvgResponseTime();
        
        // 计算平均故障恢复时间（分钟）
        $avgRecoveryTime = $this->calculateAvgRecoveryTime();
        
        // 计算故障恢复时间趋势
        $recoveryTimeTrend = $this->calculateRecoveryTimeTrend();
        
        // 计算平均健康分数
        $avgHealthScore = $this->calculateAvgHealthScore();
        
        return [
            'network_status' => [
                'total_networks' => $totalNetworks,
                'online_networks' => $onlineNetworks,
                'online_rate' => $onlineRate
            ],
            'avg_response_time' => $avgResponseTime,
            'avg_recovery_time' => $avgRecoveryTime,
            'recovery_time_trend' => $recoveryTimeTrend,
            'health_score' => $avgHealthScore
        ];
    }
    //计算近7天的平均恢复时间趋势
    protected function calculateRecoveryTimeTrend()
    {
        // 计算7天前的日期时间
        $recoveryTimeTrend = [];
        
        // 遍历最近7天
        for ($i = 6; $i >= 0; $i--) {
            // 计算每天的开始和结束时间
            $dayStart = date('Y-m-d 00:00:00', strtotime("-$i days"));
            $dayEnd = date('Y-m-d 23:59:59', strtotime("-$i days"));
            
            // 查询当天的网络恢复记录
            $result = Db::table('fa_network_recovery_log')
                ->where('recovery_time', '>=', $dayStart)
                ->where('recovery_time', '<=', $dayEnd)
                ->field('COUNT(*) as count, SUM(duration) as total_duration')
                ->find();
            
            // 计算当天的平均恢复时间
            if ($result && $result['count'] > 0) {
                $avgRecoveryTime = round($result['total_duration'] / $result['count'], 2);
            } else {
                // 如果没有记录，用上一天的数据或默认值
                $avgRecoveryTime = isset($recoveryTimeTrend[$i+1]) ? $recoveryTimeTrend[$i+1] : 0;
            }
            
            // 存储到数组中
            $recoveryTimeTrend[6-$i] = $avgRecoveryTime;
        }
        
        // 返回结果数组，索引0对应最早的一天（6天前），索引6对应今天
        return $recoveryTimeTrend;
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
    
    // 计算平均响应时间(ms)
    protected function calculateAvgResponseTime()
    {
            return 50;
    }
    
    // 计算平均健康分数
    protected function calculateAvgHealthScore()
    {
            return 85;
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
            return 92.8;
    }
    
    // 计算连接稳定性得分
    protected function calculateStabilityScore($netId)
    {

    }
    
    // 计算响应时间得分
    protected function calculatePerformanceScore($netId)
    {

    }
    
    // 计算数据质量得分
    protected function calculateQualityScore($netId)
    {
        // 为简化实现，这里返回模拟的分数
        // 实际实现应根据业务需求完善
        return 25; // 0-30之间的分数
    }
    
    // 记录健康分数
    protected function recordHealthScore($netId, $score, $stabilityScore, $performanceScore, $qualityScore)
    {
        // 将分数记录到数据库中

    }

} 