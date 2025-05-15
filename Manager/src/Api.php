<?php
namespace app\promin\controller;
use think\Db;
use app\common\controller\Backend;

use app\admin\library\myadmin\Usernode;
use app\admin\controller\myadmin\Node;
use app\admin\library\Auth;
use think\db\Expression;
/**
 * 类名：Api
 * 功能：暴露可直接访问的Api
*/
class Api extends Backend
{//'editadd_info','get_configinfo',
  // 'checkhouronline','checkonline','keepalive'
   protected $noNeedLogin=['editadd_info','get_configinfo','exchange_config_file','checkhouronline','checkonline','keepalive'];
   private $nodemodel = null;
   private $netmodel = null;
   private $table_event = "fa_event";

    protected $User=null;
    protected $model=null;
    private  $netsadded=null;
    protected $nodes = null;
    protected  $serversadded=null;
    protected  $serversmodel=null;
    private $allserver=null;
    protected $log_operations_model = null;

    
/** 
 * 函数类型：构造函数
 * */ 
   public function __construct(){
    parent::__construct();
    $this->netmodel=model("app\admin\model\\tincui\Net");
    $this->nodemodel=model("app\admin\model\\tincui\Node");


        $this->model = new \app\admin\model\myadmin\Node;
        $this->lognodes = new \app\admin\model\logmanage\Log_nodes;
        $this->netmodel=model("app\admin\model\myadmin\Net");
        $this->netsadded=$this->netmodel->select();
        $this->log_operations_model = new \app\admin\model\myadmin\Log_operations;
}



/**
 * 控制器名：editadd_info
 * 功能：主要用于节点配置的信息提示
 * 返回：true or false
*/
public function editadd_info()
{
    if($this->request->isPost()){
        $json=$this->request->post('json');
        if($json==null)
        {
            return "error: params cannot be null";
        }
        $obj=json_decode($json);
        $type=$obj->type;
        $result=$obj->result;
        $ids=$obj->ids;
     //   $log_flag=$obj->log_flag;
        if($result=="success")
        {
            if($type=="add")
            {
               Db::table('fa_node')->where('id',$ids)->update(['config'=>$obj->config_info,'uptime'=>date('Y-m-d H:i:s'),'status'=>"已上线",'config_state'=>"配置成功"]);
               $this->add_event('add',"客户端配置成功，节点以上线");
            }
          
            else if($type=="edit")
            {
                $this->nodemodel->where('id',$ids)->update(['config'=>$obj->config_info,'status'=>"已上线",'uptime'=>date('Y-m-d H:i:s'),'config_state'=>"配置成功"]);
                 //添加事件
                 $this->add_event('edit',"修改成功，节点已上线");

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
                $this->nodemodel->where('id',$ids)->update(['status'=>"已下线",'downtime'=>date('Y-m-d H:i:s'),'config_state'=>"配置失败"]);
                $this->add_event('add',"客户端配置失败，节点未能加入内网");
            }
            
            else if($type="edit")
            {
                $this->nodemodel->where('id',$ids)->update(['status'=>"已下线",'config_state'=>"配置失败"]);
                $this->info_reduction($ids);
                $this->add_event('edit',"修改失败，节点已还原");
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
public function add_event($type,$details)
{
    $event = ['type'=>$type,'details'=>$details,'time'=>date('Y-m-d H:i:s')];
    Db::table($this->table_event)->insert($event);

}

/**
 * 控制器名：info_reduction
 * 功能：还原临时保存的信息
 * 返回：int
*/
public function info_reduction($ids)
{
    if($this->request->isPost()||$this->request->isGet()||$this->request->isPut())return '警告，非法请求';
    $filename=ADDON_PATH . 'tincui' . DS . 'data' . DS . 'temp.txt';
    $str=file_get_contents($filename);
    $temp=json_decode($str);
    $this->nodemodel->where('id',$ids)->update(['password'=>$temp->password,'node_name'=>$temp->node_name,'node_ip'=>$temp->node_ip]);

}

public function get_configinfo()
{
    if($this->request->isPost())
    {
        $sid = $this->request->post('sid');
        $pass = $this->request->post('password');
        $data = $this->nodemodel->where('sid',$sid)->where('password',$pass)->find();
        $response = new response();
      
        if($data!=null)
        {
            $json_str=json_encode($data);
            $response->result_validation = true;
            $response->json = $json_str;
        }
        else{
            $response->result_validation = false;
            $response->json = null;
        }
       /* print_r("<pre>");
        var_dump($response);
        print_r("</pre>");*/
        return json($response);

    }
    else{
        return "非法请求";
    }
}

/**
 * 函数名：exchange_config_file
 * 功能：交换配置文件
 * 返回：字符串类型，main节点的配置信息
 */
public function exchange_config_file()
{
    //获取main节点的配置信息

    //测试
    $main_info = "# 中转外网服务器地址
    Address = 43.138.46.120
    Subnet = 0.0.0.0/0
    
    -----BEGIN OLD PUBLIC KEY-----
    MIIBCgKCAQEAupHX3ynXMjINT8s9AU2egmjrv0d8T4TYJB6BOyTa2dahz/9HI42b
    Cly6dApfkulm4s081maKFasaKDoLZgqkDi0mIor6mZMNh48f/q5UelTlC0MQnQDw
    h4sbw8bVauaJVsQEvsyY2fgdO/sWF+n2qwDlpdOoAtpyuhDESmeA3R0RgDtHvMUX
    fWTK1ZgZLxCUfbNCZ7iY+FYWin3hNA5ZKSdSgMWkfuN6S5/CdJDiXwKzOmFSVngb
    HwJCuoavIFvZM/3vknmQYix7Q706fsRbTZ3Yf3kC7ge4gBDOiNkzAXbQi+i0A6v9
    RiyxPWJ+A/JFl+Kfaprgvu6QpQfv8sSfkwIDAQAB
    -----END OLD PUBLIC KEY-----";
    //接受请求参数
    if($this->request->isPost())
    {
        $nodefile_content = $this->request->post('nodefile_content');
        $conf_content = $this->request->post('conf_content');
        $sid = $this->request->post('sid');
        if($nodefile_content!=null&&$conf_content!=null&&$token!=null)
        {
            //将配置信息存入数据库
            $data["nodefile_content"]=$nodefile_content;
            $data["conf_content"]=$conf_content;
            $json_data = json($data);
            $data_insert = ['config',$json_data];
            if($this->nodemodel->where('sid',$sid)->insert($data_insert)!=0)
            {
                $arr["main_info"]=$main_info;

                return json($arr);
            }
        }
        else{
            return null;
        }
    }
    else{
        return "警告，非法请求！";
    }
}


public function checkhouronline()
    { 
        $hour = date("H");
        $list = $this->netsadded;//遍历所有内网
        foreach ($list as $item)//遍历每个内网下的所有节点
        {
            $houronlineall = 0;
            $server_name = $item['server_name'];
            $net_name = $item['net_name'];
            $houronlineall  += $this->model->where('server_name', $server_name)->where('net_name', $net_name)->where('status', '已上线')->count();//当前内网在线节点数
            $row = Db::table('fa_nodeonline')->where('server_name', $server_name)->where('net_name', $net_name)->where('timepoint', $hour.':00')->update(['cntonline' => $houronlineall]);
            return '当前内网在线节点数: '.$houronlineall;
        }
    }


    public function keepalive()
    {
        if($this->request->isPost())
        {      
            $sid = $this->request->post('sid');
            $information = $this->request->post('Heart');
            $updatetime = date("Y-m-d H:i:s");
            $result = $this->model->where('sid', $sid)->update(['updatetime' => $updatetime]);
            $is_update = $this->model->where('sid', $sid)->value('is_update');
            $node_name = $this->model->where('sid', $sid)->value('node_name');
            $node_ip = $this->model->where('sid', $sid)->value('node_ip');
            $password = $this->model->where('sid', $sid)->value('password');
            $arraynodes = array('node_name' => $node_name, 'node_ip' => $node_ip);
            $this->log_operations_model->allowField(true)->save($arraynodes);
            if($result === false)
            {
                echo "请重新请求！";
                exit(0);
            }
            elseif($result === 0)
            {
                echo '没有查找到sid为' . $sid . '的记录';
                return 2;
            }
            else
            {
                $response = 0;
                switch($is_update)
                {
                    case 0:
                        $response = 1;

                        break;
                         
                    case 1:
                        $response = "Name:$node_name";
                        break;
                        
                    case 2:
                        $response = "Subnet:$node_ip";
                        break;    

                    default:
                         $response = "Password:$password";
                         break;    
                }
                $is_update = $this->model->where('sid',$sid)->value('is_update');
                if($is_update!=0)
                {
                    $this->model->where('sid',$sid)->update(['is_update'=>0]);
                }
                return $response;
      
            }
    }
    }

}

class response{
    public $result_validation;
    public $json;
}
