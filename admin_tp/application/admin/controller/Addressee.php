<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Addressee extends Common
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','收件人管理');
		$this->assign('loginname',Session::get('admin.name'));
		$this->assign('statuslable',array('0'=>'正常','1'=>'停用','2'=>'错误'));
		
    }
	
    public function index()
    {
		$sort=array('column'=>'id','type'=>'desc','sort'=>false);
		if(input('?_sort')){
			$sort=input('_sort/a');
			$sort['sort']=true;
		}
		$data=Db::table('addressee')->order($sort['column'],$sort['type'])->paginate(20);
		$count=Db::table('addressee')->count();
		$page = $data->render();
		$this->assign('data',$data);
		$this->assign('count',$count);
		$this->assign('page',page_tp($page,$sort));
		$this->assign('_sort',$sort);
		$tplname='admin/addressee';
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
			$data=Db::table('addressee')->where('id',$id)->find();
			$this->assign('data',$data);
			$this->assign('edit',true);
		}else{
			$this->assign('edit',false);
		}
		return $this->fetch('admin/addressee_create');
	}
	public function addaddressee()
	{ 
		$data=input();$dbret=0;
		//dump(input());
		//dump(input('?id'));
		if(empty($data['addressee'])){
			$this->error('地址为必填。');
		}
		
		if(input('?id')){
			$data['uptime']=time();
			$dbret=Db::table('addressee')->update($data);
		}else{
			$data['intime']=time();
			$data['uptime']=time();
			$dbret=Db::table('addressee')->insert($data);
		}
		if($dbret>0){
			$this->success('操作成功', '/admin/addressee/');
		}else{
			$this->error('操作失败');
		}
		//return $this->fetch('admin/msgtask_create');
	}
	public function deladdressee()
	{ 
		$data=input();$dbret=0;$retarr=array();
		if(input('?id')){
			$dbret=Db::table('addressee')->delete($data['id']);
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
		$dbret=Db::table('addressee')->delete(true);
		if($dbret>0){
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
		$info = $file->validate(['size'=>config('upfile_size'),'ext'=>'txt,csv'])->rule('datemd5')->move(ROOT_PATH . 'public' . DS . 'uploads/addressee',true,false);
//		var_dump($info);exit;
		if($info){
			$url='/public/uploads/addressee/'.$info->getSaveName().'_repeat.txt';
			$filepath=ROOT_PATH . 'public' . DS . 'uploads/addressee/'.$info->getSaveName();
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
					//if(strlen($csvitem[0])==11 && substr($csvitem[0],0,1)=='1'){
						//$repeat_arr[]=$csvitem[0];
						$data[$csvitem[0]]=array(
							'addressee' => $csvitem[0],
							'intime' => time()
						);
					//}
				}
			}
			fclose($handle);
			$num=count($data);
			if($num >0){
                $data = array_chunk($data,10000);
                foreach ($data as $item){
//            DB::table("account_copy") -> insert($item);
                    Db::table('addressee')->insertAll($item);
                }
            }
            $status_code=200;$message='上传成功，新增'.$num.'条数据.';
//			if($num>0){
//				Db::table('addressee')->insertAll($data);
//			}
//			$status_code=200;$message='上传成功，新增'.$num.'条数据.';
			/*
			if($num>0){
				$repeat_str=implode(",",$repeat_arr);
				$repeat_in=Db::table('addressee')->where('addressee','in',$repeat_str)->column('addressee');
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
					Db::table('addressee')->insertAll($data);
				}
			}
			*/
			//$status_code=200;$message='上传成功，新增'.$num.'条数据,重复数据 '.$num_in.'条';
		}else{
			$status_code=10002;$message=$file->getError();
		}
		return array('status_code'=>$status_code,'message'=>$message,'url'=>$url);
    }
}
