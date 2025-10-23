<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name']
    ];
}

// Require authentication
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /php-version/login.php');
        exit;
    }
}

// Set user session
function setUserSession($userId, $email, $name) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
}

// Clear user session
function clearUserSession() {
    session_unset();
    session_destroy();
}
?>
