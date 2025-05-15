<?php
namespace app\admin\library\tincui;
use app\promin\controller\Networksocket;
use think\Db;
use think\Exception;
/**
 * 类名：AccessServer
 * 功能：与接入服务器通信，数据的处理
*/
class AccessServer{
    private $networksocket=null; //实例化套接字接口
    private $netmodel=null;
    private $nodemode=null;
    private $socket_timeout = 5; // 设置套接字超时时间为5秒
    public $PubIp=null;
    public $network=null;
    public $network=n
    public function __construct($PubIp)
    {
        //实例化模型
        $this->netmodel = model("app\admin\model\\tincui\Net");
        $this->nodemodel = model("app\admin\model\\tincui\Node"); 
        $this->networksocket = new Networksocket();
        $this->PubIp=$PubIp;
        

 
    } 

    /**生成Tinc服务 */
    public function GenerateTincs(){
        try
        {
            // 接收变量，写入对应属性

            // 使用模板创建一个空白的json数据
            $json = $this->Template;

            // 设置JSON数据中对应的字段值
            $json["Object"] = "Net";
            $json["Operation"] = "Create";
            $json["Name"] = $netName;
            $json["IP"] = $geteway.".1";
            $json["Geteway"] = $geteway;
            $json["Port"] = $port;

            // 调试代码
            // echo $netName;
            // echo $ip;
            // echo $geteway;
            // var_dump($port);

            // 添加json数据到json数组中
            $this->JsonArray[] = $json;

            // 构造json数据
            $js = json_encode($this->JsonArray);
            // 调试信息
            // echo $js;

            $conn = $this->socketCommunication($js);

            if ($conn === false) {
                throw new Exception("与服务器通信失败");
            }

            return $this->Response;
        }
        catch (Exception $e)
        {
            throw new Exception("创建内网失败: " . $e->getMessage());
        }
    }

    /**删除Tinc服务 */
    public function DeleteTincs(){

    }

    /**获取当前网络响应时间 */  
    public function GetCurResTime(){

    }

    /**获取当前网络健康得分 */
    public function GetCurHealthScore(){

    }        

    /**获取当前网络流量 */
    public function GetCurTraffic(){

    }    
    
    public function SetNetwork($netname){
        $this->network=$netname;
    }
        
    /**
     * 控制器名：generate_server
     * 功能：生成接入服务
     * 输入：serverip：公网ip；netname：内网名：netsegment：虚拟网段；port：内网服务占用端口
     * 返回：一个json字符串
    */
    public function generate_server($netname,$netsegment,$port)
    {
        if($netname==null||$netsegment==null||$port==null)
        {
            throw new Exception("参数不能为空");
        }
        
        // 设置套接字超时
        $this->networksocket->setTimeout($this->socket_timeout);
        
        try {
            // 异步处理
            $response = $this->networksocket->Net_create($this->PubIp,$netname,$netsegment,$port);
            
            if ($response === null) {
                throw new Exception("创建内网失败");
            }
            
            return $response;
        } catch (Exception $e) {
            throw new Exception("创建内网时发生错误: " . $e->getMessage());
        }
    }

       /**
     * 控制器名：delete_server
     * 功能：删除接入服务
     * 输入：serverip：公网ip；netname：内网名；
     * 返回：json字符串
    */
    public function delete_server($netname)
    {
        try {
            // 设置套接字超时
            $this->networksocket->setTimeout($this->socket_timeout);
            
            $response = $this->networksocket->Net_Delete($this->PubIp,$netname);
            
            if ($response === null) {
                throw new Exception("删除内网失败");
            }
            
            return $response;
        } catch (Exception $e) {
            throw new Exception("删除内网时发生错误: " . $e->getMessage());
        }
    }

    /*获取当前网络响应时间*/
    public function CurResTime()
    {
        
        $response = $this->networksocket->Get_Current_Response_Time($this->PubIp,$this->network);
        if($response==null)$code=1
        else $code=0;
        $res=json_encode(['code'=>$code,'response'=>$response]);
        return $res;
    }
    /**
     * 控制器名：delnodeinserver
     * 功能：删除接入服服务器上的节点,支持批量删除
     * 输入：serverip：公网ip；netname：内网名；nodenamearr：节点名列表
     * 返回：json字符串
    */
    public function delnodeinserver($netname,$nodenamearr)
    {
        if($nodenamearr==null||$netname==null)return null;
        $response = $this->networksocket->Node_Delete($this->PubIp,$netname,$nodenamearr);
        return $response;

    }


   
}