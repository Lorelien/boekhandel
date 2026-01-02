<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/AuthService.php';

$db = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$currentUser->isAdmin()) {
    header('Location: login.php');
    exit;
}