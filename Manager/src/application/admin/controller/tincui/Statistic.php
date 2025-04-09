<?php
namespace app\admin\controller\tincui;
use think\Db;
use app\admin\library\Auth;
use app\common\controller\Backend;

class Statistic extends Backend
{
    protected $allnet = null;
    protected $netonline = null;
    protected $allnode = null;
    protected $nodeonline = null;
    protected $netmodel = null;
    protected $nodemodel = null;
    protected $netdata = null;
    private $user_flag = null;
    private $table_server = "fa_server";
    public function __construct(){
        parent::__construct();   
        //用户基本信息
        $auth=Auth::instance();
        $this->username=$auth->username;
        $this->user_flag = $auth->username;
        $this->netmodel=model("app\admin\model\\tincui\Net");
        $this->nodemodel=model("app\admin\model\\tincui\Node");
        $this->allnet=$this->netmodel->where('user_flag',$this->user_flag)->count();
        $this->netonline=$this->netmodel->where('user_flag',$this->user_flag)->where('status','正常运行中')->count();
        $this->allnode=$this->nodemodel->where('user_flag',$this->user_flag)->count();
        $this->nodeonline=$this->nodemodel->where('user_flag',$this->user_flag)->where('status',"已上线")->count();
        $this->netdata=$this->netmodel->where('user_flag',$this->user_flag)->select();

    }
    public function index(){
        $this->assign('allnet',$this->allnet);
        $this->assign('netonline',$this->netonline);
        $this->assign('allnode',$this->allnode);
        $this->assign('nodeonline',$this->nodeonline);
        $this->assign('netdata',$this->netdata);
        $servers_added = Db::table($this->table_server)->select();
        $this->assign('servers',$servers_added);
        return $this->view->fetch();


        
    }

    

}