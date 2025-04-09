<?php
namespace app\admin\controller\tincui;
use think\Db;
use app\common\controller\Backend;

class Events extends Backend
{
    protected $model = null;
    private $model_path = "app\admin\model\\tincui\Event";
    protected $noNeedRight=[
        'selectPage'
    ];
    public function __construct()
    {
        parent::__construct();   
        $this->model=model($this->model_path);
    }


    public function index()
    {
        if($this->request->isAjax())
        {
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model->where($where)->order($sort, $order)->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
        }
        return $this->fetch('index');
    }

    

}