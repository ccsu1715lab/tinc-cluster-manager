<?php
namespace app\admin\controller;
use think\Db;
use app\common\controller\Backend;
use think\Controller;
use think\Request;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use fast\Random;
use fast\Tree;
use think\Validate;

class Permission extends Backend
{
    protected $model = null;
    protected $rulemodel = null;
    protected $groupmodel = null;
    protected $selectpageFields = 'id,username,nickname,avatar';
    protected $searchFields = 'id,username,nickname';
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    //当前组别列表数据
    protected $grouplist = [];
    protected $groupdata = [];
    //无需要权限判断的方法
    protected $noNeedRight = ['roletree'];

    protected $rulelist = [];
    protected $multiFields = 'ismenu,status';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');
        $this->childrenAdminIds = $this->auth->getChildrenAdminIds($this->auth->isSuperAdmin());
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin());

        $this->groupmodel = model('AuthGroup');
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupList = [];
        if ($this->auth->isSuperAdmin()) {
            $groupList = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        } else {
            $groups = $this->auth->getGroups();
            $groupIds = [];
            foreach ($groups as $m => $n) {
                if (in_array($n['id'], $groupIds) || in_array($n['pid'], $groupIds)) {
                    continue;
                }
                $groupList = array_merge($groupList, Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['pid'])));
                foreach ($groupList as $index => $item) {
                    $groupIds[] = $item['id'];
                }
            }
        }
        $groupName = [];
        foreach ($groupList as $k => $v) {
            $groupName[$v['id']] = $v['name'];
        }

        $this->grouplist = $groupList;
        $this->groupdata = $groupName;
        $this->assignconfig("admin", ['id' => $this->auth->id, 'group_ids' => $this->auth->getGroupIds()]);

        $this->view->assign('groupdata', $this->groupdata);

        if (!$this->auth->isSuperAdmin()) {
            $this->error(__('Access is allowed only to the super management group'));
        }
        $this->rulemodel = model('AuthRule');
        // 必须将结果集转换为数组
        $ruleList = \think\Db::name("auth_rule")->field('type,condition,remark,createtime,updatetime', true)->order('weigh DESC,id ASC')->select();
        foreach ($ruleList as $k => &$v) {
            $v['title'] = __($v['title']);
        }
        unset($v);
        Tree::instance()->init($ruleList);
        $this->rulelist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0), 'title');
        $ruledata = [0 => __('None')];
        foreach ($this->rulelist as $k => &$v) {
            if (!$v['ismenu']) {
                continue;
            }
            $ruledata[$v['id']] = $v['title'];
            unset($v['spacer']);
        }
        unset($v);

        $this->view->assign('ruledata', $ruledata);
        $this->view->assign("menutypeList", $this->rulemodel->getMenutypeList());
        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $childrenGroupIds = $this->childrenGroupIds;
            $groupName = AuthGroup::where('id', 'in', $childrenGroupIds)
                ->column('id,name');
            $authGroupList = AuthGroupAccess::where('group_id', 'in', $childrenGroupIds)
                ->field('uid,group_id')
                ->select();

            $adminGroupName = [];
            foreach ($authGroupList as $k => $v) {
                if (isset($groupName[$v['group_id']])) {
                    $adminGroupName[$v['uid']][$v['group_id']] = $groupName[$v['group_id']];
                }
            }
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $adminGroupName[$this->auth->id][$n['id']] = $n['name'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->where($where)
                ->where('id', 'in', $this->childrenAdminIds)
                ->field(['password', 'salt', 'token'], true)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $k => &$v) {
                $groups = isset($adminGroupName[$v['id']]) ? $adminGroupName[$v['id']] : [];
                $v['groups'] = implode(',', array_keys($groups));
                $v['groups_text'] = implode(',', array_values($groups));
            }
            unset($v);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a", [], 'strip_tags');//获取用户提交的前端表单数据
            if ($params) {
                if (!$params['pid']) {
                    $this->error(__('The non-menu rule must have parent'));
                }
                $name = $this->rulemodel->where('id', $params['pid'])->value('name');//查找fa_auth_rule表的父级的name字段值
                foreach ($params as $k => $v) { //遍历得到的前端表单数据
                    if ($v == 'on') {
                        if ($k == 'add') {
                            $temp = array('ismenu' => 0, 'pid' => $params['pid'], 'name' => $name . "/add", 'title' => "添加", 'icon' => $params['icon'], 'status' => "normal");
                        }
                        else if ($k == 'del') {
                            $temp = array('ismenu' => 0, 'pid' => $params['pid'], 'name' => $name . "/del", 'title' => "删除", 'icon' => $params['icon'], 'status' => "normal");
                        }
                        else if ($k == 'edit') {
                            $temp = array('ismenu' => 0, 'pid' => $params['pid'], 'name' => $name . "/edit", 'title' => "修改", 'icon' => $params['icon'], 'status' => "normal");
                        }
                        else {
                            $temp = array('ismenu' => 0, 'pid' => $params['pid'], 'name' => $name . "/index", 'title' => "查看", 'icon' => $params['icon'], 'status' => "normal");
                        }
                        $result = $this->rulemodel->insert($temp);//将temp数组的键对应fa_auth_rule表的字段，再存入值
                        if ($result === false) {
                            $this->error($this->rulemodel->getError());
                        }
                    }
                }
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }
}