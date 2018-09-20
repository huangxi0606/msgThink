<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class Role extends Common
{
    public function _initialize()
    {
        if(!Session::has('admin.name')){
            $this->redirect('/admin/login/');
        }
//        $request= $request= \think\Request::instance();
//        $module_name=$request->module();
//        $controller_name=$request->controller();
//        $active_url=$module_name.'/'.$controller_name;
//
//        if(session('privilege')!='*' && !in_array(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME,session('privilege'))){
//        $this->error('没有权限访问该功能！');
//        }
        $this->assign('pagename','角色管理');
        $this->assign('loginname',Session::get('admin.name'));
        $this->assign('statuslable',array('5'=>'等待中','1'=>'运行中'));

    }

    public function index()
    {

        $sort=array('column'=>'id','type'=>'desc','sort'=>false);
        if(input('?_sort')){
            $sort=input('_sort/a');
            $sort['sort']=true;
        }
        $data =Db::table('role')->order($sort['column'],$sort['type'])->paginate(20);
        $count=Db::table('role')->count();
        $page = $data->render();
        $datuy=array();
        foreach ($data as $datum){
            $source =$datum['pri_id_list'];
            if($source=="*"){
                $datum['pri_id_list']="所有权限";
            }else{
                $arr = explode(',',$source);
                $ary ='';
                foreach($arr as $val){
                    $ar =Db::table("privilege")->field('pri_name')->where("id",$val)->find();
                    $ary =$ary.$ar['pri_name'].',';
                }
                $datum['pri_id_list'] =$ary;
            }
            array_push($datuy,$datum);
        }
//       var_dump($datuy[0]);exit;
        $this->assign('datuy',$datuy);

        $this->assign('page',page_tp($page,$sort));
        $this->assign('count',$count);
        $this->assign('_sort',$sort);
        $tplname='admin/role';
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
            $data=Db::table('role')->where('id',$id)->find();
            $this->assign('data',$data);
            $pris =$this->pritree();
            $this->assign('pris',$pris);
            $this->assign('edit',true);
        }else{
            $pris =$this->pritree();
            $this->assign('pris',$pris);
            $this->assign('edit',false);
        }

        return $this->fetch('admin/role_create');
    }
    public function addrole()
    {
        $data=input();$dbret=0;
        if(empty($data['rolename']) || empty($data['pri_id_list'])){
            $this->error('角色名称和角色权限为必填。');
        }
        $data['pri_id_list']=implode(",", $data['pri_id_list']);

        if(input('?id')){
            $dbret=Db::table('role')->update($data);
        }else{
            $dbret=Db::table('role')->insert($data);
        }
        if($dbret>0){
            $this->success('操作成功', '/admin/role/');
        }else{
            $this->error('操作失败');
        }
    }
    public function delrole()
    {
        $data=input();$dbret=0;$retarr=array();
        if(input('?id')){
            $dbret=Db::table('role')->delete($data['id']);
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
