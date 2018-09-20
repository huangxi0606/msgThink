<?php
function getDevice($Info){
	//xxxx/getDevice?machine=xxxx&ProductType=yyyy&status=z
	//if(empty($Info['machine']) || empty($Info['ProductType']) || $Info['status']==''){
	if(empty($Info['machine']) || $Info['status']==''){
		return '';
	}
	$DeviceInfo=array();
	$xyredis=get_redis();
	//$xyredis->set('opendevicelist',serialize(array('test1','test2')));
	if($xyredis->get('opendevice')=='1'){
		$opendevicelist = $xyredis->get('opendevicelist');
		if($opendevicelist){
			$authorize = array();
			$authorize = explode(',',$opendevicelist);
			if((intval($Info['status'])==0  and !in_array($Info['machine'],$authorize))){
				$xyredis->close();
				return '';
			}
		}
	}
	/*
	if((intval($Info['status'])==9 and $Info['machine']!='test1')){
		$xyredis->close();
		return '';
	}
	*/
	if((intval($Info['status'])==0 and $xyredis->get('opendevice')=='2') or (intval($Info['status'])==9 and !runMsgTask($xyredis))){
		$xyredis->close();
		return '';
	}
	//$ListName='DeviceList_'.$Info['ProductType'].'_'.$Info['status'];
	$ListName='DeviceList_'.$Info['status'];
	$val_str=$xyredis->blpop($ListName,1);
	//var_dump($val_str);var_dump($ListName);
	if($val_str){
		$DeviceInfo=unserialize($val_str[1]);
		$MachineInfo=array();
		$MachineInfo['id']=$DeviceInfo['id'];
		$MachineInfo['machine']=$Info['machine'];
//		//增加0917 for nine
//        $MachineInfo['status']=$Info['status'];
		$xyredis->lpush('DeviceList_machine',serialize($MachineInfo));
	}
    if(intval($Info['status'])==9){
	    $machinenine =array();
	    $machinenine['name'] =$Info['machine'];
	    //0为在线。
        $machinenine['status'] =0;
        $machinenine['uptime'] =time();
        $machinenine['level'] =1;
        $machinenine['num'] =0;
        $key ="machine:".$machinenine['name'];
        $xyredis->HMSET($key,$machinenine);
        $xyredis->sAdd("machine_add",$machinenine['name']);
    }
	$xyredis->close();
	return $DeviceInfo;
}
function replyDevice($Info){
	//xxxx/replyDevice?serial=aaaa&ProductType=bbbb&status=ccc&record=1&IMSI=&BasebandSerialNumber=&BasebandMasterKeyHash=&BasebandChipID=&Record=
	if(empty($Info['serial'])){
		return '';
	}
	$xyredis=get_redis();
	$DeviceInfo=array();
	$DeviceInfo['serial']=$Info['serial'];
	$DeviceInfo['ProductType']=$Info['ProductType'];
	$DeviceInfo['status']=$Info['status'];
	$DeviceInfo['IMSI']=$Info['IMSI'];
	$DeviceInfo['BasebandSerialNumber']=$Info['BasebandSerialNumber'];
	$DeviceInfo['BasebandMasterKeyHash']=$Info['BasebandMasterKeyHash'];
	$DeviceInfo['BasebandChipID']=$Info['BasebandChipID'];
	$DeviceInfo['Record_at']=$Info['Record'];
	//$DeviceInfo['FairPlayKeyData']=urlencode($Info['FairPlayKeyData']);
	 $DeviceInfo['FairPlayKeyData']=$Info['FairPlayKeyData'];
	$xyredis->lpush('replyDevice',serialize($DeviceInfo));
	//09.20增加
    //0为在线。
    $key ="machine:".$Info['machine'];
    $xyredis->hSet($key,'uptime',time());
    $xyredis->hSet($key,'status',0);
    $xyredis->hSet($key,'level',2);
//    $is =$xyredis->sIsMember("machine_add",$Info['machine']['machine']);
//    if($is<1){
    //无需判断 如果已有不会添加
        $xyredis->sAdd("machine_add",$Info['machine']);
//    }
	$xyredis->close();
	return $DeviceInfo['FairPlayKeyData'];
}
function getAccount($Info){
	//xxxx/getAccount?machine=xxxx&status=0
	if(empty($Info['machine']) || $Info['status']==''){
		return '';
	}
	$AccountInfo=array();
	$xyredis=get_redis();
	if(!runMsgTask($xyredis)){
		$xyredis->close();
		return '';
	}
	$val_str=$xyredis->blpop('AccountList_'.$Info['status'],1);
	if($val_str){
		$AccountInfo=unserialize($val_str[1]);
		if(empty($AccountInfo['cert'])){
			$AccountInfo['cert']='';
		}
		$MachineInfo=array();
		$MachineInfo['id']=$AccountInfo['id'];
		$MachineInfo['machine']=$Info['machine'];
		$xyredis->lpush('AccountList_machine',serialize($MachineInfo));
	}
    //09.20增加
    //0为在线。
    $key ="machine:".$Info['machine'];
    $xyredis->hSet($key,'uptime',time());
    $xyredis->hSet($key,'status',0);
    $xyredis->hSet($key,'level',3);
//    $is =$xyredis->sIsMember("machine_add",$Info['machine']['machine']);
//    if($is<1){
    //无需判断 如果已有不会添加
    $xyredis->sAdd("machine_add",$Info['machine']);
//    }
	$xyredis->close();
	return $AccountInfo;
}
function replyAccount($Info){
	//email=aaaa&status=ccc&record=1&cert=ddddd
	if(empty($Info['email']) || $Info['status']==''){
		return '';
	}
	$AccountInfo=array();
	$xyredis=get_redis();
	$AccountInfo['email']=$Info['email'];
	$AccountInfo['status']=$Info['status'];
	$AccountInfo['record']=$Info['record'];
	$AccountInfo['cert']=$Info['cert'];
	$xyredis->lpush('replyAccount',serialize($AccountInfo));
    //09.20增加
    //0为在线。
    $key ="machine:".$Info['machine'];
    $xyredis->hSet($key,'uptime',time());
    $xyredis->hSet($key,'status',0);
    $xyredis->hSet($key,'level',4);

//    $is =$xyredis->sIsMember("machine_add",$Info['machine']['machine']);
//    if($is<1){
    //无需判断 如果已有不会添加
    $xyredis->sAdd("machine_add",$Info['machine']);
//    }
	$xyredis->close();
}
function getMsgTask($Info){
	// getMsgTask?machine=P003&email=123@163.com&serial=C37NKYPWG5MP
	if(empty($Info['machine']) || empty($Info['email']) || empty($Info['serial'])){
		return '';
	}
	$MsgTaskInfo=array();
	$xyredis=get_redis();
	if(!runMsgTask($xyredis)){
		$xyredis->close();
		return '';
	}
	$MsgTaskIds=array();
	$ListName='MsgTaskList';
	$MsgTaskListArr=$xyredis->HGETALL($ListName);
	foreach($MsgTaskListArr as $MsgTaskId=>$status){
		$MsgTaskListName=$ListName.'_'.$MsgTaskId;
		if($xyredis->EXISTS($MsgTaskListName)){
			$MsgTaskMachineName='MsgTaskMachine_'.$MsgTaskId;
			/* 移至定时任务执行
			$MsgTaskMachineArr=$xyredis->HGETALL($MsgTaskMachineName);
			foreach($MsgTaskMachineArr as $machine=>$uptime){ // 删除超时机器
				if(time()-$uptime>60){
					$xyredis->HDEL($MsgTaskMachineName,$machine);
				}
			}
			*/
			$MsgTaskInfo=$xyredis->HGETALL($MsgTaskListName);
			//var_dump($MsgTaskInfo);
			if($status==1 && intval($MsgTaskInfo['sendnum'])<intval($MsgTaskInfo['sendmaxnum']) && count($MsgTaskMachineArr)<intval($MsgTaskInfo['machinenum'])){
				$xyredis->HSET($MsgTaskMachineName,$Info['machine'],time()); //添加任务机器
				break;
			}
			//任务完成容错机制
			if(intval($MsgTaskInfo['sendnum'])>=intval($MsgTaskInfo['sendmaxnum'])){
				//$xyredis->HDEL('MsgTaskList',$MsgTaskId);
				$xyredis->HSET($ListName,$MsgTaskId,3);
				$xyredis->HMSET($MsgTaskListName,array('status'=>3,'oktime'=>time())); //更新已完成
			}
			$MsgTaskInfo=array();
		}
	}
	if(intval($MsgTaskInfo['id'])>0){
		$get_c=intval($MsgTaskInfo['num']);
		for($get_i=0;$get_i<$get_c;$get_i++){
			$SendListName='SendList_'.$MsgTaskInfo['id'];
			$SendListCount=$xyredis->LLEN($SendListName);
			if($SendListCount>0){
				$val_str=$xyredis->blpop($SendListName,1); //循环取收件人
				if($val_str){
					if(strpos($val_str[1],'@') !== false){
						$val_str[1] = 'mailto:'.$val_str[1];
					}else{
						$val_str[1] = 'tel:'.$val_str[1];
					}
					$MsgTaskInfo['addressee'][]=$val_str[1];
				}
			}else{
				$xyredis->HSET($ListName,$MsgTaskInfo['id'],4);
				$xyredis->HMSET($ListName.'_'.$MsgTaskInfo['id'],array('status'=>4,'oktime'=>time())); //更新已完成
			}
		}
	}
    //09.20增加
    //0为在线。
    $key ="machine:".$Info['machine'];
    $xyredis->hSet($key,'uptime',time());
    $xyredis->hSet($key,'status',0);
    $xyredis->hSet($key,'level',5);
//    $is =$xyredis->sIsMember("machine_add",$Info['machine']['machine']);
//    if($is<1){
    //无需判断 如果已有不会添加
    $xyredis->sAdd("machine_add",$Info['machine']);
//    }
	$xyredis->close();
	return $MsgTaskInfo;
}
function replyMsgTask($Info){
	//{"email":"hds274doy@inbox.ru","serial":"F18P5NTVG5MF","addressee":[{"18286025889":"1"},{"18286025822":"2"},{"18286025822":"0"}],"machine":"P003","task_id":"5"}
	if(empty($Info['data'])){
		return '';
	}
	$data_arr=json_decode(base64_decode($Info['data']),1);
	if(intval($data_arr['task_id'])>0){
		$xyredis=get_redis();
		$MsgTaskListName='MsgTaskList_'.$data_arr['task_id'];
		if($xyredis->EXISTS($MsgTaskListName)){ 
			//$MsgTaskMachineName='MsgTaskMachine_'.$data_arr['task_id'];
			//$xyredis->HSET($MsgTaskMachineName,$data_arr['machine'],time()); //更新活跃时间
			$addressee_arr=$data_arr['addressee'];
			$sendnum_c=0;$MsgTaskInfo=array();
			foreach($addressee_arr as $addressee=>$status){
				$addressee = str_replace(array('mailto:','tel:'),'',$addressee);
				$MsgTaskInfo['uptime']=time();
				if(strlen($addressee)>5 && $status==3){
					$xyredis->lpush('SendList_'.$data_arr['task_id'],$addressee); //没有用的重新加到任务收件人池中
				}else{
					$sendlistpath=$xyredis->HGET($MsgTaskListName,'sendlist');
					if($sendlistpath==''){
						$xyredis->lpush('replyMsgTask',serialize(array('addressee'=>$addressee,'status'=>$status))); //其他丢到回更库中
					}else{
						if($status==1)
						{
							file_put_contents(realpath(dirname(__FILE__).'/../').'\admin_tp'.$sendlistpath."_ok.txt", $addressee."\r\n", FILE_APPEND);
						}
						if($status==2)
						{
							$senderrnum_c++;
							file_put_contents(realpath(dirname(__FILE__).'/../').'\admin_tp'.$sendlistpath."_err.txt", $addressee."\r\n", FILE_APPEND);
						}
					}
				}
				if(strlen($addressee)>5 && $status==1){
					$sendnum_c++;
				}
			}
			$sendnum=$xyredis->HGET($MsgTaskListName,'sendnum')+$sendnum_c;
			if($sendnum >= $xyredis->HGET($MsgTaskListName,'sendmaxnum')){
				$MsgTaskInfo['status']=3; //更新已完成
				$MsgTaskInfo['oktime']=time();
				$xyredis->HSET('MsgTaskList',$MsgTaskId,3);
			}
			if($sendnum_c>0){
				$xyredis->HINCRBY('okDevice',$data_arr['serial'],$sendnum_c);//更新设备码成功数
			}
			$xyredis->HINCRBY($MsgTaskListName,'sendnum',$sendnum_c);//更新收件人发送成功数sendnum
			$xyredis->HINCRBY($MsgTaskListName,'senderrnum',$senderrnum_c);//更新收件人发送成功数senderrnum
			$xyredis->HMSET($MsgTaskListName,$MsgTaskInfo);
            //09.20增加
            //0为在线。
            $key ="machine:".$data_arr['machine'];
            $xyredis->hSet($key,'uptime',time());
            $xyredis->hSet($key,'status',0);
            $xyredis->hSet($key,'level',6);
            $xyredis->HINCRBY($key,'num',$sendnum_c);
//    $is =$xyredis->sIsMember("machine_add",$Info['machine']['machine']);
//    if($is<1){
            //无需判断 如果已有不会添加
            $xyredis->sAdd("machine_add",$data_arr['machine']);
//    }
		}
		$xyredis->close();
	}
}
function runMsgTask($xyredis){
	$ListName='MsgTaskList';
	$MsgTaskListArr=$xyredis->HGETALL($ListName);
	foreach($MsgTaskListArr as $MsgTaskId=>$status){
		if($status==1 || $status==5){
			return true;
		}
	}/*
	$ListName='MsgTaskList';
	$MsgTaskIds=$xyredis->HLEN($ListName);
	if($MsgTaskIds>0){
		return true;
	}*/
	return false;
}
function get_redis($host='127.0.0.1',$port=6379){
	$redis = new Redis();
	$redis->connect($host,$port);
//	$redis->auth('myhotmoney');
	return $redis;
}
?>