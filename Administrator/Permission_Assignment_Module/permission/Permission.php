<?php
namespace addons\permission;

use app\common\library\Menu;
use think\Addons;

class Permission extends Addons{

    //安装插件
    public function install(){
        $menu=[
            [
                'name' =>'permission',
                'title' =>'菜单规则管理',
                'icon' => 'fa fa-bars',
                'sublist' => [
                    ['name' => 'permission/index', 'title' => '查看'],
                    ['name' => 'permission/add', 'title' => '添加'],
                ]
            ]
        ];

        Menu::create($menu);
        return true;
    }
    //卸载插件
    public function uninstall(){
        Menu::delete('permission');
        return true;
    }
    //禁用插件
    public function disable(){
        return true;
    }
    //启动插件
    public function enable(){
        return true;
    }


}

