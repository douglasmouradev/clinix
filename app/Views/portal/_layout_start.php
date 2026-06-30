<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> — Portal</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/tokens.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css?v=10">
</head>
<body class="is-guest is-portal">
<main id="main-content" class="portal-main<?= !empty($portalWide) ? ' portal-main--wide' : '' ?>">
