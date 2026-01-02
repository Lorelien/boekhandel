<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/User.php'; 

$db = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

// Alleen admins mogen verder
if (!$currentUser || !$currentUser->isAdmin()) {
    header('Location: login.php');
    exit;
}
