<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: includes/header.php - Topo e Barra de Navegação
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

exigirLogin();
$usuarioLogado = usuarioAtual();
$flashSuccess = getFlash('success');
$flashError = getFlash('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - StockControl' : 'StockControl - Controle de Estoque' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Barra de navegação com logomarca, menu Novo Produto (somente admin) e botão de logout -->
    <header class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="produtos.php" class="brand-link">
                    <span class="brand-icon">📦</span>
                    <span class="brand-name">StockControl</span>
                </a>
            </div>

            <nav class="nav-menu">
                <a href="produtos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'produtos.php' ? 'active' : '' ?>">Produtos</a>
                
                <?php if (isAdmin()): ?>
                    <!-- Menu 'Novo produto' visível apenas para administrador -->
                    <a href="produto_novo.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'produto_novo.php' ? 'active' : '' ?>">+ Novo Produto</a>
                <?php endif; ?>

                <a href="movimentacoes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'movimentacoes.php' ? 'active' : '' ?>">Movimentações</a>
            </nav>

            <div class="nav-user">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($usuarioLogado['nome']) ?></span>
                    <span class="user-role-badge <?= isAdmin() ? 'badge-admin' : 'badge-user' ?>">
                        <?= isAdmin() ? 'Administrador' : 'Operador' ?>
                    </span>
                </div>
                <!-- Botão de logout -->
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="content-container">
            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success" role="alert">
                    <span>✅</span> <?= htmlspecialchars($flashSuccess) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger" role="alert">
                    <span>⚠️</span> <?= htmlspecialchars($flashError) ?>
                </div>
            <?php endif; ?>
