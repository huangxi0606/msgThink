<?php
/**
 * Created by PhpStorm.
 * User: Hhx
 * Date: 2018/9/6
 * Time: 11:14
 */
namespace app\admin\controller;

use think\Controller;
use think\Session;
use think\Db;
class Admin extends Common
{
    public function _initialize()
    {
        if(!Session::has('admin.name')){
            $this->redirect('/admin/login/');
        }
        $this->assign('pagename','管理员管理');
        $this->assign('loginname',Session::get('admin.name'));
        $this->assign('statuslable',array('0'=>'未使用','1'=>'正常'));

    }

    public function index()
    {
        $sort=array('column'=>'id','type'=>'desc','sort'=>false);
        if(input('?_sort')){
            $sort=input('_sort/a');
            $sort['sort']=true;
        }
        $data=Db::table('admin')->order($sort['column'],$sort['type'])->paginate(20);
        $datuy=array();
        foreach ($data as $datum){
            $datun =Db::table('role')->field("rolename")->where("id",$datum['roleid'])->find();
            $datum['role'] =$datun['rolename'];
            array_push($datuy,$datum);
        }
        $count=Db::table('admin')->count();
        $page = $data->render();
        $this->assign('datuy',$datuy);
        $this->assign('page',page_tp($page,$sort));
        $this->assign('count',$count);
        $this->assign('_sort',$sort);
        $tplname='admin/admin';
        if(input('?_pjax')){
            $tplname.='_list';
        }
        return $this->fetch($tplname);
    }
    public function create()
    {
        $id=intval(input('id'));
        $data=array();
        $roles=Db::table('role')->select();
        $this->assign('roles',$roles);
        if($id>0){
            $data=Db::table('admin')->where('id',$id)->find();
            $this->assign('data',$data);
            $this->assign('edit',true);
        }else{
            $this->assign('edit',false);
        }
        return $this->fetch('admin/admin_create');
    }
    public function addadmin()
    {
        $data=input();$dbret=0;
        if(empty($data['name']) || empty($data['pass'])){
            $this->error('管理员和密码为必填。');
        }
        $data['pass']=md5($data['pass']);
        if(input('?id')){
            $dbret=Db::table('admin')->update($data);
        }else{
            $dbret=Db::table('admin')->insert($data);
        }
        if($dbret>0){
            $this->success('操作成功', '/admin/admin/');
        }else{
            $this->error('操作失败');
        }
    }
    public function deladmin()
    {
        $data=input();$dbret=0;$retarr=array();
        if(input('?id')){
            $dbret=Db::table('admin')->delete($data['id']);
        }
        if($dbret>0){
            $retarr=array('status'=>true,'message'=>'操作成功');
        }else{
            $retarr=array('status'=>false,'message'=>'操作失败');
        }
        return $retarr;
    }
}