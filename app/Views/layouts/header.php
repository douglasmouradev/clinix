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
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/img/clinix-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@500;600;700&family=Noto+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css?v=6">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/panel.css">
</head>
<body class="<?= $user ? 'is-auth' : 'is-guest' ?>">
<?php if ($user): ?>
<header class="topbar">
    <div class="topbar-inner">
    <a class="brand" href="<?= APP_URL ?>/?route=dashboard">
        <span class="brand-logo-wrap">
            <img
                src="<?= APP_URL ?>/img/clinix-logo.png"
                srcset="<?= APP_URL ?>/img/clinix-logo.png 369w, <?= APP_URL ?>/img/clinix-logo@2x.png 738w"
                sizes="120px"
                alt="Clinix"
                class="brand-logo"
                width="369"
                height="257"
                decoding="async"
            >
        </span>
    </a>
        <nav class="menu">
            <a class="<?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=dashboard">Inicio</a>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a class="<?= str_starts_with($currentRoute, 'admin.') ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.users">Administração</a>
                <a class="<?= $currentRoute === 'billing' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=billing">Billing</a>
                <a class="<?= $currentRoute === 'reports.executive' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=reports.executive">Relatórios</a>
                <a class="<?= $currentRoute === 'compliance' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=compliance">Compliance</a>
                <a class="<?= $currentRoute === 'admin.audit' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.audit">Auditoria</a>
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
    </div>
</header>
<?php endif; ?>
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
                <a class="side-link <?= $currentRoute === 'admin.api' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.api">API</a>
                <a class="side-link <?= $currentRoute === 'billing' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=billing">Billing</a>
                <a class="side-link <?= $currentRoute === 'reports.executive' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=reports.executive">Relatórios</a>
                <a class="side-link <?= $currentRoute === 'compliance' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=compliance">Compliance</a>
                <a class="side-link <?= $currentRoute === 'admin.audit' ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.audit">Auditoria</a>
                <a class="side-link <?= str_starts_with($currentRoute, 'admin.2fa') ? 'active' : '' ?>" href="<?= APP_URL ?>/?route=admin.2fa">2FA</a>
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

