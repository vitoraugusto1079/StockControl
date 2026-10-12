<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: movimentacoes.php - Registro de Entradas e Saídas de Estoque
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();
$pdo = getConnection();
$usuarioLogado = usuarioAtual();

$produtoFiltroId = filter_input(INPUT_GET, 'produto_id', FILTER_VALIDATE_INT);
$erro = null;

// PROCESSAR REGISTRO DE MOVIMENTAÇÃO (Entrada ou Saída)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produtoId  = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
    $tipo       = trim($_POST['tipo'] ?? '');
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $observacao = trim($_POST['observacao'] ?? '');

    if (!$produtoId || !in_array($tipo, ['entrada', 'saida']) || $quantidade === false || $quantidade <= 0) {
        $erro = "Por favor, preencha todos os campos obrigatórios corretamente com quantidade maior que zero.";
    } else {
        try {
            $pdo->beginTransaction();

            // Buscar dados atuais do produto para checagem e bloqueio concorrente
            $stmtProd = $pdo->prepare("SELECT id, nome, quantidade FROM produtos WHERE id = :id FOR UPDATE");
            $stmtProd->execute([':id' => $produtoId]);
            $prod = $stmtProd->fetch();

            if (!$prod) {
                throw new Exception("Produto não encontrado.");
            }

            // Se for saída, verificar se há saldo suficiente
            if ($tipo === 'saida' && $prod['quantidade'] < $quantidade) {
                throw new Exception("Saldo insuficiente! O produto '{$prod['nome']}' possui apenas {$prod['quantidade']} unidade(s) em estoque.");
            }

            // Calcular nova quantidade
            $novaQuantidade = ($tipo === 'entrada') 
                ? $prod['quantidade'] + $quantidade 
                : $prod['quantidade'] - $quantidade;

            // 1. Atualiza estoque na tabela de produtos
            $stmtUp = $pdo->prepare("UPDATE produtos SET quantidade = :nova_qtd WHERE id = :id");
            $stmtUp->execute([
                ':nova_qtd' => $novaQuantidade,
                ':id'       => $produtoId,
            ]);

            // 2. Insere registro no histórico de movimentações com responsável e data
            $stmtMov = $pdo->prepare("
                INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, observacao, data_movimentacao)
                VALUES (:produto_id, :usuario_id, :tipo, :quantidade, :observacao, NOW())
            ");
            $stmtMov->execute([
                ':produto_id' => $produtoId,
                ':usuario_id' => $usuarioLogado['id'],
                ':tipo'       => $tipo,
                ':quantidade' => $quantidade,
                ':observacao' => !empty($observacao) ? $observacao : null,
            ]);

            $pdo->commit();

            setFlash('success', "Movimentação de " . strtoupper($tipo) . " de {$quantidade} unidade(s) realizada com sucesso!");
            header("Location: movimentacoes.php" . ($produtoFiltroId ? "?produto_id=" . $produtoFiltroId : ""));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = $e->getMessage();
        }
    }
}

// Buscar produtos para o select
$produtosList = $pdo->query("SELECT id, nome, quantidade FROM produtos WHERE status = 'ativo' ORDER BY nome ASC")->fetchAll();

// Buscar histórico de movimentações
$sqlMov = "
    SELECT m.id, m.tipo, m.quantidade, m.observacao, m.data_movimentacao,
           p.nome AS produto_nome, p.imagem AS produto_imagem,
           u.nome AS usuario_nome, u.perfil AS usuario_perfil
    FROM movimentacoes m
    INNER JOIN produtos p ON m.produto_id = p.id
    INNER JOIN usuarios u ON m.usuario_id = u.id
";

if ($produtoFiltroId) {
    $sqlMov .= " WHERE m.produto_id = :prod_id ";
}
$sqlMov .= " ORDER BY m.data_movimentacao DESC";

$stmtHistorico = $pdo->prepare($sqlMov);
if ($produtoFiltroId) {
    $stmtHistorico->execute([':prod_id' => $produtoFiltroId]);
} else {
    $stmtHistorico->execute();
}
$movimentacoes = $stmtHistorico->fetchAll();

$pageTitle = "Movimentações de Estoque";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>Histórico e Registro de Movimentações</h2>
        <p class="text-muted">Registre entradas, saídas e consulte o histórico detalhado de operações.</p>
    </div>
    <a href="produtos.php" class="btn btn-secondary">
        <i data-lucide="arrow-left" class="icon-btn"></i> Voltar para Produtos
    </a>
</div>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger" role="alert">
        <i data-lucide="alert-circle" class="icon-alert"></i> <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="movement-layout">
    <!-- FORMULÁRIO DE REGISTRO DE ENTRADA / SAÍDA -->
    <div class="form-card movement-form-card">
        <h3>Nova Movimentação</h3>
        <p class="text-muted">Ajuste o estoque físico de um item ativo.</p>

        <form action="movimentacoes.php<?= $produtoFiltroId ? '?produto_id=' . $produtoFiltroId : '' ?>" method="POST">
            <div class="form-group">
                <label for="produto_id">Produto <span class="required">*</span></label>
                <select name="produto_id" id="produto_id" required>
                    <option value="">Selecione o produto...</option>
                    <?php foreach ($produtosList as $prod): ?>
                        <option value="<?= (int)$prod['id'] ?>" <?= ($produtoFiltroId === (int)$prod['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prod['nome']) ?> (Estoque atual: <?= (int)$prod['quantidade'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group col">
                    <label for="tipo">Tipo de Operação <span class="required">*</span></label>
                    <select name="tipo" id="tipo" required>
                        <option value="entrada">🟢 Entrada (Adicionar ao estoque)</option>
                        <option value="saida">🔴 Saída (Retirar do estoque)</option>
                    </select>
                </div>

                <div class="form-group col">
                    <label for="quantidade">Quantidade <span class="required">*</span></label>
                    <input type="number" id="quantidade" name="quantidade" min="1" step="1" placeholder="1" required>
                </div>
            </div>

            <div class="form-group">
                <label for="observacao">Motivo / Observação</label>
                <input type="text" id="observacao" name="observacao" placeholder="Ex: NF 1234, Venda balcão, Devolução, etc.">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Confirmar Movimentação</button>
            </div>
        </form>
    </div>

    <!-- TABELA DE HISTÓRICO DE MOVIMENTAÇÕES -->
    <div class="movement-history-card">
        <div class="history-header">
            <h3>Histórico de Movimentações (<?= count($movimentacoes) ?>)</h3>
            <?php if ($produtoFiltroId): ?>
                <a href="movimentacoes.php" class="btn btn-outline-secondary btn-sm">Ver Todos os Produtos</a>
            <?php endif; ?>
        </div>

        <?php if (empty($movimentacoes)): ?>
            <div class="empty-state-small">
                <p>Nenhuma movimentação registrada.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data & Hora</th>
                            <th>Tipo</th>
                            <th>Produto</th>
                            <th>Qtd</th>
                            <th>Responsável</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentacoes as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($m['data_movimentacao'])) ?></td>
                                <td>
                                    <?php if ($m['tipo'] === 'entrada'): ?>
                                        <span class="badge-movement badge-in"><i data-lucide="arrow-down-left" class="icon-inline"></i> ENTRADA</span>
                                    <?php else: ?>
                                        <span class="badge-movement badge-out"><i data-lucide="arrow-up-right" class="icon-inline"></i> SAÍDA</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($m['produto_nome']) ?></strong></td>
                                <td><?= (int)$m['quantidade'] ?> un.</td>
                                <td>
                                    <span><?= htmlspecialchars($m['usuario_nome']) ?></span>
                                    <small class="text-muted">(<?= $m['usuario_perfil'] ?>)</small>
                                </td>
                                <td><?= htmlspecialchars($m['observacao'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
