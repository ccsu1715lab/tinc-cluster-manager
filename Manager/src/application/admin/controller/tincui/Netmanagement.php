<?php
namespace app\admin\controller\tincui;
use think\Db;
use app\admin\library\Auth;
use app\common\controller\Backend;
use app\admin\library\tincui\Auxi;
use app\admin\library\tincui\Tincs;
/**
 * 控制器名：NetManagement
 * 功能：对内网数据的管理，包括增删改查
*/
class Netmanagement extends Backend
{
    private $username=null; //用户名
    private $netmodel=null;
    private $nodemodel=null;
    private $netmodelpath = "app\admin\model\\tincui\Net";
    private $nodemodelpath = "app\admin\model\\tincui\Node";
    private $servers_added=null;//已添加的服务器集群
    private $servertable='fa_server';//服务器表名
    private $auxi = null;
    private $this_log_type_add = "内网添加";
    private $this_log_type_del = "删除内网";
    private $log_operation_record = array(
        "username" => "",
        "type" => "",
        "result" => "",
        "details" => "",
        "occurrence_time" => ""
    );
    protected $noNeedRight = [
        'index','add','del','check','desc'
    ];
   

    public function __construct()
    {
        //继承父类
        parent::__construct();
        //用户基本信息
        $auth=Auth::instance();
        $this->username=$auth->username;
        //实例化模型
        $this->netmodel=model($this->netmodelpath);
        $this->nodemodel=model($this->nodemodelpath);
        //实例化辅助功能接口
        $this->auxi=new Auxi();
        //初始化操作日志、
        $this->log_operation_record["username"] = $this->username;

    }

     /**
      * 控制器名：index
      * 功能：将渲染好的表单数据返回
      * 返回：json字符串
    */
    public function index()
    {
        if($this->request->isAjax())
        {
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->netmodel
        ->where($where)->where('username',$this->username)
        ->order($sort, $order)
        ->paginate($limit);
         $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
        }
        return $this->fetch('index');
       
    }


    /**
     * 函数名：add
     * 功能：添加内网，向接入服务发送服务生成请求，并将数据存入数据库
     * 输入：无
     * 返回：接入服务状态信息
     */
    public function add()
    {
        //如果时get请求，则渲染html文件并返回
        if($this->request->isGet())
        {
            $this->servers_added = Db::table($this->servertable)->select();//获取服务器集群
            $this->view->assign('servers_added',$this->servers_added);//渲染表单
            return $this->view->fetch();
        }
        //如果是post请求，则添加数据,生成服务
        else if($this->request->isPost())
        {
             $params = $this->request->post('row/a');//获取表单提交的数据
             if(empty($params))
             {
                $this->error($this->error(__('error in netmanagement/add：Parameter %s can not be empty','')));
             }
             //表单数据的补充
             $params['server_ip']=$this->auxi->GetServeripByServername($params['server_name']);
             $params['port']=intval($params['port']);
             $params['node_cnt']=0;
             $params['status']="在线";
             $params['username']=$this->username;
             $params['esbtime']=date('Y-m-d H:i:s');

             //数据验证
             if($params['server_name']=="下拉选择")
             {
                $this->error("error in netmanagement/add：servername is invalid");
             }
             if($params['port']=="下拉选择"||!is_numeric($params['port']))
             {
                $this->error("port is invalid");
             }
             if($params['net_segment']=="下拉选择"||$this->auxi->Segment_validation($params['net_segment'])==false)
             {
                $this->error("netsegment is invalid");
             }
             if($params['net_name']=='all')
             {
                $this->error("ALL is key");
             }
             if(($this->auxi->IsNetRepeat($params['server_name'],$params['net_name']))==true)//如果内网名已被使用
             {
                $this->error('error in netmanagement/add：the netname'.$params['net_name'].'has existed');
             }
             if(($this->auxi->IsPortRepeat($params['server_name'],$params['port']))==true)
             {
                $this->error('error in netmanagement/add：the port'.$params['port'].'has been occupied');
             }
             if(($this->auxi->IsSegRepeat($params['server_name'],$params['net_segment']))==true)
             {
                $this->error('error in netmanagement/add：the segment'.$params['net_segment'].'has been occupied');
             }
             //向接入服务器发送请求生成服务
             else
             {
                $Tincs=new Tincs($params['server_name'],$params['net_name'],$params['server_ip'],$params['net_segment'],
                $params['port'],$params['username'],$params['desc']);
                $response = $Tincs->GenerateTincs();
                $response = json_decode($response);
                if($response->code==0){
                $result=json_decode($response->response);
                $Tincs->SetConfig($result->config);
                $Tincs->SaveInfo();
                $this->auxi->occupyport($params['server_name'],$params['net_name'],$params['port']);
                $this->auxi->occupyseg($params['server_name'],$params['net_name'],$params['net_segment']);
                $this->success();
                }else{
                $this->error($response->response);
                }
            }
                    
                  
    }
}
        

    /**
     * 函数名：del
     * 功能：删除内网(包括内网下所有的节点)（支持批量删除，但不支持同时删除不同服务器上的内网），向接入服务器发送请求,支持批量删除
     * 输入：无
     * 返回：json数据：删除信息
     */

    public function del($ids = null)
    {
        $ids = $ids ?: $this->request->post("ids");

        if($this->request->isPost())
        {
            if(empty($ids))
            {
                $this->error("error in netmanagement：ids cannot be null!");
            }
            //判断是否是同一个服务器上的内网
            $pk = $this->netmodel->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) 
            {
                $this->netmodel->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->netmodel->where($pk, 'in', $ids)->select();
            $issamenetinserver=$this->auxi->IsSameNetInServer($list);
            if($issamenetinserver==false)
            {
                $this->error("暂不支持同时删除不同服务器上的内网");
            }
            else
            {
                $netnamestodelete=array();//删除内网数据
                $serverip=$list[0]['server_ip'];
                foreach ($list as $item) 
                {
                    $netnamestodelete[]=$item['net_name'];
                }
                //删除服务
                $response=Tincs::DeleteTincs($serverip,$netnamestodelete);
                $res=json_decode($response);
                if($res->code==0){
                    Tincs::DeleteInfo($list);
                    $this->success("删除成功");
                }else{
                    $this->error($res->response);
                }
            }
        }
 }
    


    


    /**
     * 函数名：check
     * 功能：获取内网下所有的节点信息并渲染模板
     * 输入：无
     * 返回：已被渲染的模板文件
     */

    public function check($ids=null)
    {
        $ids = $ids ?: $this->request->post("ids");
        if($this->request->isGet())
        {
            
            if (empty($ids)) {
                $this->error(__('Parameter %s can not be empty', 'ids'));
            }
            $AllnodesUnderNet=$this->auxi->GetAllnodeUnderNet($ids);
            $netname = $this->netmodel->where('id',$ids)->value('net_name');
            if($netname == null)
            {
                return "error:params cannot be null";
            }
            if($AllnodesUnderNet===false)
            {
                return "error: params cannot be null";
            }
            else if($AllnodesUnderNet==null)
            {
                return "There are no Nodes Under ".$netname;
            }
            else{
                $this->view->assign('netname',$netname);
                $this->view->assign('arr',$AllnodesUnderNet);
                return $this->view->fetch();
            }
            
            
            return $this->view->fetch();
        }
  
    }
    

    /**
     * 函数名：desc
     * 功能：获取备注信息并渲染模板
     * 输入：无
     * 返回：模板文件
     * 参数：ids       //数据库id字段
     */
    public function desc($ids=null)
    {
        $ids = $ids ?: $this->request->post("ids");
        if($this->request->isGet())
        {
            
            if(empty($ids)){
                $this->error(__('Parameter %s can not be empty', 'ids'));
            }
            $desc=$this->netmodel->where('id',$ids)->value('desc');
            $this->view->assign("desc",$desc);
            return $this->view->fetch();
        }

    }


     /**
     * 控制器名：insert_net
     * 功能：将内网数据插入数据库
     * 输入：params:要插入的数据
     * 输出：true or false
    */
   public function insert_net($params)
   {
    $result = false;
    Db::startTrans();
    try {
        //是否采用模型验证
        if ($this->modelValidate) 
        {
            $name = str_replace("\\model\\", "\\validate\\", get_class($this->netmodel));
            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
            $this->netmodel->validateFailException()->validate($validate);
        }
        $result = $this->netmodel->allowField(true)->save($params);
        Db::commit();
        } 
        catch (ValidateException|PDOException|Exception $e) 
        {
        Db::rollback();
        $this->error($e->getMessage());
        }
        return $result;
   }


}

