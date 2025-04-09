<?php
/**
 * 名称：Coreplugs
 * 作用：下发节点的基本配置文件
 * 调用者：Client
 * 设计思路：验证身份 -> 检测数据 -> 生成文件 -> 打包 -> 下发
 */

 namespace app\promin\controller;

 use app\common\controller\Backend;
 use think\Request;
 use think\Db;
 use think\Exception;

 class Coreplugs
 {
    protected $Sid = '';                // POST请求的Sid数据
    protected $Token = '';              // POST请求的Token数据
    protected $Request = '';            // 实例化Request模块
    protected $nodeDate = '';           // 数据库中对应节点的数据（数组）
    protected $nodeName = '';           // 节点名称
    protected $nodeIP = '';             // 节点内网IP
    protected $netName = '';            // 节点所属内网名称
    protected $serverName = '';         // 节点所属服务器名称
    protected $Port = '';               // 节点所属内网端口
    protected $zipPath = '';            // 压缩包路径

    protected $nodeType = NULL;         // 待更新

    // 构造函数
    public function __construct()
    {
        // 初始化变量
        $this -> Request = Request::instance();

        // 当前版本，默认为1
        $this -> nodeType = 1;
    }

    /**
     * 测试HTTP请求
     */
    public function Text()
    {
        echo $this -> Request -> post('sid');
        echo "</br>";
        echo $this -> Request -> post('token');
        echo "</br>";
        echo "Finshed";
    }

    /**
     * 方法：Api
     * 作用：入口函数
     * 备注：提供给客户端调用的接口
     */
    public function Api()
    {
        // 身份验证
        $this -> Authentication();
        // 数据检查
        $this -> Check_Date();
        // 生成文件
        $this -> CreateConfigurationFile();
        // 打包
        $this -> Add_File_To_Zip();
        // 下发
        $this -> Issued();
    }

    /**
     * 方法：Authentication
     * 作用：验证请求身份
     * 备注：利用POST请求传递的参数与数据库中的数据进行匹配
     */
    protected function Authentication()
    {
        try
        {
            if(!$this -> Request -> isPost())
                throw new Exception("The Request is not a POST request!");

            // 接收POST参数
            $this -> Sid = $this -> Request -> post('sid');
            $this -> Token = $this -> Request -> post('token');

            if('' == $this -> Sid || '' == $this -> Token)
                throw new Exception("The parameter cannot be empty!");

            // 获取数据库中数据

            $nodeTableDate = Db::table('fa_node')->where('sid',$this -> Sid)->where('token',$this -> Token)->find();

            if(NULL === $nodeTableDate)
                throw new Exception("The node date is not exist!");
            
            // 身份验证完成 传递数据
            $this -> nodeDate = $nodeTableDate;
        }
        catch(Exception $e)
        {
             // 记录错误日志,
             error_log($e->getMessage(), 3, "requestError.log");
            
             // 输出错误信息
             echo $e->getMessage();

             // 返回错误消息，并终止脚本运行
             exit("There was an error processing your request. Please try again later.\n");
        }
        
        /**
         * 调试信息  */ 
        echo "身份验证成功~";
        echo "<br>";
    }

    /**
     * 方法：Check_Date()
     * 作用：检查数据库中数据是否合法
     * 备注：数据不合法，抛出异常；数据合法，接收数据
     */
    protected function Check_Date()
    {
        try
        {
            /* isset函数，检查关联数组KEY是否存在或者KEY对应Value为空 */

            // 检查节点名称
            if(!isset($this -> nodeDate['node_name']))
                throw new Exception("node_name field error!");
            
            // 检查节点ip
            if(!isset($this -> nodeDate['node_ip']))
                throw new Exception("node_ip field error!");

            // 检查节点IP是否合法
            if(!filter_var($this -> nodeDate['node_ip'], FILTER_VALIDATE_IP))
                throw new Exception("IP of node is not valid!");

            // 检查节点所属内网
            if(!isset($this -> nodeDate['net_name']))
                throw new Exception("net_name field error!");
            
            // 检查节点所属服务器
            if(!isset($this -> nodeDate['server_name']))
                throw new Exception("server_name field error!");

            // 接收数据
            $this -> nodeName = $this -> nodeDate['node_name'];
            $this -> nodeIP = $this -> nodeDate['node_ip'];
            $this -> netName = $this -> nodeDate['net_name'];
            $this -> serverName = $this -> nodeDate['server_name'];
            $this -> Port = Db::table('fa_net')->where('server_name',$this -> serverName)->where('net_name',$this -> netName)->value('port');

        }
        catch(Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "CheckDateError.log");
            
            // 输出错误信息
            echo $e->getMessage();

            // 返回错误消息，并终止脚本运行
            exit("There was an error processing your request. Please try again later.\n");
        }

        /**
         * 调试信息
         */
        echo "数据检查完毕";
        echo "<br>";
    }

    /**
     * 方法：CreateConfigurationFile
     * 作用：生成节点基本配置文件
     * 备注：生成文件的路径是在runtime文件夹下
     */
    protected function CreateConfigurationFile()
    {
        // 获取临时文件夹路径
        $tempPath = RUNTIME_PATH.'temp/';
        
        /* 创建配置文件 */
        if(file_exists($tempPath))
        {
            mkdir($tempPath.$this -> netName."/hosts",0777,true);
        }

        /* 写入基本配置文件 */
        if(is_dir($tempPath.$this -> netName))
        {
            $file = fopen($tempPath.$this -> netName."/tinc.conf","a");
            fwrite($file,"Name = ".$this -> nodeName."\n");
            fwrite($file,"Interface = VPN\n");
            fwrite($file,"ConnectTo = main\n");
            fwrite($file,"Port = ".$this -> Port);
            fclose($file);
        }
        else
            exit("conf文件写入出错");

        if(is_dir($tempPath.$this -> netName."/hosts"))
        {
            $file = fopen($tempPath.$this -> netName."/hosts/".$this -> nodeName,"a");
            fwrite($file,"Subnet = ".$this -> nodeIP."/32\n");
            fwrite($file,"Port = ".$this -> Port);
            fclose($file);
        }
        else
            exit("host配置文件写入出错");
    }

    /**
     * 方法：Add_File_To_Zip
     * 作用：将生成好的配置文件压缩
     * 备注：
     */
    protected function Add_File_To_Zip()
    {

        // 临时文件夹路径
        $tempPath = RUNTIME_PATH.'temp/';
        $zip = new \ZipArchive;
        $zip -> open('configure.zip',\ZipArchive::CREATE);
        if($this -> nodeType)
        {
            $zip -> addEmptyDir($this -> netName."/hosts");
            $zip -> addFile($tempPath.$this -> netName."/tinc.conf",$this -> netName."/tinc.conf"); 
            $zip -> addFile($tempPath.$this -> netName."/hosts"."/".$this -> nodeName,$this -> netName."/hosts/".$this -> nodeName);
        }
        else
        {
            $zip -> addEmptyDir($this -> netName."/hosts");
            $zip -> addFile($tempPath."nets.boot","nets.boot");
            $zip -> addFile($tempPath.$this -> netName."/tinc.conf",$this -> netName."/tinc.conf");
            $zip -> addFile($tempPath.$this -> netName."/tinc-down",$this -> netName."/tinc-down");
            $zip -> addFile($tempPath.$this -> netName."/tinc-up",$this -> netName."/tinc-up");
            $zip -> addFile($tempPath.$this -> netName."/hosts/main",$this -> netName."/hosts/main");
        }

        //echo "压缩包所在位置为：".realpath('configure.zip');
        $zip -> close();
        
        if($this -> nodeType)
        {
            // 删除配置文件
            unlink($tempPath.$this -> netName."/tinc.conf");
            unlink($tempPath.$this -> netName."/hosts/".$this -> nodeName);
            rmdir($tempPath.$this -> netName."/hosts");
            rmdir($tempPath.$this -> netName);
        }
        else
        {
            // 删除配置文件
            unlink($tempPath."nets.boot");
            unlink($tempPath.$this -> netName."/tinc.conf");
            unlink($tempPath.$this -> netName."/tinc-down");
            unlink($tempPath.$this -> netName."/tinc-up");
            unlink($tempPath.$this -> netName."/hosts/main");
            rmdir($tempPath.$this -> netName."/hosts");
            rmdir($tempPath.$this -> netName);
        }
        $this -> zipPath =  realpath('configure.zip');
    }

    /**
     * 方法：Issued
     * 作用：将压缩包下发
     * 备注：
     */
    protected function Issued()
    {
        if(!file_exists($this -> zipPath))
            header('HTTP/1.1 404 NOT FOUND');
        else
        \fast\Http::sendToBrowser($this -> zipPath);        
    }
 }