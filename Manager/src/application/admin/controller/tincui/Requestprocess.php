<?php
namespace app\admin\controller\tincui;
use app\common\controller\Backend;
use app\admin\library\Auth;
use think\Db;
/**
 * 类名：Auxi
 * 功能：为节点管理和内网管理提供辅助接口，包括验证
 * 
*/
class Requestprocess extends Backend{
    private $netmodel = null;
    private $nodemodel = null;
    protected $user_flag = null;
    private $table_event = "fa_event";
    private $table_nodeonline = "fa_nodeonline";
    private $view_allserver="fa_allserver";
    private $view_allnetinserver="fa_netinserver";
    public function __construct()
    {
        //继承父类
        parent::__construct();
        //实例化模型
        $this->netmodel = model("app\admin\model\\tincui\Net");
        $this->nodemodel = model("app\admin\model\\tincui\Node");
        $auth = Auth::instance();
        $this->user_flag = $auth->username; 
    }
    /**
     * 控制器名：IsNetRepeat
     * 功能：表单验证，判断内网名是否已被占用
     * 输入：服务器名，内网名
     * 输出：true or false
    */
   public function IsNetRepeat()
   {
    if($this->request->isPost())
    {
        $servername = $this->request->post('servername');
        $netname = $this->request->post('netname');
        if($servername==null||$netname==null)return null;
         $hasnet=$this->netmodel->where('server_name',$servername)->where('net_name',$netname)->find();
        if($hasnet==null)
        {
           $this->success();
        }
         $this->error($servername."已存在".$netname);   
    }
    else 
    {
        return "警告！非法请求";
    }
    
   
   }

       /**
     * 控制器名：IsPortRepeat
     * 功能：判断端口是否已被占用
     * 输入：服务器名，端口号
     * 输出：true or false
    */
    public function IsPortRepeat($serverip,$port)
    {
      $hasport=$this->netmodel->where('server_ip',$serverip)->where('port',$port)->find();
      if($hasport==null)
      {
         return false;
      }
      return true;     
    }

        /**
     * 控制器名：IsNetSeg
     * 功能：判断内网名是否已被占用
     * 输入：服务器名，网段
     * 输出：true or false
    */
   public function IsSegRepeat($serverip,$netseg)
   {
     $hasseg=$this->netmodel->where('server_ip',$serverip)->where('net_segment',$netseg)->find();
     if($hasseg==null)
     {
        return false;
     }
     return true;     
   }

   /**
    * 控制器名：GetNetsegmentPortByServername
    * 功能：获取服务器所有的端口和网段
    * 返回：json数据
   */
   public function GetNetsegmentPortByServername()
   {
    if($this->request->isPost())
    {
        $obj = new segmentandport();
        $servername = $this->request->post('servername');
        if($servername==null)
        {
            return "servername cannot be null";
        }
        $obj->seg=Db::table('fa_net_segment')->where('server_name',$servername)->where('attribution','none')->column('net_segment');
        $obj->port=Db::table('fa_port')->where('server_name',$servername)->where('attribution','none')->column('port');
        return $obj;
    }
    else
    {
        return "警告：非法请求！";
    }
   }

   /**
    * 控制器名：set_waiting
    * 功能：更行数据表字段updatestate为waiting状态
    * 返回：更行行数
   */

   public function set_waiting()
   {
    if($this->request->isPost())
    {
        $ids=$this->request->post('ids');
        if($ids==null)return 0;
        return $this->nodemodel->where('id',$ids)->update(['editsuccess'=>"waiting"]);
    }
   }


   /**
    * 控制器名：GetnetnameByServer
    * 获取服务器上的内网
    * 返回：json字符串
   */
  public function GetnetnameByServername()
  {
    if($this->request->isPost())
    {
        $servername=$this->request->post('servername');
        if($servername == null)return null;
        $seg=$this->netmodel->where('server_name',$servername)->where('user_flag',$this->user_flag)->column('net_name');
        return $seg;
    }
  }

     /**
    * 控制器名：GetSegmentByNet
    * 获取内网网段
    * 返回：网段
   */
  public function GetSegmentByNet()
  {
    if($this->request->isPost())
    {
        $servername=$this->request->post('servername');
        $netname = $this->request->post('netname');
        if($servername == null||$netname==null)return null;
        $netsadded=$this->netmodel->where('server_name',$servername)->where('net_name',$netname)->value('net_segment');
        return json($netsadded);
    }
  }

  /**
   * 控制器名：IsipRepeat
   * 功能：判断ip是否被占用
   * 返回：true or false
  */
  public function IsipRepeat()
  {
    if($this->request->isPost())
    {
        $servername=$this->request->post('servername');
        $nodeip=$this->request->post('nodeip');
        if($servername==null||$nodeip==null)
        {
            $this->error("Internal Server error：params is null");
        }
        $hasip = $this->nodemodel->where('server_name',$servername)->where('node_ip',$nodeip)->find();
        if($hasip!=null)
        {
            $this->error($servername.'上此ip已经被使用,请换一个');
        }
        else
        {
            $this->success();
        }
    }
  }

  /**
   * 控制器名：IsnodenameRepeat
   * 功能：判断一个内网下的节点名是否已被占用
   * 返回：true or false
  */
  public function IsnodenameRepeat()
  {
    if($this->request->isPost())
    {
        $nodename=$this->request->post('nodename');
        $servername=$this->request->post('servername');
        $netname=$this->request->post('netname');
        if($nodename==null||$servername==null||$netname==null)
        {
            $this->error("Internal Server Error：params is null");
        }
        $hasnode=$this->nodemodel->where('server_name',$servername)->where('net_name',$netname)->where('node_name',$nodename)->find();
        if($hasnode!=null)
        {
            $this->error($servername.'/'.$netname.'：此节点已经被使用，请换一个');
        }
        else
        {
            $this->success();
        }
    }
  }

  /**
   * 控制器名：sidmatchpass
   * 功能：判断sid和密码是否匹配
   * 返回：true or false
  */
  public function sidmatchpass()
  {
    if($this->request->isPost())
    {
        $sid=$this->request->post('sid');
        $rowpass=$this->request->post('rowpass');
        if($sid==null||$rowpass==null)
        {
            $this->error("Internal Server Error：params is null");
        }
        $result=$this->nodemodel->where('sid',$sid)->where('password',$rowpass)->find();                                         
        if($result!=null)
        {
            $this->success();
        }
        else
        {
            $this->error('请检查设备名与密码是否匹配');
        }
    }
  }

  /**
   * 控制器名：IsupdateSuccess
   * 功能：判断节点的更更新是否成功
   * 返回：节点更新状态
  */
  public function IsupdateSuccess()
  {
    if($this->request->isPost())
    {
        $ids=$this->request->post('ids');
        if($ids==null)return "error：ids is null";
        $ifsuccess=$this->nodemodel->where('id',$ids)->value('editsuccess');
        return $ifsuccess;
    }
  }

  /**
   * 控制器名：EventQuery
   * 功能：查询事件更新
   * 返回：null or data
  */
  public function EventQuery()
  {
    if($this->request->isPost())
    {
        $events=Db::table($this->table_event)->where('ifqueried',"no")->where('username',$this->user_flag)->select();
        //将已查询的事件标记为已查询
        if($events!=null)
        {
        foreach($events as $item)
        {
            Db::table($this->table_event)->where('id',$item['id'])->update(['ifqueried'=>"yes"]);
        }
        }
        return $events;
    }
  }

    /**
   * 控制器名：GetOnlinenodedata
   * 功能：获取指定内网的在线节点数据
   * 返回：null or data
  */
  public function GetOnlinenodedata()
  {
    if($this->request->isPost())
    {
        $nodeonlinedata=null;
        $servername = $this->request->post('servername');
        $netname = $this->request->post('netname');
        if($servername==null||$netname==null)
        {
            return 'Internal Service Error：params cannot be null';
        }
        else if($servername=='all')
        {
            //所有接入服务器的节点在线状况
            $nodeonlinedata=Db::table($this->view_allserver)->column('cntonline');
        }
        else if($servername!='all'&&$netname=='all')
        {
            //某台接入服务器上所有内网下的节点在线状况
            $nodeonlinedata=Db::table($this->view_allnetinserver)->where('server_name',$servername)->column('cntonline');
        }
        else
        {
            $nodeonlinedata = Db::table($this->table_nodeonline)->where('server_name',$servername)->where('net_name',$netname)->column('cntonline');
        }


        return $nodeonlinedata;
       
    }
  }

  /**
   * 函数名：updatenodeonlinestatus
   * 属性：无
   * 功能：更新节点在线信息
   */
  public function updatenodeonlinestatus()
  {
    $hour = date("H");
    $hour = intval($hour);
    $list = $this->netmodel->where('user_flag',$this->user_flag)->select();//遍历所有内网
    if (count($list) == 0) return "暂无内网数据";
    else
    {
          if ($hour  == 0)
         {
              foreach ($list as $item)
              {
                   $server_name = $item['server_name'];
                   $net_name = $item['net_name'];
                   if($server_name==null||$net_name==null)return "error:params cannot be null";
                   Db::table('fa_nodeonline')->where('server_name', $server_name)->where('net_name', $net_name)->update(['cntonline' => 0]);
              }
         }
       foreach ($list as $item)//遍历每个内网下的所有节点
       {
                $server_name = $item['server_name'];
                $net_name = $item['net_name'];
                if($server_name==null||$net_name==null)return "error:params cannot be null";
                $houronlineall = 0;
                $houronlineall  += $this->nodemodel->where('server_name', $server_name)->where('net_name', $net_name)->where('status', '已上线')->count();//当前内网在线节点数
                $row = Db::table('fa_nodeonline')->where('server_name', $server_name)->where('net_name', $net_name)->where('timepoint', $hour)->update(['cntonline' => $houronlineall]);
                //return '当前内网在线节点数: '.$houronlineall;
      }
  }
  }
}
/**
 * 类名：segmentandport
 * 属性：seg：网段，port：端口
*/
class segmentandport{
    public $seg;
    public $port;

}