<?php
require_once 'config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    log_audit('LOGOUT', 'users', $_SESSION['user_id']);
}
session_destroy();
header("location: login.php");
exit;
?>
