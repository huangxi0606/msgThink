<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Machine extends Common
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','机器管理');
		$this->assign('loginname',Session::get('admin.name'));
		$this->assign('statuslable',array('0'=>'在线','1'=>'离线'));
        $this->assign('level',array('0'=>'未定义','1'=>'取设备码','2'=>'回执设备码','3'=>'取账号','4'=>'回执账号','5'=>'取任务','6'=>'回执任务'));
		$this->assign('statuscolor',array('0'=>'aqua','1'=>'danger'));
		$this->assign('statuscolor2',array('0'=>'ffc107','1'=>'dc3545'));
	}
	
    public function index()
    {
		$sort=array('column'=>'id','type'=>'desc','sort'=>false);
		if(input('?_sort')){
			$sort=input('_sort/a');
			$sort['sort']=true;
		}
        $data=Db::table('machine')->order($sort['column'],$sort['type'])->paginate(20);
        $count=Db::table('machine')->count();
        $oncount=Db::table('machine')->where("status",0)->count();
        $outcount=Db::table('machine')->where("status",1)->count();
        if((input('serch'))!=null){
            if(input('serch')==1){
                $data=Db::table('machine')->where('status',1) ->order($sort['column'],$sort['type'])->paginate(20,true);
                $count=$outcount;
            }
            if(input('serch')==0){
//                var_dump("mama");exit;
                $data=Db::table('machine')->where('status',0)->order($sort['column'],$sort['type'])->paginate(20,true);
                $count=$oncount;
            }
        }
		$page = $data->render();
        $this->assign('data',$data);
        $this->assign('oncount',$oncount);
		$this->assign('outcount',$outcount);
		$this->assign('page',page_tp($page,$sort));
		$this->assign('count',$count);
		$this->assign('_sort',$sort);
		$tplname='admin/machine';
		if(input('?_pjax')){
			$tplname.='_list';
		}
        return $this->fetch($tplname);
    }



}
