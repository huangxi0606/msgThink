<?php
/*
总API接口
*/
ini_set("display_errors", 0);
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');
require  'core/redis.php';
$a=$_GET['a'];
//var_dump($a);exit;
//setlog("a:".$a);
if(in_array($a,array('getDevice','replyDevice','getAccount','replyAccount','getMsgTask','replyMsgTask'))){
	$code=1;$success='error';$result=null;
	if($a=='getDevice'){
		$DeviceInfo=getDevice($_GET);
//		var_dump($DeviceInfo);exit;
		if(!empty($DeviceInfo)){
			$result=array('udid'=>$DeviceInfo['udid'],'BluetoothAddress'=>$DeviceInfo['BluetoothAddress'],'Imei'=>$DeviceInfo['Imei'],'WiFiAddress'=>$DeviceInfo['WiFiAddress'],'ecid'=>$DeviceInfo['ecid'],'ProductType'=>$DeviceInfo['ProductType'],'ModelNumber'=>$DeviceInfo['ModelNumber'],'RegionInfo'=>$DeviceInfo['RegionInfo'],'serial'=>$DeviceInfo['serial'],'Ethernet'=>$DeviceInfo['Ethernet'],'ICCID'=>$DeviceInfo['ICCID'],'IMSI'=>$DeviceInfo['IMSI'],'BasebandSerialNumber'=>$DeviceInfo['BasebandSerialNumber'],'BasebandMasterKeyHash'=>$DeviceInfo['BasebandMasterKeyHash'],'BasebandChipID'=>$DeviceInfo['BasebandChipID'],'FairPlayKeyData'=>$DeviceInfo['FairPlayKeyData'],'Record_at'=>$DeviceInfo['Record_at']);
            $code=0;$success='success';
		}
	}
	if($a=='replyDevice'){
		$result = replyDevice($_POST);
		$code=0;$success='success';
		//$result='';
	}
	if($a=='getAccount'){
		$AccountInfo=getAccount($_GET);
		if(!empty($AccountInfo)){
			$result=array('email'=>$AccountInfo['email'],'cert'=>$AccountInfo['cert'],'password'=>$AccountInfo['password'],'Record_at'=>$AccountInfo['record']);
			$code=0;$success='success';
		}
	}
	if($a=='replyAccount'){
		if(strlen($_POST['email'])>6){
			replyAccount($_POST);
			$code=0;$success='success';
		}
		$result='';
	}
	if($a=='getMsgTask'){
		$MsgTaskInfo=getMsgTask($_GET);
		if(!empty($MsgTaskInfo) && $MsgTaskInfo!=null && intval($MsgTaskInfo['num'])>0){
//		    app  pic  text
            $info=array();
            if($MsgTaskInfo['record']){
                $arr =str_split($MsgTaskInfo['record']);
                foreach ($arr as $ar){
                    if($ar ==1){
                        if($MsgTaskInfo['image']){
                            $image ="http://dc.cn/uploads/".$MsgTaskInfo['image'];
                        }else{
                            $image="";
                        }
                        $app =array('type'=>"app",'caption'=>$MsgTaskInfo['caption'],'subcaption'=>$MsgTaskInfo['subcaption'],
                            'image_subtitle'=>$MsgTaskInfo['image_subtitle'],'image_title'=>$MsgTaskInfo['image_title'],
                            'secondary_subcaption'=>$MsgTaskInfo['secondary_subcaption'],
                            'tertiary_subcaption'=>$MsgTaskInfo['tertiary_subcaption'],'pic'=>$image,
                            'appid'=>$MsgTaskInfo['appid'],'appname'=>$MsgTaskInfo['appid'],'smsTitle'=>$MsgTaskInfo['smsTitle']);
                        array_push($info,$app);
                    }
                    if($ar ==2){
                        if($MsgTaskInfo['pic']){
                            $pic =array('type'=>"pic",'pic'=>"http://dc.cn/uploads/".$MsgTaskInfo['pic']);
                        }
                        array_push($info,$pic);
                    }
                    if($ar =='3'){
                        if($MsgTaskInfo['tmessage']){
                            $text =array('type'=>"text",'tmessage'=>$MsgTaskInfo['tmessage']);
                        }
                        array_push($info,$text);
                    }
                }
            }
			$result=array('msg_task_id'=>$MsgTaskInfo['id'],'msg_name'=>$MsgTaskInfo['name'],'msg_content'=>$MsgTaskInfo['content'],'num'=>$MsgTaskInfo['num'],'addressee'=>$MsgTaskInfo['addressee'],'info'=>$info);
			$code=0;$success='success';
		}
	}
	if($a=='replyMsgTask'){
//	    file_put_contents('hhh.txt',$_POST);
		if(strlen($_POST['data'])>6){
			replyMsgTask($_POST);
			$code=0;$success='success';
		}
		$result='';
	}
	$retstr=array('success'=>$success,'result'=>$result,'code'=>$code);
	echo json_encode($retstr);
}
//appid appname
//smsTitle
?>