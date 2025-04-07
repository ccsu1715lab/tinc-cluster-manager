<?php
namespace addons\trafficmonitor;

use app\common\library\Menu;
use think\Addons;

class Trafficmonitor extends Addons{

    //安装插件
    public function install(){
        $menu=[
            [
                'name' =>'trafficmonitor',
                'title' =>'流量监控',
                'icon' => 'fa fa-feed',
                'ismenu' => 1,
            ]
        ];

        Menu::create($menu);

    }
    //卸载插件
    public function uninstall(){
        Menu::delete('trafficmonitor');
    }
    //禁用插件
    public function disable(){
        Menu::disable('trafficmonitor');
    }
    //启动插件
    public function enable(){
        Menu::enable('trafficmonitor');
    }



}

