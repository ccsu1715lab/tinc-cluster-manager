<?php
namespace app\admin\controller\myadmin;
use think\Db;
use app\common\controller\Backend;
use think\Model;
use app\promin\controller\Networksocket;

class Node extends Backend{

    protected $noNeedLogin=['decrypt','api','save','get', 'edit', 'get_alluser', 'del', 'save_user', 'ifregiste'];
    protected $model=null;
    protected $servermodel=null;
    protected $netmodel=null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\myadmin\Node;
        $this->servermodel = new \app\admin\model\myadmin\Server;
        $this->netmodel = new \app\admin\model\myadmin\Net;
        $this->networkSocket = new Networksocket;
    }

    public function decrypt($str){
        $newstr = "";
        for($i=0;$i<strlen($str);$i++){
            if($str[$i]>='a' && $str[$i]<='z')$newchar=$str[$i];
            else {
                $newchar=chr(ord($str[$i])-13);
            }
            $newstr .= $newchar;
        }
        return $newstr;
    }

    public function api(){
        $sid = $this->request->post('sid');
        $password = $this->request->post('password');
        $result = Db::table('fa_node')->where('sid',$sid)->find();
        $db_sid = $result['sid'];
        $db_password = $result['password'];
        //$new_db_password = $this->decrypt($db_password);
        if($sid==$db_sid&&$password== $db_password)
        {
            return json_encode($result);
        }
        else {
            return 0;
        }
    }

    public function del($ids=null){
        if ($this->request->isPost()){
            $ids = $ids ?: $this->request->post("ids");
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            $delnodearray = array();
            try {
                foreach ($list as $item) {
                    $delnodearray[] = $item['node_name'];
                }
               $server_ip = $this->servermodel->where('id', $ids)->value('server_ip');
               $net_name = $this->netmodel->where('id', $ids)->value('net_name'); 
               $this->networkSocket->Node_Delete($server_ip, $net_name, $delnodearray);
                foreach ($list as $item) {
                    $count += $item->delete();
                }
                Db::commit();
            } catch (PDOException|Exception $e) {

                Db::rollback();
                $this->error($e->getMessage());
            }
            foreach ($list as $item) {
                $count += $item->delete();
            }
            return $count;
        }
    }
    public function edit($ids=null){
        $row = $this->model->get($ids);
        $ids = $this->request->post('ids');
        $key = $this->request->post('key');
        $value = $this->request->post('value');
        $params = array($key=>$value);

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
            if ($this->modelValidate) {
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
        if ($result === false) {
            return 0;
        }
        return 1;
    }
    public function save(){

        $sid = $this->request->post('sid');
        $password = $this->request->post('password');
        $inter_ip = $this->request->post('inter_ip');
        $node_ip = $this->request->post('node_ip');
        $node_name = $this->request->post('node_name');
        $node_type = $this->request->post('node_type');
        //$node_state = $this->request->post('net_state');
        $token = \fast\Random::uuid();
        $net_name = $this->request->post('net_name');
        $username = $this->request->post('username');
        $node_flag = $this->request->post('node_flag');
        $params = array('sid' => $sid, 'password' => $password, 'inter_ip' => $inter_ip, 'node_ip' => $node_ip,
            'node_name' => $node_name, 'node_type' => $node_type, 'password' => $password, 'token' => $token, 'net_name' => $net_name, 'username'=>$username,'node_flag' => $node_flag);

        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = $this->model->allowField(true)->save($params);
        if ($result === false)
        {
            return 0;
        }
        return 1;
    }

    public function get_alluser(){
        $node_flag = $this->request->post('node_flag');
        $all_user = Db::table('fa_node')->where('node_flag', $node_flag)->select();
        foreach($all_user as $k1 => $v1){
            unset($all_user[$k1]['password']);
        }
        return json($all_user);
    }

    public function get($sid,$token){

        $result = Db::table('fa_node')->where('sid',$sid)->find();
        $db_token = $result['token'];
        $node_name = $result['node_name'];
        $node_ip = $result['node_ip'];
        $net_name = $result['net_name'];
        $node_type = $result['node_type'];
        if($token==$db_token)
        {
            $data = ['node_name'=>$node_name,'node_ip'=>$node_ip,
                'net_name'=>$net_name,'node_type'=>$node_type];
            return json_encode($data);
        }
        else
        {
            return 0;
        }

    }

    public function save_user(){
        if($this->request->isPost()){
            $username = $this->request->post('username');
            $token = $this->request->post('token');
            $data = ['username'=>$username,
                'token'=>$token];
            Db::table('fa_myusers')->insert($data);
        }
    }

    public function ifregiste(){
        $token = $this->request->post('token');
        $hasuser=Db::table('fa_myusers')->where('token',$token)->find();
        if($hasuser==null){
            return 0;
        }
        return 1;
    }



 public function node_insert($params){
    
    
    if (false === $this->request->isPost()) {
        return $this->view->fetch();
    }
    if (empty($params)) {
        $this->error(__('Parameter %s can not be empty', ''));
    }
    $params = $this->preExcludeFields($params);

    if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
        $params[$this->dataLimitField] = $this->auth->id;
    }
    $result = false;
    Db::startTrans();
    try {
        //是否采用模型验证
        if ($this->modelValidate) {
            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
            $this->model->validateFailException()->validate($validate);
        }
        $result = $this->model->allowField(true)->save($params);
        Db::commit();
    } catch (ValidateException|PDOException|Exception $e) {
        Db::rollback();
        $this->error($e->getMessage());
    }
    if ($result === false) {
        return 0;
    }
    return 1;
    }

public function editnode($key,$value,$ids)
    { 
        $params=array($key=>$value);
        $row = $this->model->get($ids);
        
        if (!$row) {
            return -1;//$this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            return -2;//$this->error(__('You have no permission'));
        }
        /*if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }*/
       
        
        
        if (empty($params)) {
           return -3;// $this->error(__('Parameter %s can not be empty', ''));
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

  public function validata(){
        
            $rowname=$this->request->post('rowname');
            $rowpass=$this->request->post('rowpass');
            $user=$this->model;
            $device=$user->where('node_name',$rowname)->find();
      
            if($device!=null)
            {
                if($device['password']==$rowpass)
                return 1;
            }
            return 0;
    }
}