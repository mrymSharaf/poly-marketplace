<?php
@session_start();

// Must login
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /~u202301956/poly-marketplace/pages/login.php");
        exit;
    }
}

// Single role
function requireRole($role)
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        die("Access denied.");
    }
}

// Multiple roles
function requireAnyRole($roles = [])
{
    if (!in_array($_SESSION['role'], $roles)) {
        die("Access denied.");
    }
}
