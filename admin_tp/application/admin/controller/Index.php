<?php
namespace app\admin\controller;

use think\Controller;
use think\Session;

class Index extends Controller
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
	}
    public function index()
    {
		$this->redirect('/admin/SysConfig/');
        //return $this->fetch('admin/DeviceList');
    }
}
