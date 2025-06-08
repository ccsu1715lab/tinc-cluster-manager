<?php
 namespace app\admin\library\tincui;
 use think\Exception;
class Networksocket
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
    public $serverIP = NULL;
    protected $timeout = 5;// 默认超时时间为30秒
    protected $noNeedRright = ['setTimeout','socketCommunication'];

    // 构造函数
    public function __construct()
    {
        try
        {
            // 初始化套接字
            $this->Socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->Socket === false)
                throw new Exception("创建套接字失败");

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
            $this->Response = '';

        }
        catch (Exception $e)
        {
            // 记录错误日志,
            error_log($e->getMessage(), 3, "error.log");
            throw new Exception("初始化套接字失败: " . $e->getMessage());
        }

    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        socket_set_option($this->Socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
    }

    /**
     * 方法：socketCommunication
     * 作用：负责与守护进程进行套接字通讯
     * 参数：JSON数组
     * 返回：无
     */
    public function socketCommunication($jsonDateArray)
    {
        try {  
            // 设置套接字超时
            socket_set_option($this->Socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
            socket_set_option($this->Socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
            
            // 建立连接
            $conn_result = @socket_connect($this->Socket, $this->serverIP, $this->Port);
            
            // 检查连接结果
            if ($conn_result === false) {
                $error_code = socket_last_error($this->Socket);
                $error_message = socket_strerror($error_code);
                socket_close($this->Socket);
                return json_encode(array('code'=>1, 'response'=>"连接服务器失败: " . $error_message));
            }

            // 发送请求
            $write_result = @socket_write($this->Socket, $jsonDateArray, strlen($jsonDateArray));
            if ($write_result === false) {
                $error_code = socket_last_error($this->Socket);
                $error_message = socket_strerror($error_code);
                socket_close($this->Socket);
                return json_encode(array('code'=>1, 'response'=>"发送数据失败: " . $error_message));
            }

            // 读取响应
            $this->Response = '';
            $start_time = time();
            while($buffer = @socket_read($this->Socket, 2048, PHP_NORMAL_READ)) {
                $this->Response .= $buffer;
                // 检查是否超时
                if (time() - $start_time > 5) {
                    socket_close($this->Socket);
                    return json_encode(array('code'=>1, 'response'=>"读取响应超时"));
                }
            }

            // 关闭套接字
            socket_close($this->Socket);
            return json_encode(array('code'=>0, 'response'=>$this->Response));

        } catch(Exception $e) {
            // 确保在发生异常时关闭socket
            if ($this->Socket) {
                socket_close($this->Socket);
            }
            // 记录错误日志
            error_log($e->getMessage(), 3, "socket_error.log");
            return json_encode(array('code'=>1, 'response'=>$e->getMessage()));
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

        // $result = $this->Net_Create($server_ip,$netName,$geteway,$ip,$port);
        // echo "<br>";
        // var_dump(json_decode($result));

        // 测试内网删除函数
       	//  $server_ip = "47.113.146.125";
	$server_ip = "47.120.36.85";
	$netNamearray = ['dsafdds','kevin_test1','wkasas','wkere','wysds'];
	// $netNamearray = ['dsaf','wy','admin','laoda','sbv'];

        $result = $this->Net_Delete($server_ip,$netNamearray);

        var_dump($result);      // String数据
        echo "<br>";
        var_dump(json_decode($result));         // Object数据
        echo "<br>";

        // 测试节点创建函数
        // $server_ip = "47.120.36.85";
        // $netName = "zwgk";
        // $nodeName = "JiuYang";
        // $configFile = "九阳123";

        // $result = $this->Node_Create($server_ip,$netName,$nodeName,$configFile);

        // var_dump($result);
        // echo "<br>";
        // var_dump(json_decode($result));         // Object数据
        // echo "<br>";

        // 测试节点删除函数
        // $server_ip = "47.120.36.85";
        // $netName = "zwgk";
        // $nodeNamearray = ['JiuYang'];

        // $result = $this->Node_Delete($server_ip,$netName,$nodeNamearray);

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
