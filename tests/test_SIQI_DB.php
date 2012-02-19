<?php
require_once('includes/config.php');
require_once('includes/SQ_Error.php');
require_once('includes/functions.php');
require_once('includes/SQ_DB.php');
global $test_db;

function test_new_db(){
	$test_db = new SQ_DB("root", "zaq12wsx", "testDB", "localhost");
}

function test_insert(){
	$test_db->insert("user", array("user_login"=>"userNO1", "user_pass"=>"pass1", "ID"=>1));
}

function test_update(){
	$test_db->update("user", array("user_login"=>"user1"), array("ID"=>1));
}

?>