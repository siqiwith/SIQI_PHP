<?php
require('../includes/SQ_Captcha.php');
header("Content-type:image/png");
$g_captcha = new SQ_Captcha();
$g_captcha->generate_captcha();
session_start();
$_SESSION["captcha"] = $g_captcha->captcha_string;
$g_captcha->output_captcha_image();
?>
