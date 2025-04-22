<?php

namespace app\admin\controller\tincui;

use app\common\controller\Backend;
use app\admin\model\tincui\Nwdata;
use think\Db;
use think\Exception;

/**
 * 网络监控面板
 *
 * @icon fa fa-dashboard
 */
class NetworkDashboard extends Backend
{
    protected $model = null;
    protected $searchFields = 'server_name,net_name';
    
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 查看
     */
    public function index()
    {
        return $this->view->fetch();
    }
    
    /**
     * 获取服务器列表
     */
    public function getServers()
    {
        try {
            $servers = Db::name('server')
                ->field('id, name')
                ->where('status', 'normal')
                ->order('id ASC')
                ->select();
                
            return json(['code' => 1, 'msg' => '获取成功', 'data' => $servers]);
        } catch (Exception $e) {
            return json(['code' => 0, 'msg' => '获取服务器列表失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取网络列表
     */
    public function getNetworks()
    {
        $serverId = $this->request->param('server_id/d', 0);
        if (!$serverId) {
            return json(['code' => 0, 'msg' => '请选择服务器', 'data' => []]);
        }
        
        try {
            $networks = Db::name('net')
                ->field('id, name')
                ->where('server_id', $serverId)
                ->order('id ASC')
                ->select();
                
            return json(['code' => 1, 'msg' => '获取成功', 'data' => $networks]);
        } catch (Exception $e) {
            return json(['code' => 0, 'msg' => '获取网络列表失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取网络统计数据
     */
    public function getNetworkStats()
    {
        $networkId = $this->request->param('network_id/d', 0);
        if (!$networkId) {
            return json(['code' => 0, 'msg' => '请选择网络', 'data' => []]);
        }
        
        try {
            // 获取网络基本信息
            $network = Db::name('net')
                ->alias('n')
                ->join('server s', 's.id = n.server_id')
                ->field('n.id, n.name as net_name, n.net_status, n.server_id, s.name as server_name')
                ->where('n.id', $networkId)
                ->find();
                
            if (!$network) {
                return json(['code' => 0, 'msg' => '网络不存在', 'data' => []]);
            }
            
            // 实例化Nwdata模型
            $nwdataModel = new Nwdata();
            
            // 获取平均响应时间 (毫秒)
            $avgResponseTime = Db::name('node_ping_log')
                ->where('net_id', $networkId)
                ->whereTime('create_time', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->avg('response_time');
            $avgResponseTime = round($avgResponseTime ?: 0, 1);
            
            // 获取健康评分
            $healthScore = $nwdataModel->calculateHealthScore($networkId);
            
            // 获取平均恢复时间 (分钟)
            $avgRecoveryTime = $nwdataModel->calculateAvgRecoveryTime($networkId);
            
            // 获取当前流量
            $currentTraffic = [
                'upload' => '0.0 Mbps',
                'download' => '0.0 Mbps'
            ];
            
            $latestTraffic = Db::name('node_traffic')
                ->where('net_id', $networkId)
                ->order('create_time DESC')
                ->find();
                
            if ($latestTraffic) {
                $currentTraffic = [
                    'upload' => $this->formatBandwidth($latestTraffic['upload_speed']),
                    'download' => $this->formatBandwidth($latestTraffic['download_speed'])
                ];
            }
            
            // 获取健康评分趋势 (最近7天)
            $healthScoreTrend = $this->getHealthScoreTrend($networkId);
            
            // 获取网络中断趋势 (最近7天)
            $disruptionTrend = $this->getDisruptionTrend($networkId);
            
            // 获取响应时间趋势 (最近24小时)
            $responseTimeTrend = $this->getResponseTimeTrend($networkId);
            
            // 获取恢复时间趋势 (最近7天)
            $recoveryTimeTrend = $this->getRecoveryTimeTrend($networkId);
            
            // 组装返回数据
            $data = [
                'network_id' => $network['id'],
                'network_name' => $network['net_name'],
                'server_id' => $network['server_id'],
                'server_name' => $network['server_name'],
                'network_status' => $network['net_status'],
                'avg_response_time' => $avgResponseTime,
                'health_score' => $healthScore,
                'avg_recovery_time' => $avgRecoveryTime,
                'current_traffic' => $currentTraffic,
                'health_score_trend' => $healthScoreTrend,
                'disruption_trend' => $disruptionTrend,
                'response_time_trend' => $responseTimeTrend,
                'recovery_time_trend' => $recoveryTimeTrend
            ];
            
            return json(['code' => 1, 'msg' => '获取成功', 'data' => $data]);
        } catch (Exception $e) {
            return json(['code' => 0, 'msg' => '获取网络统计数据失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取健康评分趋势 (最近7天)
     */
    protected function getHealthScoreTrend($networkId)
    {
        $dates = [];
        $scores = [];
        
        // 获取最近7天的日期
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = date('m-d', strtotime($date));
            
            // 获取该日期的健康评分
            $score = Db::name('network_health_score')
                ->where('net_id', $networkId)
                ->whereTime('record_date', 'between', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->value('score');
                
            $scores[] = $score ? intval($score) : 0;
        }
        
        return [
            'dates' => $dates,
            'scores' => $scores
        ];
    }
    
    /**
     * 获取网络中断趋势 (最近7天)
     */
    protected function getDisruptionTrend($networkId)
    {
        $dates = [];
        $counts = [];
        
        // 获取最近7天的日期
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = date('m-d', strtotime($date));
            
            // 获取该日期的中断次数
            $count = Db::name('network_disruption_log')
                ->where('net_id', $networkId)
                ->whereTime('offline_time', 'between', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->count();
                
            $counts[] = intval($count);
        }
        
        return [
            'dates' => $dates,
            'counts' => $counts
        ];
    }
    
    /**
     * 获取响应时间趋势 (最近24小时)
     */
    protected function getResponseTimeTrend($networkId)
    {
        $dates = [];
        $times = [];
        
        // 获取最近24小时的时间点
        for ($i = 23; $i >= 0; $i--) {
            $time = date('Y-m-d H:00:00', strtotime("-$i hours"));
            $dates[] = date('H:00', strtotime($time));
            
            // 获取该时间点附近的平均响应时间
            $timeStart = date('Y-m-d H:00:00', strtotime($time));
            $timeEnd = date('Y-m-d H:59:59', strtotime($time));
            
            $avgTime = Db::name('node_ping_log')
                ->where('net_id', $networkId)
                ->whereTime('create_time', 'between', [$timeStart, $timeEnd])
                ->avg('response_time');
                
            $times[] = $avgTime ? round($avgTime, 1) : 0;
        }
        
        return [
            'dates' => $dates,
            'times' => $times
        ];
    }
    
    /**
     * 获取恢复时间趋势 (最近7天)
     */
    protected function getRecoveryTimeTrend($networkId)
    {
        $dates = [];
        $times = [];
        
        // 获取最近7天的日期
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = date('m-d', strtotime($date));
            
            // 获取该日期的平均恢复时间 (分钟)
            $avgTime = Db::name('network_recovery_log')
                ->where('net_id', $networkId)
                ->whereTime('recovery_time', 'between', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->avg('duration');
                
            $times[] = $avgTime ? round($avgTime / 60, 1) : 0; // 转换为分钟
        }
        
        return [
            'dates' => $dates,
            'times' => $times
        ];
    }
    
    /**
     * 格式化带宽显示
     */
    protected function formatBandwidth($bpsValue)
    {
        if ($bpsValue >= 1000000000) {
            return round($bpsValue / 1000000000, 2) . ' Gbps';
        } elseif ($bpsValue >= 1000000) {
            return round($bpsValue / 1000000, 2) . ' Mbps';
        } elseif ($bpsValue >= 1000) {
            return round($bpsValue / 1000, 2) . ' Kbps';
        } else {
            return round($bpsValue, 2) . ' bps';
        }
    }
} 