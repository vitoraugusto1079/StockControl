<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: produto_excluir.php - Exclusão de Produto (Admin apenas)
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Apenas administradores podem excluir produtos
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produtoId = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);

    if ($produtoId) {
        try {
            $pdo = getConnection();

            // Atualiza o status para inativo (exclusão lógica) para manter histórico íntegro
            $stmt = $pdo->prepare("UPDATE produtos SET status = 'inativo' WHERE id = :id");
            $stmt->execute([':id' => $produtoId]);

            if ($stmt->rowCount() > 0) {
                setFlash('success', 'Produto excluído com sucesso!');
            } else {
                setFlash('error', 'Produto não encontrado ou já inativo.');
            }
        } catch (Exception $e) {
            setFlash('error', 'Erro ao excluir produto: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Identificador de produto inválido.');
    }
}

header("Location: produtos.php");
exit;
