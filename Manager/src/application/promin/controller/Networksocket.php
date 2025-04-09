<?php

/**
 * NetworkSocket类
 * 作用：用于管控系统中，负责与守护进程进行网络通讯的类
 * 主要方法：节点的增删改查，内网的增删改查
 */

 namespace app\promin\controller;

 use app\common\controller\Backend;
 use think\Exception;

class Networksocket extends Backend
{
    // 套接字变量
    protected $Socket = NULL;
    // 守护进程对应的端口号
    protected $Port = NULL;
    // 定义数据模板
    protected $Template = NULL;
    // json数据数组
    protected $JsonArray = NULL;
    // 响应消息
    protected $Response = NULL;
    // 守护进程所在服务器IP
    protected $serverIP = NULL;

    // 构造函数
    public function __construct()
    {
        try
        {
            // 初始化套接字
            $this->Socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->Socket === false)
                throw new Exception("Creating socket encounter an error!");

            // 初始化端口号
            $this->Port = 55555;

            // 初始化数据模板
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

            // 初始化响应
            $this -> Response = '';

        }
        catch (Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "error.log");

            // 返回错误消息，并终止脚本运行
            exit("There was an error processing your request. Please try again later.\n");
        }

    }

    /**
     * 方法：Net_Create
     * 作用：建立套接字连接，通知守护进程，创建一个内网
     * 参数：服务器外网IP，内网名，内网网关，内网端口
     * 返回：内网配置信息(变量类型：JSON对象)
     */
    public function Net_Create($server_ip,$netName,$geteway,$port)
    {
        // 接收变量，写入对应属性
               $this -> serverIP = $server_ip;

                // 使用模板创建一个空白的json数据
                $json = $this -> Template;

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
                $this -> JsonArray[] = $json;

                // 构造json数据
                $js = json_encode($this -> JsonArray);
                // 调试信息
                // echo $js;

                $conn=$this -> socketCommunication($js);

                return $this -> Response;
 
       
    }

    /**
     * 方法：Net_Delete
     * 作用：建立套接字连接，通知守护进程，删除内网（支持批量删除）
     * 参数：接入服务器IP，内网名数组
     * 返回：操作信息
     */
    public function Net_Delete($server_ip,$netNamearray)
    {
        try
        {
            // 接收参数，写入对应属性
            $this -> serverIP = $server_ip;

            if(is_array($netNamearray))
            {
                // 使用模板创建一个空白的json数据
                $json = $this -> Template;

                // 从内网数组中取出内网名，构建对应的JSON数据
                foreach($netNamearray as &$netName)
                {

                    // 设置JSON数据中对应的字段值
                    $json["Object"] = "Net";
                    $json["Operation"] = "Delete";
                    $json["Name"] = $netName;

                    // 添加json数据到json数组中
                    $this -> JsonArray[] = $json;
                }

                // 构造JSON数据
                $js = json_encode($this -> JsonArray);
                
                // 套接字通讯
                $this -> socketCommunication($js);

                // 处理响应数据
                return $this -> Response;
            }
            else
            {
                throw new Exception("The variable \$array is not array!");
            }
        }
        catch(Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "error.log");       

            echo "There have an error in Net_Delete!\n";

            echo $e -> getMessage();
        }
    }

    /**
     * 方法：Node_Create
     * 作用：建立套接字连接，通知守护进程，接收节点
     * 参数：服务器外网IP，内网名称，节点名称，节点配置信息
     * 返回：
     */
    public function Node_Create($server_ip,$netName,$nodeName,$configFile)
    {
        try
        {
            // 接收参数，写入对应属性
            $this -> serverIP = $server_ip;
            
            // 使用模板创建一个空白的JSON数据
            $json = $this -> Template;

            // 设置JSON数据中对应的字段值
            $json["Object"] = "Node";
            $json["Operation"] = "Create";
            $json["Name"] = $nodeName;
            $json["Internet"] = $netName;
            $json["content"] = $configFile;
            
            $this -> JsonArray[] = $json;

            // 构造JSON数据
            $js = json_encode($this -> JsonArray);

            // 发送数据
            $this -> socketCommunication($js);

            return $this -> Response;
        }
        catch(Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "error.log");       

            echo "There have an error in Node_Create!\n";
            
            echo $e -> getMessage();
        }
    }

            /**
     * 方法：surveyDaemonsSocketCommunication
     * 作用：负责与监测型守护进程进行套接字通讯
     * 参数：JSON数组
     */
    public function surveyDaemonsSocketCommunication($server_IP,$json)
    {

        // $js = json_encode($this -> JsonArray);

        // 建立连接 
        socket_connect($this -> Socket,$server_IP,5555);

        // 发送请求
        socket_write($this -> Socket,$json,strlen($json));

        // 读取响应
        $this -> Response = '';
        while($buffer = @socket_read($this -> Socket,2048,PHP_NORMAL_READ))
        {
            $this -> Response .= $buffer;
        }

        // 关闭套接字
        socket_close($this -> Socket);

    }

    /**
     * 方法：Node_Delete
     * 作用：建立套接字连接，通知守护进程，删除节点（支持批量删除）
     * 参数：服务器外网IP，内网名称，节点名称数组
     * 返回：操作结果
     */
    public function Node_Delete($server_ip,$netName,$nodeNameArray)
    {
        try
        {
            // 接收参数，写入对应属性
            $this -> serverIP = $server_ip;

            if(is_array($nodeNameArray))
            {
                // 使用模板创建一个空白的json数据
                $json = $this -> Template;

                // 从内网数组中取出内网名，构建对应的JSON数据
                foreach($nodeNameArray as &$nodeName)
                {

                    // 设置JSON数据中对应的字段值
                    $json["Object"] = "Node";
                    $json["Operation"] = "Delete";
                    $json["Name"] = $nodeName;
                    $json["Internet"] = $netName;

                    // 添加json数据到json数组中
                    $this -> JsonArray[] = $json;
                }

                // 构造JSON数据
                $js = json_encode($this -> JsonArray);
                
                // 套接字通讯
                $this -> socketCommunication($js);

                // 处理响应数据
                return $this -> Response;
            }
            else
            {
                throw new Exception("The variable \$array is not array!");
            }
        }
        catch(Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "error.log");       

            echo "There have an error in Net_Delete!\n";

            echo $e -> getMessage();
        }
    }

    /**
     * 方法：socketCommunication
     * 作用：负责与守护进程进行套接字通讯
     * 参数：JSON数组
     * 返回：无
     */
    public function socketCommunication($jsonDateArray)
    {
      
        try{  
            // 建立连接
            $conn_result=socket_connect($this -> Socket,$this -> serverIP,$this -> Port);
            // 发送请求
            socket_write($this -> Socket,$jsonDateArray,strlen($jsonDateArray));

            // 读取响应
            $this -> Response = '';
            while($buffer = @socket_read($this -> Socket,2048,PHP_NORMAL_READ))
            {
                $this -> Response .= $buffer;
            }

            // 关闭套接字
            socket_close($this -> Socket);

            // 格式化信息
            // $this -> Response = json_encode($this -> Response);
            // var_dump(json_decode($this -> Response));  

        }catch(Exception $e)
        {
            return;
        }
          

    }


    /**
     * 方法：Text
     * 作用：测试该类中的其他方法
     * 参数：无
     * 返回：无
     */
    public function Text()
    {
        echo "开始测试！";
        echo "<br>";

        // 测试内网创建函数
        // $server_ip = "47.120.36.85";
        // $netName = "zwgk";
        // $geteway = "192.168.100";
        // $ip = "192.168.100.1";
        // $port = 656;

        // $result = $this -> Net_Create($server_ip,$netName,$geteway,$ip,$port);
        // echo "<br>";
        // var_dump(json_decode($result));

        // 测试内网删除函数
       	//  $server_ip = "47.113.146.125";
	$server_ip = "47.120.36.85";
	$netNamearray = ['dsafdds','kevin_test1','wkasas','wkere','wysds'];
	// $netNamearray = ['dsaf','wy','admin','laoda','sbv'];

        $result = $this -> Net_Delete($server_ip,$netNamearray);

        var_dump($result);      // String数据
        echo "<br>";
        var_dump(json_decode($result));         // Object数据
        echo "<br>";

        // 测试节点创建函数
        // $server_ip = "47.120.36.85";
        // $netName = "zwgk";
        // $nodeName = "JiuYang";
        // $configFile = "九阳123";

        // $result = $this -> Node_Create($server_ip,$netName,$nodeName,$configFile);

        // var_dump($result);
        // echo "<br>";
        // var_dump(json_decode($result));         // Object数据
        // echo "<br>";

        // 测试节点删除函数
        // $server_ip = "47.120.36.85";
        // $netName = "zwgk";
        // $nodeNamearray = ['JiuYang'];

        // $result = $this -> Node_Delete($server_ip,$netName,$nodeNamearray);

        // var_dump($result);      // String数据
        // echo "<br>";
        // var_dump(json_decode($result));         // Object数据
        // echo "<br>";
    }

}


/*
    {
    "Object": "Net/Node",
    "Operation": "Create/Delete/Able/Disable",
    "Name": "Value",
    "Extranet_IP": "Value",
    "IP": "Value",
    "Port": "Value",
    "Geteway": "Value",
    "Internet": "Value",
    "content": "Value"
    }
 */
