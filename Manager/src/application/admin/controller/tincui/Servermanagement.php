<?php
namespace app\admin\controller\tincui;
use think\Db;
use app\common\controller\Backend;
use app\admin\library\Auth;

class Servermanagement extends Backend
{
    protected $model=null;
    protected $modelnet_segment=null;
    protected $modelport=null;
    protected $searchFields = 'server_name';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\tincui\Server;
        $this->modelnet_segment = new \app\admin\model\tincui\Net_segment;
        $this->modelport = new \app\admin\model\tincui\Port;
    }

    public function selectpage()
    {
        $origin = parent::selectpage();
        $result = $origin->getData();
        $list = [];
        foreach ($result['list'] as $k => $v)
        {
            $list[] = ['server_name' => $v->server_name];
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
/*
    public function insert_fa_net_segment($str1, $end_port, $number1, $server_name){
        $net_segment_number = $number1;
        $datalist = array(); // 初始化数据项数组
        for($i = 655; $i <= $end_port; $i ++,  $net_segment_number ++)//将输入的起始和终止网段同步到fa_net_segment
        {
            $net_segment = $str1[0].".".$str1[1].".".strval($net_segment_number);
            $port = $i;
            $data = array('net_segment' => $net_segment, 'belong_serve' => $server_name, 'port' => $port); // 初始化一个新的数据项
            $datalist[] = $data;
        }
        $this->modelnet_segment->allowField(true)->insertAll($datalist);
    }
*/

    public function insert_data($str1, $start_port, $end_port, $number1, $server_name, $server_ip){
        $net_segment_number = $number1;
        $datalist1 = array(); // 初始化数据项数组
        $datalist2 = array();
        $start_port = intval($start_port);
        $end_port = intval($end_port);
        for($i = $start_port; $i <= $end_port; $i ++,  $net_segment_number ++)//将输入的起始和终止网段同步到fa_net_segment
        {
            $net_segment = $str1[0].".".$str1[1].".".strval($net_segment_number);
            $port = $i;
            $data1 = array('server_name' => $server_name, 'server_ip' => $server_ip, 'net_segment' => $net_segment, 'attribution' => 'none'); // 初始化一个新的数据项
            $data2 = array('server_name' => $server_name, 'server_ip' => $server_ip, 'port' => $port, 'attribution' => 'none'); // 初始化一个新的数据项
            $datalist1[] = $data1;
            $datalist2[] = $data2;
        }
        $this->modelnet_segment->allowField(true)->insertAll($datalist1);
        $this->modelport->allowField(true)->insertAll($datalist2);
    }

    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $params = $this->preExcludeFields($params);
        $auth=Auth::instance();
        $username = $auth->username;
        $server_name=$params['server_name'];
        $server_ip=$params['server_ip'];
        $start_segment = $params['start_segment'];
        $end_segment = $params['end_segment'];
        $start_port = $params['start_port'];
        $end_port = $params['end_port'];
        $desc = $params['desc'];
        $str1 = explode(".", $start_segment);//以'.'分割输入的起始网段字符串
        $number1 = intval($str1[2]);//将起始网段字符串的第三部分的字符串转成整数类型
        $str2 = explode(".", $end_segment);
        $number2 = intval($str2[2]);
        if ($end_port - $start_port != $number2 - $number1)  $this->error(__('输入的端口范围的大小不等于网段范围的大小！'));
        /*
        $end_port = 655 + $number2 - $number1;//通过起始网段和终止网段计算终止端口号
        $port_range = "655 ~ $end_port";*/
        //$this->insert_fa_net_segment($str1, $end_port, $number1, $server_name);
        $this->insert_data($str1, $start_port, $end_port, $number1, $server_name, $server_ip);
        $params = array('server_name' => $server_name, 'server_ip' => $server_ip, 'start_segment' => $start_segment, 'end_segment' => $end_segment, 'port_range' => $start_port . '~' . $end_port, 'desc' => $desc);
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = $this->model->allowField(true)->save($params);

        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success(__('服务器' . $server_name . '添加成功!'));
    }

    public function edit($ids = null)
    {
        if($this->request->isPost()){
            $ids = $ids ?: $this->request->post("ids");
            $server_name_before = $this->model->where('id', $ids)->value('server_name');
            $params = $this->request->post('row/a');
            $server_name_after = $params['server_name'];
            foreach($params as $key=>$value){
                if($value!=null){
                    $result=$this->editserver($key, $value, $ids);
                    break;
                }
            }

            if($result == 1 && $params['server_name']){
                $auth=Auth::instance();
                $username = $auth->username;
                $this->success(__('服务器名称' . $server_name_before . '成功修改为'. $server_name_after));
            }

        }
        return $this->fetch('edit');
    }

    public function editserver($key,$value,$ids)
    {
        $params=array($key=>$value);
        $row = $this->model->get($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {return 0;
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            return 0;
        }
        return 1;
    }

    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }

        $ids = $ids ?: $this->request->post("ids");
        $auth=Auth::instance();
        $username = $auth->username;
        $server_name = $this->model->where('id', $ids)->value('server_name');

        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $result=$this->delserver($ids);
        if($result){
            $this->success(__('服务器' . $server_name . '删除成功!'));
        }
        else{
            $this->error(__('No rows were deleted'));
        }
    }

    public function delserver($ids){
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        return $count;
    }

}

?>