<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: produtos.php - Listagem de Produtos Ativos
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = "Estoque de Produtos";
require_once __DIR__ . '/includes/header.php';

$pdo = getConnection();

// Requisito 5.3: Mostrar apenas produtos ativos
try {
    $stmt = $pdo->query("
        SELECT id, nome, descricao, quantidade, preco, status, imagem, data_inclusao 
        FROM produtos 
        WHERE status = 'ativo' 
        ORDER BY id DESC
    ");
    $produtos = $stmt->fetchAll();
} catch (Exception $e) {
    $produtos = [];
    $erroListagem = "Erro ao carregar produtos: " . $e->getMessage();
}
?>

<div class="page-heading">
    <div>
        <h2>📦 Controle de Produtos em Estoque</h2>
        <p class="text-muted">Acompanhe itens cadastrados, níveis de estoque e observações.</p>
    </div>
    <?php if (isAdmin()): ?>
        <a href="produto_novo.php" class="btn btn-primary">+ Cadastrar Novo Produto</a>
    <?php endif; ?>
</div>

<?php if (isset($erroListagem)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erroListagem) ?></div>
<?php endif; ?>

<?php if (empty($produtos)): ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>Nenhum produto ativo encontrado</h3>
        <p>Não há produtos ativos cadastrados no momento.</p>
        <?php if (isAdmin()): ?>
            <a href="produto_novo.php" class="btn btn-primary">Cadastrar Primeiro Produto</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($produtos as $p): ?>
            <div class="product-card" id="produto-card-<?= (int)$p['id'] ?>">
                <div class="product-image-wrapper">
                    <?php 
                        $imgSrc = !empty($p['imagem']) ? htmlspecialchars($p['imagem']) : 'assets/img/placeholder.svg';
                    ?>
                    <img 
                        src="<?= $imgSrc ?>" 
                        alt="<?= htmlspecialchars($p['nome']) ?>" 
                        class="product-image"
                        onerror="this.onerror=null; this.src='assets/img/placeholder.svg';"
                    >
                    <span class="stock-badge <?= $p['quantidade'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $p['quantidade'] > 0 ? $p['quantidade'] . ' em estoque' : 'Esgotado' ?>
                    </span>
                </div>

                <div class="product-body">
                    <h3 class="product-title"><?= htmlspecialchars($p['nome']) ?></h3>
                    <p class="product-description"><?= htmlspecialchars($p['descricao']) ?></p>
                    
                    <div class="product-meta">
                        <div class="product-price">
                            <small>Preço unitário</small>
                            <strong>R$ <?= number_format($p['preco'], 2, ',', '.') ?></strong>
                        </div>
                        <div class="product-qty-info">
                            <small>Quantidade</small>
                            <span><?= (int)$p['quantidade'] ?> un.</span>
                        </div>
                    </div>
                </div>

                <div class="product-actions">
                    <!-- Botão para comentários (Regra 5.2 e 6) -->
                    <a href="comentarios.php?produto_id=<?= (int)$p['id'] ?>" 
                       class="btn btn-outline-primary btn-sm btn-comentarios" 
                       data-produto-id="<?= (int)$p['id'] ?>"
                       data-produto-nome="<?= htmlspecialchars($p['nome']) ?>">
                        💬 Comentários
                    </a>

                    <!-- Botão de movimentação rápida para agilizar entrada/saída -->
                    <a href="movimentacoes.php?produto_id=<?= (int)$p['id'] ?>" 
                       class="btn btn-outline-secondary btn-sm" title="Registrar Entrada/Saída">
                        ↕ Movimentar
                    </a>

                    <?php if (isAdmin()): ?>
                        <!-- Botão para exclusão (administrador apenas - Regra 5.2 e 5.4) -->
                        <form action="produto_excluir.php" method="POST" class="form-excluir d-inline" 
                              onsubmit="return confirmarExclusao(event, this);">
                            <input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                🗑️ Excluir
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- MODAL PARA COMENTÁRIOS VIA AJAX / INTERATIVO -->
<div id="modal-comentarios" class="modal" aria-hidden="true" role="dialog">
    <div class="modal-backdrop" onclick="fecharModalComentarios()"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-produto-titulo">Comentários do Produto</h3>
                <!-- Botão para fechar a tela (Regra 6) -->
                <button type="button" class="btn-close" onclick="fecharModalComentarios()">&times;</button>
            </div>
            
            <div class="modal-body" id="modal-comentarios-body">
                <div class="spinner-container">
                    <div class="spinner"></div>
                    <p>Carregando observações...</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModalComentarios()">Fechar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
