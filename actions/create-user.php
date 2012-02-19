<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../includes/SQ_Error.php';
include_once '../includes/SQ_DB.php';
include_once '../includes/user.php';

global $g_sq_db;
$g_sq_db = new SQ_DB("root", "zaq12wsx", "testDB", "localhost");

$user_login = $_GET["user_login"];
$password = $_GET["password"];
$email = $_GET["email"];

$result = sq_create_user($user_login, $password, $email);
if($result){
	print("user created!");
}else{
	print("failed to create user");
}
?>