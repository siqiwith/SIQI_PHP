<?php
require('../includes/config.php');
require('../includes/errors.php');
require('../includes/SQ_Error.php');
require('../includes/functions.php');
require('../includes/SQ_DB.php');
?>
<html>
<head>
</head>
<body>
Here's the page
</body>
</html>

<?php
$global_db = new SQ_DB("root", "zaq12wsx", "testDB", "localhost");
//$global_db->timer_start();
//$duration = $global_db->timer_stop();
//$global_db->query("CREATE");
class testA{
	function testMethod(){
		$caller = sq_get_caller();
	}
}

class testB{
	public $pA = null;
	function __construct(){
		$this->pA = new testA();
	}
	function methodB(){
		$this->pA->testMethod();
	}
}

$var2 = new testB();
$var2->methodB();
//sq_get_caller
?>