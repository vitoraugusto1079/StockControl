-- ==============================================================
-- PROJETO: StockControl - Controle de Estoque
-- CURSO TÉCNICO DE DESENVOLVIMENTO DE SISTEMAS - SESI / SENAI
-- Script físico do Banco de Dados Relacional MySQL
-- ==============================================================

CREATE DATABASE IF NOT EXISTS stockcontrol
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE stockcontrol;

-- Desabilitar temporariamente checagem de chaves para recriação limpa
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS movimentacoes;
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------
-- 1. TABELA DE USUÁRIOS
-- Perfis: 'admin' (acesso total) e 'comum' (somente consulta/movimentação)
-- --------------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'comum') NOT NULL DEFAULT 'comum',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 2. TABELA DE PRODUTOS
-- Status: 'ativo' (visível na listagem principal) e 'inativo' (excluído logicamente)
-- --------------------------------------------------------------
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    preco DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    imagem VARCHAR(255) NOT NULL,
    data_inclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 3. TABELA DE COMENTÁRIOS / OBSERVAÇÕES
-- Permite registrar observações por produto com usuário e data
-- --------------------------------------------------------------
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    texto TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comentarios_produto FOREIGN KEY (produto_id) 
        REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_usuario FOREIGN KEY (usuario_id) 
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 4. TABELA DE MOVIMENTAÇÕES
-- Histórico completo de entradas e saídas de produtos
-- --------------------------------------------------------------
CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    quantidade INT NOT NULL,
    observacao VARCHAR(255) NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimentacoes_produto FOREIGN KEY (produto_id) 
        REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_movimentacoes_usuario FOREIGN KEY (usuario_id) 
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- CARGA INICIAL DE DADOS (SEEDS PARA TESTES E AVALIAÇÃO)
-- ==============================================================

-- Usuários:
-- 1) Admin: admin@stockcontrol.com | Senha: admin123
-- 2) Usuário Comum: operador@stockcontrol.com | Senha: user123
INSERT INTO usuarios (id, nome, email, senha, perfil) VALUES
(1, 'Administrador do Sistema', 'admin@stockcontrol.com', '$2y$10$9EztKT38XOZ/FnZKNlAkKuAcX8ndakzP1Xg9Xlf0xjGiqvoG.Zik.', 'admin'),
(2, 'Operador de Estoque', 'operador@stockcontrol.com', '$2y$10$VPFSHEtsAIviYkuJOfIW3eYr4Hv.C5MeHnCIYBGwVohMaiguuDZta', 'comum');

-- Produtos de Exemplo
INSERT INTO produtos (id, nome, descricao, quantidade, preco, status, imagem, data_inclusao) VALUES
(1, 'Teclado Mecânico RGB', 'Teclado mecânico com switches azuis, iluminação RGB e layout ABNT2.', 25, 249.90, 'ativo', 'uploads/teclado.jpg', NOW() - INTERVAL 5 DAY),
(2, 'Mouse Gamer 7200 DPI', 'Mouse ergonômico com sensor óptico de alta precisão e 6 botões programáveis.', 40, 129.50, 'ativo', 'uploads/mouse.jpg', NOW() - INTERVAL 4 DAY),
(3, 'Monitor 24 Polegadas Full HD', 'Monitor IPS 75Hz com bordas ultrafinas, entradas HDMI e VGA.', 15, 689.00, 'ativo', 'uploads/monitor.jpg', NOW() - INTERVAL 3 DAY),
(4, 'Headset USB 7.1 Surround', 'Headset com cancelamento de ruído passivo e microfone articulado.', 18, 199.90, 'ativo', 'uploads/headset.jpg', NOW() - INTERVAL 2 DAY),
(5, 'Cabo HDMI 2.0 2 Metros (Antigo)', 'Lote antigo de cabos com defeito de fabricação.', 0, 19.90, 'inativo', 'uploads/cabo.jpg', NOW() - INTERVAL 10 DAY);

-- Comentários Iniciais
INSERT INTO comentarios (id, produto_id, usuario_id, texto, data_criacao) VALUES
(1, 1, 1, 'Lote recebido sem avarias na embalagem externa.', NOW() - INTERVAL 4 DAY),
(2, 1, 2, 'Verificado funcionamento de todos os LEDs.', NOW() - INTERVAL 3 DAY),
(3, 2, 2, 'Alta demanda esperada para a promoção de fim de mês.', NOW() - INTERVAL 2 DAY),
(4, 3, 1, 'Restam apenas 15 unidades, avaliar novo pedido ao fornecedor.', NOW() - INTERVAL 1 DAY);

-- Movimentações Iniciais
INSERT INTO movimentacoes (id, produto_id, usuario_id, tipo, quantidade, observacao, data_movimentacao) VALUES
(1, 1, 1, 'entrada', 30, 'Estoque inicial da compra #1020', NOW() - INTERVAL 5 DAY),
(2, 1, 2, 'saida', 5, 'Venda balcão para laboratório', NOW() - INTERVAL 3 DAY),
(3, 2, 1, 'entrada', 50, 'Recebimento de lote distribuidor', NOW() - INTERVAL 4 DAY),
(4, 2, 2, 'saida', 10, 'Remessa para filiais', NOW() - INTERVAL 2 DAY),
(5, 3, 1, 'entrada', 15, 'Nota fiscal 4589', NOW() - INTERVAL 3 DAY),
(6, 4, 1, 'entrada', 18, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY);
