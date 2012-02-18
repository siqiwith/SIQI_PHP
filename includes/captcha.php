<?php

$alpha = "abcdefghijkmnopqrstuvwxyz"; //验证码内容1:字母
$number = "023456789"; //验证码内容2:数字

function sq_generate_auth_num(){
	
	$_Session["authNum"] = 123; 
}
?>