# StockControl - Sistema de Controle de Estoque

> **Situação de Aprendizagem (Full Stack) - Curso Técnico em Desenvolvimento de Sistemas**  
> **SESI / SENAI** | Professores: Luis Felipe Cardoso e Lucas Machado

---

## Estrutura do Banco de Dados (`script.sql`)

O banco relacional `stockcontrol` é composto por 4 tabelas interligadas por chaves estrangeiras:

1. **`usuarios`**: Cadastro de operadores e administradores com controle de acesso.
2. **`produtos`**: Itens cadastrados, dados de preço, estoque e status (ativo/inativo).
3. **`comentarios`**: Observações e notas técnicas vinculadas a produtos e usuários.
4. **`movimentacoes`**: Histórico detalhado de entradas e saídas físicas de estoque.

---

## Como Executar o Projeto com XAMPP

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

## Credenciais para Avaliação e Testes

| Perfil | E-mail | Senha | Permissões |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@stockcontrol.com` | `admin123` | Acesso total (Cadastrar produtos, excluir, comentar, movimentar) |
| **Operador Comum** | `operador@stockcontrol.com` | `user123` | Consulta, registrar comentários e movimentações |
