<?php

namespace app\admin\controller\tincui;

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
            $servers = Db::table('fa_server')
                ->field('id, server_name')
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
       // $servername = $this->request->param('server_name/d', 0);
       $servername=$this->request->get('server_name');
        if (!$servername) {
            return json(['code' => 0, 'msg' => '请选择服务器', 'data' => ['server_name'=> $servername]]);
        }
        
        try {
            $networks = Db::table('fa_net')
                ->field('id, net_name')
                ->where('server_name', $servername)
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
        $servername = $this->request->get('server_name');
        $netname = $this->request->get('net_name');
        $current_net=Db::table('fa_net')->where('server_name',$servername)->where('net_name',$netname)->find();
        $avg_response_time=$this->calculateAvgResponseTime($servername,$netname);
        $health_score=$this->calculateHealthScore($servername,$netname);
        $avg_recovery_time=$this->calculateAvgRecoveryTime($servername,$netname);
        $traffic=$this->calculateTraffic($servername,$netname);
        $health_score_trend=$this->getHealthScoreTrend($servername,$netname);
        $disruption_trend=$this->getDisruptionTrend($servername,$netname);
        // 返回静态测试数据，不查询数据库
        // 生成7天的日期数据
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('m/d', strtotime("-$i days"));
            $dates[] = $date;
        }
        
        // 生成测试数据，简单使用networkId作为随机种子以保持一致性
        $seed = intval($netname) ?: rand(1, 1000);
        srand($seed);
        
        // 测试数据
        $mockData = [        
            'net_name' => $netname,
            'server_name' => $servername,
            'status' => rand(0, 10) > 8 ? '离线' : '在线',
            'avg_response_time' => rand(5, 100),
            'health_score' => rand(60, 95),
            'avg_recovery_time' => $avg_recovery_time,
            'traffic' => [
                'upload' => rand(50, 200) . ' Mbps',
                'download' => rand(100, 500) . ' Mbps'
            ],
            'health_score_trend' => [
                'dates' => $dates,
                'scores' => array_map(function() { return rand(50, 100); }, range(1, 7))
            ],
            'disruption_trend' => [
                'dates' => $disruption_trend['dates'],
                'counts' => $disruption_trend['counts']
            ]
        ];
        
        return json(['code' => 1, 'msg' => '获取成功', 'data' => $mockData]);
    }

    public function calculateAvgResponseTime($servername,$netname){
        return round(rand(1,100),2);
    }

    public function calculateHealthScore($servername,$netname){
        return round(rand(1,100),2);
    }

    public function calculateAvgRecoveryTime($servername,$netname){
        // 只查询一次数据库，获取所有满足条件的记录
        $records = Db::table('fa_network_recovery_log')
            ->where('server_name', $servername)
            ->where('net_name', $netname)
            ->where('recovery_time', '>=', date('Y-m-d 00:00:00', strtotime('-7 days')))
            ->where('recovery_time', '<=', date('Y-m-d 23:59:59'))
            ->column('duration');
        
        // 如果没有记录，直接返回0
        if (empty($records)) {
            return 0;
        }
        
        // 手动计算平均值
        $avg_recovery_time = array_sum($records) / count($records);
        
        return round($avg_recovery_time, 2);
    }

    public function calculateTraffic($servername,$netname){
            $traffic=[];
            $upload=round(rand(50,200)).'Mbps';
            $download=round(rand(100,500)).'Mbps';
            $traffic['upload']=$upload;
            $traffic['download']=$download;
            return $traffic;
    }
    
    /**
     * 获取健康评分趋势 (最近7天)
     */
    protected function getHealthScoreTrend($servername,$netname)
    {
        // 静态测试数据，不查询数据库
        $dates = [];
        $scores = [];
        
        // 获取最近7天的日期
        for ($i = 6; $i >= 0; $i--) {
            $date = date('m/d', strtotime("-$i days"));
            $dates[] = $date;
            
            // 使用networkId作为随机种子，确保同一网络每次生成相似数据
            $seed = intval($netname) + $i;
            srand($seed);
            $scores[] = rand(50, 100);
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
            $endtime=date('Y-m-d 23:59:59',strtotime("-$i days"));
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
