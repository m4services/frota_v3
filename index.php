<?php
// Redirect to dashboard if logged in, otherwise to login
require_once 'config/session.php';

if (isLoggedIn()) {
    header('Location: /frota/dashboard.php');
} else {
    header('Location: /frota/login.php');
}
exit;
?>