<?php
/**
 * MY TAYLOR - Authentication and Role Authorization Helper
 */

require_once __DIR__ . '/../config/config.php';

function getCurrentUser() {
    if (isset($_SESSION['myt_user_id'])) {
        return [
            'id'     => $_SESSION['myt_user_id'],
            'name'   => $_SESSION['myt_user_name'] ?? 'User',
            'email'  => $_SESSION['myt_user_email'] ?? '',
            'mobile' => $_SESSION['myt_user_mobile'] ?? '',
            'role'   => $_SESSION['myt_user_role'] ?? 'customer'
        ];
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['myt_user_id']);
}

function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        $target = $redirectUrl ?: (APP_URL . '/login');
        header("Location: " . $target);
        exit;
    }
}

function requireRole($roles, $redirectUrl = null) {
    requireLogin($redirectUrl);
    $currentUser = getCurrentUser();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array($currentUser['role'], $roles)) {
        // Forbidden or redirect to their appropriate portal
        if ($currentUser['role'] === 'customer') {
            header("Location: " . APP_URL . "/dashboard");
        } else {
            header("Location: " . APP_URL . "/portal/login?msg=unauthorized");
        }
        exit;
    }
}

function loginUser($user) {
    $_SESSION['myt_user_id']     = $user['id'];
    $_SESSION['myt_user_name']   = $user['name'];
    $_SESSION['myt_user_email']  = $user['email'];
    $_SESSION['myt_user_mobile'] = $user['mobile'];
    $_SESSION['myt_user_role']   = $user['role'];
}

function logoutUser() {
    unset($_SESSION['myt_user_id']);
    unset($_SESSION['myt_user_name']);
    unset($_SESSION['myt_user_email']);
    unset($_SESSION['myt_user_mobile']);
    unset($_SESSION['myt_user_role']);
    session_destroy();
}
