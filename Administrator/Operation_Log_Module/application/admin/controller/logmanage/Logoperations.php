<?php
namespace app\admin\controller\logmanage;
use think\Db;
use app\common\controller\Backend;
use think\Controller;

class Logoperations extends Backend
{
   
    protected $model = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\logmanage\Log_operations;
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

    public function index(){
   
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