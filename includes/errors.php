<?php
include_once 'config.php';

define('SQ_DB_CONN_ERROR_CODE', 501);
define('SQ_DB_CONN_ERROR_MESSAGE', 'Cannot establish the DB connection with following info:\n
		dbhost: %s \n dbuser: %s \n');

define('SQ_DB_SELECT_TABLE_ERROR_CODE', 502);
define('SQ_DB_SELECT_TABLE_ERROR_MESSAGE', 'Failed to select the DB with following info:\n
		dbname: %s');

define('SQ_MYSQL_ERROR_CODE', 503);

function print_error($Exception){
	if(SQ_DEBUG){
		print $Exception;
	}else{
		//header('Location: '.SQ_500_URL, true, 500);
		header('Location: '.SQ_500_URL, true);
	}
	exit();
}
?>