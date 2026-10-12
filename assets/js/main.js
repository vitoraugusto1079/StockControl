// ==============================================================
// PROJETO: StockControl
// ARQUIVO: assets/js/main.js - Interatividade, Validações e Modais
// ==============================================================

document.addEventListener('DOMContentLoaded', () => {
    initValidacaoCadastroProduto();
    initModalComentarios();
});

/**
 * REQUISITO 5.4: Confirmação de exclusão de produto
 * Mensagem exata exigida pelo professor:
 * "Atenção! Tem certeza que deseja excluir o produto? Essa ação não poderá ser desfeita."
 */
function confirmarExclusao(event, formElement) {
    const confirmacao = window.confirm("Atenção! Tem certeza que deseja excluir o produto? Essa ação não poderá ser desfeita.");
    if (!confirmacao) {
        event.preventDefault();
        return false;
    }
    return true;
}

/**
 * REQUISITO 8.2: Botão "Cadastrar" habilitado apenas se todos os campos preenchidos
 * Campos: nome, descrição, quantidade, preço, status (ativo/inativo), imagem
 */
function initValidacaoCadastroProduto() {
    const form = document.getElementById('form-produto');
    const btnCadastrar = document.getElementById('btn-cadastrar-produto');

    if (!form || !btnCadastrar) return;

    const inputNome = document.getElementById('nome');
    const inputDescricao = document.getElementById('descricao');
    const inputQuantidade = document.getElementById('quantidade');
    const inputPreco = document.getElementById('preco');
    const selectStatus = document.getElementById('status');
    const inputImagem = document.getElementById('imagem');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImg = document.getElementById('image-preview');

    function validarCampos() {
        const nomePreenchido = inputNome.value.trim() !== '';
        const descricaoPreenchida = inputDescricao.value.trim() !== '';
        const quantidadePreenchida = inputQuantidade.value.trim() !== '' && Number(inputQuantidade.value) >= 0;
        const precoPreenchido = inputPreco.value.trim() !== '' && Number(inputPreco.value) >= 0;
        const statusPreenchido = selectStatus.value.trim() !== '';
        const imagemPreenchida = inputImagem.files && inputImagem.files.length > 0;

        const todosPreenchidos = nomePreenchido && 
                                 descricaoPreenchida && 
                                 quantidadePreenchida && 
                                 precoPreenchido && 
                                 statusPreenchido && 
                                 imagemPreenchida;

        btnCadastrar.disabled = !todosPreenchidos;
    }

    // Pré-visualização da imagem quando selecionada
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
        validarCampos();
    });

    const campos = [inputNome, inputDescricao, inputQuantidade, inputPreco, selectStatus];
    campos.forEach(campo => {
        campo.addEventListener('input', validarCampos);
        campo.addEventListener('change', validarCampos);
    });

    // Executa uma checagem inicial
    validarCampos();
}

/**
 * REQUISITOS 6 e 7: Modal Interativo de Comentários
 */
let produtoAtivoId = null;

function initModalComentarios() {
    const botoesComentarios = document.querySelectorAll('.btn-comentarios');
    const modal = document.getElementById('modal-comentarios');

    if (!modal) return;

    botoesComentarios.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const produtoId = btn.getAttribute('data-produto-id');
            const produtoNome = btn.getAttribute('data-produto-nome');
            abrirModalComentarios(produtoId, produtoNome);
        });
    });
}

function abrirModalComentarios(produtoId, produtoNome) {
    produtoAtivoId = produtoId;
    const modal = document.getElementById('modal-comentarios');
    const modalTitulo = document.getElementById('modal-produto-titulo');
    const modalBody = document.getElementById('modal-comentarios-body');

    modalTitulo.textContent = `Observações: ${produtoNome}`;
    modalBody.innerHTML = `
        <div class="spinner-container">
            <div class="spinner"></div>
            <p>Carregando comentários...</p>
        </div>
    `;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');

    carregarComentariosDoProduto(produtoId);
}

function fecharModalComentarios() {
    const modal = document.getElementById('modal-comentarios');
    if (modal) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }
}

async function carregarComentariosDoProduto(produtoId) {
    const modalBody = document.getElementById('modal-comentarios-body');

    try {
        const response = await fetch(`comentarios.php?produto_id=${produtoId}&ajax=1`);
        if (!response.ok) throw new Error('Falha ao comunicar com o servidor');

        const dados = await response.json();
        renderizarConteudoModalComentarios(dados);
    } catch (err) {
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                Não foi possível carregar os comentários. Tente novamente mais tarde.
            </div>
        `;
    }
}

function renderizarConteudoModalComentarios(dados) {
    const modalBody = document.getElementById('modal-comentarios-body');
    const comentarios = dados.comentarios || [];

    let html = `
        <!-- Formulário para cadastro de novo comentário (Regra 7) -->
        <div class="modal-new-comment-box">
            <h4>➕ Novo Comentário</h4>
            <div id="modal-alert-box" style="display: none;"></div>
            <form id="form-modal-comentario" onsubmit="enviarNovoComentarioModal(event)">
                <div class="form-group">
                    <textarea 
                        id="modal-texto-comentario" 
                        class="form-control" 
                        rows="3" 
                        placeholder="Escreva sua observação sobre o produto..." 
                        required
                    ></textarea>
                </div>
                <div class="modal-comment-actions">
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-salvar-comentario-modal">
                        Cadastrar Comentário
                    </button>
                </div>
            </form>
        </div>

        <hr class="divider">

        <!-- Lista de comentários (Regra 6: ordenados do mais recente para o mais antigo) -->
        <div class="modal-comments-list">
            <h4>Histórico de Observações (${comentarios.length})</h4>
    `;

    if (comentarios.length === 0) {
        html += `<p class="text-muted text-center py-3">Nenhum comentário cadastrado para este produto ainda.</p>`;
    } else {
        html += `<div class="comments-timeline">`;
        comentarios.forEach(c => {
            const roleClass = c.usuario_perfil === 'admin' ? 'badge-admin' : 'badge-user';
            const roleNome = c.usuario_perfil === 'admin' ? 'Administrador' : 'Operador';
            html += `
                <div class="comment-bubble">
                    <div class="comment-bubble-header">
                        <div class="comment-author">
                            <strong>👤 ${c.usuario_nome}</strong>
                            <span class="user-role-badge ${roleClass}">${roleNome}</span>
                        </div>
                        <time class="comment-date">🕒 ${c.data_formatada}</time>
                    </div>
                    <div class="comment-text">${c.texto.replace(/\\n/g, '<br>')}</div>
                </div>
            `;
        });
        html += `</div>`;
    }

    html += `</div>`;
    modalBody.innerHTML = html;
}

async function enviarNovoComentarioModal(event) {
    event.preventDefault();
    const textarea = document.getElementById('modal-texto-comentario');
    const texto = textarea.value.trim();
    const alertBox = document.getElementById('modal-alert-box');
    const btnSubmit = document.getElementById('btn-salvar-comentario-modal');

    // Regra 7.1: Campo de texto obrigatório (não vazio)
    if (!texto) {
        alertBox.className = 'alert alert-danger';
        alertBox.textContent = 'O comentário não pode ficar vazio.';
        alertBox.style.display = 'block';
        return;
    }

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Gravando...';

    const formData = new FormData();
    formData.append('produto_id', produtoAtivoId);
    formData.append('texto', texto);
    formData.append('ajax', '1');

    try {
        const response = await fetch('comentarios.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const resultado = await response.json();

        if (resultado.sucesso) {
            // Regra 7.4: Mensagem de sucesso
            alertBox.className = 'alert alert-success';
            alertBox.textContent = resultado.mensagem; // "Sucesso! Comentário cadastrado para o produto."
            alertBox.style.display = 'block';
            textarea.value = '';

            // Recarregar os comentários para exibir o novo comentário no topo
            setTimeout(() => {
                carregarComentariosDoProduto(produtoAtivoId);
            }, 1000);
        } else {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = resultado.erro || 'Erro ao cadastrar comentário.';
            alertBox.style.display = 'block';
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Cadastrar Comentário';
        }
    } catch (err) {
        alertBox.className = 'alert alert-danger';
        alertBox.textContent = 'Falha na requisição ao servidor.';
        alertBox.style.display = 'block';
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Cadastrar Comentário';
    }
}
