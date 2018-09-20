<?php
require  'inc/Medoo.php';

function get_redis($host='127.0.0.1',$port=6379){
	$redis = new Redis();  
	$redis->connect($host,$port);
	//$redis->auth('000');  
	return $redis;
}
function setlog($str){
	file_put_contents('log/'.date("Y-m-d").'.log',$str."\r\n", FILE_APPEND);
}
function setmsg($logname,$str){
	global $runid; 
	$logname.='_'.date("Y-m-d",time()).".log";
	file_put_contents($logname, "\r\n".date("Y-m-d H:i:s") . ":".$str, FILE_APPEND);
    //echo "\r\n".date("Y-m-d H:i:s") . ":".$str;
}
?>