<?php
namespace addons\logmanage;

use app\common\library\Menu;
use think\Addons;

class Logmanage extends Addons{

    //安装插件
    public function install(){
        $menu = [
                    [
                        'name' =>'logmanage/logoperations',
                        'title' =>'操作日志管理',
                        'icon' => 'fa fa-th-large',
                        'ismenu' => 1,
                    ]
        ];

        Menu::create($menu);

    }
    //卸载插件
    public function uninstall(){
         Menu::delete('logmanage/logoperations');
    }
    //禁用插件
    public function disable(){
        Menu::disable('logmanage/logoperations');
    }
    //启动插件
    public function enable(){
        Menu::enable('logmanage/logoperations');
    }

}

