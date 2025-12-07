<?php
session_start();
session_destroy();
header('Location: user_page.php');
exit;
?>