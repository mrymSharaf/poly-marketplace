<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/~u202301956/poly-marketplace');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>PolyMarketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --pm-navy:   #0d1b4b;
            --pm-blue:   #1e3a8a;
            --pm-accent: #3b82f6;
            --pm-light:  #f0f4ff;
        }
        body { background-color: var(--pm-light); font-family: 'Segoe UI', system-ui, sans-serif; }

        /* Navbar */
        .navbar-pm { background-color: var(--pm-navy); }
        .navbar-pm .navbar-brand { color: #fff; font-weight: 700; font-size: 1.2rem; }
        .navbar-pm .nav-link { color: rgba(255,255,255,.8); transition: color .2s; }
        .navbar-pm .nav-link:hover { color: #fff; }
        .navbar-pm .navbar-toggler { border-color: rgba(255,255,255,.3); }
        .navbar-pm .navbar-toggler-icon { filter: invert(1); }

        /* Buttons */
        .btn-accent { background-color: var(--pm-accent); color: #fff; border: none; }
        .btn-accent:hover { background-color: var(--pm-blue); color: #fff; }

        /* Cards */
        .card-pm { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(13,27,75,.08); }

        /* Status badges */
        .badge-published { background-color: #22c55e; }
        .badge-draft     { background-color: #94a3b8; }

        /* Page header banner */
        .page-header { background-color: var(--pm-navy); color: #fff; padding: 2rem 0; margin-bottom: 2rem; }
        .page-header .breadcrumb-item a { text-decoration: none; color: rgba(255,255,255,.6); }
        .page-header .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.4); }
    </style>
</head>
<body>
