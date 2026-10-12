<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: comentarios.php - Listagem e Cadastro de Comentários
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();
$pdo = getConnection();
$usuarioLogado = usuarioAtual();

$produtoId = filter_input(INPUT_GET, 'produto_id', FILTER_VALIDATE_INT) 
             ?: filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);

if (!$produtoId) {
    setFlash('error', 'Produto não informado.');
    header("Location: produtos.php");
    exit;
}

// Busca informações do produto selecionado
$stmtProd = $pdo->prepare("SELECT id, nome, quantidade, preco, status, imagem FROM produtos WHERE id = :id");
$stmtProd->execute([':id' => $produtoId]);
$produto = $stmtProd->fetch();

if (!$produto) {
    setFlash('error', 'Produto não encontrado.');
    header("Location: produtos.php");
    exit;
}

$mensagemSucesso = null;
$mensagemErro = null;

// PROCESSAMENTO DO CADASTRO DE NOVO COMENTÁRIO (Regra 7)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $textoComentario = trim($_POST['texto'] ?? '');
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || isset($_POST['ajax']);

    // Regra 7.1: Campo de texto obrigatório (não vazio)
    if (empty($textoComentario)) {
        $mensagemErro = "O texto do comentário é obrigatório.";
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['sucesso' => false, 'erro' => $mensagemErro]);
            exit;
        }
    } else {
        try {
            // Regra 7.3: Botão "Cadastrar" insere comentário no banco com usuário logado e data atual
            $stmtInsert = $pdo->prepare("
                INSERT INTO comentarios (produto_id, usuario_id, texto, data_criacao)
                VALUES (:produto_id, :usuario_id, :texto, NOW())
            ");
            $stmtInsert->execute([
                ':produto_id' => $produtoId,
                ':usuario_id' => $usuarioLogado['id'],
                ':texto'      => $textoComentario,
            ]);

            // Regra 7.4: Mensagem de sucesso exata
            $msgSucessoTexto = "Sucesso! Comentário cadastrado para o produto.";

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['sucesso' => true, 'mensagem' => $msgSucessoTexto]);
                exit;
            }

            $mensagemSucesso = $msgSucessoTexto;
        } catch (Exception $e) {
            $mensagemErro = "Erro ao registrar comentário: " . $e->getMessage();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['sucesso' => false, 'erro' => $mensagemErro]);
                exit;
            }
        }
    }
}

// CONSULTA DE COMENTÁRIOS DO PRODUTO (Regra 6)
// Regras: Mostrar perfil do usuário, data e texto do comentário.
// Exibidos do mais recente para o mais antigo (ORDER BY data_criacao DESC).
$stmtComentarios = $pdo->prepare("
    SELECT c.id, c.texto, c.data_criacao, u.nome AS usuario_nome, u.perfil AS usuario_perfil
    FROM comentarios c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.produto_id = :produto_id
    ORDER BY c.data_criacao DESC
");
$stmtComentarios->execute([':produto_id' => $produtoId]);
$comentarios = $stmtComentarios->fetchAll();

// Se for requisição AJAX para carregar o conteúdo do modal
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'produto'     => $produto,
        'comentarios' => array_map(function($c) {
            return [
                'id'             => $c['id'],
                'texto'          => htmlspecialchars($c['texto']),
                'usuario_nome'   => htmlspecialchars($c['usuario_nome']),
                'usuario_perfil' => $c['usuario_perfil'],
                'data_formatada' => date('d/m/Y \à\s H:i', strtotime($c['data_criacao'])),
            ];
        }, $comentarios)
    ]);
    exit;
}

$pageTitle = "Comentários: " . $produto['nome'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>Observações & Comentários</h2>
        <p class="text-muted">Produto: <strong><?= htmlspecialchars($produto['nome']) ?></strong></p>
    </div>
    <!-- Regra 6 e 7.2: Botão para fechar e voltar à lista de produtos -->
    <a href="produtos.php" class="btn btn-secondary">
        <i data-lucide="arrow-left" class="icon-btn"></i> Fechar e Voltar
    </a>
</div>

<?php if (!empty($mensagemSucesso)): ?>
    <div class="alert alert-success" role="alert">
        <i data-lucide="check-circle" class="icon-alert"></i> <?= htmlspecialchars($mensagemSucesso) ?>
    </div>
<?php endif; ?>

<?php if (!empty($mensagemErro)): ?>
    <div class="alert alert-danger" role="alert">
        <i data-lucide="alert-circle" class="icon-alert"></i> <?= htmlspecialchars($mensagemErro) ?>
    </div>
<?php endif; ?>

<div class="comments-layout">
    <!-- SEÇÃO: CADASTRO DE NOVO COMENTÁRIO (Regra 7) -->
    <div class="comment-new-card" id="form-cadastro-comentario">
        <h3>Adicionar Novo Comentário</h3>
        <p class="text-muted">Insira notas sobre inspeção, recebimento, defeitos ou logística.</p>

        <form action="comentarios.php?produto_id=<?= (int)$produto['id'] ?>" method="POST">
            <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">

            <div class="form-group">
                <label for="texto">Comentário / Observação <span class="required">*</span></label>
                <!-- Regra 7.1: Campo de texto obrigatório (não vazio) -->
                <textarea 
                    id="texto" 
                    name="texto" 
                    rows="4" 
                    placeholder="Digite aqui sua observação sobre o produto..." 
                    required
                ></textarea>
            </div>

            <div class="form-actions">
                <!-- Regra 7.2: Botão para fechar e voltar à lista de produtos -->
                <a href="produtos.php" class="btn btn-secondary">Fechar e Voltar</a>
                
                <!-- Regra 7.3: Botão "Cadastrar" insere comentário -->
                <button type="submit" class="btn btn-success">
                    <i data-lucide="send" class="icon-btn"></i> Cadastrar
                </button>
            </div>
        </form>
    </div>

    <!-- SEÇÃO: LISTAGEM DE COMENTÁRIOS (Regra 6) -->
    <div class="comments-list-section">
        <div class="comments-list-header">
            <h3>Histórico de Observações (<?= count($comentarios) ?>)</h3>
            <span class="badge-info">Ordenado: Mais recente para o mais antigo</span>
        </div>

        <?php if (empty($comentarios)): ?>
            <div class="empty-state-small">
                <p>Nenhum comentário registrado para este produto até o momento.</p>
            </div>
        <?php else: ?>
            <div class="comments-timeline">
                <?php foreach ($comentarios as $c): ?>
                    <div class="comment-bubble">
                        <div class="comment-bubble-header">
                            <div class="comment-author">
                                <i data-lucide="user" class="icon-inline"></i>
                                <strong><?= htmlspecialchars($c['usuario_nome']) ?></strong>
                                <span class="user-role-badge <?= $c['usuario_perfil'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                    <?= $c['usuario_perfil'] === 'admin' ? 'Administrador' : 'Operador' ?>
                                </span>
                            </div>
                            <!-- Regra 6: Data do comentário -->
                            <time class="comment-date" datetime="<?= $c['data_criacao'] ?>">
                                <i data-lucide="clock" class="icon-inline"></i> <?= date('d/m/Y \à\s H:i', strtotime($c['data_criacao'])) ?>
                            </time>
                        </div>
                        <!-- Regra 6: Texto do comentário -->
                        <div class="comment-text">
                            <?= nl2br(htmlspecialchars($c['texto'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
