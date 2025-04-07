<?php
namespace app\admin\controller\myadmin;
use think\Db;
use app\common\controller\Backend;
use app\admin\library\Auth;
use app\promin\controller\Networksocket;
class Serve extends Backend
{
    protected $model=null;
    protected $modelnet_segment=null;
    protected $modelport=null;
    protected $serverstatusmodel=null;
    protected $log_operations_model = null;
    protected $searchFields = 'server_name';
    protected $networksocket= null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\myadmin\Server;
        $this->modelnet_segment = new \app\admin\model\myadmin\Net_segment;
        $this->modelport = new \app\admin\model\myadmin\Port;
        $this->serverstatusmodel = new \app\admin\model\myadmin\Serverstatus;
        $this->log_operations_model = new \app\admin\model\myadmin\Log_operations;
        $this->networksocket=new Networksocket();
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
        //参数补充
        //$params['server_id']=\fast\Random::uuid();
        $params['port_range']=$params['start_port'] . '~' . $params['end_port'];
        //$auth=new Auth();
        //$params['username']=$auth->username;
       /* //与接入服务器建立连接
        $arr=array('Localadd'=>$params['Localadd'],'Localport'=>$params['Localport'],'server_id'=>$params['server_id']);
        $this->networksocket->surveyDaemonsSocketCommunication($params['server_ip'],json_encode($arr));*/

        //将数据存储进数据库
        $this->store_port($params['server_name'],$params['server_ip'],$params['start_port'],$params['end_port']);
        unset($params['Localadd']);
        unset($params['Localport']);
        unset($params['start_port']);
        unset($params['end_port']);
        $result=$this->model->insert($params);
        /*$this->serverstatusmodel->insert(array('server_id'=>$params['server_id'],'conn_status'=>"unormal",'uptime'=>date("Y-m-d H:i:s")));*/
        //添加端口
        
        //添加网段
        $this->store_ipfragment($params['server_name'],$params['server_ip'],$params['start_segment'],$params['end_segment']);
        $auth=Auth::instance();
        $username = $auth->username;
        $logs = array('username' => $username, 'type' => '服务器添加', 'result' => '成功', 'details' => '服务器' . $params['server_name'] . '添加成功', 'occurrence_time' => date('Y-m-d H:i:s', time()));
        $this->log_operations_model->allowField(true)->save($logs);
        if($result!=0){
            $this->success(__('服务器' . $params['server_name'] . '添加成功!'));
        }
        $this->error(__('No rows were inserted'));
        /*$str1 = explode(".", $start_segment);//以'.'分割输入的起始网段字符串
        $number1 = intval($str1[2]);//将起始网段字符串的第三部分的字符串转成整数类型
        $str2 = explode(".", $end_segment);
        $number2 = intval($str2[2]);
        if ($end_port - $start_port != $number2 - $number1)  $this->error(__('输入的端口范围的大小不等于网段范围的大小！'));
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
        $logs = array('username' => $username, 'type' => '服务器添加', 'result' => '成功', 'details' => '服务器' . $server_name . '添加成功', 'occurrence_time' => date('Y-m-d H:i:s', time()));
        $this->log_operations_model->allowField(true)->save($logs);
        $this->success(__('服务器' . $server_name . '添加成功!'));
        */
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
                $logs = array('username' => $username, 'type' => '服务器修改', 'result' => '成功', 'details' => '服务器名称' . $server_name_before . '成功修改为'. $server_name_after, 'occurrence_time' => date('Y-m-d H:i:s', time()));
                $this->log_operations_model->allowField(true)->save($logs);
                $this->success(__('服务器名称' . $server_name_before . '成功修改为'. $server_name_after));
            }

        }
        return $this->fetch('edit');
    }

    public function store_ipfragment($server_name,$server_ip,$startip,$endip)
    {
        $all=array();
        list($ip1,$ip2,$ip3)=explode(".",$startip);
        list($ipa,$ipb,$ipc)=explode(".",$endip);
        $ip1=intval($ip1);
        $ip2=intval($ip2);
        $ip3=intval($ip3);
        $ipa=intval($ipa);
        $ipb=intval($ipb);
        $ipc=intval($ipc);
        $start=($ip1<<16)|($ip2<<8)|($ip3);
        $end=($ipa<<16)|($ipb<<8)|($ipc);
        $start=sprintf("%u",$start);
        $end=sprintf("%u",$end);
        $gross=$end-$start;
        //ip插入数据库
        $tmp=array('server_name' => $server_name, 'server_ip' => $server_ip, 'net_segment' => $startip, 'attribution' => 'none');
        $ip;
        $all[]=$tmp;
        $ip3++;
        while($gross!=0){
            if($ip3==256){
                $ip3=0;
                if($ip2==255){
                    $ip2=0;
                    $ip3++;
                    //将ip存入数据库
                    $ip=strval($ip1).".".strval($ip2).".".strval($ip3);
                    $tmp=array('server_name' => $server_name, 'server_ip' => $server_ip, 'net_segment' => $ip, 'attribution' => 'none');
                    $all[]=$tmp;
                }else{
                    $ip2++;
                    //将ip存入数据库
                    $ip=strval($ip1).".".strval($ip2).".".strval($ip3);
                    $tmp=array('server_name' => $server_name, 'server_ip' => $server_ip, 'net_segment' => $ip, 'attribution' => 'none');
                    $all[]=$tmp;
                }
            }else{
                //将ip存入数据库
                $ip=strval($ip1).".".strval($ip2).".".strval($ip3);
                $tmp=array('server_name' => $server_name, 'server_ip' => $server_ip, 'net_segment' => $ip, 'attribution' => 'none');
                $all[]=$tmp;
            } 
             $ip3++;
             $gross--;
        }
        $this->modelnet_segment->allowField(true)->insertAll($all);
      
    }

    public function store_port($server_name,$server_ip,$startport,$endport){
        $cnt=$endport-$startport;
        $port=$startport;
        $all=array();
        $tmp=array('server_name' => $server_name, 'server_ip' => $server_ip, 'port' => $startport, 'attribution' => 'none');
        $all[]=$tmp;
        while($cnt!=0){
            $port++;
            $tmp=array('server_name' => $server_name, 'server_ip' => $server_ip, 'port' => $port, 'attribution' => 'none');
            $all[]=$tmp;
            $cnt--;
        }
        $this->modelport->allowField(true)->insertAll($all);
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

    public function check($ids=null){
        $ids = $ids ?: $this->request->post('ids');
        if(empty($ids)){
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $this->view->assign('ids',$ids);
        return $this->view->fetch();

    }

    public function get_serverstatus(){
        if($this->request->isPost()){
            $ids=$this->request->post('ids');//获取服务器id
            //获取服务器sid
            $serverid=$this->model->where('id',$ids)->value('server_id');
            //获取服务器状态数据
            $data=$this->serverstatusmodel->where('server_id',$serverid)->find();
            return $data;

        }
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
        $logs = array('username' => $username, 'type' => '服务器删除', 'result' => '成功', 'details' => '服务器' . $server_name . '删除成功', 'occurrence_time' => date('Y-m-d H:i:s', time()));
        $this->log_operations_model->allowField(true)->save($logs);
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