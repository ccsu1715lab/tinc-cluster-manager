<?php
namespace app\admin\controller\tincui;
use think\Db;
use app\admin\library\tincui\Auxi;
use app\admin\library\Auth;
use app\common\controller\Backend;
use app\admin\library\tincui\Tincc;
/**
 * 类名：Nodemanagement
 * 功能：主要涉及节点的管理，包括增删改查
*/
class Nodemanagement extends Backend
{
    private $nodemodel = null;
    private $netmodel = null;
    private $nodemodel_path = "app\admin\model\\tincui\Node";
    private $netmodel_path = "app\admin\model\\tincui\Net";
    private $username = null;
    private $servers_added = null;
    private $auxi = null;
    private $servertable = "fa_server";
    private $this_log_type_add = "添加节点";
    private $this_log_type_del = "删除节点";
    private $this_log_type_edit = "更新节点";
    private $log_operation_record = array(
        "username" => "",
        "type" => "",
        "result" => "",
        "details" => "",
        "occurrence_time" => ""
    );
    protected $noNeedRight = [
        'index','add','del','edit','desc'
    ];

    public function __construct()
    {
        //继承父类
        parent::__construct();
        //实例化模型
        $this->nodemodel = model($this->nodemodel_path);
        $this->netmodel = model($this->netmodel_path);
        //初始化用户基本信息
        $auth = Auth::instance();
        $this->username = $auth->username;
        $this->log_operation_record['username']=$this->username;
        //初始化接口类
        $this->auxi = new Auxi();

    }

     
    public function index()
    {
        if($this->request->isAjax())
        {
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->nodemodel->where($where)->order($sort, $order)->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
        }
        return $this->fetch('index');
       
    }

    public function add()
    {
        //如果是get请求，则渲染模板文件并返回
        if($this->request->isGet())
        {
            $this->servers_added = Db::table($this->servertable)->select();
            $this->view->assign('servers',$this->servers_added);
            return $this->view->fetch();

        }
        //如果是post请求，则对数据进行处理并添加
        else if($this->request->isPost())
        {
            try{
            $params = $this->request->post('row/a');
            if(empty($params))
            {
                $this->error("error in nodemanagement/add：params cannot be null");
            }
            $params = $this->preExcludeFields($params);
            $netsegment = $this->auxi->GetSegOnNet($params['server_name'],$params['net_name']);
            //数据格式的验证
            if($params['server_name']=="init")
            {
                $this->error('error in nodenamagement/add：servername is invalid');
            }
            if($params['net_name']=='init')
            {
                $this->error('error in nodenamegement/add：netname is invalid');
            }
            
            if(strlen($params['password'])<6||strlen($params['password'])>16)
            {
                $this->error('lenth of password must be form 6 to 16!');
            }

            if($this->auxi->IsNodeRepeat($params['server_name'],$params['net_name'],$params['node_name'])==true)
            {
                $this->error($params['node_name']."has been used in ".$params['net_name']." on ".$params['server_name']);

            }
           
            if(($this->auxi->IPv4_validation($params['node_ip']))==false)
            {
                $this->error("nodeip is unvalid");
            }

            if($this->auxi->isipoccupied($params['node_ip'])==true)
            {
                $this->error($params['node_ip']."has been occupied");
            }

            if($this->auxi->ip_location_validation($params['node_ip'],$netsegment)==false)
            {
                $this->error("this ip is not ip under Net you selected "."segment：".$netsegment." ip：".$params['node_ip']);
            }
            
            //数据的补充
            $params['sid']=\fast\Random::uuid();
            $params['server_ip']=$this->auxi->GetServeripByServername($params['server_name']);
            $params['username']=$this->username;
            $params['esbtime']=date('Y-m-d H:i:s');
            $params['updatetime']=$params['esbtime'];
            $params['status']="断开连接";
            $params['config_state']="等待配置";
            $Tincc = new Tincc($params['sid'],$params['password'],$params['server_name'],$params['server_ip'],$params['node_ip'],$params['net_name'],$params['node_name'],$params['username'],$params['status'],$params['updatetime'],$params['desc'],$params['config_state'],$params['esbtime']);
            $Tincc->SaveInfo();
            $this->success("添加成功"); 
            }         
            catch(Exception $e){
            $this->error($e->getMessage());
            }

        }

 
    }

    public function del($ids = null)
    {
        if($this->request->isPost()===false)
        {
            $this->error("警告，非法请求！");
        }
        else
        {
            try{
            $ids = $ids ?: $this->request->post('ids');
            if(empty($ids))
            {
                $this->error("error in nodemanagement/del：ids cannot be empty");
            }
            $pk = $this->nodemodel->getPK();//获取主键名称
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->nodemodel->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->nodemodel->where($pk,'in',$ids)->select();
            if($this->auxi->IsSameNodeInNet($list)==false)
            {
                $this->error("暂不支持同时删除不同内网下的节点");
            }
            //构造将删除节点的数组
            $del_node_list = array();
            foreach($list as $item){
                $del_node_list[] = $item['node_name'];
            }
            Tincc::DelInfo($ids);
            $result=json_decode(Tincc::DelTincc($list[0]['server_ip'],$list[0]['net_name'],$del_node_list));
            $this->success($result->response);
         }
         catch(Exception $e){
            $this->error($e->getMessage());
         }
     }
    }


            

    public function edit($ids = null)
    {            
       $ids = $ids ?: $this->request->post('ids');
        if($this->request->isPost())
        {

            $params = $this->request->post('row/a');
            
            if($ids==null||$params==null)
            {
                $this->error("error in nodemanagement：params,ids cannot be null");
            }

            //数据验证
            $type = $params['type'];
            $servername = $params['server_name'];
            $netname = $params['net_name'];
            if($type == null||$servername==null||$netname==null)
            {
                $this->error("error：params  updatetype/servername/netname cannot be null");
            }
            $result = false;
            switch($type)
            {
                case "updatepassword":
                    $password = $params['password'];
                    if($password==null)
                    {
                        $this->error("error in updatepassword：params password cannot be null ");
                    }
                    if(strlen($password)<6||strlen($password)>16)
                    {
                        $this->error("error in updatepassword：lenth of password must be form 6 to 16");
                    }
                    if($password==$params['pass'])
                    {
                        $this->error('error in updatepassword：no row is updated');
                    }
                    $result = $this->update_nodeinfo($ids,"password",$password);

                    break;

                case "updatenodename":
                    $nodename = $params['node_name'];
                    $old_nodename=$this->nodemodel->where('id',$ids)->value('node_name');
                    if($nodename==null)
                    {
                        $this->error("error in updatenodename：params nodename cannot be null");
                    }
                    if(strlen($nodename)<3)
                    {
                        $this->error("lenth of nodename must be out of 3");
                    }
                    if($nodename==$old_nodename)
                    {
                        $this->error('error in updatenodename：no row is updated');
                    }
                    if($this->auxi->IsNodeRepeat($servername,$netname,$nodename)==true)
                    {
                        $this->error($nodename."has been used in ".$netname." on ".$nodename);
                    }
                    $result = $this->update_nodeinfo($ids,"node_name",$nodename);
                    break;
                    
                case "updatenodeip":
                    $nodeip = $params['node_ip']; 
                    $old_nodeip = $this->nodemodel->where('id',$ids)->value('node_ip');
                    $netsegment = $this->auxi->GetSegOnNet($params['server_name'],$params['net_name']);
                    //数据验证
                    if($nodeip==null)
                    {
                        $this->error("error in updatenodename：params nodename cannot be null");
                    }
                    if(($this->auxi->IPv4_validation($nodeip))==false)
                    {
                        $this->error("nodeip is unvalid");
                    }
                    if($nodeip==$old_nodeip)
                    {
                        $this->error('error in updatenodename：no row is updated');
                    }      
                    if($this->auxi->isipoccupied($nodeip)==true)
                    {
                        $this->error($nodeip."has been occupied");
                    }
        
                    if($this->auxi->ip_location_validation($nodeip,$netsegment)==false)
                    {
                        $this->error("this ip is not ip under Net you selected "."segment：".$netsegment." ip：".$nodeip);
                    }
                    $result = $this->update_nodeinfo($ids,"node_ip",$nodeip);
                    break;
                    
                default:
                $this->error("error:value of updatetype is false");
                break;    
            }

            //$params=json_decode($params,true);//将json字符串转化为关联数组
            if($result==true)
            {
                $this->success("successfully update,please wait for a while patiently");
            }
            $this->error("error in nodemanagement：no rows were update");

    
        }
        else if($this->request->isGet())
        {
            $servername = $this->nodemodel->where('id',$ids)->value('server_name');
            $netname = $this->nodemodel->where('id',$ids)->value('net_name');
            $sid = $this->nodemodel->where('id',$ids)->value('sid');
            $seg = $this->auxi->GetSegOnNet($servername,$netname);
            if($servername ==null||$netname==null||$sid==null||$seg==null)
            {
                return "error：params cannot be null";
            }
            if($servername!=null&&$netname!=null)
            {
                $this->view->assign('servername',$servername);
                $this->view->assign('netname',$netname);
                $this->view->assign('sid',$sid);
                $this->view->assign('seg',$seg);
                return $this->view->fetch();

            }
            return "error";
            
        }        
        
    }       


    public function desc($ids=null)
    { 
        $ids = $ids ?: $this->request->post("ids");
        if($this->request->isGet())
        {
       
        if(empty($ids)){
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $desc=$this->nodemodel->where('id',$ids)->value('desc');
        $this->view->assign("desc",$desc);
        return $this->view->fetch();
        }
        else
        {
            return "警告！非法请求";
        }

 
    }

       /**
     * 控制器名：insert_node
     * 功能：将节点数据插入数据库
     * 输入：params:要插入的数据
     * 输出：true or false
    */
    public function insert_node($params)
    {
     $result = false;
     Db::startTrans();
     try {
         //是否采用模型验证
         if ($this->modelValidate) 
         {
             $name = str_replace("\\model\\", "\\validate\\", get_class($this->nodemodel));
             $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
             $this->nodemodel->validateFailException()->validate($validate);
         }
         $result = $this->nodemodel->allowField(true)->save($params);
         Db::commit();
         } 
         catch (ValidateException|PDOException|Exception $e) 
         {
         Db::rollback();
         $this->error($e->getMessage());
         }
         return $result;
    }


        /**
     * 控制器名：update_nodeinfo
     * 功能：更新节点信息
     * 输入：ids：主键；key：字段；value：值
     * 返回：true or false
     * 
    */
    public function update_nodeinfo($ids,$key,$value)
    { 
        
        if($key==null||$value==null||$ids==null)return false;
        $is_update=0; //默认正常
        $details = "";
        switch($key)
        {
            case "node_name":
                $is_update = 1;
                $details = "修改了节点名称";
                break;

            case "node_ip":
                $is_update = 2;
                $details = "修改了内网IP";
                break;
                
            case "password":
                $is_update = 3;
                $details = "修改了密码";
                break;    
        }
        
        if($key!="password")
        $params=array($key=>$value,"status"=>"已下线","downtime"=>date('Y-m-d H:i'),"config_state"=>"配置中","is_update"=>$is_update);
        else{
            $params=array($key=>$value);
        }
        $row = $this->nodemodel->get($ids);
        if ($row==null||empty($params))
        {
            return false;
        }           
        //初始化日志模板
        $servername = $row["server_name"];
        $netname = $row["net_name"];
        $nodename = $row["node_name"];
        $log = $this->log_operation_record;
        $log["type"] = $this->this_log_type_edit;
        $log["occurrence_time"] = date('Y-m-d H:i:s');
        //$log["Object"] = "PublicServer：".$servername.";"."UnderNet：".$netname.";"."Node：".$nodename;
        

        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->nodemodel));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();

        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $log["details"] = $e->getMessage();
        }
        if($result != false)
        {
            $log["result"] = "成功";
            $log["details"] = $details;
        }
        else
        {
            $log["result"] = "失败";
            $log["details"] = "Internal Server Error";
        }
        $this->auxi->log_operation($log);
        return $result;
    }



   

    

    

}