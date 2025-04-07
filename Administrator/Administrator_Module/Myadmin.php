<?php
namespace addons\myadmin;

use app\common\library\Menu;
use think\Addons;

class Myadmin extends Addons{

    //安装插件
    public function install(){
        $menu = [

                      [
                        'name' =>'myadmin/serve',
                        'title' =>'服务器管理',
                        'icon'   => 'fa fa-th-large',
                        'ismenu' => 1,
                    ],
           ];

        Menu::create($menu);

    }
    //卸载插件
    public function uninstall(){
        Menu::delete('myadmin/serve');
    }
    //禁用插件
    public function disable(){
        Menu::disable('myadmin/serve');
    }
    //启动插件
    public function enable(){
        Menu::enable('myadmin/serve');
    }

}

