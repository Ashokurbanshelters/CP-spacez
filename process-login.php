<?php
session_start();
require_once 'auth-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Load users
$usersFile = 'data/users.json';
$users = [];

if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
}

// Find user
$userFound = false;
$userData = null;

foreach ($users as $user) {
    if ($user['username'] === $username) {
        $userFound = true;
        $userData = $user;
        break;
    }
}

if ($userFound && password_verify($password, $userData['password'])) {
    // Successful login
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['user_role'] = $userData['role'];
    $_SESSION['user_name'] = $userData['name'];
    $_SESSION['login_time'] = time();
    
    // Update last login
    foreach ($users as &$user) {
        if ($user['id'] === $userData['id']) {
            $user['last_login'] = date('Y-m-d H:i:s');
            break;
        }
    }
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    
    // Remember me functionality
    if (isset($_POST['remember'])) {
        setcookie('remember_user', $username, time() + (30 * 24 * 60 * 60), '/');
    }
    
    header('Location: dashboard.php');
    exit;
} else {
    // Failed login
    header('Location: login.php?error=invalid');
    exit;
}
?>