<?php

use App\Core\Auth;

$user = Auth::user();
$currentRoute = $_GET['route'] ?? 'dashboard';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@500;600;700&family=Noto+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --bg: #f6fafb;
            --card: #ffffff;
            --ink: #1f3b4d;
            --muted: #5f7787;
            --line: #d7e4ea;
            --brand: #3f7f95;
            --brand-dark: #316a7d;
            --brand-soft: #eef5f8;
            --accent: #4f8a72;
            --header: #244b5a;
            --success: #3f7d67;
            --danger-bg: #fee2e2;
            --danger-ink: #991b1b;
            --shadow: 0 10px 24px rgba(31, 59, 77, .08);
            --shadow-strong: 0 16px 34px rgba(31, 59, 77, .14);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Noto Sans", "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); background: radial-gradient(circle at top right, #eef6f8 0, #f6fafb 45%); }
        body::before {
            content: ""; position: fixed; inset: 0; z-index: -1;
            background:
                radial-gradient(720px 350px at 92% -8%, rgba(63,127,149,.14), transparent 60%),
                radial-gradient(540px 320px at -6% 18%, rgba(130,169,184,.10), transparent 68%);
            pointer-events: none;
        }
        h1, h2, h3 { margin: 0 0 8px; letter-spacing: -.02em; font-family: "Figtree", "Noto Sans", sans-serif; }
        p { margin: 0 0 10px; color: var(--muted); line-height: 1.55; }
        .topbar {
            position: sticky; top: 0; z-index: 20;
            background: linear-gradient(90deg, #244b5a, #316a7d);
            border-bottom: 1px solid rgba(255,255,255,.08);
            backdrop-filter: blur(6px);
        }
        .topbar-inner {
            max-width: 1280px; margin: 0 auto; padding: 14px 18px;
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
        }
        .brand { color: #fff; font-weight: 700; text-decoration: none; letter-spacing: .02em; font-size: 18px; }
        .menu { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .menu a { color: #e5f2f6; text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 12px; border-radius: 10px; transition: .18s ease; }
        .menu a:hover { background: rgba(187,213,223,.25); color: #fff; }
        .menu a.active { background: rgba(187,213,223,.36); color: #fff; box-shadow: inset 0 0 0 1px rgba(229,242,246,.36); }
        .user-chip {
            background: rgba(94,134,150,.24); color: #f1f7f9; padding: 7px 11px;
            border: 1px solid rgba(217,232,238,.34); border-radius: 999px; font-size: 13px;
        }
        .app-shell { max-width: 1280px; margin: 24px auto 34px; padding: 0 18px; display: grid; grid-template-columns: 240px 1fr; gap: 18px; }
        .sidebar {
            background: #fff; border: 1px solid #d9edf3; border-radius: 16px; box-shadow: var(--shadow);
            padding: 14px; height: fit-content; position: sticky; top: 84px;
        }
        .sidebar-user {
            margin-bottom: 12px; background: linear-gradient(135deg, #ecfeff, #f0fdfa);
            border: 1px solid #bdebf6; border-radius: 12px; padding: 11px 12px;
        }
        .sidebar-user strong { display: block; font-size: 14px; }
        .sidebar-user span { color: #475569; font-size: 12px; }
        .side-title { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin: 4px 6px 10px; }
        .side-menu { display: grid; gap: 6px; }
        .side-link {
            text-decoration: none; color: #164e63; font-weight: 600; font-size: 14px;
            border: 1px solid transparent; border-radius: 10px; padding: 10px 12px; transition: .18s ease;
        }
        .side-link:hover { background: #f4f9fb; border-color: #c8dde6; color: #316a7d; }
        .side-link.active { background: #eef5f8; border-color: #b6cfda; color: #316a7d; }
        .content { min-width: 0; }
        .card {
            background: var(--card); border: 1px solid #d8eaf1; border-radius: 16px;
            padding: 20px; box-shadow: var(--shadow); margin-bottom: 16px;
        }
        .card.soft { background: linear-gradient(135deg, #ffffff, #f5fafc); }
        .card-title { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .muted { color: var(--muted); font-size: 14px; }
        .grid { display: grid; gap: 14px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .stat { background: #f8fbfc; border: 1px solid var(--line); border-radius: 14px; padding: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #255468; }
        input, select, textarea {
            width: 100%; min-height: 44px; padding: 11px 12px; border-radius: 10px; border: 1px solid #b8d9e4;
            outline: none; background: #fff; color: #0f172a; transition: border-color .16s, box-shadow .16s;
        }
        input:focus, select:focus, textarea:focus { border-color: #3f7f95; box-shadow: 0 0 0 3px rgba(63,127,149,.2); }
        textarea { min-height: 110px; resize: vertical; }
        .btn, button {
            width: 100%; padding: 10px 14px; border-radius: 10px; border: 0; cursor: pointer;
            background: linear-gradient(180deg, #4d8ba0, #3f7f95); color: #fff; font-weight: 700; font-size: 14px;
            transition: transform .12s ease, box-shadow .14s ease;
        }
        .btn:hover, button:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(63,127,149,.24); }
        .btn:focus-visible, button:focus-visible, .side-link:focus-visible, .menu a:focus-visible, a.link:focus-visible {
            outline: 3px solid rgba(63,127,149,.32);
            outline-offset: 2px;
        }
        .btn.secondary { background: #fff; color: #316a7d; border: 1px solid #b6cfda; }
        .btn.small { width: auto; padding: 8px 12px; font-size: 13px; }
        .table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        thead th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #5f7787; background: #f3f8fa; }
        th, td { padding: 12px; border-bottom: 1px solid #e4f3f8; text-align: left; font-size: 14px; }
        tbody tr:nth-child(even) { background: #fbfdfe; }
        tbody tr:hover { background: #f2f7f9; }
        .error { background: var(--danger-bg); color: var(--danger-ink); padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; border: 1px solid #fecaca; }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; border: 1px solid #bbf7d0; }
        .info { background: #e0f2fe; color: #075985; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; border: 1px solid #bae6fd; }
        .pill { display: inline-flex; align-items: center; gap: 6px; background: #e8f1f5; color: #2d6274; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 700; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .actions form { margin: 0; }
        a.link { color: var(--brand-dark); text-decoration: none; font-weight: 700; }
        a.link:hover { text-decoration: underline; }
        .login-shell { min-height: calc(100vh - 110px); display: flex; align-items: center; justify-content: center; }
        .login-wrap { width: 100%; max-width: 980px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: stretch; }
        .login-hero {
            background: linear-gradient(140deg, #244b5a, #316a7d 65%, #82a9b8);
            border-radius: 16px; padding: 28px; color: #e2e8f0; box-shadow: var(--shadow-strong);
            border: 1px solid rgba(255,255,255,.18);
        }
        .login-hero h2 { color: #fff; font-size: 30px; margin-bottom: 6px; }
        .login-hero p { color: #cbd5e1; }
        .hero-list { margin-top: 18px; display: grid; gap: 8px; font-size: 14px; }
        .hero-item { background: rgba(15,23,42,.2); border: 1px solid rgba(191,219,254,.25); border-radius: 10px; padding: 8px 10px; }
        .login-card { width: 100%; max-width: 100%; }
        .timeline-item { padding: 14px 0; border-bottom: 1px solid #e5e7eb; }
        .timeline-item:last-child { border-bottom: 0; padding-bottom: 0; }
        @media (max-width: 820px) {
            .topbar-inner { flex-direction: column; align-items: flex-start; }
            .menu { width: 100%; }
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .login-wrap { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
    <a class="brand" href="<?= APP_URL ?>/?route=dashboard"><?= e(APP_NAME) ?></a>
    <?php if ($user): ?>
        <nav class="menu">
            <a class="<?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=dashboard">Inicio</a>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a class="<?= str_starts_with($currentRoute, 'admin.') ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.users">Administração</a>
                <a class="<?= $currentRoute === 'billing' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=billing">Billing</a>
                <a class="<?= $currentRoute === 'reports.executive' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=reports.executive">Relatórios</a>
                <a class="<?= $currentRoute === 'compliance' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=compliance">Compliance</a>
            <?php endif; ?>
            <a class="<?= str_starts_with($currentRoute, 'patient') || $currentRoute === 'patients' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=patients">Pacientes</a>
            <a class="<?= str_starts_with($currentRoute, 'appointment') || $currentRoute === 'appointments' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=appointments">Agenda</a>
            <a class="<?= $currentRoute === 'queue' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=queue">Fila</a>
            <a class="<?= $currentRoute === 'queue.panel' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=queue.panel">Painel</a>
            <span class="user-chip"><?= e($user['name']) ?> - <?= e(roleLabel($user['role'])) ?></span>
            <form method="post" action="<?= APP_URL ?>/?route=logout">
                <?= csrfInput() ?>
                <button class="btn small">Sair</button>
            </form>
        </nav>
    <?php endif; ?>
    </div>
</header>
<?php if ($user): ?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-user">
            <strong><?= e($user['name']) ?></strong>
            <span><?= e(roleLabel($user['role'])) ?></span>
        </div>
        <div class="side-title">Navegação</div>
        <nav class="side-menu">
            <a class="side-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=dashboard">Painel</a>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a class="side-link <?= str_starts_with($currentRoute, 'admin.') ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.users">Administração</a>
                <a class="side-link <?= $currentRoute === 'admin.clinic' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.clinic">Slug da clínica</a>
                <a class="side-link <?= $currentRoute === 'admin.panel' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.panel">Token do painel</a>
                <a class="side-link <?= $currentRoute === 'billing' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=billing">Billing</a>
                <a class="side-link <?= $currentRoute === 'reports.executive' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=reports.executive">Relatórios</a>
                <a class="side-link <?= $currentRoute === 'compliance' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=compliance">Compliance</a>
            <?php endif; ?>
            <a class="side-link <?= str_starts_with($currentRoute, 'patient') || $currentRoute === 'patients' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=patients">Pacientes</a>
            <a class="side-link <?= str_starts_with($currentRoute, 'appointment') || $currentRoute === 'appointments' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=appointments">Agenda</a>
            <a class="side-link <?= $currentRoute === 'queue' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=queue">Fila</a>
            <a class="side-link <?= $currentRoute === 'queue.panel' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=queue.panel">Painel de chamada</a>
        </nav>
    </aside>
    <main class="content">
<?php else: ?>
<main>
<?php endif; ?>

<?php if (!empty($flash['message'])): ?>
    <div class="<?= e($flash['type'] ?? 'info') ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

