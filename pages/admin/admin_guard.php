<?php
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
requireRole('admin');
?>