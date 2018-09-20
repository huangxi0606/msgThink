<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Msgtask extends Common
{
	public function _initialize()
    {
		if(!Session::has('admin.name')){
			$this->redirect('/admin/login/');
		}
		$this->assign('pagename','任务管理');
		$this->assign('loginname',Session::get('admin.name'));
		$this->assign('statuslable',array('5'=>'等待中','1'=>'运行中','2'=>'已取消','3'=>'已完成','4'=>'空收件人'));
		
    }
	
    public function index()
    {
		$sort=array('column'=>'id','type'=>'desc','sort'=>false);
		if(input('?_sort')){
			$sort=input('_sort/a');
			$sort['sort']=true;
		}
		$data=Db::table('msgtask')->order($sort['column'],$sort['type'])->paginate(20);
		//echo '<!--'.Db::getLastSql().'-->';
		$count=Db::table('msgtask')->count();
		$page = $data->render();
		$this->assign('data',$data);
		$this->assign('page',page_tp($page,$sort));
		$this->assign('count',$count);
		$this->assign('_sort',$sort);
		$tplname='admin/msgtask';
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
			$data=Db::table('msgtask')->where('id',$id)->find();
			$this->assign('data',$data);
			$this->assign('edit',true);
		}else{
			$this->assign('edit',false);
		}
		return $this->fetch('admin/msgtask_create');
	}
	public function addtask()
	{ 
		$data=input();$dbret=0;
        if(empty($data['name']) || empty($data['content'])){
            $this->error('任务名称和发送内容为必填。');
        }
		if($data['mid']>0){
            $old =Db::table('msgtask')->
            field('image_title,image_subtitle,caption,subcaption,secondary_subcaption,tertiary_subcaption,image,pic,tmessage,record,appid,appname,smsTitle')->where('id',$data['mid'])->find();
            if($old){
                foreach ($old as $key => $value) {
                    $data[$key]=$value;
                }
            }else{
                $this->error('此任务id没有内容');
            }
        }else{
            $file = request()->file('image');
            if($file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info){
                }else{
                    $this->error('图片上传失败');
                }
            }
            $img = request()->file('pic');
            if($img){
                $infoa = $img->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($infoa){
                    $data['pic'] = str_replace("\\",'/',$infoa->getSaveName());
                }else{
                    $this->error('图片上传失败');
                }
            }
            if($data['record']){
                if(strpos($data['record'],'1') !== false){
                    if(empty($data['appid']) || empty($data['appname'])){
                        $this->error('appid和appname为必填。');
                    }
                }
                if(!$file){
                    $this->error('商店类型图片为必填。');
                }
            }
        }
		if($data['enable']==1){
			if($data['status']==2){
				$data['status']=1;
			}
		}else{
			$data['status']=2;
		}
		unset($data['r1']);
		unset($data['enable']);
		if(input('?id')){
			$data['uptime']=time();
			$dbret=Db::table('msgtask')->update($data);
		}else{
			$data['intime']=time();
			$data['uptime']=time();
			$dbret=Db::table('msgtask')->insert($data);
		}
		if($dbret>0){
			$this->success('操作成功', '/admin/msgtask/');
		}else{
			$this->error('操作失败');
		}
	}
	public function deltask()
	{ 
		$data=input();$dbret=0;$retarr=array();
		if(input('?id')){
			$dbret=Db::table('msgtask')->delete($data['id']);
			delMsgTask($data['id']);
		}
		if($dbret>0){
			$retarr=array('status'=>true,'message'=>'操作成功');
		}else{
			$retarr=array('status'=>false,'message'=>'操作失败');
		}
		return $retarr;
	}
	public function enable()
	{ 
		$data=input();$dbret=0;$retarr=array();
		if($data['enable']=='on'){
			$data['status']=1;
		}else{
			$data['status']=2;
		}
		unset($data['enable']);
		//dump($data);
		if(input('?id')){
			$dbret=Db::table('msgtask')->update($data);
			enableMsgTask($data['id'],$data['status']);
		}
		if($dbret>0){
			$retarr=array('status'=>true,'message'=>'操作成功');
		}else{
			$retarr=array('status'=>false,'message'=>'操作失败');
		}
		return $retarr;
	}
	public function import()
    {
		$status_code=0;$message='err';$url='';
		$file = request()->file('file');
		$info = $file->validate(['size'=>config('upfile_size'),'ext'=>'txt,csv'])->rule('datemd5')->move(ROOT_PATH . 'public' . DS . 'uploads',true,false);
		if($info){
			$url='/public/uploads/'.$info->getSaveName();
			$status_code=200;$message='上传成功。';
		}else{
			$status_code=10002;$message=$file->getError();
		}
		return array('status_code'=>$status_code,'message'=>$message,'url'=>$url);
    }
//    public function rebuild(){
//        system('./');
//        $retarr=array('status'=>false,'message'=>'操作失败');
//        return $retarr;
//    }


    public function hhx(){
        $file = $_FILES["file"]; // file是上传按钮名
        if(!isset($file['tmp_name']) || !$file['tmp_name']) {
            $data=array('code' => 401, 'msg' => '没有文件上传');
            return $data;
        }
        if($file["error"] > 0) {
            $data=array('code' => 402, 'msg' => $file["error"]);
            return $data;
        }
        $upload_path = $_SERVER['DOCUMENT_ROOT']."/uploads/images/";
        $file_path   = 'http://' . $_SERVER['HTTP_HOST']."/uploads/images/";
        if(!is_dir($upload_path)){
            $data=array('code' => 403, 'msg' => '上传目录不存在');
            return $data;
        }
        if(move_uploaded_file($file["tmp_name"], $upload_path.time().$file['name'])){
           $data= array('code' => 200, 'src' => $file_path.time().$file['name']);
            return $data;
        }else{
            $data= array('code' => 404, 'msg' => '上传失败');
            return $data;
        }
    }

}
