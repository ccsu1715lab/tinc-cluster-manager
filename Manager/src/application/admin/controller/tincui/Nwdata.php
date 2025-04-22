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
            // 获取网络统计数据
            $networkStats = $this->model->getNetworkStats();
            
            // 获取中断趋势
            $disruptionTrend = $this->model->getDisruptionTrend();
            

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

} 