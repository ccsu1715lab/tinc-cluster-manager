<?php

namespace addons\tincui;

use app\common\library\Menu;
use think\Addons;

class Tincui extends Addons
{
    //安装插件
    public function install(){
        $menu = include ADDON_PATH . 'tincui' . DS . 'data' . DS . 'menu.php';
        Menu::create($menu);

}
   //卸载插件 
    public function uninstall(){
    Menu::delete('tincui/singlenwdata');
    Menu::delete('tincui/nwdata');
    Menu::delete('tincui/netmanagement');
    Menu::delete('tincui/nodemanagement');
    Menu::delete('tincui/events');
    Menu::delete('tincui/servermanagement');
}
     //禁用插件
    public function disable(){
        Menu::disable('tincui/singlenwdata');
        Menu::disable('tincui/nwdata');
        Menu::disable('tincui/netmanagement');
        Menu::disable('tincui/nodemanagement');
        Menu::disable('tincui/events');
        Menu::disable('tincui/servermanagement');
}
    //启动插件
    public function enable(){
        Menu::enable('tincui/singlenwdata');
        Menu::enable('tincui/nwdata');
        Menu::enable('tincui/netmanagement');
        Menu::enable('tincui/nodemanagement');
        Menu::enable('tincui/events');
        Menu::enable('tincui/servermanagement');
}



}