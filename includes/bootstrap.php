<?php
include_once 'config.php';
include_once 'errors.php';
include_once 'SQ_DB.php';

global $g_sq_db;
try{
	$g_sq_db = new SQ_DB(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
}catch(SQ_Exception $e){
	print_error($e);
}
?>