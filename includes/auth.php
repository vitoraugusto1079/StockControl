<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: includes/auth.php - Controle de Sessão e Autenticação
// ==============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se há um usuário autenticado na sessão
 * @return bool
 */
function usuarioLogado(): bool {
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

/**
 * Retorna os dados do usuário atualmente logado
 * @return array|null
 */
function usuarioAtual(): ?array {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Verifica se o usuário autenticado possui perfil de administrador
 * @return bool
 */
function isAdmin(): bool {
    $usuario = usuarioAtual();
    return $usuario !== null && isset($usuario['perfil']) && $usuario['perfil'] === 'admin';
}

/**
 * Obriga o usuário a estar logado para acessar a página
 */
function exigirLogin(): void {
    if (!usuarioLogado()) {
        $_SESSION['flash_error'] = "Por favor, faça login para acessar o sistema.";
        header("Location: index.php");
        exit;
    }
}

/**
 * Obriga o usuário a ser administrador para executar a ação
 */
function exigirAdmin(): void {
    exigirLogin();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = "Acesso negado. Apenas administradores podem realizar esta ação.";
        header("Location: produtos.php");
        exit;
    }
}

/**
 * Define uma mensagem flash (temporária) para a próxima requisição
 */
function setFlash(string $tipo, string $mensagem): void {
    $_SESSION['flash_' . $tipo] = $mensagem;
}

/**
 * Recupera e consome uma mensagem flash da sessão
 */
function getFlash(string $tipo): ?string {
    $chave = 'flash_' . $tipo;
    if (isset($_SESSION[$chave])) {
        $msg = $_SESSION[$chave];
        unset($_SESSION[$chave]);
        return $msg;
    }
    return null;
}
