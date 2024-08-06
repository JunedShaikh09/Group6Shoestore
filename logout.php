<?php
if (!isset($_SESSION)) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page or home page
header('Location: sign-in.php');
exit();
