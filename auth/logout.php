<?php
// Redirect to the routing system's logout handler
session_start();
session_unset();
session_destroy();
header("Location: /../");
exit();
?>
