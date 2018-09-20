<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Login extends Controller
{
    public function index()
    {
        return $this->fetch('admin/login');
    }
	public function check()
    {

		if(input('?user') && input('?pass')){
			if(input('user')!='' && input('pass')!=='' ){
			    $admin =Db::table('admin')->where("name",input('user'))->field("pass,roleid")->find();
			    $pass =$admin['pass'];
//			    var_dump($admin);exit;
                $auth =$this->getpri($admin['roleid']);
			    if(!$pass){
                    $this->success('请输入正确的登陆信息！', '/admin/SysConfig/');
                }
                if($pass!= md5(input('pass'))){
                    $this->success('请输入正确的登陆信息！', '/admin/SysConfig/');
                }
			    Session::set('admin.name',input('user'));
				Session::set('admin.time',time());
			}

		}
		$this->success('请输入正确的登陆信息！', '/admin/Msgtask');
    }
	public function logout()
    {
        session(null);
//		Session::delete('admin');
//        Session::delete(session('privilege'));
		$this->redirect('/admin/');
	}
    public function getpri($roleid){
        $role =Db::table("role")->field('rolename,pri_id_list')->find($roleid);
        session('rolename',$role['rolename']);
        if($role['pri_id_list']=='*'){
            session('privilege','*');
            $menu=Db::table("privilege")->where("parentid=0")->select();
            foreach ($menu as $k => $v) {
                $menu[$k]['sub']=Db::table("privilege")->where('parentid='.$v['id'])->select();
            }
            session('menu',$menu);
        }else{
            $li =$role['pri_id_list'];
            $arr = explode(',',$li);
            $pris=array();
            foreach($arr as $val){
                $ar =Db::table("privilege")->field('id,mname,cname,parentid')->where("id",$val)->find();
                 $ar['url']=$ar['mname'].'/'.$ar['cname'];
                array_push($pris,$ar);
            }
            $menu=array();
            foreach($pris as $k=>$v){
                $_pris[]=$v['url'];
                if($v['parentid']==0){
                    $menu[]=$v;
                }
            }
            session('privilege',$_pris);
            foreach ($menu as $k => $v) {
                foreach ($pris as $k1 => $v1) {
                    if($v1['parentid']==$v['id']){
                        $menu[$k]['sub'][]=$v1;
                    }

                }
            }
            session('menu',$menu);
        }

    }


}
