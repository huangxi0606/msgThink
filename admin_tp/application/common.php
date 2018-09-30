<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function delredis($name){
	if($name!=''){
		$xyredis=get_redis();
		$count=$xyredis->DEL($name);
		$xyredis->close();
	}
}

function openmachine($val){
	$xyredis=get_redis();
	if($val!=''){
		$xyredis->SET('openmachine',$val);
	}
	$status=$xyredis->GET('openmachine');
	$xyredis->close();
	return $status;
}
function opendevice($val){
	$xyredis=get_redis();
	if($val!=''){
		$xyredis->SET('opendevice',$val);
	}
	$status=$xyredis->GET('opendevice');
	$xyredis->close();
	return $status;
}
function opendevicelist($val){
	$xyredis=get_redis();
	if($val!=''){
		$xyredis->SET('opendevicelist',$val);
	}
	$list=$xyredis->GET('opendevicelist');
	$xyredis->close();
	return $list;
}
function delMsgTask($id){
	if($id!=''){
		$xyredis=get_redis();
		$count=$xyredis->DEL('MsgTaskList_'.$id);
		$count=$xyredis->DEL('MsgTaskMachine_'.$id);
		$count=$xyredis->DEL('SendList_'.$id);
		$count=$xyredis->HDEL('MsgTaskList',$id);
		$xyredis->close();
	}
}

function enableMsgTask($id,$status){
	if($id!=''){
		$xyredis=get_redis();
		$xyredis->HSET('MsgTaskList',$id,$status);
		$xyredis->HSET('MsgTaskList_'.$id,'status',$status);
		$xyredis->close();
	}
	
}

function getMsgInfo($id){
	$xyredis=get_redis();
	$MsgInfo = $xyredis->HGETALL('MsgTaskList_'.$id);
	if(count($MsgInfo)<1){
		$MsgInfo = array('sendnum'=>0,'senderrnum'=>0,'sendlistnum'=>0);
	}
	$xyredis->close();
	return $MsgInfo;
}
function getsendnum($id){ // 动态获取Redis在线计数
	$xyredis=get_redis();
	$count=$xyredis->HGET('MsgTaskList_'.$id,'sendnum');
	$count=$count*intval(config('msgtask_sendnum'));
	$xyredis->close();
	return $count;
}
function getsenderrnum($id){ // 动态获取Redis在线计数
	$xyredis=get_redis();
	$count=$xyredis->HGET('MsgTaskList_'.$id,'senderrnum');
	$count=$count;
	$xyredis->close();
	return $count;
}
function getmachinecount($id){ // 动态获取Redis在线计数
	$xyredis=get_redis();
	$count=$xyredis->Hlen('MsgTaskMachine_'.$id);
	$xyredis->close();
	return $count;
}
function getCount($status,$table){
	$count=Db($table)->where('status',$status)->count();
	return $count;
}

function getTime($time){
	return date('Y-m-d H:i:s', $time);
}

function page_tp($page,$sort){
	$sortstr='?';
	if($sort['sort']){
		$sortstr='?_sort%5Bcolumn%5D='.$sort['column'].'&_sort%5Btype%5D='.$sort['type'].'&';
	}
	return str_replace(array('<ul class="pagination">','<li class="disabled"><span>','<li class="active"><span>','<li>','<a','?'),array('<ul class="pagination pagination-sm no-margin pull-right">','<li class="page-item disabled"><span class="page-link">','<li class="page-item active"><span class="page-link">','<li class="page-item">','<a class="page-link"',$sortstr),$page);
}

function get_redis($host='127.0.0.1',$port=6379){
	$redis = new Redis();  
	$redis->connect($host,$port);
	//$redis->auth('myhotmoney');  
	return $redis;
}
function getToday(){ // 动态获取当日Redis在线计数
    $xyredis=get_redis();
    $data['todayDe'] =$xyredis->sCard("todaydevice");
    $data['todayAc'] =$xyredis->sCard("todayaccount");
    $data['sucnum'] = $xyredis->HGET('todayMsg','succnum');
    $data['errnum'] = $xyredis->HGET('todayMsg','errnum');
    $data['uptime'] = time();
    $xyredis->close();
    return $data;
}

/**
 * 修改config的函数
 * @param $arr1 配置前缀
 * @param $arr2 数据变量
 * @return bool 返回状态
 */
function setconfig($pat, $rep)
{
    /**
     * 原理就是 打开config配置文件 然后使用正则查找替换 然后在保存文件.
     * 传递的参数为2个数组 前面的为配置 后面的为数值.  正则的匹配为单引号  如果你的是分号 请自行修改为分号
     * $pat[0] = 参数前缀;  例:   default_return_type
       $rep[0] = 要替换的内容;    例:  json
     */
    if (is_array($pat) and is_array($rep)) {
        for ($i = 0; $i < count($pat); $i++) {
            $pats[$i] = '/\'' . $pat[$i] . '\'(.*?),/';
            $reps[$i] = "'". $pat[$i]. "'". "=>" . "'".$rep[$i] ."',";
        }
        $fileurl = APP_PATH . "config.php";
        $string = file_get_contents($fileurl); //加载配置文件
        $string = preg_replace($pats, $reps, $string); // 正则查找然后替换
        file_put_contents($fileurl, $string); // 写入配置文件
        return true;
    } else {
        return flase;
    }
}