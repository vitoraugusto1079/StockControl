<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: produto_editar.php - Edição de Informações do Produto (Admin)
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Apenas administradores podem editar produtos
exigirAdmin();

$pdo = getConnection();
$erro = null;

// Obter ID do produto via GET ou POST
$produtoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$produtoId) {
    setFlash('error', 'Produto não especificado ou inválido.');
    header("Location: produtos.php");
    exit;
}

// Buscar dados atuais do produto
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $produtoId]);
$produto = $stmt->fetch();

if (!$produto) {
    setFlash('error', 'Produto não encontrado no sistema.');
    header("Location: produtos.php");
    exit;
}

// Processar envio do formulário (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome       = trim($_POST['nome'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $preco      = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $status     = trim($_POST['status'] ?? '');

    // Validações básicas dos campos textuais e numéricos
    if (empty($nome) || empty($descricao) || $quantidade === false || $preco === false || empty($status)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($quantidade < 0) {
        $erro = "A quantidade não pode ser negativa.";
    } elseif ($preco < 0) {
        $erro = "O preço não pode ser negativo.";
    } elseif (!in_array($status, ['ativo', 'inativo'])) {
        $erro = "Status inválido. Escolha 'ativo' ou 'inativo'.";
    } else {
        $caminhoImagem = $produto['imagem']; // Mantém a imagem atual por padrão
        $novaImagemEnviada = isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK;

        // Se uma nova imagem foi enviada pelo formulário
        if ($novaImagemEnviada) {
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

                if (move_uploaded_file($arquivoTmp, $destino)) {
                    $caminhoImagem = 'uploads/' . $nomeNovoArquivo;
                } else {
                    $erro = "Falha ao salvar a nova imagem no servidor.";
                }
            }
        }

        // Se não houve erro até aqui, prossegue com a atualização no banco de dados
        if (!$erro) {
            try {
                $pdo->beginTransaction();

                $qtdAnterior = (int)$produto['quantidade'];
                $diferencaQtd = $quantidade - $qtdAnterior;

                // Atualizar dados do produto
                $stmtUpdate = $pdo->prepare("
                    UPDATE produtos 
                    SET nome = :nome,
                        descricao = :descricao,
                        quantidade = :quantidade,
                        preco = :preco,
                        status = :status,
                        imagem = :imagem
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ':nome'       => $nome,
                    ':descricao'  => $descricao,
                    ':quantidade' => $quantidade,
                    ':preco'      => $preco,
                    ':status'     => $status,
                    ':imagem'     => $caminhoImagem,
                    ':id'         => $produtoId,
                ]);

                // Se a quantidade foi alterada diretamente, registrar no histórico de movimentações
                if ($diferencaQtd !== 0) {
                    $tipoMovimento = $diferencaQtd > 0 ? 'entrada' : 'saida';
                    $qtdMovimento  = abs($diferencaQtd);
                    $usuarioId     = usuarioAtual()['id'];

                    $stmtMov = $pdo->prepare("
                        INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, observacao, data_movimentacao)
                        VALUES (:produto_id, :usuario_id, :tipo, :quantidade, :observacao, NOW())
                    ");
                    $stmtMov->execute([
                        ':produto_id' => $produtoId,
                        ':usuario_id' => $usuarioId,
                        ':tipo'       => $tipoMovimento,
                        ':quantidade' => $qtdMovimento,
                        ':observacao' => "Ajuste manual via edição do produto (de {$qtdAnterior} para {$quantidade})",
                    ]);
                }

                $pdo->commit();

                setFlash('success', "Produto \"{$nome}\" atualizado com sucesso!");
                header("Location: produtos.php");
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erro = "Erro ao atualizar produto: " . $e->getMessage();
            }
        }
    }

    // Em caso de erro, recarrega os dados preenchidos para não perder digitação
    $produto['nome']       = $nome ?? $produto['nome'];
    $produto['descricao']  = $descricao ?? $produto['descricao'];
    $produto['quantidade'] = $quantidade ?? $produto['quantidade'];
    $produto['preco']      = $preco ?? $produto['preco'];
    $produto['status']     = $status ?? $produto['status'];
}

$pageTitle = "Editar Produto #" . $produto['id'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <h2>Editar Produto: <?= htmlspecialchars($produto['nome']) ?></h2>
        <p class="text-muted">Altere as informações, preço, estoque ou envie uma nova foto para o produto.</p>
    </div>
</div>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger" role="alert">
        <i data-lucide="alert-circle" class="icon-alert"></i> <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form action="produto_editar.php" method="POST" enctype="multipart/form-data" id="form-editar-produto">
        <input type="hidden" name="id" value="<?= (int)$produto['id'] ?>">

        <div class="form-group">
            <label for="nome">Nome do Produto <span class="required">*</span></label>
            <input 
                type="text" 
                id="nome" 
                name="nome" 
                class="campo-produto" 
                value="<?= htmlspecialchars($produto['nome']) ?>" 
                placeholder="Ex: Teclado Mecânico RGB" 
                required
            >
        </div>

        <div class="form-group">
            <label for="descricao">Descrição Completa <span class="required">*</span></label>
            <textarea 
                id="descricao" 
                name="descricao" 
                rows="4" 
                class="campo-produto" 
                placeholder="Detalhes técnicos, modelo, características..." 
                required
            ><?= htmlspecialchars($produto['descricao']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group col">
                <label for="quantidade">Quantidade em Estoque <span class="required">*</span></label>
                <input 
                    type="number" 
                    id="quantidade" 
                    name="quantidade" 
                    min="0" 
                    step="1" 
                    class="campo-produto" 
                    value="<?= (int)$produto['quantidade'] ?>" 
                    required
                >
            </div>

            <div class="form-group col">
                <label for="preco">Preço Unitário (R$) <span class="required">*</span></label>
                <input 
                    type="number" 
                    id="preco" 
                    name="preco" 
                    min="0" 
                    step="0.01" 
                    class="campo-produto" 
                    value="<?= htmlspecialchars($produto['preco']) ?>" 
                    required
                >
            </div>

            <div class="form-group col">
                <label for="status">Status do Produto <span class="required">*</span></label>
                <select id="status" name="status" class="campo-produto" required>
                    <option value="ativo" <?= $produto['status'] === 'ativo' ? 'selected' : '' ?>>Ativo (Visível na listagem)</option>
                    <option value="inativo" <?= $produto['status'] === 'inativo' ? 'selected' : '' ?>>Inativo (Oculto)</option>
                </select>
            </div>
        </div>

        <!-- Seção da Imagem: Exibe a imagem atual e campo para substituir -->
        <div class="form-group">
            <label>Foto do Produto</label>
            
            <div class="current-image-container" style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 0.75rem; padding: 0.85rem; background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-md);">
                <img 
                    src="<?= !empty($produto['imagem']) ? htmlspecialchars($produto['imagem']) : 'assets/img/placeholder.svg' ?>" 
                    alt="Foto atual" 
                    id="current-image-thumb"
                    style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--gray-300);"
                    onerror="this.onerror=null; this.src='assets/img/placeholder.svg';"
                >
                <div>
                    <strong style="color: var(--brand-navy); display: block; font-size: 0.95rem;">Imagem Atual Cadastrada</strong>
                    <span style="font-size: 0.825rem; color: var(--gray-500);"><?= htmlspecialchars($produto['imagem']) ?></span>
                </div>
            </div>

            <label for="imagem" style="font-weight: 500; font-size: 0.9rem; margin-top: 0.5rem;">Substituir Foto (Opcional)</label>
            <input type="file" id="imagem" name="imagem" accept="image/*" class="campo-produto">
            <small class="form-hint">Deixe em branco se desejar manter a foto atual. Formatos aceitos: JPG, PNG, WEBP ou GIF.</small>
        </div>

        <!-- Preview da nova imagem selecionada -->
        <div id="image-preview-container" class="image-preview" style="display: none; margin-top: 0.75rem;">
            <p><strong>Nova Foto Selecionada (Pré-visualização):</strong></p>
            <img id="image-preview" src="#" alt="Prévia da Nova Imagem" style="max-height: 160px; border-radius: var(--radius-sm); border: 1px solid var(--gray-300);">
        </div>

        <div class="form-actions">
            <a href="produtos.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" id="btn-salvar-produto" class="btn btn-success">
                <i data-lucide="save" class="icon-btn"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

<script>
// Pré-visualização instantânea da nova imagem no formulário de edição
document.addEventListener('DOMContentLoaded', () => {
    const inputImagem = document.getElementById('imagem');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImg = document.getElementById('image-preview');

    if (inputImagem && previewContainer && previewImg) {
        inputImagem.addEventListener('change', () => {
            if (inputImagem.files && inputImagem.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(inputImagem.files[0]);
            } else {
                previewContainer.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
