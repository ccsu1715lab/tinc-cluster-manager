<?php
namespace app\admin\controller;

use app\admin\model\Traffic_monitor;
use app\common\controller\Backend;
use think\Controller;
use think\Request;
use think\facade\View;

class Trafficmonitor extends Backend
{

    protected $model=null;
    protected $trafficData=null;
    protected $noNeedLogin=['upsertTrafficData'];

     public function _initialize()
    {
        parent::_initialize();
        $this->model = model('app\admin\model\Traffic_monitor');
    }

    /**
     * 保存流量数据
     * @return \think\response\Json
     */
    public function saveTrafficData(Request $request)
    {
        $jsonData = $request->getContent();
        $data = json_decode($jsonData, true);
        if (isset($data['record_time'])) {
            $timestamp = strtotime($data['record_time']);
            if ($timestamp!== false) {
                // 将时间戳转换为 YYYY-MM-DD HH:MM:SS 格式
                $data['record_time'] = date('Y-m-d H:i:s', $timestamp);
            }
        }
        try {
            $this->model->create($data);
            return json(['code' => 200, 'msg' => 'Traffic data saved successfully']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => 'Failed to save traffic data: '. $e->getMessage()]);
        }
    }

    /**
     * 插入并更新流量数据
     * @return \think\response\Json
     */
    public function upsertTrafficData(Request $request)
    {
        $jsonData = $request->getContent();
        $data = json_decode($jsonData, true);
        $hostname = $data['hostname'];
        try {
            // 检查是否存在该主机名的记录
            $existingRecord = $this->model->where('hostname', $hostname)->find();
            if ($existingRecord) {
                // 如果记录存在，更新数据
                $this->model->where('hostname', $hostname)->update([
                        'host_ip' => $data['host_ip'],
                        'sent_speed' => $data['sent_speed'],
                        'recv_speed' => $data['recv_speed'],
                        'record_time' => $data['record_time']
                    ]);
            } else {
                // 如果记录不存在，插入新数据
                $this->model->create($data);
                return json(['code' => 200, 'msg' => 'Traffic data upserted successfully']);
            }
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => 'Failed to upsert traffic data: '. $e->getMessage()]);
        };
    }


    public function selectpage()
    {
        $origin = parent::selectpage();
        $result = $origin->getData();
        $list = [];
        foreach ($result['list'] as $k => $v)
        {
            $list[] = ['net_name' => $v->net_name];
        }
        $result['list'] = $list;
        return json($result);
    }

    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();


            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}