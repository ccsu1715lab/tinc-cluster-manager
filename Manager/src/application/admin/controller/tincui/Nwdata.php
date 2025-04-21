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
    protected $model = null;
    private $model_path = "app\admin\model\\tincui\Nwdata";
    protected $noNeedRight = [
        'index'
    ];
    
    private $auxi = null;
    
    public function __construct()
    {
        parent::__construct();
        $this->model = model($this->model_path);
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
               // 这里将来可以实现Ajax请求的数据返回
            // 当前仅供前端演示，实际数据会从数据库获取
          /*  $mockData = [
                'total_networks' => 200,
                'online_networks' => 183,
                'online_rate' => 91.5,
                'avg_recovery_time' => 8.3, // 分钟
                'health_scores' => [92, 95, 89, 94, 96, 91, 93],
                'health_max' => 96,
                'health_min' => 89,
                'health_avg' => 92.9,
                'disruptions' => [3, 1, 5, 2, 0, 1, 2],
                'disruption_max' => 5,
                'disruption_min' => 0,
                'disruption_freq' => 2.0
            ];
            return json($mockData);*/
            // 获取网络统计数据
            $networkStats = $this->model->getNetworkStats();
            
            // 获取健康分数趋势
            $healthScoreTrend = $this->model->getHealthScoreTrend();
            
            // 获取中断趋势
            $disruptionTrend = $this->model->getDisruptionTrend();
            
            // 组合数据
            $data = [
                'total_networks' => $networkStats['total_networks'],
                'online_networks' => $networkStats['online_networks'],
                'online_rate' => $networkStats['online_rate'],
                'avg_recovery_time' => $networkStats['avg_recovery_time'],
                'health_scores' => $healthScoreTrend['scores'],
                'health_max' => $healthScoreTrend['max'],
                'health_min' => $healthScoreTrend['min'],
                'health_avg' => $healthScoreTrend['avg'],
                'disruptions' => $disruptionTrend['disruptions'],
                'disruption_max' => $disruptionTrend['max'],
                'disruption_min' => $disruptionTrend['min'],
                'disruption_freq' => $disruptionTrend['freq']
            ];
            
            return json($data);

        }
        
        return $this->view->fetch('index');
    }

} 