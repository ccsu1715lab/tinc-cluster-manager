<?php
namespace app\promin\controller;
use think\Db;
use app\common\controller\Backend;
use app\admin\library\tincui\Auxi;
use app\admin\library\tincui\Arrangetincbynetsocket;
use think\db\Expression;
use think\Exception;
/**
 * 类名：Api
 * 功能：暴露可直接访问的Api
*/
class Api extends Backend
{//'editadd_info','get_configinfo',
  // 'checkhouronline','checkonline','keepalive'
   protected $noNeedLogin=['measure_serverconn','update_serverstatus','device_login','addorupdate_node','editadd_info','get_configinfo','exchange_config_file','updatenodeonlinestatus','checkonline','keepalive'];
   private $nodemodel = null;
   private $netmodel = null;
   private $table_event = "fa_event";
   protected $auth=null;
    private $User=null;
    protected $model=null;
    private  $netsadded=null;
    protected $nodes = null;
    protected  $serversadded=null;
    protected  $serversmodel=null;
    private $allserver=null;
    protected $log_operations_model = null;
    private $ArrangeTinc = null;
    private $Auxi = null;
    private $user_flag=null;
    private $serverstatusmodel=null;
    
/** 
 * 函数类型：构造函数
 * */ 
   public function __construct(){
    parent::__construct();
    $this->netmodel=model("app\admin\model\\tincui\Net");
    $this->nodemodel=model("app\admin\model\\tincui\Node");
    $this->serverstatusmodel=model("app\admin\model\\tincui\Serverstatus");
    $this->ArrangeTinc = new Arrangetincbynetsocket();
    $this->Auxi = new Auxi();
    $this->lognodes = new \app\admin\model\tincui\Log_nodes;
    $this->netsadded=$this->netmodel->select();
    $this->log_operations_model = new \app\admin\model\tincui\Log_operations;
}



/**
 * 控制器名：editadd_info
 * 功能：主要用于节点配置的信息提示
 * 返回：true or false
*/
public function editadd_info()
{
    if($this->request->isPost()){
        /*$json=$this->request->post('json');
       if($json==null)
       {
       return "error:params cannot be null";
        }
        $obj=json_decode($json);
        $type=$obj->type;
        $result=$obj->result;
        $ids=$obj->ids;*/
        $type = $this->request->post('type');
        $result = $this->request->post('result');
        $details = $this->request->post('details');
        $ids = $this->request->post('ids');
        $key=$this->request->post('key');
        $value=$this->request->post('value');
        $username=$this->nodemodel->where("id",$ids)->value('username');
        /*$sid=$this->request->post('sid');
        $user=$this->nodemodel->where('sid',$sid)->value('user');
        
        */

        if($type==null||$result==null||$details==null||$ids==null)
        {
            return "error ：params cannot be null";
        }
     //   $log_flag=$obj->log_flag;
        if($result=="success")
        {
            if($type=="add")
            {
               
               $this->nodemodel->where('id',$ids)->update(['uptime'=>date('Y-m-d H:i:s'),'status'=>"已上线",'config_state'=>"配置成功"]);
               $this->add_event('节点接入',$details,$result,$username);
            }
          
            else if($type=="edit")
            {
                $this->nodemodel->where('id',$ids)->update(['status'=>"已上线",'uptime'=>date('Y-m-d H:i:s'),'config_state'=>"配置成功"]);
                Db::table('fa_node_backup')->where('id_foreign',$ids)->update([$key=>$value]);
                //添加事件
                 $this->add_event('节点修改',$details,$result,$username);

            }               
            /*
            Db::table('fa_operate_log')->where('log_flag',$log_flag)->update(['result'=>"成功"]);
            */
            return "成功";
        }
        else
        {
            if($type=="add")
            {
                $this->nodemodel->where('id',$ids)->update(['status'=>"已下线",'config_state'=>"配置失败"]);
                $this->add_event('节点接入',$details,$result,$username);
            }
            
            else if($type="edit")
            {
                $this->nodemodel->where('id',$ids)->update(['status'=>"已下线",'config_state'=>"配置失败"]);
                $this->info_reduction($ids);
                $this->add_event('节点修改',$details,$result,$username);
            }
            /*
            Db::table('fa_operate_log')->where('log_flag',$log_flag)->update(['result'=>"失败",'details'=>$obj->details]);
            */
            return "失败";
        }
    }
}


/**
 * 控制器名：add_event
 * 功能：添加事件
*/
public function add_event($type,$details,$result,$username)
{
    $event = ['type'=>$type,'details'=>$details,'time'=>date('Y-m-d H:i:s'),'result'=>$result,'username'=>$username];
    Db::table($this->table_event)->insert($event);

}



/**
 * 
 * kevin自己的客户端口
 */
public function get_configinfo()
{
    if($this->request->isPost())
    {
        header("Content-Type: application/json");
        try{
            $sid = $this->request->post('sid');
            $pass = $this->request->post('password');
            if($sid==null||$pass==null)
            {
                return json_encode(["status"=>"error","message"=>"params cannot be null"]);
            }
            $data = $this->nodemodel->where('sid',$sid)->where('password',$pass)->find();
            $response = new response();
        
            if($data!=null)
            {
                $json_str=json_encode($data);
                $response->result_validation = true;
                $response->json = $json_str;
                $message = json_encode($response);
                return json_encode(["status"=>"success","message"=>$message]);
            }
            else{
                $response->result_validation = false;
                $response->json = null;
                $message = json_encode($response);
                return json_encode(["status"=>"success","message"=>$message]);
            }
        }catch(Exception $e){
            http_response_code(500);
            return json_encode(["status"=>"error","message"=>$e->getMessage()]);

        }
        

    }
    else{
        return "非法请求";
    }
}


/**
 * 用户登录
 */
public function device_login(){
    if($this->request->isPost())
    {
    $sid = $this->request->post('sid');
    $password = $this->request->post('password');
    if($sid==null||$password==null)
    {
        return "params cannot be null";
    }
    $result = $this->nodemodel->where('sid',$sid)->find();
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
}

/**
 * 函数名：exchange_config_file
 * 功能：交换配置文件
 * 返回：字符串类型，main节点的配置信息
 * kevin自己的客户端接口
 */
public function exchange_config_file()
{
    //获取main节点的配置信息

    //测试
    /*$main_info = "# 中转外网服务器地址
    Address = 43.138.46.120
    Subnet = 0.0.0.0/0
    
    -----BEGIN OLD PUBLIC KEY-----
    MIIBCgKCAQEAupHX3ynXMjINT8s9AU2egmjrv0d8T4TYJB6BOyTa2dahz/9HI42b
    Cly6dApfkulm4s081maKFasaKDoLZgqkDi0mIor6mZMNh48f/q5UelTlC0MQnQDw
    h4sbw8bVauaJVsQEvsyY2fgdO/sWF+n2qwDlpdOoAtpyuhDESmeA3R0RgDtHvMUX
    fWTK1ZgZLxCUfbNCZ7iY+FYWin3hNA5ZKSdSgMWkfuN6S5/CdJDiXwKzOmFSVngb
    HwJCuoavIFvZM/3vknmQYix7Q706fsRbTZ3Yf3kC7ge4gBDOiNkzAXbQi+i0A6v9
    RiyxPWJ+A/JFl+Kfaprgvu6QpQfv8sSfkwIDAQAB
    -----END OLD PUBLIC KEY-----";*/

    //接受请求参数
    if($this->request->isPost())
    {
        header("Content-type:application/json");
        try{
            $nodefile_content = $this->request->post('nodefile_content');//节点文件配置信息
        $conf_content = $this->request->post('conf_content');//节点conf文件配置信息
        $sid = $this->request->post('sid');//节点sid
        $token = $this->request->post('token');//节点token用于身份验证
        $servername = null;
        $netname = null;
        $serverip = null;
        if($nodefile_content==null||$conf_content==null||$sid==null||$token==null)
        {
            $error_message="error：params cannot be null";
            return json_encode(["status"=>"error","message"=>$error_message]);
        }
        
        $right_token = $this->nodemodel->where('sid',$sid)->value('token');
        if($right_token==null)
        {
            $error_message="Internal Server Error：token is null";
            return json_encode(["status"=>"error","message"=>$error_message]);
        }
        //身份验证
        if($right_token!=$token)
        {
            $error_message="Warning：identity validation is not passed,connect refused";
            return json_encode(["status"=>"error","message"=>$error_message]);
        }

        //获取数据
        $nodecontent = $this->nodemodel->where('sid',$sid)->find();
        

        if($nodecontent==null)
        {
            $error_message = "Internal Server Error：nodecontent of".$sid."has not existed!";
            return json_encode(["status"=>"error","message"=>$error_message]);
        }
        $serverip = $nodecontent["server_ip"];
        $netname = $nodecontent["net_name"];
        $nodename = $nodecontent["node_name"];
        $servername = $nodecontent["server_name"];
        //数据验证
        if($serverip==null||$netname==null||$nodename==null||$servername==null||$this->Auxi->IPv4_validation($serverip)==false)
        {
            $error_message = "Internal Server Error：Nodecontent in MANAGEMENTSERVER is illeagl";
            return json_encode(["status"=>"error","message"=>$error_message]);
        }
        
        //正式交换配置文件
        $main_info = $this->netmodel->where('server_name',$servername)->where('net_name',$netname)->value('config');
        if($main_info==null)
        {
            $error_message="Internal Server Error：".$netname." has no config infomation,please scrutinize process";
            return json_encode(["status"=>"error","message"=>$error_message]);
        }
        else
        {
             //部署在接入服务器
             $response=$this->ArrangeTinc->NodeCreate($serverip,$netname,$nodename,$nodefile_content);
             
             //将配置信息存入数据库
             $data["nodefile_content"]=$nodefile_content;
             $data["conf_content"]=$conf_content;
             $nodeconfiginfo= json_encode($data);
             if($this->nodemodel->where('sid',$sid)->update(["config"=>$nodeconfiginfo])!=0)
             {
                $arr["main_info"]=$main_info;   
                $success_message=json_encode($arr);
                return json_encode(["status"=>"success","message"=>$success_message]);
             }
             else
             {
                
                $error_message="fail to preserve node config information ";
                return json_encode(["status"=>"error","message"=>$error_message]);
             }
        }

        }catch(PDOException $e){
            http_response_code(500);
            echo json_encode(["status" => "error","message"=>$e->getMessage()]);
            error_log($e->getMessage(),3,"requesterror.log");
        }
        
    }
    else{
        return "警告，非法请求！";
    }
}


public function updatenodeonlinestatus()
{
  $hour = date("H");
  $hour = intval($hour);
  $list = $this->netmodel->select();//遍历所有内网
  if (count($list) == 0) return "暂无内网数据";
  else
  {
        if ($hour  == 0)
       {
            foreach ($list as $item)
            {
                 $server_name = $item['server_name'];
                 $net_name = $item['net_name'];
                 if($server_name==null||$net_name==null)return "error:params cannot be null";
                 Db::table('fa_nodeonline')->where('server_name', $server_name)->where('net_name', $net_name)->update(['cntonline' => 0]);
            }
       }
     foreach ($list as $item)//遍历每个内网下的所有节点
     {
              $server_name = $item['server_name'];
              $net_name = $item['net_name'];
              if($server_name==null||$net_name==null)return "error:params cannot be null";
              $houronlineall = 0;
              $houronlineall  += $this->nodemodel->where('server_name', $server_name)->where('net_name', $net_name)->where('status', '已上线')->count();//当前内网在线节点数
              $row = Db::table('fa_nodeonline')->where('server_name', $server_name)->where('net_name', $net_name)->where('timepoint', $hour)->update(['cntonline' => $houronlineall]);
              //return '当前内网在线节点数: '.$houronlineall;
    }
}
}

    public function checkonline(){
        $list = $this->nodemodel->select();
            foreach ($list as $item) {
                $current_time = date("Y-m-d H:i:s");
                $item['current_time'] = $current_time;
                $updatetime = $item['updatetime'];
                $time_difference = strtotime($current_time) - strtotime($updatetime);
                $username = $item['username'];
                $sid=$item['sid'];
                if ($time_difference > 5&&$item['status']=='已上线') {                
                    $this->nodemodel->where('sid', $sid)->update(['status' => '已下线','downtime' => date("Y-m-d H:i")]);
                    //添加日志
                    $details="节点".$item['sid'].",位置：".$item['server_name'].'/'.$item['net_name']."。最近一次下线时间".date("Y-m-d H:i");
                    $this->add_event('status',$details,'下线',$username);
                }
                else if($time_difference <=5&&$item['status']=='已下线') 
                {
                    $this->nodemodel->where('sid', $sid)->update(['status' => '已上线','uptime' => date("Y-m-d H:i")]);
                    $details="节点".$item['sid'].",位置：".$item['server_name'].'/'.$item['net_name']."。最近一次上线时间".date("Y-m-d H:i");
                    $this->add_event('status',$details,'上线',$username);
                }
    
            }
    }

    //接入服务器连接状态判断接口
    public function measure_serverconn()
    {
        $all = $this->serverstatusmodel->select();
        foreach ($all as $item){
            $current_time = date("Y-m-d H:i:s"); 
            $time_difference = strtotime($current_time) - strtotime($item['uptime']);
            $username=Db::table('fa_server')->where('server_id',$item['server_id'])->value('username');
            if($item['conn_status']=='normal'&&$time_difference>5){
                //连接断开
                $this->serverstatusmodel->where('server_id',$item['server_id'])->update(['conn_status'=>'unormal']);
                //添加事件
                $this->add_event("接入服务器状态",'接入服务器'.$item['server_id']."服务异常","连接已断开",$username);
            }
            else if($item['conn_status']!='normal'&&$time_difference<=5){
                //连接建立
                $this->serverstatusmodel->where('server_id',$item['server_id'])->update(['conn_status'=>'normal']);
                $this->add_event("接入服务器状态",'与接入服务器'.$item['server_id']."建立连接","连接已建立",$username);
            }
        }
    }

    //接入服务器守护进程的接口
    public function update_serverstatus()
    {
        if($this->request->isPost()){
            $server_id=$this->request->post('server_id');
            $cpu_rate=$this->request->post('cpu_rate');
            $memory_rate=$this->request->post('memory_rate');
            $daemon_status=$this->request->post('daemon_status');
            $uptime=date("Y-m-d H:i:s");
            $this->serverstatusmodel->where('server_id',$server_id)->update(['cpu_rate'=>$cpu_rate,
                                                                             'memory_rate'=>$memory_rate,
                                                                             'daemon_status'=>$daemon_status,
                                                                             'uptime'=>$uptime]);
        }
    }

    public function keepalive()
    {
        if($this->request->isPost()===false){
            return "滚！";
        }

        $infomation = $this -> request -> post('Heart');
        $sid = $this -> request -> post('sid');
        if($sid==null||$infomation==null)return json(array("error"=>"params cannot be null"));
        $updatetime = date("Y-m-d H:i:s");
        $port = $this -> nodemodel->where('sid',$sid)->value('port');
        $result = $this->nodemodel->where('sid', $sid)->update(['updatetime' => $updatetime]);
        $is_update = $this->nodemodel->where('sid', $sid)->value('is_update');
        $node_name = $this->nodemodel->where('sid', $sid)->value('node_name');
        $node_ip = $this->nodemodel->where('sid', $sid)->value('node_ip');
        $password = $this->nodemodel->where('sid', $sid)->value('password');
        if($result === false)
        {
            return -1;
            exit(0);
        }
        elseif($result === 0)
        {
            // echo "没有查询到sid为".$sid."的记录。";
            return 2;
        }
        else
        {
            if ($is_update == 0)   return 1;
            else if($is_update == 1) {$this->nodemodel->where('sid', $sid)->update(['is_update' => 0]); return "Name：$node_name";}
            else if($is_update == 2) {$this->nodemodel->where('sid', $sid)->update(['is_update' => 0]); return "Subnet：$node_ip";}
	    else if($is_update == 3) {$this->nodemodel->where('sid', $sid)->update(['is_update' => 0]); return "Port:$port";}
	    else {$this->nodemodel->where('sid', $sid)->update(['is_update' => 0]); return "Password：$password";}
        }
    }



    /**
     * 老大的接口
     * 功能：添加或更新节点的接口
     */
    public function addorupdate_node()
    {
        if($this->request->isPost())
        {
            $sid=$this->request->post('sid');
            $token=$this->request->post('token');
            $config_info=$this->request->post('config_info');
            $action=$this->request->post('action');
            if($sid==null||$token==null||$config_info==null)
            {
                return 'params cannot be null';
            }
            $nodeinfo=$this->nodemodel->where('sid',$sid)->find();
            $server_ip=$nodeinfo['server_ip'];
            $net_name=$nodeinfo['net_name'];
            $node_name=$nodeinfo['node_name'];
            if($nodeinfo==null)
            {
                return 'has no sid';
            }
            if($nodeinfo['token']!=$token)  return 'identifacation error!';
            //判断是添加还是更新
            if($action=='add')
            {
                $this->exchange_config($sid,$server_ip,$net_name,$node_name,$config_info);
                return '添加成功';

            }
            else if($action=='edit')
            {
                $temp=array();
                $temp[0]= $node_name;
                $this->ArrangeTinc->DelNode($server_ip,$net_name,$temp);
                $this->exchange_config($sid,$server_ip,$net_name,$node_name,$config_info);
                return '更新成功';
            }

        }
        else
        {
            return '非法请求';
        }
    }


    public function exchange_config($sid,$server_ip,$net_name,$node_name,$config_info)
    {
        if($sid==null||$config_info==null) return 'params cannot be null';
        //写入数据库
        $this->nodemodel->where('sid',$sid)->update(['config'=>$config_info]);
        //部署接入服务器
        $reponse=$this->ArrangeTinc->NodeCreate($server_ip,$net_name,$node_name,$config_info);
    }


    


}

class response{
    public $result_validation;
    public $json;
}
