<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Account extends Common
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','帐号管理');
		$this->assign('loginname',Session::get('admin.name'));
		$this->assign('statuslable',array('0'=>'未使用','1'=>'正常','2'=>'锁定','3'=>'5092','4'=>'未知'));
    }
	
    public function index()
    {
		$sort=array('column'=>'id','type'=>'desc','sort'=>false);
		if(input('?_sort')){
			$sort=input('_sort/a');
			$sort['sort']=true;
		}
		$data=Db::table('account')->order($sort['column'],$sort['type'])->paginate(20);
		$count=Db::table('account')->count();
		$page = $data->render();
		$this->assign('data',$data);
		$this->assign('page',page_tp($page,$sort));
		$this->assign('count',$count);
		$this->assign('_sort',$sort);
		$tplname='admin/account';
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
			$data=Db::table('account')->where('id',$id)->find();
			$this->assign('data',$data);
			$this->assign('edit',true);
		}else{
			$this->assign('edit',false);
		}
		return $this->fetch('admin/account_create');
	}
	public function addaccount()
	{ 
		$data=input();$dbret=0;
		if(empty($data['email']) || empty($data['password'])){
			$this->error('邮箱和密码为必填。');
		}
		
		if(input('?id')){
			$data['uptime']=time();
			$dbret=Db::table('account')->update($data);
		}else{
			$data['intime']=time();
			$data['uptime']=time();
			$dbret=Db::table('account')->insert($data);
		}
		if($dbret>0){
			$this->success('操作成功', '/admin/account/');
		}else{
			$this->error('操作失败');
		}
		//return $this->fetch('admin/msgtask_create');
	}
	public function delaccount()
	{ 
		$data=input();$dbret=0;$retarr=array();
		if(input('?id')){
			$dbret=Db::table('account')->delete($data['id']);
		}
		if($dbret>0){
			$retarr=array('status'=>true,'message'=>'操作成功');
		}else{
			$retarr=array('status'=>false,'message'=>'操作失败');
		}
		return $retarr;
	}
	public function trash()
	{ 
		$dbret=Db::table('account')->delete(true);
		if($dbret>0){
			delredis('AccountList_0');
			$retarr=array('status'=>true,'message'=>'操作成功');
		}else{
			$retarr=array('status'=>false,'message'=>'操作失败');
		}
		return $retarr;
	}
	public function import()
    {
		$status_code=0;$message='err';$url='';$num_in=0;
		$file = request()->file('file');
//		var_dump($file);exit;
		$info = $file->validate(['size'=>config('upfile_size'),'ext'=>'txt,csv'])->rule('datemd5')->move(ROOT_PATH . 'public' . DS . 'uploads/account',true,false);
		if($info){
			$url='/public/uploads/account/'.$info->getSaveName().'_repeat.txt';
			$filepath=ROOT_PATH . 'public' . DS . 'uploads/account/'.$info->getSaveName();
			$line_number = 0;
			$data=array();$repeat_arr=array();
			$handle = fopen($filepath,"r");
			while ($csvitem = fgetcsv($handle)) {	
				if($line_number == 0){ //跳过表头
						$line_number++;
						continue;
				}
				//print_r(fgetcsv($file));
				if(count($csvitem)>0){
					//$repeat_arr[]=$csvitem[0];
					$data[$csvitem[0]]=array(
						'email' => $csvitem[0],
						'password' => $csvitem[1],
						'intime' => time()
					);
				}
			}
			fclose($handle);
			$num=count($data);
			if($num>0){
				Db::table('account')->insertAll($data);
			}
			$status_code=200;$message='上传成功，新增'.$num.'条数据.';
			/*
			if($num>0){
				$repeat_str=implode(",",$repeat_arr);
				$repeat_in=Db::table('account')->where('email','in',$repeat_str)->column('email');
				$num_in=count($repeat_in);
				if($num_in>0){
					file_put_contents($filepath.'_repeat.txt',$repeat_str);
				}
				foreach($repeat_in as $repeat){
					unset($data[$repeat]);
				}
				//var_dump($data);
				$num=count($data);
				if($num>0){
					Db::table('account')->insertAll($data);
				}
			}
			
			$status_code=200;$message='上传成功，新增'.$num.'条数据,重复数据 '.$num_in.'条';
			*/
		}else{
			$status_code=10002;$message=$file->getError();
		}
		return array('status_code'=>$status_code,'message'=>$message,'url'=>$url);
    }
}
