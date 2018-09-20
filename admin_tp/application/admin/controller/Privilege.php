<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Privilege extends Common
{
    public function _initialize()
    {
//        if(!Session::has('admin.name')){
//            $this->redirect('/admin/login/');
//        }
        $this->assign('pagename','权限管理');
        $this->assign('loginname',Session::get('admin.name'));
        $this->assign('statuslable',array('5'=>'等待中','1'=>'运行中'));

    }

    public function index()
    {
//        $pri=D('privilege');
//        $pris=$pri->pritree();
//        $this->assign('pris',$pris);
//        $this->display();
        $sort=array('column'=>'id','type'=>'desc','sort'=>false);
        if(input('?_sort')){
            $sort=input('_sort/a');
            $sort['sort']=true;
        }
        $data =$this->pritree();

        $count=Db::table('privilege')->count();
//        $page = $data->render();
        $this->assign('data',$data);
//        $this->assign('page',page_tp($page,$sort));
        $this->assign('count',$count);
        $this->assign('_sort',$sort);
        $tplname='admin/privilege';
        if(input('?_pjax')){
            $tplname.='_list';
        }
        return $this->fetch($tplname);
    }

    public function create()
    {
        $id=intval(input('id'));
        $data=array();

        if($id>0){
            $data=Db::table('privilege')->where('id',$id)->find();
            $this->assign('data',$data);
            $pris =$this->pritree();
//            var_dump($pris);exit;
            $this->assign('pris',$pris);
            $this->assign('edit',true);
        }else{
            $pris =$this->pritree();
//            var_dump($pris);exit;
            $this->assign('pris',$pris);
            $this->assign('edit',false);
        }

        return $this->fetch('admin/privilege_create');
    }
    public function addprivilege()
    {
        $data=input();$dbret=0;

        //dump(input());
        //dump(input('?id'));
//        if(empty($data['name']) || empty($data['content'])){
//            $this->error('任务名称和发送内容为必填。');
//        }

        if(input('?id')){
            $dbret=Db::table('privilege')->update($data);
        }else{
            $dbret=Db::table('privilege')->insert($data);
        }
        if($dbret>0){
            $this->success('操作成功', '/admin/privilege/');
        }else{
            $this->error('操作失败');
        }
        //return $this->fetch('admin/msgtask_create');
    }
    public function delprivilege()
    {
        $data=input();$dbret=0;$retarr=array();
        if(input('?id')){
            $dbret=Db::table('privilege')->delete($data['id']);

        }
        if($dbret>0){
            $retarr=array('status'=>true,'message'=>'操作成功');
        }else{
            $retarr=array('status'=>false,'message'=>'操作失败');
        }
        return $retarr;
    }

    public function pritree()
    {
        $data=Db::table('privilege')->select();
        return $this->resort($data);
    }

    public function resort($data,$parentid=0,$level=0)
    {
        static $ret=array();
        foreach ($data as $k => $v)
        {
            if($v['parentid']==$parentid)
            {
                $v['level']=$level;
                $ret[]=$v;
                $this->resort($data,$v['id'],$level+1);
            }
        }
        return $ret;

    }

}
