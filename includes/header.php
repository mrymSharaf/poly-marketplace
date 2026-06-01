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
            --pm-navy:   #1a2e6b;
            --pm-blue:   #1e40af;
            --pm-cyan:   #00c4d6;
            --pm-orange: #f97316;
            --pm-accent: #2563eb;
            --pm-light:  #f0f7ff;
        }
        body { background-color: #f4f7fb; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }

        /* ── Navbar ── */
        .navbar-pm {
            background: linear-gradient(90deg, #0d1a46 0%, #1a2e6b 60%, #1e3a8a 100%);
            border-bottom: 2px solid var(--pm-cyan);
            box-shadow: 0 2px 16px rgba(0,0,0,.25);
        }
        .navbar-pm .navbar-brand { color: #fff; font-weight: 700; font-size: 1.25rem; letter-spacing: -.3px; }
        .navbar-pm .navbar-brand i { color: var(--pm-cyan); }
        .navbar-pm .nav-link { color: rgba(255,255,255,.8); transition: color .2s; }
        .navbar-pm .nav-link:hover { color: #fff; }
        .navbar-pm .navbar-toggler { border-color: rgba(255,255,255,.3); }
        .navbar-pm .navbar-toggler-icon { filter: invert(1); }

        /* ── Accent buttons ── */
        .btn-accent { background-color: var(--pm-accent); color: #fff; border: none; }
        .btn-accent:hover { background-color: var(--pm-blue); color: #fff; }
        .btn-navy { background-color: var(--pm-navy); color: #fff; border: 2px solid var(--pm-navy); }
        .btn-navy:hover { background-color: #0f1e4a; color: #fff; border-color: #0f1e4a; }
        .btn-outline-navy { color: var(--pm-navy); border: 1.5px solid #c5d0e8; background: transparent; }
        .btn-outline-navy:hover { background-color: var(--pm-navy); color: #fff; border-color: var(--pm-navy); }

        /* ── Hero ── */
        .hero-section {
            background: linear-gradient(135deg, #091536 0%, #1a2e6b 45%, #0e6ea8 80%, #00a8bf 100%);
            color: #fff;
            padding: 4.5rem 0 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M0 0h40v40H0zm40 40h40v40H40z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .hero-eyebrow { font-size: .82rem; letter-spacing: .1em; text-transform: uppercase; color: var(--pm-cyan); font-weight: 600; }
        .hero-title { font-size: clamp(2rem, 4.5vw, 3.1rem); font-weight: 800; letter-spacing: -.5px; line-height: 1.15; }
        .hero-subtitle { font-size: 1.05rem; color: rgba(255,255,255,.72); max-width: 500px; margin: 0 auto; }
        .hero-search .form-control { border-radius: 50px 0 0 50px; border: none; padding: .75rem 1.5rem; font-size: 1rem; box-shadow: none; }
        .hero-search .hero-search-btn { background-color: var(--pm-orange); color: #fff; border: none; border-radius: 0 50px 50px 0; padding: .75rem 1.6rem; font-weight: 600; }
        .hero-search .hero-search-btn:hover { background-color: #ea6c0a; color: #fff; }
        .stat-number { font-size: 1.75rem; font-weight: 800; color: #fff; line-height: 1; }
        .stat-label  { font-size: .72rem; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }
        .stat-divider { width: 1px; background: rgba(255,255,255,.2); }

        /* ── Category bar ── */
        .category-bar { position: sticky; top: 0; z-index: 99; border-bottom: 1px solid #e5eaf4; }
        .category-pill { border-radius: 50px !important; font-size: .8rem !important; padding: .28rem .95rem !important; transition: all .15s ease; }

        /* ── Listing cards ── */
        .listing-card { border: none !important; border-radius: 14px !important; overflow: hidden; box-shadow: 0 2px 10px rgba(26,46,107,.07); transition: transform .22s ease, box-shadow .22s ease; }
        .listing-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(26,46,107,.16); }
        .listing-card:hover .listing-img { transform: scale(1.04); }
        .listing-img { transition: transform .35s ease; }
        .listing-img-wrap { height: 210px; overflow: hidden; }
        .card-img-placeholder { background: linear-gradient(135deg, #dce6f5, #c3d2ed); height: 210px; display: flex; align-items: center; justify-content: center; }
        .category-badge { background-color: rgba(0,196,214,.12); color: #0882a0; font-weight: 500; border-radius: 50px; font-size: .73rem; padding: .25rem .7rem; }
        .creator-chip { font-size: .72rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
        .price-tag { font-weight: 800; color: var(--pm-navy); font-size: 1.05rem; }
        .btn-view { background-color: var(--pm-orange); color: #fff; border: none; border-radius: 50px; font-size: .8rem; padding: .32rem 1rem; font-weight: 600; transition: background .15s; }
        .btn-view:hover { background-color: #ea6c0a; color: #fff; }
        .text-navy { color: var(--pm-navy) !important; }
        .card-listing-title { font-size: .93rem; color: var(--pm-navy); }
        .star-icon    { font-size: .7rem; }
        .star-icon-lg { font-size: 1rem; }
        .star-filled  { color: var(--pm-orange); }
        .star-empty   { color: #cbd5e1; }
        .icon-empty-state { color: var(--pm-cyan); opacity: .35; }
        .avatar-circle { width: 38px; height: 38px; font-size: .85rem; flex-shrink: 0; border-radius: 50%; background-color: var(--pm-navy); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; }

        /* ── Auth pages ── */
        .auth-bg {
            background: linear-gradient(135deg, #091536 0%, #1a2e6b 45%, #0e6ea8 80%, #00a8bf 100%);
            position: relative;
        }
        .auth-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M0 0h40v40H0zm40 40h40v40H40z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .auth-card { border: none; border-radius: 16px; box-shadow: 0 16px 48px rgba(0,0,0,.25); position: relative; z-index: 1; }
        .auth-icon-wrap { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--pm-navy), var(--pm-cyan)); display: flex; align-items: center; justify-content: center; margin: 0 auto; }

        /* ── Stat cards (dashboards) ── */
        .stat-card { border: none !important; border-radius: 12px !important; box-shadow: 0 2px 10px rgba(26,46,107,.07); }
        .stat-card-navy   { border-left: 4px solid var(--pm-navy) !important; }
        .stat-card-green  { border-left: 4px solid #22c55e !important; }
        .stat-card-orange { border-left: 4px solid var(--pm-orange) !important; }
        .stat-card-cyan   { border-left: 4px solid var(--pm-cyan) !important; }
        .stat-card-gray   { border-left: 4px solid #94a3b8 !important; }

        /* ── Admin / Creator navigation tabs ── */
        .section-nav .btn { border-radius: 8px !important; font-size: .83rem !important; padding: .3rem .9rem !important; }

        /* ── Tables ── */
        .pm-table-card { border: none !important; border-radius: 12px !important; box-shadow: 0 2px 10px rgba(26,46,107,.07); overflow: hidden; }

        /* ── Sticky-footer layout ── */
        main, .page-content { flex-grow: 1; }
        .page-content { padding-top: 2rem; padding-bottom: 2rem; }
        footer { margin-top: auto; }

        /* ── Pagination ── */
        .pagination .page-link {
            color: var(--pm-navy);
            border-color: #c5d0e8;
            font-size: .875rem;
            padding: .38rem .72rem;
            border-radius: 8px !important;
            transition: background .15s, color .15s, border-color .15s;
        }
        .pagination .page-item + .page-item { margin-left: 3px; }
        .pagination .page-item.active .page-link {
            background-color: var(--pm-navy);
            border-color: var(--pm-navy);
            color: #fff;
            font-weight: 600;
        }
        .pagination .page-link:hover,
        .pagination .page-link:focus {
            background-color: var(--pm-light);
            color: var(--pm-blue);
            border-color: var(--pm-cyan);
            box-shadow: none;
        }
        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background-color: #f8f9fa;
            border-color: #dee2e6;
            pointer-events: none;
        }

        /* ── Footer ── */
        .footer-pm { background-color: var(--pm-navy); }
        .footer-heading { letter-spacing: .05em; }
        .footer-link { color: rgba(255,255,255,.6); text-decoration: none; transition: color .15s; display: inline-block; margin-bottom: .25rem; }
        .footer-link:hover { color: var(--pm-cyan); }

        /* ── Legacy ── */
        .card-pm { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(13,27,75,.08); }
        .badge-published { background-color: #22c55e; }
        .badge-draft     { background-color: #94a3b8; }
        .page-header { background-color: var(--pm-navy); color: #fff; padding: 2rem 0; margin-bottom: 0; }
        .page-header .breadcrumb-item a { text-decoration: none; color: rgba(255,255,255,.6); }
        .page-header .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.4); }
        .section-nav { padding-top: 0.6rem !important; margin-bottom: 1rem !important; }
    </style>
</head>
<body>
