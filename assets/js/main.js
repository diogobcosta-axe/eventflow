// assets/js/main.js — Interações do lado do cliente

// --- Menu mobile ---
function toggleMobileMenu() {
    document.getElementById('mobileMenu')?.classList.toggle('open');
}

// --- Flash message: desaparece automaticamente após 5 segundos ---
setTimeout(function() {
    document.getElementById('flashMsg')?.remove();
}, 5000);

// --- Pré-visualização da imagem antes de carregar ---
document.addEventListener('change', function(e) {
    const input = e.target;
    if (input.type !== 'file' || !input.closest('.file-input-wrap')) return;

    const ficheiro = input.files[0];
    if (!ficheiro) return;

    const wrap    = input.closest('.file-input-wrap');
    const preview = wrap.querySelector('.upload-preview');
    const label   = wrap.querySelector('.upload-label');

    if (ficheiro.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            if (preview) {
                preview.innerHTML = '<img src="' + ev.target.result + '" style="max-height:160px;border-radius:8px;margin-top:10px;">';
            }
            if (label) label.textContent = ficheiro.name;
        };
        reader.readAsDataURL(ficheiro);
    } else {
        if (label) label.textContent = ficheiro.name;
    }
});

// --- Confirmação antes de ações destrutivas (ex: apagar evento) ---
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

// --- Toast (notificação flutuante) — usado pela marcação de presenças ---
function showToast(msg, tipo) {
    tipo = tipo || 'success';
    var toast = document.createElement('div');
    toast.className = 'flash flash--' + tipo;
    toast.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;border-radius:12px;min-width:260px;';
    toast.innerHTML = '<span>' + msg + '</span><button onclick="this.parentElement.remove()" class="flash__close">×</button>';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 4000);
}

// --- Marcar presença (AJAX) — usado na página de presenças pelo organizador ---
function togglePresenca(inscricaoId, btn) {
    var csrf = document.getElementById('csrf');
    if (!csrf) return;

    fetch('/pages/ajax_presenca.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'inscricao_id=' + inscricaoId + '&csrf_token=' + csrf.value
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.classList.toggle('btn--success', data.presenca);
            btn.classList.toggle('btn--ghost',   !data.presenca);
            btn.textContent = data.presenca ? '✓ Presente' : 'Marcar';
            showToast(data.msg, 'success');
        } else {
            showToast(data.msg, 'error');
        }
    })
    .catch(function() {
        showToast('Erro de ligação. Tenta novamente.', 'error');
    });
}
