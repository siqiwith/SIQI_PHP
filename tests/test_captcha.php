<?php
require('../includes/SQ_Captcha.php');
header("Content-type:image/png");
$g_captcha = new SQ_Captcha();
$g_captcha->generate_captcha();
$g_captcha->output_captcha_image();
?>

