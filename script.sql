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
(5, 'Cabo HDMI 2.0 2 Metros (Antigo)', 'Lote antigo de cabos com defeito de fabricação.', 0, 19.90, 'inativo', 'uploads/cabo.jpg', NOW() - INTERVAL 10 DAY),
(6, 'SSD NVMe M.2 1TB', 'Unidade de armazenamento ultrarrápida PCIe 4.0 com velocidades de leitura de até 7000MB/s e dissipador térmico integrado.', 35, 489.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(7, 'Placa de Vídeo RTX 4060 8GB', 'Placa de vídeo com arquitetura Ada Lovelace, 8GB GDDR6, suporte a Ray Tracing e tecnologia DLSS 3.', 12, 2199.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(8, 'Memória RAM DDR4 16GB 3200MHz', 'Módulo de memória de alta performance com dissipador de calor em alumínio anodizado e compatibilidade XMP 2.0.', 50, 239.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(9, 'Roteador Wi-Fi 6 Gigabit Dual Band', 'Roteador de alta velocidade com 4 antenas externas de alto ganho, suporte a MU-MIMO, Beamforming e portas Gigabit.', 20, 349.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(10, 'Gabinete Gamer Mid Tower RGB', 'Gabinete espaçoso com lateral em vidro temperado, painel frontal em mesh de alto fluxo de ar e 4 fans ARGB inclusas.', 18, 319.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(11, 'Fonte ATX 650W 80 Plus Bronze', 'Fonte de alimentação com certificação 80 Plus Bronze, PFC ativo, ventilador silencioso de 120mm e cabos flat pretos.', 25, 369.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(12, 'Processador Octa-Core 4.6GHz', 'Processador de 8 núcleos e 16 threads, clock turbo de até 4.6GHz, cache L3 de 32MB e cooler incluso na caixa.', 14, 1150.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(13, 'Placa-Mãe B550M Micro-ATX', 'Placa-mãe com socket AM4, slot PCIe 4.0 x16 reforçado, dois slots M.2 NVMe e suporte a até 128GB DDR4.', 22, 599.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(14, 'Suporte Articulado para Monitor a Gás', 'Braço articulado com pistão a gás para telas de 17 a 32 polegadas, ajuste milimétrico de altura, rotação 360° e padrão VESA.', 30, 189.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(15, 'Webcam Full HD 1080p 60FPS', 'Câmera com lente grande angular de vidro, microfone duplo com cancelamento de ruído e capa protetora de privacidade.', 28, 219.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(16, 'Nobreak Interativo 1200VA Bivolt', 'Nobreak estabilizado com bateria interna, 6 tomadas tripolares, proteção contra sobretensão e autonomia para computadores de escritório.', 8, 789.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(17, 'Switch Gigabit 8 Portas 10/100/1000', 'Switch de rede metálico desktop, 8 portas com autonegociação MDI/MDIX, sem ruído (fanless) e economia inteligente de energia.', 45, 159.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(18, 'Cadeira Gamer Ergonômica', 'Cadeira com encosto reclinável até 180 graus, apoio de braço bidirecional, pistão classe 4 e almofadas lombar e cervical removíveis.', 10, 899.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(19, 'Microfone Condensador USB Profissional', 'Microfone de captação cardioide com botão de mute sensível ao toque, controle de ganho, shock mount e pop filter inclusos.', 16, 279.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(20, 'Caixa de Som Soundbar Bluetooth 40W', 'Soundbar compacta para monitores e TVs com potência de 40W RMS, conexão Bluetooth 5.0, entrada óptica e auxiliar P2.', 24, 189.00, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY),
(21, 'Hub USB-C 7 em 1 com HDMI 4K', 'Adaptador multiportas compacto em liga de alumínio com saída HDMI 4K, 3 portas USB 3.0, leitores SD/MicroSD e Power Delivery 100W.', 40, 149.90, 'ativo', 'assets/img/placeholder.svg', NOW() - INTERVAL 2 DAY);

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
(6, 4, 1, 'entrada', 18, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(7, 6, 1, 'entrada', 35, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(8, 7, 1, 'entrada', 12, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(9, 8, 1, 'entrada', 50, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(10, 9, 1, 'entrada', 20, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(11, 10, 1, 'entrada', 18, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(12, 11, 1, 'entrada', 25, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(13, 12, 1, 'entrada', 14, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(14, 13, 1, 'entrada', 22, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(15, 14, 1, 'entrada', 30, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(16, 15, 1, 'entrada', 28, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(17, 16, 1, 'entrada', 8, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(18, 17, 1, 'entrada', 45, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(19, 18, 1, 'entrada', 10, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(20, 19, 1, 'entrada', 16, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(21, 20, 1, 'entrada', 24, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY),
(22, 21, 1, 'entrada', 40, 'Carga inicial do estoque', NOW() - INTERVAL 2 DAY);
