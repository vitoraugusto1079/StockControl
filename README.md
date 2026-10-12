# StockControl - Sistema de Controle de Estoque

> **Situação de Aprendizagem (Full Stack) - Curso Técnico em Desenvolvimento de Sistemas**  
> **SESI / SENAI** | Professor: Luis Felipe Cardoso

---

## 📌 Sobre o Projeto

O **StockControl** é uma aplicação web completa desenvolvida para suprir a demanda corporativa de gerenciamento eficiente de produtos e materiais. O sistema controla estoque físico, registra histórico de entradas e saídas com rastreabilidade de responsáveis, permite comentários e anotações técnicas por item e restringe acessos administrativos.

---

## 🚀 Funcionalidades Implementadas

- [x] **Autenticação Segura (Login)**:
  - Validação direta no banco de dados relacional.
  - Campo de senha protegido com asteriscos.
  - Mensagens de erro amigáveis para credenciais inválidas.
  - Controle de perfis: **Administrador** e **Operador Comum**.
- [x] **Gestão de Produtos (CRUD)**:
  - Listagem inteligente de produtos (exibindo apenas itens **ativos**).
  - Exibição de foto, nome, descrição, quantidade em estoque e preço unitário.
  - Permissão exclusiva para Administradores cadastrarem e excluírem itens.
  - Confirmação formal com mensagem oficial: *"Atenção! Tem certeza que deseja excluir o produto? Essa ação não poderá ser desfeita."*
- [x] **Cadastro Reativo de Produtos**:
  - Campos: Nome, Descrição, Quantidade, Preço, Status e Imagem.
  - Botão *"Cadastrar"* interativo: habilitado apenas se **todos** os campos estiverem preenchidos e válidos.
  - Upload de foto com armazenamento local e caminho no banco.
  - Data de inclusão automática e redirecionamento para a lista.
- [x] **Histórico de Comentários / Observações**:
  - Consulta de notas por produto com autor, perfil e data/hora.
  - Ordenação cronológica decrescente (do mais recente para o mais antigo).
  - Cadastro de novos comentários com validação de campo não-vazio.
  - Mensagem oficial de sucesso: *"Sucesso! Comentário cadastrado para o produto."*
  - Modal dinâmico assíncrono (AJAX) + suporte a página dedicada.
- [x] **Histórico e Registro de Movimentações (Entradas e Saídas)**:
  - Registro de operações de entrada e saída.
  - Checagem automática de saldo antes de saídas.
  - Registro do usuário responsável, data e observação.

---

## 🗄️ Estrutura do Banco de Dados (`script.sql`)

O banco relacional `stockcontrol` é composto por 4 tabelas interligadas por chaves estrangeiras:

1. **`usuarios`**: Cadastro de operadores e administradores com controle de acesso.
2. **`produtos`**: Itens cadastrados, dados de preço, estoque e status (ativo/inativo).
3. **`comentarios`**: Observações e notas técnicas vinculadas a produtos e usuários.
4. **`movimentacoes`**: Histórico detalhado de entradas e saídas físicas de estoque.

---

## 🛠️ Como Executar o Projeto com XAMPP

1. **Iniciar os Serviços**:
   - Abra o **XAMPP Control Panel**.
   - Clique em **Start** nos módulos **Apache** e **MySQL**.

2. **Importar o Banco de Dados**:
   - Acesse o phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
   - Clique na aba **Importar** (ou **SQL**).
   - Selecione o arquivo `script.sql` localizado na raiz do projeto e clique em **Executar**.

3. **Acessar a Aplicação**:
   - Abra o navegador e digite: [http://localhost/stockcontrol](http://localhost/stockcontrol).

---

## 🔑 Credenciais para Avaliação e Testes

| Perfil | E-mail | Senha | Permissões |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@stockcontrol.com` | `admin123` | Acesso total (Cadastrar produtos, excluir, comentar, movimentar) |
| **Operador Comum** | `operador@stockcontrol.com` | `user123` | Consulta, registrar comentários e movimentações |
