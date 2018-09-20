<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Device extends Common
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','设备码管理');
		$this->assign('loginname',Session::get('admin.name'));
		$this->assign('statuslable',array('0'=>'空闲','1'=>'高版本激活','2'=>'无法激活','3'=>'ID激活锁','4'=>'激活失败','5'=>'5092','6'=>'sim卡无效','7'=>'码错误','8'=>'当天已用','9'=>'探测成功','10'=>'码缺损'));
		$this->assign('statuscolor',array('0'=>'aqua','1'=>'danger','2'=>'danger','3'=>'danger','4'=>'danger','5'=>'danger','6'=>'danger','7'=>'danger','8'=>'primary','9'=>'success','10'=>'danger'));
		$this->assign('statuscolor2',array('0'=>'ffc107','1'=>'dc3545','2'=>'dc3545','3'=>'dc3545','4'=>'dc3545','5'=>'dc3545','6'=>'dc3545','7'=>'dc3545','8'=>'007bff','9'=>'28a745','10'=>'dc3545'));
		$this->assign('statuslableup',array('0'=>'新增','1'=>'已导入','2'=>'导入错误','3'=>'导入中'));
	}
	
    public function index()
    {
		$sort=array('column'=>'id','type'=>'desc','sort'=>false);
		if(input('?_sort')){
			$sort=input('_sort/a');
			$sort['sort']=true;
		}
		//SELECT * FROM deviceid INNER JOIN (SELECT id FROM deviceid ORDER BY 'status' LIMIT 356280,20) as a USING (id)
		//$data=Db::table('deviceid')->join('deviceid',)->order($sort['column'],$sort['type'])->paginate(20);
		$data=Db::table('deviceid')->order($sort['column'],$sort['type'])->paginate(20);
		$count=Db::table('deviceid')->count();
		$page = $data->render();
		$this->assign('data',$data);
		$this->assign('page',page_tp($page,$sort));
		$this->assign('count',$count);
		$data_upfilelog=Db::table('upfilelog')->order('id','desc')->paginate(100);
		$count_upfilelog=Db::table('upfilelog')->count();
		$page_upfilelog = $data_upfilelog->render();
		$this->assign('data_upfilelog',$data_upfilelog);
		$this->assign('page_upfilelog',page_tp($page_upfilelog,$sort));
		$this->assign('count_upfilelog',$count_upfilelog);
		
		$this->assign('opendevice',opendevice(''));
		$this->assign('_sort',$sort);
		$tplname='admin/device';
		if(input('?_pjax')){
			$tplname.='_list';
		}
        return $this->fetch($tplname);
    }
	public function create()
	{ 
		$id=intval(input('param.id'));
		$data=array();
		if($id>0){
			$data=Db::table('deviceid')->where('id',$id)->find();
			$this->assign('data',$data);
			$this->assign('edit',true);
		}else{
			$this->assign('edit',false);
		}
		return $this->fetch('admin/device_create');
	}
	public function adddevice()
	{ 
		$data=input();$dbret=0;
		
		//dump(input());
		//dump(input('?id'));
		if(empty($data['serial']) || empty($data['udid'])){
			$this->error('serial和udid为必填。');
		}
		if(input('?id')){
			$data['uptime']=time();
			$dbret=Db::table('deviceid')->update($data);
		}else{
			$data['intime']=time();
			$data['uptime']=time();
			$dbret=Db::table('deviceid')->insert($data);
		}
		if($dbret>0){
			$this->success('操作成功', '/admin/device/');
		}else{
			$this->error('操作失败');
		}
		//return $this->fetch('admin/msgtask_create');
	}
	public function deldevice()
	{ 
		$data=input();$dbret=0;$retarr=array();
		if(input('?id') && input('?deltype')){
			$table = 'deviceid';
			if($data['deltype']==1){
				$table = 'upfilelog';
			}
			$dbret=Db::table($table)->delete($data['id']);
		}
		if($dbret>0){
			$retarr=array('status'=>true,'message'=>'操作成功');
		}else{
			$retarr=array('status'=>false,'message'=>'操作失败');
		}
		return $retarr;
	}
	
	public function opendevice(){
		$data=input();
		if(input('?enable')){
			if($data['enable']=='on'){
				$data['status']=1;
			}else{
				$data['status']=2;
			}
			opendevice($data['status']);
		}
		echo $data['status'];
	}
	
	public function trash()
	{ 
		$dbret=Db::table('deviceid')->delete(true);
		if($dbret>0){
			delredis('DeviceList_0');
			delredis('DeviceList_9');
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
		$info = $file->validate(['size'=>config('upfile_size'),'ext'=>'txt,csv'])->rule('datemd5')->move(ROOT_PATH . 'public' . DS . 'uploads/device',true,false);
		if($info){
			$url='/public/uploads/device/'.str_replace("\\","/", $info->getSaveName());;
			$filepath=ROOT_PATH . 'public' . DS . 'uploads/device/'.$info->getSaveName();
			$filepath=str_replace("\\","\\\\", realpath($filepath));
			$importName = input('importName');
			if($importName==''){
				$infos = $info->getInfo(); 
				$importName = $infos['name'];
			}
			$uid = $file->md5();
			$data = ['uid' => $uid, 'name' => $importName ,'url'=>$url,'path'=>$filepath,'intime' => time()];
			Db::table('upfilelog')->insert($data);
			$status_code=200;$message='上传成功.';
		}else{
			$status_code=10002;$message=$file->getError();
		}
		return array('status_code'=>$status_code,'message'=>$message,'url'=>$url);
		/*
		$info = $file->validate(['size'=>config('upfile_size'),'ext'=>'txt,csv'])->rule('datemd5')->move(ROOT_PATH . 'public' . DS . 'uploads/device',true,false);
		if($info){
			$url='/public/uploads/device/'.$info->getSaveName().'_repeat.txt';
			$filepath=ROOT_PATH . 'public' . DS . 'uploads/device/'.$info->getSaveName();
			$filepath=str_replace("\\","\\\\", realpath($filepath));
			$sql='LOAD DATA INFILE "'.$filepath.'"  IGNORE INTO TABLE deviceid CHARACTER SET utf8 FIELDS TERMINATED BY ","  ENCLOSED BY \'"\' LINES TERMINATED BY "\r\n"  IGNORE 1 LINES (serial,Imei,BluetoothAddress,WiFiAddress,Ethernet,ecid,udid,ModelNumber,ProductType,RegionInfo,ICCID)' ;
			//echo $sql;
			$old_count=Db::table('deviceid')->count();
			$maxid= Db::table('deviceid')->max('id');
			Db::execute($sql);
			Db::table('deviceid')->where('id>'.$maxid)->update(array('intime'=>time()));
			$new_count=Db::table('deviceid')->count();
			$num=$new_count-$old_count;
			$status_code=200;$message='上传成功，新增'.$num.'条数据.';
		}else{
			$status_code=10002;$message=$file->getError();
		}
		return array('status_code'=>$status_code,'message'=>$message,'url'=>$url);
		*/
	}
	
	public function import2()
    {
		$status_code=0;$message='err';$url='';$num_in=0;
		$file = request()->file('file');
		$info = $file->validate(['size'=>config('upfile_size'),'ext'=>'txt,csv'])->rule('datemd5')->move(ROOT_PATH . 'public' . DS . 'uploads',true,false);
		if($info){
			$url='/public/uploads/'.$info->getSaveName().'_repeat.txt';
			$filepath=ROOT_PATH . 'public' . DS . 'uploads/'.$info->getSaveName();
			$line_number = 0;
			$data=array();$repeat_arr=array();
			$handle = fopen($filepath,"r");
			while ($csvitem = fgetcsv($handle)) {	
				if($line_number == 0){ //跳过表头
						$line_number++;
						continue;
				}
				//print_r(fgetcsv($file));
				if(count($csvitem)>5){
					$repeat_arr[]=$csvitem[0];
					$data[$csvitem[0]]=array(
						'udid' => $csvitem[6],
						'BluetoothAddress' => $csvitem[2],
						'Imei' => $csvitem[1],
						'WiFiAddress' => $csvitem[3],		
						'ecid' => $csvitem[5],	
						'ProductType' => $csvitem[8],			
						'ModelNumber' => $csvitem[7],			
						'RegionInfo' => $csvitem[9],		
						'serial' => $csvitem[0],		
						'Ethernet' => $csvitem[4],			
						'ICCID' => $csvitem[10],		
						'intime' => time()
					);
				}
			}
			fclose($handle);
			$num=count($data);
			if($num>0){
				Db::table('deviceid')->insertAll($data);
			}
			/*  去重
			if($num>0){
				$repeat_str=implode(",",$repeat_arr);
				$repeat_in=Db::table('deviceid')->where('serial','in',$repeat_str)->column('serial');
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
					Db::table('deviceid')->insertAll($data);
				}
			}
			*/
			$status_code=200;$message='上传成功，新增'.$num.'条数据,重复数据 '.$num_in.'条';
		}else{
			$status_code=10002;$message=$file->getError();
		}
		return array('status_code'=>$status_code,'message'=>$message,'url'=>$url);
    }
}
