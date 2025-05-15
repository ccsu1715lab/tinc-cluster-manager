<?php

namespace app\admin\controller\tincui;

use app\common\controller\Backend;
use app\admin\model\tincui\Nwdata;
use think\Db;
use think\Exception;
use app\admin\library\tincui\Tincs;
/**
 * 单网数据可视化
 *
 * @icon fa fa-line-chart
 */
class Singlenwdata extends Backend
{
    protected $model = null;
    protected $searchFields = 'server_name,net_name';
    protected $noNeedRight = [
        'index','getServers','getNetworks','getNetworkData','getHealthScoreTrend','getDisruptionTrend','calculateAvgResponseTime','calculateHealthScore','calculateAvgRecoveryTime','calculateTraffic'
    ];
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
    public function GetServers()
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
    public function GetNetworks()
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
    public function GetNetworkData()
    {
        $servername = $this->request->get('server_name');
        $netname = $this->request->get('net_name');
        $current_net=Db::table('fa_net')->where('server_name',$servername)->where('net_name',$netname)->find();
        $res_response_time=$this->GetCurResTime($servername,$netname);
        $arr_response_time=json_decode($res_response_time,true);
        $response_time=$arr_response_time['response'];

        $res_health_score=$this->GetCurHealScore($servername,$netname);
        $arr_health_score=json_decode($res_health_score,true);
        $health_score=$arr_health_score['response'];

        $avg_recovery_time=$this->GetRevTime($servername,$netname);
        $traffic=$this->GetCurTraffic($servername,$netname);
        $arr1=json_decode($traffic,true);
        $arr2=json_decode($arr1['response'],true);
        $upload=$arr2['upload'];
        $download=$arr2['download'];
        $MaxRate=$arr2['MaxRate'];
        $RevTimeTrend=$this->GetRevTimeTrend($servername,$netname);
        $health_score_trend=$this->GetHealTrend($servername,$netname);
        $disruption_trend=$this->GetDisrupTrend($servername,$netname);
        // 修复未定义的 len() 函数
        if (count($RevTimeTrend) == 0) {
            $RevTimeTrend['dates'] = [];
            $RevTimeTrend['duration'] = [];
        }
        // 返回静态测试数据，不查询数据库
        // 生成7天的日期数据
        $dates = [];
        for ($i = 7; $i > 0; $i--) {
            $date = date('m/d', strtotime("-$i days"));
            $dates[] = $date;
        }
        
        // 测试数据
        $mockData = [        
            'net_name' => $netname,
            'server_name' => $servername,
            'status' => rand(0, 10) > 8 ? '离线' : '在线',
            'response_time' => rand(5, 100),
            'health_score' => rand(60, 95),
            'avg_recovery_time' => $avg_recovery_time,
            'traffic' => [
                'upload' => rand(50, 200) . ' Mbps',
                'download' => rand(100, 500) . ' Mbps',
                'MaxRate' => $MaxRate
            ],
            'health_score_trend' => [
                'dates' => $health_score_trend['dates'],
                'scores' => $health_score_trend['scores']
            ],
            'disruption_trend' => [
                'dates' => $disruption_trend['dates'],
                'counts' => $disruption_trend['counts']
            ],
            'recovery_trend' => $RevTimeTrend
        ];
        
        return json(['code' => 1, 'msg' => '获取成功', 'data' => $mockData]);
    }


    /*计算平均响应时间*/
    public function GetCurResTime($servername=null,$netname=null){
        if($this->request->isAjax()){
            $servername=$this->request->get('server_name');
            $netname=$this->request->get('net_name');
        }
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetCurResTime();
    }

    public function GetCurHealScore($servername=null,$netname=null){
        if($this->request->isAjax()){
            $servername=$this->request->get('server_name');
            $netname=$this->request->get('net_name');
        }
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetCurHealScore();
    }

    public function GetRevTime($servername,$netname){
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetRevTime();
    }

    public function GetCurTraffic($servername=null,$netname=null){
        if($this->request->isAjax()){
            $servername=$this->request->get('server_name');
            $netname=$this->request->get('net_name');
        }
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetCurTraffic();
    }

    public function GetRevTimeTrend($servername,$netname){
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetRevTimeTrend(); 
    }
    
    /**
     * 获取健康评分趋势 (最近7天)
     */
    protected function GetHealTrend($servername,$netname)
    {
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetHealthTrend();

    }
    
    /**
     * 获取网络中断趋势 (最近7天)
     */
    protected function GetDisrupTrend($servername,$netname)
    {
        $Tincs=new Tincs($servername,$netname);
        return $Tincs->GetDisrupTrend();
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
