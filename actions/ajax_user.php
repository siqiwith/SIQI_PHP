<?php
include_once '../includes/bootstrap.php';
include_once '../includes/user.php';

$method = $_SERVER['REQUEST_METHOD'];

function user_do_post(){
	$login = $_POST['login'];
	$password = $_POST['password'];
	$email = $_POST['email'];
	sq_ajax_create_user($login, $password, $email);
}

switch($method){
	case 'PUT':
		//user_do_put($request);
		break;
	case 'POST':
		user_do_post();
		break;
	case 'GET':
		//user_do_get($request);
		break;
	case 'DELETE':
		//user_do_delete($request);
		break;
}
?>