// assets/js/main.js — Comportamentos do lado do cliente (JavaScript)
// Este ficheiro é carregado em todas as páginas pelo footer.php


// ============================================================
// MENU MOBILE
// Abre/fecha o menu de navegação em dispositivos móveis
// ============================================================
function toggleMobileMenu() {
    // toggle('open') adiciona a classe 'open' se não existir, remove se já existir
    document.getElementById('mobileMenu')?.classList.toggle('open');
    // O '?' (optional chaining) evita erro se o elemento não existir na página
}


// ============================================================
// FLASH MESSAGE — desaparece automaticamente após 5 segundos
// ============================================================
setTimeout(function() {
    // Remove o elemento da mensagem flash do DOM (se existir)
    document.getElementById('flashMsg')?.remove();
}, 5000); // 5000 milissegundos = 5 segundos


// ============================================================
// PRÉ-VISUALIZAÇÃO DE IMAGEM ANTES DO UPLOAD
// Quando o utilizador seleciona um ficheiro, mostra uma miniatura
// ============================================================
document.addEventListener('change', function(e) {
    const input = e.target; // Elemento que disparou o evento

    // Só processa inputs do tipo 'file' dentro de um elemento '.file-input-wrap'
    if (input.type !== 'file' || !input.closest('.file-input-wrap')) return;

    const ficheiro = input.files[0]; // Primeiro ficheiro selecionado
    if (!ficheiro) return;           // Sai se não houver ficheiro

    // Encontra os elementos dentro do wrapper do input
    const wrap    = input.closest('.file-input-wrap');
    const preview = wrap.querySelector('.upload-preview'); // Div onde mostra a imagem
    const label   = wrap.querySelector('.upload-label');   // Label com o nome do ficheiro

    // Só mostra pré-visualização se for uma imagem
    if (ficheiro.type.startsWith('image/')) {
        // FileReader permite ler o conteúdo do ficheiro no browser (sem upload)
        const reader = new FileReader();

        // Função chamada quando o FileReader termina de ler o ficheiro
        reader.onload = function(ev) {
            if (preview) {
                // Cria uma tag <img> com a imagem em base64 (data URL)
                preview.innerHTML = '<img src="' + ev.target.result + '" style="max-height:160px;border-radius:8px;margin-top:10px;">';
            }
            if (label) label.textContent = ficheiro.name; // Mostra o nome do ficheiro
        };

        // Lê o ficheiro como Data URL (base64) — dispara o evento onload quando terminar
        reader.readAsDataURL(ficheiro);
    } else {
        // Se não é imagem, mostra apenas o nome do ficheiro
        if (label) label.textContent = ficheiro.name;
    }
});


// ============================================================
// CONFIRMAÇÃO ANTES DE AÇÕES DESTRUTIVAS (ex: apagar evento)
// Elementos com data-confirm="mensagem" pedem confirmação antes de agir
// ============================================================
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        // confirm() mostra uma caixa de diálogo nativa do browser
        // Se o utilizador clicar em "Cancelar", o evento é cancelado
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault(); // Cancela a navegação ou submissão do formulário
        }
    });
});


// ============================================================
// TOAST — notificação flutuante temporária
// Usada pelo JavaScript após chamadas AJAX (ex: marcação de presença)
// ============================================================
function showToast(msg, tipo) {
    tipo = tipo || 'success'; // Valor padrão: sucesso (verde)

    // Cria um novo elemento div para o toast
    var toast = document.createElement('div');
    toast.className = 'flash flash--' + tipo; // Usa as mesmas classes CSS do flash normal

    // Posiciona o toast no canto superior direito da página (por cima de tudo)
    toast.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;border-radius:12px;min-width:260px;';

    // Conteúdo: mensagem + botão de fechar
    toast.innerHTML = '<span>' + msg + '</span><button onclick="this.parentElement.remove()" class="flash__close">×</button>';

    // Adiciona o toast ao body da página
    document.body.appendChild(toast);

    // Remove o toast automaticamente após 4 segundos
    setTimeout(function() { toast.remove(); }, 4000);
}


// ============================================================
// MARCAR PRESENÇA (AJAX) — chamado na página de presenças
// Envia um pedido ao servidor sem recarregar a página inteira
// ============================================================
function togglePresenca(inscricaoId, btn) {
    // Lê o token CSRF do campo escondido na página (obrigatório para segurança)
    var csrf = document.getElementById('csrf');
    if (!csrf) return; // Sai se o token não existir

    // fetch() faz um pedido HTTP assíncrono ao servidor (AJAX moderno)
    fetch('/pages/ajax_presenca.php', {
        method: 'POST', // Método POST (envia dados)
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Formato dos dados
        body: 'inscricao_id=' + inscricaoId + '&csrf_token=' + csrf.value // Dados enviados
    })
    .then(function(r) { return r.json(); }) // Converte a resposta JSON em objeto JavaScript
    .then(function(data) {
        if (data.success) {
            // Atualiza o estilo do botão consoante o novo estado
            btn.classList.toggle('btn--success', data.presenca); // Verde se presente
            btn.classList.toggle('btn--ghost',   !data.presenca); // Cinzento se não presente
            btn.textContent = data.presenca ? '✓ Presente' : 'Marcar'; // Texto do botão
            showToast(data.msg, 'success'); // Mostra notificação de sucesso
        } else {
            showToast(data.msg, 'error'); // Mostra notificação de erro
        }
    })
    .catch(function() {
        // Chamado se houver erro de rede (sem internet, servidor em baixo, etc.)
        showToast('Erro de ligação. Tenta novamente.', 'error');
    });
}
