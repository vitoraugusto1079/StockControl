<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: produto_novo.php - Cadastro de Novo Produto (Admin)
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Apenas administradores podem cadastrar produtos
exigirAdmin();

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome       = trim($_POST['nome'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $preco      = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $status     = trim($_POST['status'] ?? '');

    // Validação estrita de todos os campos
    if (empty($nome) || empty($descricao) || $quantidade === false || $preco === false || empty($status)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!in_array($status, ['ativo', 'inativo'])) {
        $erro = "Status inválido. Escolha 'ativo' ou 'inativo'.";
    } elseif (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        $erro = "Selecione uma imagem válida para o produto.";
    } else {
        // Processar upload da imagem
        $arquivoTmp   = $_FILES['imagem']['tmp_name'];
        $nomeOriginal = $_FILES['imagem']['name'];
        $extensao     = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extensao, $extensoesPermitidas)) {
            $erro = "Formato de imagem não suportado. Utilize JPG, PNG, WEBP ou GIF.";
        } else {
            $pastaUploads = __DIR__ . '/uploads';
            if (!is_dir($pastaUploads)) {
                mkdir($pastaUploads, 0755, true);
            }

            $nomeNovoArquivo = uniqid('prod_', true) . '.' . $extensao;
            $destino = $pastaUploads . '/' . $nomeNovoArquivo;
            $caminhoRelativo = 'uploads/' . $nomeNovoArquivo;

            if (move_uploaded_file($arquivoTmp, $destino)) {
                try {
                    $pdo = getConnection();
                    $pdo->beginTransaction();

                    // Regra 8.3: Data de inclusão automática
                    $stmt = $pdo->prepare("
                        INSERT INTO produtos (nome, descricao, quantidade, preco, status, imagem, data_inclusao)
                        VALUES (:nome, :descricao, :quantidade, :preco, :status, :imagem, NOW())
                    ");
                    $stmt->execute([
                        ':nome'       => $nome,
                        ':descricao'  => $descricao,
                        ':quantidade' => $quantidade,
                        ':preco'      => $preco,
                        ':status'     => $status,
                        ':imagem'     => $caminhoRelativo,
                    ]);

                    $produtoId = $pdo->lastInsertId();

                    // Se quantidade inicial > 0, registra a movimentação de entrada inicial
                    if ($quantidade > 0) {
                        $stmtMov = $pdo->prepare("
                            INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, observacao, data_movimentacao)
                            VALUES (:produto_id, :usuario_id, 'entrada', :quantidade, 'Estoque inicial cadastrado com o produto', NOW())
                        ");
                        $stmtMov->execute([
                            ':produto_id' => $produtoId,
                            ':usuario_id' => usuarioAtual()['id'],
                            ':quantidade' => $quantidade,
                        ]);
                    }

                    $pdo->commit();

                    // Regra 8.4: Após cadastro, redirecionar para a lista de produtos
                    setFlash('success', 'Produto cadastrado com sucesso!');
                    header("Location: produtos.php");
                    exit;

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if (file_exists($destino)) {
                        unlink($destino);
                    }
                    $erro = "Erro ao cadastrar produto no banco: " . $e->getMessage();
                }
            } else {
                $erro = "Falha ao salvar a imagem enviada no servidor.";
            }
        }
    }
}

$pageTitle = "Novo Produto";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>➕ Cadastrar Novo Produto</h2>
        <p class="text-muted">Preencha todos os campos abaixo para disponibilizar o item no estoque.</p>
    </div>
    <a href="produtos.php" class="btn btn-secondary">← Voltar para a Lista</a>
</div>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger" role="alert">
        <span>⚠️</span> <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form action="produto_novo.php" method="POST" enctype="multipart/form-data" id="form-produto">
        
        <div class="form-group">
            <label for="nome">Nome do Produto <span class="required">*</span></label>
            <input type="text" id="nome" name="nome" class="campo-produto" placeholder="Ex: Teclado Mecânico RGB" required>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição Completa <span class="required">*</span></label>
            <textarea id="descricao" name="descricao" rows="4" class="campo-produto" placeholder="Detalhes técnicos, modelo, características..." required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group col">
                <label for="quantidade">Quantidade em Estoque <span class="required">*</span></label>
                <input type="number" id="quantidade" name="quantidade" min="0" step="1" class="campo-produto" placeholder="0" required>
            </div>

            <div class="form-group col">
                <label for="preco">Preço Unitário (R$) <span class="required">*</span></label>
                <input type="number" id="preco" name="preco" min="0" step="0.01" class="campo-produto" placeholder="0.00" required>
            </div>

            <div class="form-group col">
                <label for="status">Status do Produto <span class="required">*</span></label>
                <select id="status" name="status" class="campo-produto" required>
                    <option value="">Selecione...</option>
                    <option value="ativo" selected>Ativo (Visível na listagem)</option>
                    <option value="inativo">Inativo (Oculto)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="imagem">Foto / Imagem do Produto <span class="required">*</span></label>
            <input type="file" id="imagem" name="imagem" accept="image/*" class="campo-produto" required>
            <small class="form-hint">Formatos suportados: JPG, PNG, WEBP ou GIF.</small>
        </div>

        <!-- Preview da imagem selecionada -->
        <div id="image-preview-container" class="image-preview" style="display: none;">
            <p><strong>Pré-visualização:</strong></p>
            <img id="image-preview" src="#" alt="Prévia da Imagem">
        </div>

        <div class="form-actions">
            <a href="produtos.php" class="btn btn-secondary">Cancelar</a>
            <!-- Regra 8.2: Botão "Cadastrar" habilitado apenas se todos os campos preenchidos -->
            <button type="submit" id="btn-cadastrar-produto" class="btn btn-success" disabled>
                ✓ Cadastrar Produto
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
