<?php
namespace app\admin\library\tincui;
use app\promin\controller\Networksocket;
use think\Db;
/**
 * 类名：AccessServer
 * 功能：与接入服务器通信，数据的处理
*/
class AccessServer{
    private $networksocket=null; //实例化套接字接口
    private $netmodel=null;
    private $nodemode=null;
    public function __construct()
    {
        //实例化模型
        $this->netmodel = model("app\admin\model\\tincui\Net");
        $this->nodemodel = model("app\admin\model\\tincui\Node"); 
        $this->networksocket = new Networksocket();

 
    } 
    /**
     * 控制器名：generate_server
     * 功能：生成接入服务
     * 输入：serverip：公网ip；netname：内网名：netsegment：虚拟网段；port：内网服务占用端口
     * 返回：一个json字符串
    */
    public function generate_server($serverip,$netname,$netsegment,$port)
    {
        if($serverip==null||$netname==null||$netsegment==null||$port==null)
        {
            echo "error in generate_server：params cannot be null!";
            return null;
        }
        $response = $this->networksocket->Net_create($serverip,$netname,$netsegment,$port);
        return $response;

    }

       /**
     * 控制器名：delete_server
     * 功能：删除接入服务
     * 输入：serverip：公网ip；netname：内网名；
     * 返回：json字符串
    */
    public function delete_server($serverip,$netname)
    {
        $response = $this->networksocket->Net_Delete($serverip,$netname);
        return $response;

    }

    /**
     * 控制器名：delnodeinserver
     * 功能：删除接入服服务器上的节点,支持批量删除
     * 输入：serverip：公网ip；netname：内网名；nodenamearr：节点名列表
     * 返回：json字符串
    */
    public function delnodeinserver($serverip,$netname,$nodenamearr)
    {
        if($serverip==null||$nodenamearr==null||$netname==null)return null;
        $response = $this->networksocket->Node_Delete($serverip,$netname,$nodenamearr);
        return $response;

    }

    /**
     * 控制器名：addnode
     * 
     */
   
}