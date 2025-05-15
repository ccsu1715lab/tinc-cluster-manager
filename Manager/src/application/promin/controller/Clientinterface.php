<?php
 namespace app\promin\controller;
 use think\Response;
 use app\common\controller\Backend;
 use think\request;
 use think\Db;
 use app\admin\library\tincui\Tincc;
 use think\Exception;
 use app\admin\library\tincui\Auxi;
 class Clientinterface extends Backend
 {
    protected $Auxi=null;
    protected $noNeedLogin=['get_configinfo','exchange_config_file','ClientInfo'];
    public function __construct(){
        $this->Auxi=new Auxi();
        parent::__construct();
    }


    public function get_configinfo()
    {
        if($this->request->isPost())
        {
            // 设置响应头
            header('Content-Type: application/json; charset=utf-8');
            // 关闭调试模式
            \think\Config::set('app_debug', false);
            
            try{
                $sid = $this->request->post('sid');
                $pass = $this->request->post('password');
                if($sid==null||$pass==null)
                {
                    return json_encode(["status"=>"error","message"=>"params cannot be null"]);
                }
                $data = Db::table('fa_node')->where('sid',$sid)->where('password',$pass)->find();
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
            return json_encode(["status"=>"error","message"=>"非法请求"]);
        }
    }

    public function exchange_config_file()
    {   
        //接受请求参数
        if($this->request->isPost())
        {
            header("Content-type:application/json");
            try{
            $nodefile_content = $this->request->post('nodefile_content');//节点文件配置信息
            $conf_content = $this->request->post('conf_content');//节点conf文件配置信息
            $sid = $this->request->post('sid');//节点sid
            $servername = null;
            $netname = null;
            $serverip = null;
            if($nodefile_content==null||$conf_content==null||$sid==null)
            {
                $error_message="error：params cannot be null";
                return json_encode(["status"=>"error","message"=>$error_message]);
            }

            //获取数据
            $nodecontent = Db::table('fa_node')->where('sid',$sid)->find();
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
            /*if($serverip==null||$netname==null||$nodename==null||$servername==null||$this->Auxi->IPv4_validation($serverip)==false)
            {
                $error_message = "Internal Server Error：Nodecontent in MANAGEMENTSERVER is illeagl";
                return json_encode(["status"=>"error","message"=>$error_message]);
            }
               */ 
            //正式交换配置文件
            $main_info = Db::table('fa_net')->where('server_name',$servername)->where('net_name',$netname)->value('config');
            if($main_info==null)
            {
                $error_message=" has no config infomation,please scrutinize process";
                return json_encode(["status"=>"error","message"=>$error_message]);
            }
            else
            {
                //部署在接入服务器
                $response=Tincc::AddTincc($serverip,$netname,$nodename,$nodefile_content);
                $res=json_decode($response,true);
                if($res["code"]==1){
                    return json_encode(array(status=>"error",message=>$res["response"]));
                }
                //将配置信息存入数据库
                $data["nodefile_content"]=$nodefile_content;
                $data["conf_content"]=$conf_content;
                $nodeconfiginfo= json_encode($data);
                if(Db::table('fa_node')->where('sid',$sid)->update(["config"=>$nodeconfiginfo])!=0)
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

    /**客户端配置结果通知 */
    public function ClientInfo(){
        try{
            $type=$this->request->post('type');
            $information=$this->request->post('information');
            $result=$this->request->post('result');
            $sid=$this->request->post('sid');
            $username=$this->request->post('username');
            if($result==null||$sid==null||$username==null||$type==null||$information==null)return json_encode(array("code"=>1,"response"=>"params is null"));
            $this->add_event($type,$information,$result,$username);
            Db::table('fa_node')->where('sid',$sid)->update(['uptime'=>date('Y-m-d H:i:s'),'config_state'=>$result,'status'=>"连接正常"]);
            return json_encode(array("code"=>0,"reponse"=>"成功"));
        }catch(PDOException $e){
            return json_encode(array("code"=>1,"response"=>$e->getMessage()));
        }
    }

    public function add_event($type,$details,$result,$username)
    {
        $event = ['type'=>$type,'details'=>$details,'time'=>date('Y-m-d H:i:s'),'result'=>$result,'username'=>$username];
        Db::table('fa_event')->insert($event);
    }

    public function test(){
        $sid="7c696bb7-04b2-4b30-9e47-40502a770a0e";
        $password="123456";
        return  \fast\Http::post("http://123.56.165.58/index.php/promin/Clientinterface/get_configinfo", ['sid'=>$sid, 'password'=>$password]);
    }

    public function test1(){
        $sid="db8d1ddd-dec1-43f9-9689-1b8257917a2d";
        $conf_content="Name = kkkkk
                 Interface = VPN
                 ConnectTo = main";
        $nodefile_content="Subnet = 192.168.3.4/32
                            -----BEGIN RSA PUBLIC KEY-----
                            MIIBCgKCAQEAqSJXPj+Q8YSDcu3pQG8MoH163KCYktzRMbSZz/YoSR5kVMKyVb0E
                            f7U9Ra9VTaWR8nfrpF/UNepTjN3CmdD1pTKPTrhZakpBf0aTdFUW7ebqlt3zKnRx
                            sZbajDahhjpv5m/ptfpSPokeXnFSXDuf2Bio3gygygxWNCkkf8TRsJwP2kB/nPo0
                            LbgWfS7UyykCwKC4bHeKPL5IKQaKheSfJnAf3Z6/wz67urxcH05UMHH7Jl1i75KE
                            Rx4dv+bximMUaqpgJIAqaAGYJTuiRL5Jp7dz63j9Dp/XbebcJtO6AmwrZbZEvXdC
                            anqblOa5H3GHahbjLca5hGAvEJxlAFUihQIDAQAB
                            -----END RSA PUBLIC KEY-----
                            ";
        return  \fast\Http::post("http://123.56.165.58/index.php/promin/Clientinterface/exchange_config_file", ['sid'=>'db8d1ddd-dec1-43f9-9689-1b8257917a2d', 'nodefile_content'=>$nodefile_content,'conf_content'=>$conf_content]);
    }

    public function testconfinfo(){
        $sid="5c996f3b-704f-4cab-af87-06d695fc4da2";
        $result="配置成功";
        $type="添加节点";
        $information="information";
        $username="admin";
        return  \fast\Http::post("http://123.56.165.58/index.php/promin/Clientinterface/ClientInfo", ['sid'=>$sid,'type'=>$type,'result'=>$result,'information'=>$information,'username'=>$username]);
    }
 }
