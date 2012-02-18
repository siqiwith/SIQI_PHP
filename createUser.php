<?php
require_once 'loadDB.php';
session_start();
if($_GET["captcha"] == $_SESSION["captcha"]){
	echo("Captcha ok");
	$username = $_GET["username"];
	$password = $_GET["password"];
	if(empty($username) && empty($password)){
		echo("user name is empty");
	}else{
		$sql = "INSERT INTO `testdb`.`user` (`ID`, `user_pass`, `user_login`) VALUES (NULL, '".$username."', '".$password."');";
		$global_db->query($sql);
	}
}else{
	echo("Captcha error!");
}
?>