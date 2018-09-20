<?php
require  'Medoo.php';
use Medoo\Medoo;
$uploads='D:\wwwroot\admin_tp';

function put_addressee($data){
	global $xyredis,$database,$logfile,$uploads; 
	$line_number=0;
	$ListName='SendList_'.$data['id'];
	$all_num=$xyredis->llen($ListName);
	//echo $ListName.'_'.$all_num;
	$sendlistnum = 0;
	if(intval($all_num)==0){
		if(!empty($data['sendlist'])){
			$filepath = $uploads.$data['sendlist'];

			$handle = fopen($filepath,"r");
			while ($csvitem = fgetcsv($handle)) {	
				if($line_number == 0){ //跳过表头
						$line_number++;
						continue;
				}
				//print_r(fgetcsv($file));
				if(count($csvitem)>0){
					$sendlistnum++;
					$xyredis->lpush($ListName,$csvitem[0]);
				}
			}
			fclose($handle);
		}else{
			$sendlist = $database->select("Addressee", ["addressee"], ["status" => 0,'LIMIT' => $data['sendmaxnum']*5]);
			foreach($sendlist as $addressee){
				$sendlistnum++;
				$xyredis->lpush($ListName,$addressee['addressee']);
			}
		}
	}
	return intval($sendlistnum);
}
function up_machine($ListName,$table){
	global $xyredis,$database,$logfile; 
	//$ListName='DeviceList_machine'; "DeviceID"
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
}
function setmsg($logname,$str){
	$logname.='_'.date("Y-m-d",time()).".log";
	file_put_contents($logname, "\r\n".date("Y-m-d H:i:s") . ":".$str, FILE_APPEND);
}
function getdb(){
	return new Medoo(['database_type' => 'mysql','database_name' => 'wroot','server' => '127.0.0.1','username' => 'root','password' => 'DRsXT5ZJ6Oi55LPQ','charset' => 'utf8','port' => 3306]);
}

function get_redis($host='127.0.0.1',$port=6379){
    $redis =new Redis();
    $redis->connect($host,$port);
    return $redis;
}
?>