<?php
namespace app\admin\controller;

use CFPropertyList\CFPropertyList;
use think\Controller;
use think\Session;
use think\Config;

class Sysconfig extends Controller
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','系统设置');
		$this->assign('loginname',Session::get('admin.name'));
	}
    public function index()
    {
        return $this->fetch('admin/Config');
    }
	public function save()
    {
		$data=input('config/a');
		if($data['user']==''  && $data['pass']=='' && intval($data['pass'])<1){
			$this->error('帐号密码不能为空，量级最小为1。');
		}

		setconfig(array('user','pass','msgtask_sendnum'),array($data['user'],$data['pass'],$data['msgtask_sendnum']));
		config($data,'app');

		//config($data);
		//config('user',$data['user'],'app');
		//config('pass',$data['pass'],'app');
		
		//Config::set('msgtask_sendnum',$data['msgtask_sendnum']);
		
        $this->success('操作成功', '/admin/sysconfig');
    }

//    public function plist(){
//        $file = "./message.plist";
//        $plist = new \CFPropertyList\CFPropertyList( $file, \CFPropertyList\CFPropertyList::FORMAT_BINARY );
////            var_dump($plist -> toArray());
//        print_r($plist->toArray());
//    }

}
