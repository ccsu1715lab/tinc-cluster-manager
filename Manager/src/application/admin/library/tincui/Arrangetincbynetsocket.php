<?php
namespace app\admin\library\tincui;
use think\Db;
use app\admin\library\tincui\Auxi;
use app\promin\controller\Networksocket;
use think\Exception;
/**
 * 类名：NetSocket
 * 功能：通过套接字部署tinc
 */
class Arrangetincbynetsocket
{
    protected $noNeedLogin = ['NodeCreate','BuildSocketCommunication'];
    /*private $DataTemplate = array
    (
        "Object"=>"",                //操作对象
        "Operation"=>"",             //操作对象
        "ObjectName"=>"",                  //对象名称
        "ServerIP"=>"",             //公网IP/接入服务器IP
        "InnerIP"=>"",              //内网IP
        "Port"=>"",                  //内网占用端口
        "Geteway"=>"" ,              //？？？
        "Netofnode"=>"",              //节点所属内网
        "NodeConfiginfo"=>""         //节点配置信息
    );*/
    private $DataTemplate = array(
        "Object" => "",                 // 操作对象
        "Operation" => "",              // 操作类型
        "Name" => "",                   // 对象名称
        "Extranet_IP" => "",            // 外网IP
        "IP" => "",                     // 内网IP
        "Port" => "",                   // 内网对应端口
        "Geteway" => "",                // 内网网关
        "Internet" => "",               // 节点所属内网
        "content" => ""                 // 节点配置信息
    );
    private $Auxi = null;
    private $ErrorInfoHeader = "Error in app\admin\controller\\tincui\ArrangeTincByNetSocket：";
    private $Port = 55555;           //服务守护进程所占用端口号
    private $networksocket = null;

    public function __construct()
    {
        $this->Auxi = new Auxi();
        $this->networksocket = new Networksocket();
       
    }

    /**
     * 控制器名：NodeCreate
     * 功能：再指定接入服务器的内网下添加一个节点（暂时使用JiuYang协议）
     * 返回：
     */
   
    public function NodeCreate($serverip,$netname,$nodename,$nodeconfiginfo)
    {
        try{    
        if($serverip==null||$netname==null||$nodeconfiginfo==null||$nodename==null)
        {
            throw new Exception($this->ErrorInfoHeader."params cannot be null!");
        }
        if($this->Auxi->IPv4_validation($serverip)===false)
        {
            throw new Exception($this->ErrorInfoHeader."Invalid params serverip ".$serverip);
        }
        //初始化数据模板
        $data_template = $this->DataTemplate; 
        $data_template["Object"] = "Node";
        $data_template["Operation"] = "Create";
        $data_template["Name"] = $nodename;
        $data_template["Internet"] = $netname;
        $data_template["content"] = $nodeconfiginfo;
        $JsonArray[] = $data_template;//JiuYang协议
        //将数据转化为json字符串
        $json_str = json_encode($JsonArray);
        //建立套接字通讯并返回响应
        $response = $this->BuildSocketCommunication($json_str,$serverip);
        if($response==false)
        {
            throw new Exception($this->ErrorInfoHeader."Refuse Connection on Host"."'".$serverip."'");
        }
        return $response;

        }catch(Exception $e)
        {
            throw new Exception("Error Processing Request", $e->getMessage);
            
        }

    }

        /**
     * 控制器名：delnodeinserver
     * 功能：删除接入服服务器上的节点,支持批量删除
     * 输入：serverip：公网ip；netname：内网名；nodenamearr：节点名列表
     * 返回：json字符串
    */
    public function DelNode($serverip,$netname,$nodenamearr)
    {
        if($serverip==null||$nodenamearr==null||$netname==null)return null;
        $response = $this->networksocket->Node_Delete($serverip,$netname,$nodenamearr);
        return $response;

    }

    /** 
     * 控制器名：BuildSocketCommunication
     * 功能：建立套接字通讯
     * 返回：false or data
    */
    public function BuildSocketCommunication($data,$serverip)
    {
        //舒适化套接字
        try{
            //数据验证
            if($this->Auxi->IPv4_validation($serverip)==false)
            throw new Exception($this->ErrorInfoHeader."\""."BuildSocketCommunication"."Invalid params serverip：".$serverip);
            if(empty(json_decode($data,true)))
            throw new Exception($this->ErrorInfoHeader."\""."BuildSocketCommunication"."Invalid params data：no json string");
            $Socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);//使用tcp协议
            if($Socket == false)
            {
                throw new Exception($this->ErrorInfoHeader."Fail to create Socket");
            }
            //建立套接字连接
            $connection=socket_connect($Socket,$serverip,$this->Port);

            if($connection==false)
            {
                return false;
            }
            //发送请求
            socket_write($Socket,$data,strlen($data));

            //接受响应
            $Response = '';
            while($buffer = socket_read($Socket,2048,PHP_NORMAL_READ))
            {
                $Response .= $buffer;
            }
            
            //关闭套接字连接
            socket_close($Socket);
            return $Response;

           
        }catch(Exception $e){
            return $this->ErrorInfoHeader.$e->getMessage();
        }
      
    }









    

}