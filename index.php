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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        /* Regras inline prioritárias para garantir renderização imediata sem cache stale */
        body.login-split-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex !important;
            flex-direction: row !important;
            background-color: #ffffff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-split-container {
            display: flex !important;
            flex-direction: row !important;
            width: 100% !important;
            min-height: 100vh !important;
        }
        .login-hero-side {
            position: relative !important;
            width: 55% !important;
            flex: 0 0 55% !important;
            background: #062B5E url('assets/img/login_bg.jpg') center center / cover no-repeat !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            padding: 4.5rem 4rem !important;
            color: #ffffff !important;
            box-sizing: border-box !important;
        }
        .login-hero-overlay {
            position: absolute !important;
            inset: 0 !important;
            background: linear-gradient(135deg, rgba(6, 43, 94, 0.92) 0%, rgba(11, 75, 153, 0.85) 100%) !important;
            backdrop-filter: blur(2px) !important;
            z-index: 1 !important;
        }
        .login-hero-content {
            position: relative !important;
            z-index: 2 !important;
            max-width: 560px !important;
        }
        .hero-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.4rem !important;
            padding: 0.35rem 0.9rem !important;
            background: rgba(255, 255, 255, 0.16) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 9999px !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #ffffff !important;
            margin-bottom: 1.75rem !important;
        }
        .hero-title {
            font-size: 2.3rem !important;
            font-weight: 800 !important;
            line-height: 1.25 !important;
            color: #ffffff !important;
            margin-bottom: 1.25rem !important;
            letter-spacing: -0.02em !important;
        }
        .hero-description {
            font-size: 1.05rem !important;
            line-height: 1.6 !important;
            color: rgba(255, 255, 255, 0.9) !important;
            margin-bottom: 2rem !important;
        }
        .hero-features {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.85rem !important;
            margin-bottom: 2.5rem !important;
        }
        .feature-item {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            font-size: 0.95rem !important;
            color: #ffffff !important;
        }
        .feature-icon {
            width: 20px !important;
            height: 20px !important;
            color: #00B050 !important;
            stroke-width: 2.5px !important;
            flex-shrink: 0 !important;
        }
        .hero-footer-quote {
            padding-top: 1.5rem !important;
            border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
        .hero-footer-quote blockquote {
            font-style: italic !important;
            font-size: 0.925rem !important;
            line-height: 1.5 !important;
            color: rgba(255, 255, 255, 0.85) !important;
            margin: 0 !important;
        }
        .login-form-side {
            width: 45% !important;
            flex: 0 0 45% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 3rem 2.5rem !important;
            background-color: #ffffff !important;
            box-sizing: border-box !important;
        }
        .login-form-wrapper {
            width: 100% !important;
            max-width: 400px !important;
        }
        .login-header {
            text-align: left !important;
            margin-bottom: 2rem !important;
        }
        .login-logo-img {
            height: 42px !important;
            width: auto !important;
            max-width: 200px !important;
            margin-bottom: 1.5rem !important;
            object-fit: contain !important;
        }
        .login-header h2 {
            font-size: 1.6rem !important;
            font-weight: 700 !important;
            color: #06336D !important;
            margin-bottom: 0.35rem !important;
        }
        .login-subtitle {
            color: #64748b !important;
            font-size: 0.9rem !important;
        }
        .input-icon-wrapper {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
        }
        .field-icon {
            position: absolute !important;
            left: 1rem !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 18px !important;
            height: 18px !important;
            color: #94a3b8 !important;
            pointer-events: none !important;
            z-index: 2 !important;
        }
        .input-icon-wrapper input {
            width: 100% !important;
            padding: 0.75rem 1rem 0.75rem 2.8rem !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            font-size: 0.925rem !important;
            box-sizing: border-box !important;
        }
        .btn-login {
            width: 100% !important;
            padding: 0.85rem 1.25rem !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            margin-top: 1rem !important;
            background-color: #00B050 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
        }
        .btn-login:hover {
            background-color: #009945 !important;
        }
        .login-footer-info {
            margin-top: 2rem !important;
            padding-top: 1.25rem !important;
            border-top: 1px solid #e2e8f0 !important;
            text-align: center !important;
            font-size: 0.8rem !important;
            color: #64748b !important;
        }
        @media (max-width: 900px) {
            body.login-split-body, .login-split-container {
                flex-direction: column !important;
            }
            .login-hero-side {
                width: 100% !important;
                flex: none !important;
                padding: 3rem 1.5rem !important;
            }
            .hero-features, .hero-footer-quote {
                display: none !important;
            }
            .login-form-side {
                width: 100% !important;
                flex: none !important;
                padding: 2.5rem 1.5rem !important;
            }
        }
    </style>
</head>
<body class="login-split-body">
    <div class="login-split-container">
        <!-- LADO ESQUERDO: BANNER VISUAL COM IMAGEM E FRASE DE IMPACTO -->
        <div class="login-hero-side">
            <div class="login-hero-overlay"></div>
            <div class="login-hero-content">
                <div class="hero-badge">
                    <i data-lucide="shield-check" class="icon-inline"></i> Gestão Inteligente de Estoque
                </div>
                
                <h1 class="hero-title">
                    Controle total, eficiência máxima e precisão em cada movimentação.
                </h1>
                
                <p class="hero-description">
                    Centralize entradas, saídas, níveis críticos de suprimentos e histórico completo em uma plataforma ágil e segura.
                </p>

                <div class="hero-features">
                    <div class="feature-item">
                        <i data-lucide="check-circle-2" class="feature-icon"></i>
                        <span>Rastreabilidade em tempo real de operadores e produtos</span>
                    </div>
                    <div class="feature-item">
                        <i data-lucide="check-circle-2" class="feature-icon"></i>
                        <span>Histórico de entradas, saídas e notas de inspeção</span>
                    </div>
                    <div class="feature-item">
                        <i data-lucide="check-circle-2" class="feature-icon"></i>
                        <span>Visão clara para evitar perdas e rupturas no estoque</span>
                    </div>
                </div>

                <div class="hero-footer-quote">
                    <blockquote>
                        “Um bom sistema de estoque evita perdas, rupturas e auxilia na tomada rápida de decisão.”
                    </blockquote>
                </div>
            </div>
        </div>

        <!-- LADO DIREITO: FORMULÁRIO DE LOGIN -->
        <div class="login-form-side">
            <div class="login-form-wrapper">
                <div class="login-header">
                    <img src="assets/img/logo.png" alt="StockControl" class="login-logo-img">
                    <h2>Acesse sua Conta</h2>
                    <p class="login-subtitle">Insira suas credenciais para gerenciar o estoque.</p>
                </div>

                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i data-lucide="alert-circle" class="icon-alert"></i> <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">E-mail Corporativo</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="mail" class="field-icon"></i>
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
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha de Acesso</label>
                        <!-- Campo de senha mostra asteriscos conforme regra de negócio -->
                        <div class="input-icon-wrapper">
                            <i data-lucide="lock" class="field-icon"></i>
                            <input 
                                type="password" 
                                id="senha" 
                                name="senha" 
                                placeholder="••••••••" 
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-login">
                        <i data-lucide="log-in" class="icon-btn"></i> Entrar no Sistema
                    </button>
                </form>

                <div class="login-footer-info">
                    <p><i data-lucide="shield" class="icon-inline"></i> Acesso seguro restrito aos usuários cadastrados no banco de dados.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/lucide.min.js"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
