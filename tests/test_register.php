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
<form action="../createUser.php">
<label for="uname">user name:</label><input id="uname" name="username"></input><br/>
<label for="pwd">password:</label><input id="pwd" name="password"></input><br/>
<label for="cpc">captcha:</label><img src="../captcha.php"><input id="cpc" name="captcha"></input><br/>
<button type="submit">submit</button>
</form>
</body>
</html>
