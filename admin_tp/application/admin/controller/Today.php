<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;


class Today extends Common
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','统计管理');
		$this->assign('loginname',Session::get('admin.name'));
    }
	
    public function index()
    {
		$sort=array('column'=>'id','type'=>'desc','sort'=>false);
		if(input('?_sort')){
			$sort=input('_sort/a');
			$sort['sort']=true;
		}
		$data=Db::table('today')->order($sort['column'],$sort['type'])->paginate(20);
		$count=Db::table('today')->count();
		$page = $data->render();
		$this->assign('data',$data);
		$this->assign('page',page_tp($page,$sort));
		$this->assign('count',$count);
		$this->assign('_sort',$sort);
		$tplname='admin/today';
		if(input('?_pjax')){
			$tplname.='_list';
		}
        return $this->fetch($tplname);
    }
    public function today()
    {
        $data =getToday();
        $this->assign('data',$data);
        $tplname='admin/today';
            $tplname.='_show';
        return $this->fetch($tplname);
    }





}
