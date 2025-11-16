<?php
require_once 'config.php';

session_start();

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard if logged in
    redirect('dashboard.php');
} else {
    // Redirect to login page if not logged in
    redirect('login.php');
}