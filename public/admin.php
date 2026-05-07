<?php
require_once __DIR__ . '/../includes/header.php';

if (!$isAdmin) {
    header("Location: index.php");
    exit;
}
?>
