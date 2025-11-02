<?php
session_start();
session_destroy();
header("Location: ../LoginPage/HalamanLogin.html");
exit;
?>
