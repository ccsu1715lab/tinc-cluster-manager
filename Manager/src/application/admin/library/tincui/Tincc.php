<?php

namespace app\admin\library\tincui;
use app\common\controller\Backend;
use app\admin\library\tincui\Networksocket;
use think\Db;
use think\Exception;
class Tincc extends Backend
{
    public $sid=null;
    public $password=null;
    public $PriIp=null;
    public $node_name=null;
    public $net_name=null;
    public $username=null;
    public $status=null;
    public $updatetime=null;
    public $uptime=null;
    public $downtime=null;
    public $desc=null;
    public $is_update=null;
    public $server_name=null;
    public $PubIp=null;
    public $ConfigInfo=null;
    public $editsuccess=null;
    public $config_state=null;
    public $esbtime=null;
    public $current_time=null;
    public $port=null;
    private $Template=null;
    private $JsonArray=null;

    public function __construct($sid,$password,$server_name,$PubIp,$PriIp,$net_name,$node_name,$username,$status,$updatetime,$desc,$config_state,$esbtime)
    {
        parent::__construct();   
        $this->sid=$sid;
        $this->password=$password;
        $this->server_name=$server_name;
        $this->PubIp=$PubIp;
        $this->PriIp=$PriIp;
        $this->net_name=$net_name;
        $this->node_name=$node_name;
        $this->username=$username;
        $this->status=$status;
        $this->updatetime=$updatetime;
        $this->desc=$desc;
        $this->config_state=$config_state;
        $this->esbtime=$esbtime;
        $this->Template = array(
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
        
                    // 初始化JSON数组
        $this->JsonArray = [];
    }
    
    //添加节点
    public static function AddTincc($PubIp,$net_name,$node_name,$config_content){
            // 接收参数，写入对应属性
            $networksocket = new Networksocket();
            $networksocket->serverIP=$PubIp;
            $JsonArray=[];
            // 使用模板创建一个空白的JSON数据
            $json = array(
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
            // 设置JSON数据中对应的字段值
            $json["Object"] = "Node";
            $json["Operation"] = "Create";
            $json["Name"] = $node_name;
            $json["Internet"] = $net_name;
            $json["content"] = $config_content;
            
            $JsonArray[] = $json;

            // 构造JSON数据
            $js = json_encode($JsonArray);
            // 发送数据
            return $networksocket->socketcommunication($js);

    }

    public function SaveInfo(){
        $params['sid']=$this->sid;
        $params['password']=$this->password;
        $params['node_ip']=$this->PriIp;
        $params['net_name']=$this->net_name;
        $params['node_name']=$this->node_name;
        $params['username']=$this->username;
        $params['status']=$this->status;
        $params['updatetime']=$this->updatetime;
        $params['desc']=$this->desc;
        $params['server_name']=$this->server_name;
        $params['server_ip']=$this->PubIp;
        $params['config']=$this->ConfigInfo;
        $params['config_state']=$this->config_state;
        $params['esbtime']=$this->esbtime;
        Db::table('fa_node')->insert($params);

    }

    //删除节点
    public static function DelTincc($server_ip,$net_name,$nodearray){
        $networksocket = new Networksocket();
        if(is_array($nodearray))
        {
            $networksocket->serverIP=$server_ip;
            // 使用模板创建一个空白的json数据
            $JsonArray=[];
            $json = array(
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
                // 从内网数组中取出内网名，构建对应的JSON数据
            foreach($nodearray as &$node_name)
                {
        
                // 设置JSON数据中对应的字段值
                     $json["Object"] = "Node";
                     $json["Operation"] = "Delete";
                     $json["Name"] = $node_name;
                     $json["Internet"] = $net_name;
                    // 添加json数据到json数组中
                     $JsonArray[] = $json;
                }
        }
        $js = json_encode($JsonArray);
        return $networksocket->socketCommunication($js);
    }

    public static function DelInfo($ids){
        Db::table('fa_node')->where('id','in',$ids)->delete();
    }


    

}
