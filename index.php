<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: index.php - Tela de Login e Controle de Acesso
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Se o usuário já estiver logado, redireciona para a listagem
if (usuarioLogado()) {
    header("Location: produtos.php");
    exit;
}

$erro = getFlash('error');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = "Por favor, preencha o e-mail e a senha.";
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            // Valida se o usuário existe e se a senha confere (com hash bcrypt ou texto plano)
            if ($usuario && (password_verify($senha, $usuario['senha']) || $senha === $usuario['senha'])) {
                $_SESSION['usuario'] = [
                    'id'     => $usuario['id'],
                    'nome'   => $usuario['nome'],
                    'email'  => $usuario['email'],
                    'perfil' => $usuario['perfil'],
                ];
                
                header("Location: produtos.php");
                exit;
            } else {
                // Regra de negócio: Ao clicar em "Entrar" com senha inválida, exibir mensagem.
                $erro = "Acesso negado: E-mail ou senha inválidos.";
            }
        } catch (Exception $e) {
            $erro = "Erro ao autenticar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StockControl</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-header">
            <img src="assets/img/logo.png" alt="StockControl" class="login-logo-img">
            <p class="login-subtitle">Gestão Integrada de Estoque & Movimentações</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger" role="alert">
                <span>⚠️</span> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="email">E-mail do Usuário</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= htmlspecialchars($email) ?>" 
                    placeholder="exemplo@stockcontrol.com" 
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <!-- Campo de senha mostra asteriscos conforme regra de negócio -->
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-login">
                <i data-lucide="log-in" class="icon-btn"></i> Entrar no Sistema
            </button>
        </form>
    </div>

    <script src="assets/js/lucide.min.js"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
