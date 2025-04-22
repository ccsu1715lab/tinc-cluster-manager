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
            $servers = Db::name('fa_server')
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
        $servername = $this->request->param('server_name/d', 0);
        if (!$servername) {
            return json(['code' => 0, 'msg' => '请选择服务器', 'data' => []]);
        }
        
        try {
            $networks = Db::name('fa_net')
                ->field('id, name')
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
        $networkId = $this->request->param('network_id/d', 0);
        
        // 返回静态测试数据，不查询数据库
        // 生成7天的日期数据
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('m/d', strtotime("-$i days"));
            $dates[] = $date;
        }
        
        // 生成测试数据，简单使用networkId作为随机种子以保持一致性
        $seed = intval($networkId) ?: rand(1, 1000);
        srand($seed);
        
        // 测试数据
        $mockData = [
            'net_name' => '测试内网' . ($networkId ?: '1'),
            'server_name' => '测试服务器' . (intval($networkId / 100) ?: '1'),
            'status' => rand(0, 10) > 8 ? '离线' : '在线',
            'avg_response_time' => rand(5, 100),
            'health_score' => rand(60, 95),
            'avg_recovery_time' => rand(3, 30),
            'traffic' => [
                'upload' => rand(50, 200) . ' Mbps',
                'download' => rand(100, 500) . ' Mbps'
            ],
            'health_score_trend' => [
                'dates' => $dates,
                'scores' => array_map(function() { return rand(50, 100); }, range(1, 7))
            ],
            'disruption_trend' => [
                'dates' => $dates,
                'counts' => array_map(function() { return rand(0, 5); }, range(1, 7))
            ]
        ];
        
        return json(['code' => 1, 'msg' => '获取成功', 'data' => $mockData]);
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
        // 静态测试数据，不查询数据库
        $dates = [];
        $counts = [];
        
        // 获取最近7天的日期
        for ($i = 6; $i >= 0; $i--) {
            $date = date('m/d', strtotime("-$i days"));
            $dates[] = $date;
            
            // 使用networkId作为随机种子，确保同一网络每次生成相似数据
            $seed = intval($netname) + $i + 100; // 加100区分于健康评分
            srand($seed);
            $counts[] = rand(0, 5);
        }
        
        return [
            'dates' => $dates,
            'counts' => $counts
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
