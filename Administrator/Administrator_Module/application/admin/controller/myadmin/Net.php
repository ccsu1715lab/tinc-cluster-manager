<?php
namespace app\admin\controller\myadmin;
use think\Db;
use app\common\controller\Backend;
use think\Model;
use app\promin\controller\Networksocket;

class Net extends Backend{

    protected $noNeedLogin=['get_netsegment', 'api','save_net', 'edit', 'get_allnet', 'del', 'get_userbynet'];
    protected $model=null;
	protected $networkSocket = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\myadmin\Net;
    	$this->networkSocket = new Networksocket;
    }

    public function api(){

    }
    public function del($ids=null){
        if ($this->request->isPost()){
            $ids = $ids ?: $this->request->post("ids");
            $net_segment = Db::table('fa_net')->where('id', $ids)->value('net_segment');
            Db::table('fa_segment')->where('segment', $net_segment)->update(['state' => 0]);
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
            foreach ($list as $item) {
                $count += $item->delete();
            }
            return $count;
        }
    }

    public function edit($ids=null){
        $row = $this->model->get($ids);
        $ids = $this->request->post('ids');
        $newname = $this->request->post('newname');
        $params = array('net_name'=>$newname);

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
        if (false === $result) {
            return 0;
        }
        return 1;
    }
    public function save_net(){
        $net_name = $this->request->post('net_name');
        $token = $this->request->post('token');
        $net_segment = $this->request->post('netsegment');
        $params = array('net_name' => $net_name, 'token' => $token, 'net_segment' => $net_segment);
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = $this->model->allowField(true)->save($params);
        Db::table('fa_segment')->where('segment', $net_segment)->update(['state' => 1]);
        if ($result === false)
        {
            return 0;
        }
        return 1;
    }

    public function get_allnet(){
        $token = $this->request->post('token');
        $all_usernet = Db::table('fa_net')->where('token', $token)->select();
        foreach ($all_usernet as $key => $value)
        {
            unset($all_usernet[$key]['username']);
        }
        return json($all_usernet);
    }
    public function get_userbynet()
    {
        $ids = null;
        $ids = $this->request->post('ids');
        if(!$ids)
        {
            return -1;
        }
        $net_name = Db::table('fa_net')->where('id', $ids)->value('net_name');
        if($net_name)
        {
            $net_user = Db::table('fa_node')->where('net_name', $net_name)->select();
            foreach ($net_user as $k1 => $v1)
            {
                unset($net_user[$k1]['password']);
            }
            return json($net_user);
        }
        return null;
    }

    public function get_netsegment()
    {
        $net_segment = Db::table('fa_segment')->where('state', 0)->select();
        return json($net_segment);
    }

    
 public function save_net1($params){
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
  public function delnet($ids){
  
      
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();
        $delnetarray = array();
        $count = 0;
        Db::startTrans();
        try {
             foreach($list as $item){
		     $delnetarray[] = $item['net_name'];

	     }
            $server_ip = $this->model->where('id', $ids)->value('server_ip');
	    $this -> networkSocket -> Net_Delete($server_ip,$delnetarray);
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

public function editnet($ids,$params)
{ 
    $row = $this->model->get($ids);
    
    if (!$row) {
        $this->error(__('No Results were found'));
    }
    $adminIds = $this->getDataLimitAdminIds();
    if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
        $this->error(__('You have no permission'));
    }
    /*if (false === $this->request->isPost()) {
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }*/
   
    
    
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

 public function get_userbynet1($ids){
        if($ids==null){
            return -1;
        }           
                                                                                                                                                                             
         $net_name=$this->model->where('id',$ids)->value('net_name');
    
         if($net_name!=null){
            $node=model("app\admin\model\myadmin\Node");
             $net_user=$node->where('net_name',$net_name)->select();                   
             $this->view->assign("arr",$net_user);                                                                                                                                                                                   
         }
       
         $arr=array('key'=>'null');
}

}
