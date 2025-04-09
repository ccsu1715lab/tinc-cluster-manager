<?php

/**
 * Client interface类
 * 作用：提供给客户端调用的接口
 * 主要方法：配置文件交换
 */

 namespace app\promin\controller;

 use think\Response;
 use app\admin\library\tincui\Arrangetincbynetsocket;
 use app\common\controller\Backend;
 use think\request;
 use think\Db;
 use app\promin\controller\Networksocket;
 use think\Exception;

 class Clientinterface extends Backend
 {
    // 客户端SID
    protected $Sid = NULL;
    // 客户端Token
    protected $Token = NULL;
    // 客户端节点名称
    protected $nodeName = NULL;
    // 客户端配置文件
    protected $clientFile = NULL;
    // 接入服务器IP
    protected $serverIP = NULL;
    // 内网名称
    protected $netName = NULL;
    // 内网配置文件
    protected $netConfigFile = NULL;
    // 套接字控制器
    protected $Networksocket = NULL;

    private $ArrangeTinc = NULL;

    // 构造函数
    public function __construct()
    {
        $this->ArrangeTinc = new Arrangetincbynetsocket();
        // 初始化SID
        $this -> Sid = '';
        // 初始化接入服务器IP
        $this -> serverIP = '';
        // 初始化节点名称
        $this -> nodeName = '';
        // 初始化配置文件信息
        $this -> clientFile = '';
        // 初始化令牌
        $this -> Token = '';
        // 初始化内网名称
        $this -> netName = '';
        // 初始化内网配置文件
        $this -> netConfigFile = '';

        try
        {
            // 初始化套接字控制器
            $this -> Networksocket = new Networksocket();

            if($this -> Networksocket == NULL)
                throw new Exception("Initialization failed!");
        }
        catch(Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "error.log");

            // 返回错误消息，并终止脚本运行
            exit("There was an error processing your request. Please try again later.\n");
        }

    }

    /**
     * 方法：exchangeFile
     * 作用：用于客户端交换配置文件
     * 参数：无
     * 返回：服务器配置文件
     */
    public function exchangeFile()
    {
        // 请求认证
        if(!Request::instance() -> isPost())
        {
            echo "你干嘛~";
            echo "<br>";
            echo "非法访问！警告一次！";
            exit(1);
        }   

        // HTTP请求，参数接收
        $this -> Sid = Request::instance() -> post('sid');
        $this -> Token = Request::instance() -> post('token');
        $this -> clientFile = Request::instance() -> post('content');
        $action = Request::instance() -> post('action');
        $key=Request::instance() -> post('key');
  
        // 判断参数是否为空
        if('' == $this -> Sid||'' == $this -> clientFile||'' == $this -> Token||$action=='')
        {
            return "The parameter cannot be empty!";
        }

        // 查询数据库中的节点数据
        $nodeInformation = Db::table('fa_node')->where('sid',$this -> Sid)->find();

        // 查询结果判断
        if($nodeInformation === false)
        {
            return "The information of node is not found!";
        }

        // 身份验证
        if($nodeInformation['token']!=$this -> Token)
        {
            return "Warning!Trespassing!";
        }

        $this -> serverIP = $nodeInformation['server_ip'];
        $this -> netName = $nodeInformation['net_name'];
        $this -> nodeName = $nodeInformation['node_name'];

        // 判断数据库中数据是否合法
        if('' == $this -> serverIP||'' == $this -> netName)
        {
            return "There is an error with the data in the database, please check the database!";
        }

        // 查询数据库中内网数据
        $netInformation = Db::table('fa_net')->where('server_ip',$this -> serverIP)->where('net_name',$this -> netName)->find();
        $this -> netConfigFile = $netInformation['config'];

        //判断内网配置文件是否为空
        if('' == $this -> netConfigFile)
        {
            return "An error occurred in the intranet database, please check the database!";
        }

        // 客户端配置文件写入数据库
        $writeResult = Db::table('fa_node')->where('sid',$this -> Sid)->update(['config' => $this -> clientFile]);

        // 判断写入结果
        if(false === $writeResult)
            return "Configuration file write failed, check the database!";

        // 接入服务器配置
        if($action=='update')
        {
            if($key==null) 
                return "params cannot be null";
            if($key=='node_name')
            {
                $rawname=Db::table('fa_node_backup')->where('sid',$this->Sid)->value('node_name');
                $nodename=array();
                $nodename[0]=$rawname;
                $this->ArrangeTinc->DelNode($this->serverIP,$this->netName,$nodename);
                $this->ArrangeTinc->NodeCreate($this->serverIP,$this->netName,$this->nodeName,$this->clientFile);
                
            }
            else if($key=='node_ip')
            {
                $nodename=array();
                $nodename[0]=$this->nodeName;
                $this->ArrangeTinc->DelNode($this->serverIP,$this->netName,$nodename);
                $this->ArrangeTinc->NodeCreate($this->serverIP,$this->netName,$this->nodeName,$this->clientFile);
                //$this->Networksocket -> Node_Delete($this->serverIP,$this->netName,$nodename);
                //$this -> Networksocket -> Node_Create($this -> serverIP,$this -> netName,$this -> nodeName,$this -> clientFile);
            }
            
        }
        else if($action=='add')
        {
            $this -> Networksocket -> Node_Create($this -> serverIP,$this -> netName,$this -> nodeName,$this -> clientFile);
            // 下发配置文件
            $this -> sendFiledToBrowser();   
        }
        


    }

    /**
     * 方法：sendFiledToBrowser
     * 作用：以下载文件的方式输出到客户端
     * 参数：无
     * 返回：实体文件
     */
    protected function sendFiledToBrowser()
    {
        // 设置响应头，告诉浏览器返回的结果是文件而不是网页
        header("Content-Type: application/octet-stream");
    
        // 设置响应头，告诉浏览器使用下载方式处理文件
        header('Content-Disposition: attachment; filename="main"');
    
        // 创建Response对象
        $response = Response::create($this->netConfigFile, 'file');
    
        // 发送响应
        $response->send();
    
        // 终止程序执行
        exit();
    }

    /**
     * 方法：Text
     * 作用：测试Client Interface 中的方法
     * 参数：无
     * 返回：测试结果
     */
    protected function Text()
    {
        
    }
 }
