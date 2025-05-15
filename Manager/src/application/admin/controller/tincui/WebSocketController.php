<?php
namespace app\admin\controller\tincui;

use think\Controller;
use think\Db;
use app\admin\library\tincui\AccessServer;
use Workerman\Worker;

class WebSocketController extends Controller
{
    protected $accessServer = null;
    
    public function __construct()
    {
        $this->accessServer = new AccessServer();
    }
    
    public function start()
    {
        // 创建一个Worker监听2346端口，使用websocket协议通讯
        $ws_worker = new Worker("websocket://0.0.0.0:2346");
        
        // 启动4个进程对外提供服务
        $ws_worker->count = 4;
        
        // 当收到客户端发来的数据后返回hello $data给客户端
        $ws_worker->onMessage = function($connection, $data) {
            $data = json_decode($data, true);
            
            if ($data['type'] === 'create_net') {
                // 解析表单数据
                parse_str($data['data'], $params);
                
                // 发送进度消息
                $connection->send(json_encode([
                    'type' => 'progress',
                    'progress' => 10,
                    'message' => '开始创建内网...'
                ]));
                
                try {
                    // 生成服务
                    $response = $this->accessServer->generate_server(
                        $params['server_ip'],
                        $params['net_name'],
                        $params['net_segment'],
                        $params['port']
                    );
                    
                    // 更新进度
                    $connection->send(json_encode([
                        'type' => 'progress',
                        'progress' => 50,
                        'message' => '正在配置内网...'
                    ]));
                    
                    if ($response === null) {
                        throw new \Exception('创建内网失败');
                    }
                    
                    // 更新进度
                    $connection->send(json_encode([
                        'type' => 'progress',
                        'progress' => 80,
                        'message' => '正在保存配置...'
                    ]));
                    
                    // 保存到数据库
                    $result = Db::name('fa_net')->insert([
                        'server_ip' => $params['server_ip'],
                        'net_name' => $params['net_name'],
                        'net_segment' => $params['net_segment'],
                        'port' => $params['port'],
                        'status' => '正常运行中',
                        'create_time' => time()
                    ]);
                    
                    if (!$result) {
                        throw new \Exception('保存配置失败');
                    }
                    
                    // 发送成功消息
                    $connection->send(json_encode([
                        'type' => 'result',
                        'success' => true,
                        'message' => '内网创建成功'
                    ]));
                    
                } catch (\Exception $e) {
                    // 发送错误消息
                    $connection->send(json_encode([
                        'type' => 'result',
                        'success' => false,
                        'message' => $e->getMessage()
                    ]));
                }
            }
        };
        
        // 运行worker
        Worker::runAll();
    }
} 