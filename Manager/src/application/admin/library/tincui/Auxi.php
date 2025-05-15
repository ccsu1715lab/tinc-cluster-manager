<?php
namespace app\admin\library\tincui;
use think\Db;
use think\Exception;
/**
 * 类名：Auxi
 * 功能：为节点管理和内网管理提供辅助接口，包括验证
 * 
*/
class Auxi{
    private $netmodel = null;
    private $nodemodel = null;
    private $table_log = "fa_log_operations";
    public function __construct()
    {
        //实例化模型
        $this->netmodel = model("app\admin\model\\tincui\Net");
        $this->nodemodel = model("app\admin\model\\tincui\Node"); 
    }
    /**
     * 控制器名：IsNetRepeat
     * 功能：判断内网名是否已被占用
     * 输入：服务器名，内网名
     * 输出：true or false
    */
   public function IsNetRepeat($servername,$netname)
   {
     $hasnet=$this->netmodel->where('server_name',$servername)->where('net_name',$netname)->find();
     if($hasnet==null)
     {
        return false;
     }
     return true;     
   }

   public function save_netinfo($params){
    if($params==null)
    {
        return false;
    }
    $this->netmodel->insert($params);
    return true;
   }

       /**
     * 控制器名：IsPortRepeat
     * 功能：判断端口是否已被占用
     * 输入：服务器名，端口号
     * 输出：true or false
    */
    public function IsPortRepeat($servername,$port)
    {
      $hasport=$this->netmodel->where('server_name',$servername)->where('port',$port)->find();
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
   public function IsSegRepeat($servername,$netseg)
   {
     $hasseg=$this->netmodel->where('server_name',$servername)->where('net_segment',$netseg)->find();
     if($hasseg==null)
     {
        return false;
     }
     return true;     
   }

        /**
     * 控制器名：IsNodeRepeat
     * 功能：判断节点名是否已被占用
     * 输入：服务器名，内网名,节点名
     * 输出：true or false
    */
    public function IsNodeRepeat($servername,$netname,$nodename)
    {
        if($servername==null||$netname==null||$nodename==null)
        {
            return true;
        }
        else
        {
            $result = $this->nodemodel->where("server_name",$servername)->where("net_name",$netname)->where("node_name",$nodename)->find();
            if($result == null)return false;
            return true;
        }
   
    }


    /**
     * 控制器名：del_node
     * 功能：删除节点数据
     * 返回：删除行数
    */

    public function del_node($list)
    {
        if($list==null)return 0; 
        $count = 0;
        Db::startTrans();
        try 
        {
            foreach ($list as $item) 
            {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) 
        {
            Db::rollback();
            $this->error($e->getMessage());
        }
        return $count;
                        
                           

    }

      /**
     * 控制器名：insert_nodeonlinedatainnet
     * 功能：插入内网的在线节点数据
     * 返回：true or false
    */
    public function insert_nodeonlinedatainnet($servername,$netname)
    {
        $data = array();         
        $data["server_name"]=$servername;
        $data["net_name"]=$netname;
        if($servername == null || $netname == null)
        {
            return false;
        }
        for($i=0;$i<24;$i++)
        {
            $timepoint = $i;
            $data["timepoint"]=$timepoint;
            $data["cntonline"]=0;
            Db::table('fa_nodeonline')->insert($data);
        }
        return true;
    }

    public function del_nodeonlinedatainnet($servername,$netname)
    {
        Db::table('fa_nodeonline')->where('server_name',$servername)->where('net_name',$netname)->delete();
    }



     /**
     * 控制器名：occupyport
     * 功能：标记端口为占用
     * 输入：servername:接入服务器，netname:内网名，port:端口
     * 输出：true or false
    */

   public function occupyport($servername,$netname,$port)
   {
    if($servername==null||$netname==null||$port==null)
    {
        return false;
    }
    $temp=Db::table('fa_port')->where('server_name',$servername)->where('port',$port)->update(['attribution'=>$servername.'.'.$netname]);
    if($temp==0)return false;
    return true;
   }

   /**
     * 控制器名：occupseg
     * 功能：标记网段为占用
     * 输入：servername:接入服务器，netname:内网名，seg:网段
     * 输出：true or false
    */
   public function occupyseg($servername,$netname,$seg)
   {
    if($servername==null||$netname==null||$seg==null)
    {
        return false;
    }
    $temp=Db::table('fa_net_segment')->where('server_name',$servername)->where('net_segment',$seg)->update(['attribution'=>$servername.'.'.$netname]);
    if($temp==0)return false;
    return true;
   }

    /**
     * 控制器名：reliseport
     * 功能：释放端口
     * 输入：servername:接入服务器，port:端口
     * 输出：true or false
    */
    public function realiseport($servername,$port)
    {
    if($servername==null||$port==null)
    {
        return false;
    }    
    $temp=Db::table('fa_port')->where('server_name',$servername)->where('port',$port)->update(['attribution'=>'none']);
    if($temp==0)return false;
    return true;
    }
    /**
     * 控制器名：relisesegment
     * 功能：释放网段
     * 输入：servername:接入服务器，seg：网段
     * 输出：true or false
    */
    public function realisesegment($servername,$seg)
    {
    if($servername==null||$seg==null)
    {
        return false;
    }    
    $temp=Db::table('fa_net_segment')->where('server_name',$servername)->where('net_segment',$seg)->update(['attribution'=>'none']);
    if($temp==0)return false;
    return true;
    }


    /**
     * 控制器名：IsSameNetInServer
     * 功能：判断内网是否是同一个服务器上的
     * 输入：list:内网列表
     * 输出：true or false
    */
    public function IsSameNetInServer($list)
    {
          $flag=true;//默认是同一个服务器的内网
          for($i=1;$i<count($list);$i++)
          {
           if($list[$i]['server_name']!=$list[0]['server_name'])
           {
               $flag=false;
               break;
           }
          }
          return $flag;
    }

        /**
     * 控制器名：IsSameNodeInNet
     * 功能：判断节点是否是同一个内网下的
     * 输入：list:内网列表
     * 输出：true or false
    */
    public function IsSameNodeInNet($list)
    {
          $flag=true;//默认是同一个内网下的节点
          for($i=1;$i<count($list);$i++)
          {
           if($list[$i]['server_name']!=$list[0]['server_name']||$list[$i]['net_name']!=$list[0]['net_name'])
           {
               $flag=false;
               break;
           }
          }
          return $flag;
    }


   
    /**
     * 控制器名：DelAllNodeInNet
     * 功能：删除一个内网下的所有节点
     * 输入：servername,netname
     * 输出：int
    */
 
    public function DelAllNodeInNet($servername,$netname)
    {
        if($servername==null||$netname==null)
        {
            return 0;
        }
        $count=$this->nodemodel->where('server_name',$servername)->where('net_name',$netname)->delete();
        return $count;
    }
      /**
     * 控制器名：GetServeripByServername
     * 功能：获取服务器的ip
     * 输入：servername：服务器名
     * 返回：服务器ip
    */
    public function GetServeripByServername($servername)
    {
        if($servername==null)return null;
        return Db::table('fa_server')->where('server_name',$servername)->value('server_ip');
        
    }

          /**
     * 控制器名：GetSegOnNet
     * 功能：获取内网网段
     * 输入：servername：服务器名，netname：内网名
     * 返回：内网网段
    */
    public function GetSegOnNet($servername,$netname)
    {
        if($servername==null||$netname==null)return false;
        $result = $this->netmodel->where('server_name',$servername)->where('net_name',$netname)->find();
        if($result==null)return false;
        return $result['net_segment'];      
    }

    /**
     * 控制器名：generateRandomString($length)
     * 功能：生成长度为lenth的随机字符串
     * 输入：lenth:长度
     * 返回：随机字符串
    */
    public function generateRandomString($length)
     {
        if($length<=0)
        {
            return null;
        }
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return substr(sha1($string), 0, $length);
     }

     /**
      * 控制器名：changenodecntincnt
      * 功能：改变内网节点数据
      * 返回：true or false
     */
    public function changenodecntinnet($servername,$netname,$var_cnt)
    {
        if($servername==null||$netname==null)return false;
        $cur_cnt = $this->netmodel->where('server_name',$servername)->where('net_name',$netname)->value('node_cnt');
        if($cur_cnt>=0)
        {
            $updated_cnt = $cur_cnt + $var_cnt;
            if($updated_cnt<0)return false;
            if(($this->netmodel->where('server_name',$servername)->where('net_name',$netname)->update(['node_cnt'=>$updated_cnt]))!=0)
            {
                return true;
            }
            return false;

        }


    }

    /**
      * 控制器名：preserve_temp_nodeinfo
      * 功能：临时储存节点信息
      * 返回：true or false
     */
    public function preserve_temp_nodeinfo($ids)
    {
        if($ids==null)
        {
            return false;
        }
        $filename=ADDON_PATH . 'tincui' . DS . 'data' . DS . 'temp.txt';
        $stock_info=$this->nodemodel->where('id',$ids)->find();
        $str="{\""."password"."\":"."\"".$stock_info['password']."\"".","."\""."node_name"."\":"."\"".$stock_info['node_name']."\"".","."\""."node_ip"."\":"."\"".$stock_info['node_ip']."\""."}";
        file_put_contents($filename,$str);
        return true;

    }


    /**
     * 控制器名：save_configinfo
     * 功能：将内网的配置信息保存到数据库
     * 参数：服务器名：内网名
     * 返回，影响的行数
     */
    public function save_netconfiginfo($servername,$netname,$configinfo)
    {
        if($servername==null||$netname==null)
        {
            return 0;
        }
        else
        {
            $row=$this->netmodel->where('server_name',$servername)->where('net_name',$netname)->update(['config'=>$configinfo]);
            return $row;
        }
    }

    public function GetAllnodeUnderNet($ids)
    {
        if($ids==null)
        {
            return false;
        }
        $netdata = $this->netmodel->where('id',$ids)->find();
        if($netdata==null)
        {
            return false;
        }
        $servername = $netdata['server_name'];
        $netname = $netdata['net_name'];
        if($servername==null||$netname==null)
        {
            return false;
        }
        $Allnodes = $this->nodemodel->where('server_name',$servername)->where('net_name',$netname)->select();
        if(count($Allnodes)==0)
        {
            return null;
        }
        else
        {
            return $Allnodes;

        }
    }

    public function IPv4_validation($ip)
    {
        $arr = explode(".",$ip);
        if(is_array($arr))
        {
            if(count($arr)!=4)
            {
                return false;
            }
            else
            {
                if(intval($arr[3])==1) return false;
                for($i=0;$i<count($arr);$i++)
                {
                    if(!is_numeric(intval($arr[$i]))||intval($arr[$i])>255||intval($arr[$i])<0||$arr[$i][0]=='0'&&strlen($arr[$i])!=1)
                    {
                        return false;
                    }
                }
                return true;

            }
        }
        else
        {
            return false;
        }
        
    }

    public function Segment_validation($seg)
    {
        $arr = explode(".",$seg);
        if(is_array($arr))
        {
            if(count($arr)!=3)
            {
                return false;
            }
            else
            {
                for($i=0;$i<count($arr);$i++)
                {
                    if(!is_numeric(intval($arr[$i]))||intval($arr[$i])>255||intval($arr[$i])<0||$arr[$i][0]=='0'&&strlen($arr[$i])!=1)
                    {
                        return false;
                    }
                }
                return true;

            }
        }
        else
        {
            return false;
        }
    }

    /**
     * 控制器名：isipccupied
     * 功能：判断ip是否被占用
     * 返回：true or false
     */
    public function isipoccupied($ip)
    {
        if($ip==null)
        {
            return true;
        }
        else
        {
            $result = $this->nodemodel->where('node_ip',$ip)->find();
            if($result==null) return false;
            return true;

        }
    }

       /**
     * 控制器名：ip_location_validation
     * 功能：判断ip是否是所选择的内网下的ip
     * 返回：true or false
     */
    public function ip_location_validation($ip,$seg)
    {
        if($ip==null||$seg==null)
        {
            return false;
        }
        else
        {
            $ip_arr = explode(".",$ip);
            $seg_arr = explode(".",$seg);
    
            if(is_array($ip_arr)&&is_array($seg_arr)&&count($ip_arr)==4&&count($seg_arr)==3)
            {
                for($i=0;$i<3;$i++)
                {
                    if($ip_arr[$i]!=$seg_arr[$i])
                    {
                        return false;
                    }

                }
                return true;
            }
            else
            {
                return false;
            }


        }
    }

    /**
     * 控制器名：log_operation
     * 功能：添加操作日志
     * 返回：无
     */
    public function log_operation($log)
    {
        try{
            if($log==null||!is_array($log))
            {
                return "Invalid data log：array required!";
            }
            Db::table($this->table_log)->insert($log);
        }catch(Exception $e)
        {
           throw new Exception($e->getMessage());
        }
     

    
    }





}