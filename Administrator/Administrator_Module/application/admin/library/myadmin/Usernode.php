<?php
namespace app\admin\library\myadmin;
use think\Db;

class Usernode
{

    protected $all_user=null;
    protected $chars= array("a","b","c","d","e","f","g","h","i","j","k","l","m","n",
        "o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9");

    public function __construct(){
        $this->all_user=Db::table('fa_node')->select();
    }

    public function show_user(){
        $all_user=$this->all_user;
        foreach($all_user as $k1=>$v1)
        {
            unset($all_user[$k1]['password']);
        }
        return $all_user;

    }

    public function get_sid(){
        $chars=$this->chars;
        $charslen=count($chars)-1;
        shuffle($chars);
        $output="";
        for($i=0;$i<8;$i++){
            $output .=$chars[mt_rand(0,$charslen)];
        }
        return $output;

    }
   
}



?>