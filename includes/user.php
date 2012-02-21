<?php
include_once 'bootstrap.php';

function sq_create_user($login, $password, $email){
	global $g_sq_db;
	if(!sq_validate_login($login) || !sq_validate_password($password) || !sq_validate_email($email)){
		return false;
	}
	if($g_sq_db->insert("user", array("login"=>$user_login, "password"=>$password, "email"=>$email))){
		return true;
	};
	return false;
}

function sq_ajax_create_user($login, $password, $email){
	global $g_sq_db;
	$response = array();
	if(sq_create_user($login, $password, $email)){
		$response['data'] = 'User created successfully';
	}else{
		$response['error'] = 'Unable to create the user due to illegal inputs';
	}	
	header('Content-type: text/json');
	echo json_encode($response);
}

function sq_validate_login($login){
	global $g_sq_db;
	//Is empty
	if(empty($login)){
		return false;
	}
	//Length between 4~20
	if(strlen($login) < 4 || strlen($login) > 20){
		return false;
	}
	//Contain only number and alphabatical
	if(preg_match("\W", $login) == 0){
		return false;
	}
	return sq_check_login_exist($login);
}

function sq_check_login_exist($login){
	//If user exists
	$g_sq_db->query($g_sq_db->prepare("SELECT * FROM `user` WHERE user_login = %s", $user_login));
	if($g_sq_db->$num_rows > 0){
		return false;
	}else{
		return true;
	}
}

function sq_validate_password($password){
	//TODO
	return true;
}

function sq_validate_email($email){
	//TODO
	return true;
}
?>