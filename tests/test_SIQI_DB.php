<?php
require_once('../includes/SQ_DB.php');
global $test_db;

function test_new_db(){
	global $test_db;
	$test_db = new SQ_DB("root", "zaq12wsx", "testDB", "localhost");
}

function test_insert(){
	global $test_db;
	$test_db->insert("user", array("login"=>"userNO112", "password"=>"pass1", "id"=>13, "email"=>"fefej@fejife.cn"));
}

function test_update(){
	global $test_db;
	$test_db->update("user", array("login"=>"user13"), array("id"=>13));
}

test_new_db();
test_insert();
test_update();
?>