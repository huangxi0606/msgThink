<?php
//ini_set("display_errors", 0);
//error_reporting(0);
//set_time_limit(0);
//ini_set('memory_limit', '512M');

require  'core.php';

$logfile='log/putdata';
//argv包含当运行于命令行下时传递给当前脚本的参数的数组
$runtype=intval($argv['1']);
$config_file_path = 'config.ini';
//parse_ini_file成功时以关联数组 array 返回设置，失败时返回 FALSE
$config_info = parse_ini_file($config_file_path, true);
$xyredis=get_redis();
$database = getdb();
/*********************************************************************************************************/
if($runtype==1 || $runtype==0){
	//var_dump('回更机器编号');
	//回更机器编号 以及 计数
	up_machine('DeviceList_machine','DeviceID');
	//回更机器码状态
	//var_dump('回更机器码状态');
	$ListName='replyDevice';
	$all_num=$xyredis->llen($ListName);
	if(intval($all_num)>0){
		for($d_i=0;$d_i<$all_num;$d_i++){
			$val_str=$xyredis->blpop($ListName,1);
			if($val_str){
			    //h var_arr 是redis保存的device数据
				$var_arr=unserialize($val_str[1]);
				// h   up_arr 是新的数组 以便更新数据库
				$up_arr=array();
				$up_arr['status']=$var_arr['status'];
				//h IMSI BasebandSerialNumber BasebandMasterKeyHash BasebandChipID Record_at FairPlayKeyData 字符长度大于5更新
				if(strlen($var_arr['IMSI'])>5){$up_arr['IMSI']=$var_arr['IMSI'];}
				if(strlen($var_arr['BasebandSerialNumber'])>5){$up_arr['BasebandSerialNumber']=$var_arr['BasebandSerialNumber'];}
				if(strlen($var_arr['BasebandMasterKeyHash'])>5){$up_arr['BasebandMasterKeyHash']=$var_arr['BasebandMasterKeyHash'];}
				if(strlen($var_arr['BasebandChipID'])>5){$up_arr['BasebandChipID']=$var_arr['BasebandChipID'];}
				if(strlen($var_arr['Record_at'])>5){$up_arr['Record_at']=$var_arr['Record_at'];}
				if(strlen($var_arr['FairPlayKeyData'])>5){$up_arr['FairPlayKeyData']=$var_arr['FairPlayKeyData'];}
				$up_arr['uptime']=time();
				//h更新数据库
				$data_up = $database->update('DeviceID',$up_arr, ["serial" => $var_arr['serial']]);
				//var_dump($database->last());
				$errType=$database->error();
				//h 如果数据库更新错误  继续放回内存
				if($errType[1]>0){ //更新错误，放回内存
					echo '[replyDevice]error:'.implode('_',$errType).'<br/>';
					setmsg($logfile,'[replyDevice]error:'.implode('_',$errType));
					$xyredis->rpush($ListName,$val_str[1]);
				}
			}
		}
	}
	//var_dump('提取硬件码数据到内存');
	// 提取硬件码数据到内存
	//$ProductType_arr = explode('$',$config_info['run']['ProductType']);
	$datas=array();$status_arr=array('0','9');//$ProductType_arr=array('iPhone7,2');
	//foreach($ProductType_arr as $ProductType){
		foreach($status_arr as $status){
			//$ListName='DeviceList_'.$ProductType.'_'.$status;
			$ListName='DeviceList_'.$status;
			$all_num=$xyredis->llen($ListName);
			//echo $ListName.'_'.$all_num;
			var_dump($ListName."_".$all_num);
			//h如果相对应的status列表中的数量为0
			if(intval($all_num)==0){
				//$get_num = $database->count("DeviceID", ["ProductType" => $ProductType,"status" => $status]);
				$get_num = $database->count("DeviceID", ["status" => $status]);
				//var_dump("get_num:".$get_num);
                //h如果数据库这个状态的数量大于500000 就按500000算
				if($get_num>500000){
					$get_num=500000;
				}
				//h以50000每次缓存进去
				$get_one=50000;
				$get_max=ceil($get_num/$get_one);
				//var_dump("get_max:".$get_max);
				for($get_i=0;$get_i<$get_max;$get_i++){
					$get_lnum=$get_i*$get_one;
					$datas = $database->select("DeviceID", ["id","udid","BluetoothAddress","Imei","WiFiAddress","ecid","ProductType","ModelNumber","RegionInfo","serial","Ethernet","ICCID","IMSI","BasebandSerialNumber","BasebandMasterKeyHash","BasebandChipID","Record_at","FairPlayKeyData"], ["status" => $status,"LIMIT" => [$get_lnum,$get_one]]);
					//var_dump( $database->last());
					var_dump( count($datas));
					foreach($datas as $data){
						if($data){
						    //h 序列化缓存到列表
							$xyredis->lpush($ListName,serialize($data));
						}
					}
					unset($datas);
				}
				/*
				$datas = $database->select("DeviceID", ["id","udid","BluetoothAddress","Imei","WiFiAddress","ecid","ProductType","ModelNumber","RegionInfo","serial","Ethernet","ICCID","IMSI","BasebandSerialNumber","BasebandMasterKeyHash","BasebandChipID","Record_at"], ["ProductType" => $ProductType,"status" => $status,'LIMIT' => 100000]);
				//var_dump( $database->last());
				foreach($datas as $data){
					$xyredis->lpush($ListName,serialize($data));
				}
				*/
			}
		}
	//}
}
/*********************************************************************************************************/
if($runtype==2 || $runtype==0){
	//回更机器编号 以及 计数
	up_machine('AccountList_machine','Account');
	
	//回更设备码发送成功统计
	$ListName='okDevice';
	//h查看哈希表中是否存在$listName字段
	if($xyredis->EXISTS($ListName)){
	    //h如果字段存在获取在哈希表中指定 key 的所有字段和值
		$MsgTaskMachineArr=$xyredis->HGETALL($ListName);
		foreach($MsgTaskMachineArr as $serial=>$num){
            //h删除一个或多个哈希表字段$ListName中的$serial
			$xyredis->HDEL($ListName,$serial);
			$data_up = $database->update('deviceid', ["okcount[+]" => $num,'uptime'=>time()], ["serial" => $serial]);
            $datas = $database->select("deviceid", ["upid"], ["serial" => $serial]);
            $data = $database->update('upfilelog', ["okcount[+]" => $num,'uptime'=>time()], ["id" => $datas[0]]);
		}
	}
		
	$all_num=$xyredis->llen($ListName);
	if(intval($all_num)>0){
		for($d_i=0;$d_i<$all_num;$d_i++){
			$val_str=$xyredis->blpop($ListName,1);
			if($val_str){
				$var_arr=unserialize($val_str[1]);
				$up_arr=array();
				$up_arr['status']=$var_arr['status'];
				$up_arr['cert']=$var_arr['cert'];
				$up_arr['record']=$var_arr['record'];
				$up_arr['uptime']=time();
				$data_up = $database->update('Account',$up_arr, ["email" => $var_arr['email']]);
				$errType=$database->error();
				if($errType[1]>0){ //更新错误，放回内存
					echo '[replyAccount]error:'.implode('_',$errType).'<br/>';
					setmsg($logfile,'[replyAccount]error:'.implode('_',$errType));
					$xyredis->rpush($ListName,$val_str[1]);
				}
			}
		}
    }
//为什么重复呢？？？？？？？？？？？？
	//回更状态和证书
	$ListName='replyAccount';
	$all_num=$xyredis->llen($ListName);
	if(intval($all_num)>0){
		for($d_i=0;$d_i<$all_num;$d_i++){
			$val_str=$xyredis->blpop($ListName,1);
			if($val_str){
				$var_arr=unserialize($val_str[1]);
				$up_arr=array();
				$up_arr['status']=$var_arr['status'];
				$up_arr['cert']=$var_arr['cert'];
				$up_arr['record']=$var_arr['record'];
				$up_arr['uptime']=time();
				$data_up = $database->update('Account',$up_arr, ["email" => $var_arr['email']]);
				$errType=$database->error();
				//var_dump($database->last());
				if($errType[1]>0){ //更新错误，放回内存
					echo '[replyAccount]error:'.implode('_',$errType).'<br/>';
					setmsg($logfile,'[replyAccount]error:'.implode('_',$errType));
					$xyredis->rpush($ListName,$val_str[1]);
				}
			}
		}
	}
	// 提取账号到内存
	$datas=array();
	$status_arr=array('0','1');
	//foreach($status_arr as $status){
		//$ListName='AccountList_'.$status;
		$ListName='AccountList_0';
		$all_num=$xyredis->llen($ListName);
		//echo $ListName.'_'.$all_num;
		if(intval($all_num)==0){
			$datas = $database->select("Account", ["id","email","cert","password","record"], ["status[<]" => 2]);
			//echo $database->last();
			//var_dump($datas);
			foreach($datas as $data){
				$xyredis->lpush($ListName,serialize($data));
			}
			unset($datas);
		}
	//}
}
/*********************************************************************************************************/
if($runtype==3 || $runtype==0){

	//回更收件人状态
	$ListName='replyMsgTask';
	$all_num=$xyredis->llen($ListName);
	$addressee_ids=array();$addressee_ids2=array();
	if(intval($all_num)>0){
		for($d_i=0;$d_i<$all_num;$d_i++){
			$val_str=$xyredis->blpop($ListName,1);
			if($val_str){
				$var_arr=unserialize($val_str[1]);
				if($var_arr['status']==2){
					$addressee_ids[]=$var_arr['addressee'];
				}else{
					$addressee_ids2[]=$var_arr['addressee'];
				}
			}
		}
		$data_up = $database->update('Addressee',['uptime'=>time()], ["addressee" => $addressee_ids2]);
		$data_up = $database->update('Addressee',['status'=>2,'uptime'=>time()], ["addressee" => $addressee_ids]);
		$errType=$database->error();
		if($errType[1]>0){ //更新错误，放回内存
			echo '[replyMsgTask]error:'.implode('_',$errType).'<br/>';
			setmsg($logfile,'[replyMsgTask]error:'.implode('_',$errType));
			$xyredis->rpush($ListName,$val_str[1]);
		}
	}
	//回更任务状态
	$MsgTaskIds=array();
	$ListName='MsgTaskList';
	$MsgTaskListArr=$xyredis->HGETALL($ListName);
	foreach($MsgTaskListArr as $MsgTaskId=>$status){
		// 删除超时机器开始
		$MsgTaskMachineName='MsgTaskMachine_'.$MsgTaskId;
		if($xyredis->EXISTS($MsgTaskMachineName)){
			$MsgTaskMachineArr=$xyredis->HGETALL($MsgTaskMachineName);
			foreach($MsgTaskMachineArr as $machine=>$uptime){ 
				if(time()-$uptime>60){
					$xyredis->HDEL($MsgTaskMachineName,$machine);
				}
			}
		}

		$MsgTaskListName=$ListName.'_'.$MsgTaskId;
		if($xyredis->EXISTS($MsgTaskListName)){
			$MsgTaskInfo=array();
			$MsgTaskInfo['status']=$xyredis->HGET($MsgTaskListName,'status');
			$MsgTaskInfo['uptime']=$xyredis->HGET($MsgTaskListName,'uptime');
			$MsgTaskInfo['oktime']=$xyredis->HGET($MsgTaskListName,'oktime');
			$MsgTaskInfo['sendnum']=$xyredis->HGET($MsgTaskListName,'sendnum');
			$MsgTaskInfo['senderrnum']=$xyredis->HGET($MsgTaskListName,'senderrnum');
			$database->update('MsgTask',['status'=>$MsgTaskInfo['status'],'uptime'=>$MsgTaskInfo['uptime'],'oktime'=>$MsgTaskInfo['oktime'],"sendnum" => $MsgTaskInfo['sendnum'],"senderrnum"=>$MsgTaskInfo['senderrnum']],['id'=>$MsgTaskId]);
			if($MsgTaskInfo['status']==3){
				$xyredis->DEL('SendList_'.$MsgTaskId); //删除已完成的收件人列表
			}
		}
	}
	
	// 提取任务与收件人列表到内存
	$datas=array();$MsgTaskIds=array();
	$datas = $database->select("MsgTask", ["id","name","content","machinenum","sendmaxnum","sendnum","senderrnum","num","sendlist","status","caption","subcaption","image_subtitle","image_title","secondary_subcaption","tertiary_subcaption","image","pic","tmessage","record","appid","appname","smsTitle"], ["status" => [1,5]]);
	//var_dump($database->last());
	foreach($datas as $data){
		$MsgTaskListName=$ListName.'_'.$data['id'];
		$status=$data['status'];
		if($status==5){
			$status=1;
			$database->update('MsgTask',['status'=>1],['id'=>$data['id']]);
		}
		$MsgTaskIds[$data['id']]=$status;
		if(!$xyredis->EXISTS($MsgTaskListName)){ //内存中不存在此任务 全新读取
			$sendlistnum = put_addressee($data);
			$data['sendlistnum'] = $sendlistnum;
			$xyredis->HMSET($MsgTaskListName,$data);
		}else{
			if($xyredis->HGET($MsgTaskListName,'sendlist')!=$data['sendlist']){ //收件人类型或地址发生改变的时候重读收件人
				$sendlistnum = put_addressee($data);
			}
			$MsgTaskInfo=array("name"=>$data['name'],"content"=>$data['content'],"machinenum"=>$data['machinenum'],"sendmaxnum"=>$data['sendmaxnum'],"num"=>$data['num'],"sendlist"=>$data['sendlist'],'sendlistnum'=>$sendlistnum,'status'=>$status,'caption'=>$data['caption'],'subcaption'=>$data['subcaption'],'image_subtitle'=>$data['image_subtitle'],'image_title'=>$data['image_title'],'secondary_subcaption'=>$data['secondary_subcaption'],'tertiary_subcaption'=>$data['tertiary_subcaption'],'image'=>$data['image'],'pic'=>$data['pic'],'tmessage'=>$data['tmessage'],'record'=>$data['record'],'appid'=>$data['appid'],'appname'=>$data['appname'],'smsTitle'=>$data['smsTitle']);
			$xyredis->HMSET($MsgTaskListName,$MsgTaskInfo);
		}
	}
	$xyredis->HMSET($ListName,$MsgTaskIds);
}
/*********************************************************************************************************/
if($runtype==4 || $runtype==0){
	//遍历设备码表
	$datas=array();
	$datas = $database->select("upfilelog", ["id","path","status"], ["status[<]" => 2]);
	//var_dump($datas);
	//var_dump($datas);
	$conn = mysqli_connect('localhost', 'root', 'DRsXT5ZJ6Oi55LPQ', 'wroot', '3306') or die('打开失败');
	mysqli_set_charset($conn, 'utf-8');
	foreach($datas as $data){
		//var_dump($data);
		$numarr = array();
		$data['id'] = intval($data['id']);
		$data['status'] = intval($data['status']);
		if($data['status']==0){
			$filepath=str_replace("\\","\\\\", realpath($data['path']));
			$sql='LOAD DATA INFILE "'.$filepath.'"  IGNORE INTO TABLE deviceid CHARACTER SET utf8 FIELDS TERMINATED BY ","  ENCLOSED BY \'"\' LINES TERMINATED BY "\r\n"  IGNORE 1 LINES (serial,Imei,BluetoothAddress,WiFiAddress,Ethernet,ecid,udid,ModelNumber,ProductType,RegionInfo,ICCID)' ;
			$res = mysqli_query($conn, $sql);
			$errTyp = 0;
			if (!$res) {
				$errTyp = 100;
				echo "sql语句执行失败<br>";
				echo "错误编码是" . mysqli_errno($conn) . "<br>";
				echo "错误信息是" . mysqli_error($conn) . "<br>";
				echo $sql;
				die();
			}
			/*
			var_dump($filepath);
			var_dump($sql);
			$datas = $database->exec($sql);
			$errType = $database->error();
			var_dump($database->last());
			var_dump($errType);
			$errType=$errType[1];
			*/
			
		}
		if($errType>0){ //更新错误
			var_dump('errType');
			$numarr['status'] = 2;
		}else{
		    //更新正确后
			if($data['status']==0){
				$numarr['status'] = 1;
				$data_up = $database->update('deviceid',["upid"=>$data['id']],['upid'=>0]);
				$numarr['num'] = intval($data_up->rowCount());
			}
			for($i=0;$i<11;$i++){
				$numarr['num'.($i+1)] = intval($database->count("deviceid", ["status" => $i,'upid'=>$data['id']]));
			}
		}
		var_dump($numarr);
		$database->update('upfilelog',$numarr,['id'=>$data['id']]);
		var_dump($database->last());
		var_dump($database->error());
	}
	mysqli_close($conn);
}
/*********************************************************************************************************/
//机器状态 09.18
if($runtype==5 || $runtype==0)
    $machine_list=array();$machine_key=array();
$all_num=$xyredis->llen($ListName);
if(intval($all_num)>0){
    for($d_i=0;$d_i<$all_num;$d_i++){
        $val_str=$xyredis->blpop($ListName,1);
        if($val_str){
            $machine=unserialize($val_str[1]);
            //h 过滤重复$machine_key 是列表 如果不在 就加入
            if(!in_array($machine['machine'],$machine_key)){
                $machine_key[]=$machine['machine'];
            }
            $machine_list[$machine['machine']][]=$machine['id'];
        }
    }
    //var_dump($machine_key);
    foreach($machine_key as $key){
        $machine_up=array();
        foreach($machine_list[$key] as $val){
            $machine_up[]=$val;
        }
        $data_up = $database->update($table, ["count[+]" => 1,"machine"=> $key,'uptime'=>time()], ["id" => $machine_up]);
        //ninie更新  09.18
        //如果状态存在 就存在 不存在就默认为0 查找数据表是否存在 不存在添加  存在更新
        //$nine=update("nine",, ["status" => 更新为值], ["name" => $machine]);

        $errType=$database->error();
        if($errType[1]>0){ //更新错误，放回内存
            echo '['.$name.']error:'.implode('_',$errType).'<br/>';
            setmsg($logfile,'['.$name.']error:'.implode('_',$errType));
            foreach($machine_list[$key] as $val){
                $xyredis->rpush($ListName,serialize(array('id'=>$val,'machine'=>$key)));
            }
        }
    }
}
    /*********************************************************************************************************/


$xyredis->close();
exit();
?>