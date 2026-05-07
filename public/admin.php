<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../includes/header.php';
?>
