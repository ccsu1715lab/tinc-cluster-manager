<?php

namespace app\admin\library\tincui;
use app\common\controller\Backend;
use app\admin\library\tincui\Networksocket;
use think\Db;
use think\Exception;
class Tincs extends Backend
{
    public $ServerName=null;
    public $PubIp=null;
    public $Network=null;
    public $PriIp=null;
    public $Port=null;
    public $CreateTime=null;
    public $Username=null;
    public $status=null;
    public $OnlineTime=null;
    public $OfflineTime=null;
    public $Config=null;
    public $NodeCnt=null;
    public $Desc=null;
    public $Token=null;
    private $Template=null;
    private $JsonArray=null;

    public function __construct($ServerName,$Network,$PubIp=null,$PriIp=null,$Port=null,$Username=null,$Desc=null)
    {
        parent::__construct();   
        $this->ServerName=$ServerName;
        $this->PubIp=$PubIp;
        $this->Network=$Network;
        $this->PriIp=$PriIp;
        $this->Port=$Port;
        $this->Username=$Username;
        $this->Desc=$Desc;
        $this->CreateTime=date('Y-m-d H:i:s');
        $this->OnlineTime=date('Y-m-d H:i:s');
        $this->NodeCnt=0;
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
    
    public function GenerateTincs()
    {
        try{
            $json = $this->Template;
            $json["Object"] = "Net";
            $json["Operation"] = "Create";
            $json["Name"] = $this->Network;
            $json["IP"] = $this->PriIp.".1";
            $json["Geteway"] = $this->PriIp;
            $json["Port"] = $this->Port;
            $this->JsonArray[] = $json;
            $js = json_encode($this->JsonArray);
            $NetworkSocket=new Networksocket();
            $NetworkSocket->serverIP=$this->PubIp;
            $response=$NetworkSocket->socketCommunication($js);
            return $response;
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }
 

    }

    public function SetConfig($Config){
        $this->Config=$Config;
    }

    public static function DeleteTincs($ServerIp,$NetArray)
    {
        try{
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
            foreach($NetArray as &$netName)
            {
                $json["Object"] = "Net";
                $json["Operation"] = "Delete";
                $json["Name"] = $netName;
                $JsonArray[] = $json;
            }
            $js = json_encode($JsonArray);
            $NetworkSocket=new Networksocket();
            $NetworkSocket->serverIP=$ServerIp;
            $response=$NetworkSocket->socketCommunication($js);
            return $response;
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }

    }

    public function GetDailyOnlineRate($servername, $netname, $date) {
        // 获取当天的所有状态变化日志
        $logs = Db::table('fa_network_status_log')
            ->where('server_name', $servername)
            ->where('net_name', $netname)
            ->where('change_time', '>=', $date . ' 00:00:00')
            ->where('change_time', '<=', $date . ' 23:59:59')
            ->order('change_time ASC')
            ->select();
        
        // 添加0号节点（当天0点）
        array_unshift($logs, [
            'status' => $this->getInitialStatus($servername, $netname, $date),
            'change_time' => $date . ' 00:00:00'
        ]);
        
        // 如果节点数量为奇数，添加最后一刻作为最后一个节点
        if (count($logs) % 2 != 0) {
            $logs[] = [
                'status' => end($logs)['status'],
                'change_time' => $date . ' 23:59:59'
            ];
        }
        
        // 计算总时间
        $totalOnlineTime = 0;
        $totalOfflineTime = 0;
        
        // 每两个节点为一组计算时间
        for ($i = 0; $i < count($logs); $i += 2) {
            $startTime = strtotime($logs[$i]['change_time']);
            $endTime = strtotime($logs[$i + 1]['change_time']);
            $duration = $endTime - $startTime;
            
            if ($logs[$i]['status'] === '在线') {
                $totalOnlineTime += $duration;
            } else {
                $totalOfflineTime += $duration;
            }
        }
        
        // 计算在线率
        $totalTime = 24 * 3600; // 24小时的总秒数
        $onlineRate = ($totalOnlineTime / $totalTime) * 100;
        
        return round($onlineRate, 2);
    }
    
    /**
     * 获取网络在指定日期开始时的初始状态
     */
    private function getInitialStatus($servername, $netname, $date) {
        // 查找日期开始前最后一条状态记录
        $lastLog = Db::table('fa_network_status_log')
            ->where('server_name', $servername)
            ->where('net_name', $netname)
            ->where('change_time', '<', $date . ' 00:00:00')
            ->order('change_time DESC')
            ->find();
        
        // 如果没有历史记录，从网络表中获取当前状态
        if (!$lastLog) {
            $network = Db::table('fa_net')
                ->where('server_name', $servername)
                ->where('net_name', $netname)
                ->find();
            return $network['status'];
        }
        
        return $lastLog['status'];
    }

    private function GetDailyHealScore($servername,$netname,$date){
        $DailyOnlineRateScore=$this->GetDailyOnlineRateScore($servername,$netname,$date);
        $DailyDisrupScore=$this->GetDailyDisrupScore($servername,$netname,$date);
        $DailyRecoveryTimeScore=$this->GetDailyRevScore($servername,$netname,$date);
        return round($DailyOnlineRateScore*0.5+$DailyDisrupScore*0.3+$DailyRecoveryTimeScore*0.2,2);
        
    }

    private function GetDailyOnlineRateScore($servername,$netname,$date){
        $DailyOnlineRate=$this->GetDailyOnlineRate($servername,$netname,$date);
        return $DailyOnlineRate;
    }

    private function GetDailyDisrupScore($servername,$netname,$date){
        $count=Db::table('fa_network_disruption_log')->where('server_name',$servername)->where('net_name',$netname)->where('offline_time','>=',$date.' 00:00:00')->where('offline_time','<=',$date.' 23:59:59')->count();
        $MaxCnt=5;
        $score=$count>$MaxCnt?0:($MaxCnt-$count)*(100/$MaxCnt);
        return $score;
    }

   private function GetDailyRevScore($servername,$netname,$date){
        $DailyAvgRevTime=Db::table('fa_network_recovery_log')->where('server_name',$servername)->where('net_name',$netname)->where('recovery_time','>=',$date.' 00:00:00')->where('recovery_time','<=',$date.' 23:59:59')->avg('duration');
        $MaxTime=30;
        $score=$DailyAvgRevTime>$MaxTime?0:($MaxTime-$DailyAvgRevTime)*(100/$MaxTime);
        return $score;
   }

    /**删除内网信息 */
    public static function DeleteInfo($List){
        foreach($List as $item){
            //删除内网
            Db::table('fa_net')->where('server_name',$item['server_name'])->where('net_name',$item['net_name'])->delete();
            //删除内网下的节点
            Db::table('fa_node')->where('server_name',$item['server_name'])->where('net_name',$item['net_name'])->delete();
            //释放端口
            $auxi=new Auxi();
            $auxi->realiseport($item['server_name'],$item['port']);
            //释放网段
            $auxi->realisesegment($item['server_name'],$item['net_segment']);
        }

    }

    public function GetCurResTime()
    {
        try{
            $response_time=round(rand(5,100));
            return json_encode(array('code'=>0,'response'=>$response_time));
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }

    }

    public function GetCurTraffic()
    {
        try{
            $upload=round(rand(50,200));
            $download=round(rand(100,500));
            $MaxRate=1000;
            $response=json_encode(array('upload'=>$upload,'download'=>$download,'MaxRate'=>$MaxRate));
            return json_encode(array('code'=>0,'response'=>$response));
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }
    }

    private function GetCurTrafficSocre(){
        try{
            $ResTraffic=json_decode(json_encode(array('code'=>0,'response'=>array('Upload'=>100,'Download'=>200,'MaxRate'=>1000))),true);
            if($ResTraffic['code']==1){
                return json_encode(array('code'=>1,'response'=>'流量获取异常'));
            }
            $Traffic=$ResTraffic['response'];
            $UploadRate=$Traffic['Upload'];
            $DownloadRate=$Traffic['Download'];
            $MaxRate=$Traffic['MaxRate'];
            $TrafficScore=round((1-($UploadRate+$DownloadRate)/$MaxRate)*100);
            return json_encode(array('code'=>0,'response'=>$TrafficScore));
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }
    }

    public function GetCurHealScore()
    {
        try{
            #流量得分
            $TrafficRes=json_decode($this->GetCurTrafficSocre(),true);
            if($TrafficRes['code']==1){
                return json_encode(array('code'=>1,'response'=>$TrafficRes['response']));
            }
            $TrafficScore=$TrafficRes['response'];
            #响应时间得分
            $ResponseTimeRes=json_decode($this->GetCurResTime(),true);
            if($ResponseTimeRes['code']==1){
                return json_encode(array('code'=>1,'response'=>'响应时间得分获取异常'));
            }
            $ResponseTimeScore=$ResponseTimeRes['response'];
            $MaxReScore=200;//最大响应时间为200ms
            $ResponseTimeScore=round((1-($ResponseTimeScore/$MaxReScore))*100);
            #在线得分
            if(Db::table('fa_net')->where('server_name',$this->ServerName)->where('net_name',$this->Network)->value('status')=='在线'){
                $OnlineScore=100;
            }else $OnlineScore=0;
            #健康得分
            $HealScore=round($OnlineScore*0.2+$TrafficScore*0.4+$ResponseTimeScore*0.4);
            return json_encode(array('code'=>0,'response'=>$HealScore));
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }
    }

    public function GetHealthTrend()
    {
        try{
            // 静态测试数据，不查询数据库
            $dates = [];
            $scores = [];
            
            // 获取最近7天的日期,不包括今天
            for ($i = 7; $i > 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dates[] = $date;
                $scores[]=$this->GetDailyHealScore($this->ServerName,$this->Network,$date);
            }
            
            return [
                'dates' => $dates,
                'scores' => $scores
            ];
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }

    }

    public function GetDisrupTrend()
    {
        try{
            $dates = [];
            $counts = [];
            for($i=6;$i>=0;$i--){
                $starttime=date('Y-m-d 00:00:00',strtotime("-$i days"));
                $endtime=date('Y-m-d 23:59:59',strtotime("-$i days"));
                $count=Db::table('fa_network_disruption_log')->where('server_name',$this->ServerName)->where('net_name',$this->Network)->where('offline_time','>=',$starttime)->where('offline_time','<=',$endtime)->count();
                $dates[]=date('m/d',strtotime("-$i days"));
                $counts[]=$count; 
            }
            return ['dates'=>$dates,'counts'=>$counts];
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }

    }

    public function GetRevTime()
    {
        try{
            // 只查询一次数据库，获取所有满足条件的记录
            $records = Db::table('fa_network_recovery_log')
                ->where('server_name', $this->ServerName)
                ->where('net_name', $this->Network)
                ->where('recovery_time', '>=', date('Y-m-d 00:00:00', strtotime('-7 days')))
                ->where('recovery_time', '<=', date('Y-m-d 23:59:59'))
                ->column('duration');
            // 如果没有记录，直接返回0
            if (empty($records)) {
                return 0;
            }
            // 手动计算平均值
            $avg_recovery_time = array_sum($records) / count($records);
            
            return round($avg_recovery_time, 2);
        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }

    }

    public function GetRevTimeTrend(){
        try{
            $records = Db::table('fa_network_recovery_log')
                ->where('server_name', $this->ServerName)
                ->where('net_name', $this->Network)
                ->field('offline_time as date,duration')
                ->order('recovery_time DESC')
                ->limit(7)
                ->select();

            return array_map(function($item){
                return [
                    'dates' => $item['date'],
                    'duration' => $item['duration']
                ];
            }, $records ?: []);

        }catch(Exception $e){
            return json_encode(array('code'=>1,'response'=>$e->getMessage()));
        }
    }

    public function SetStatus(){
        $this->status='在线';
    }

    public function SaveInfo()
    {
        $this->Setstatus();
        $params=array('server_name'=>$this->ServerName,'server_ip'=>$this->PubIp,'net_name'=>$this->Network,
                       'net_segment'=>$this->PriIp,'port'=>$this->Port,'username'=>$this->Username,
                    'status'=>$this->status,'online_time'=>$this->OnlineTime,'esbtime'=>$this->CreateTime,'config'=>$this->Config
                     ,'node_cnt'=>$this->NodeCnt,'desc'=>$this->Desc);
        Db::table('fa_net')->insert($params);
    }
}
