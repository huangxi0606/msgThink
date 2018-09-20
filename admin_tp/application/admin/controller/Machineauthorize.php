<?php
namespace app\admin\controller;

use think\Controller;
use think\Session;
use think\Config;

class Machineauthorize extends Controller
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','设备授权');
		$this->assign('loginname',Session::get('admin.name'));
	}
    public function index()
    {
		$opendevicelist = opendevicelist('');
		$this->assign('opendevicelist',$opendevicelist);
		$this->assign('openmachine',openmachine(''));
        return $this->fetch('admin/machineauthorize');
    }
	public function openmachine(){
		$data=input();
		if(input('?enable')){
			if($data['enable']=='on'){
				$data['status']=1;
			}else{
				$data['status']=2;
			}
			openmachine($data['status']);
		}
		echo $data['status'];
	}
	
	public function save()
    {
		$data=input();
		if(input('?opendevicelist')){
			opendevicelist($data['opendevicelist']);
			$this->success('操作成功', '/admin/machineauthorize');
		}else{
			$this->error('操作失败');
		}
    }
}
