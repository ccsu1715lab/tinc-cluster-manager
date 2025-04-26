<?php

namespace app\admin\model\tincui;

use app\common\controller\Backend;
use app\admin\model\tincui\Nwdata;
use think\Db;
use think\Exception;

/**
 * 单网数据可视化
 *
 * @icon fa fa-line-chart
 */
class Singlenwdata extends Backend
{
    protected $model = null;
    protected $searchFields = 'server_name,net_name';
    
    public function _initialize()
    {
        parent::_initialize();
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
     * 获取单个网络的数据
     */
    public function getNetworkData()
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
                ->field('n.id, n.name as net_name, n.net_status as status, n.server_id, s.name as server_name')
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
            
            // 组装返回数据
            $data = [
                'network_id' => $network['id'],
                'network_name' => $network['net_name'],
                'server_id' => $network['server_id'],
                'server_name' => $network['server_name'],
                'status' => $network['status'],
                'avg_response_time' => $avgResponseTime,
                'health_score' => $healthScore,
                'avg_recovery_time' => $avgRecoveryTime,
                'traffic' => $currentTraffic,
                'health_score_trend' => $healthScoreTrend,
                'disruption_trend' => $disruptionTrend
            ];
            
            return json(['code' => 1, 'msg' => '获取成功', 'data' => $data]);
        } catch (Exception $e) {
            return json(['code' => 0, 'msg' => '获取网络数据失败: ' . $e->getMessage()]);
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
            $dates[] = date('m/d', strtotime($date));
            
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
    protected function getDisruptionTrend($servername,$netname)
    {
        $dates = [];
        $counts = [];
        for($i=6;$i>=0;$i--){
            $starttime=date('Y-m-d 00:00:00',strtotime("-$i days"));
            $endtime=date('Y-m-d 23:59:59',strtotime("-$i days +1 day -1 second"));
            $count=Db::table('fa_network_disruption_log')->where('server_name',$servername)->where('net_name',$netname)->where('offline_time','>=',$starttime)->where('offline_time','<=',$endtime)->count();
            $dates[]=date('m/d',strtotime("-$i days"));
            $counts[]=$count;
        }
        return ['dates'=>$dates,'counts'=>$counts];
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
