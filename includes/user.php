<?php
/*
 * Require modules:
 * 	SQ_DB.php
 * 	$g_sq_db global SQ_DB instantce
 */

function sq_create_user($user_login, $password, $email){
	global $g_sq_db;
	//Escape the parameters
	//SQ_DB::escape($user_login);
	//SQ_DB::escape($password);
	//SQ_DB::escape($email);
	if(!sq_validate_user_login($user_login)){
		return false;
	}
	if(!sq_validate_password($password)){
		return false;
	}
	if(!sq_validate_email($email)){
		return false;
	}
	return $g_sq_db->insert("user", array("user_login"=>$user_login, "password"=>$password, "email"=>$email));
}

function sq_validate_user_login($user_login){
	global $g_sq_db;
	//Is empty
	if(empty($user_login)){
		return new SQ_Error("'SQ_USER_LOGIN_EMPTY", "User login is empty!");
	}
	//Length between 4~20
	if(strlen($user_login) < 4 || strlen($user_login) > 20){
		return new SQ_Error("SQ_USER_LOGIN_ILLEGAL_LENGTH", "The length of user login should between 4 and 20!");
	}
	//Contain only number and alphabatical
	if(preg_match("\W", $user_login) == 0){
		return new SQ_Error("SQ_USER_LOGIN_ILLEGAL_CHARACTER", "The user login contains illegal characters!");
	}
	return sq_check_user_login_exist($user_login);
}

function sq_check_user_login_exist($user_login){
	//If user exists
	$g_sq_db->query($g_sq_db->prepare("SELECT * FROM `user` WHERE user_login = %s", $user_login));
	if($g_sq_db->$num_rows > 0){
		return new SQ_Error("SQ_USER_LOGIN_EXIST", "The user login already exist!");
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