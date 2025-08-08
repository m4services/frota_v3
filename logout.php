<?php
require_once 'config/session.php';
session_destroy();
header('Location: /frota/login.php');
exit;
?>