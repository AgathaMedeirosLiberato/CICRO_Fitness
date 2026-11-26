(function () {
    'use strict';

    var CSS = [
        'dialog.sair-modal{position:fixed;inset:0;margin:auto;height:fit-content;width:min(400px,calc(100vw - 32px));box-sizing:border-box;',
        'padding:28px;border:0;border-radius:24px;background:#fff;color:#222;text-align:center;',
        'font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;box-shadow:0 24px 80px rgba(0,0,0,.35)}',
        'dialog.sair-modal::backdrop{background:rgba(10,10,10,.55)}',
        'dialog.sair-modal h2{margin:0 0 8px;padding:0;font:800 1.25rem/1.3 "Helvetica Neue",Helvetica,Arial,sans-serif;color:#111;text-align:center}',
        'dialog.sair-modal p{margin:0 0 22px;font-size:.92rem;line-height:1.5;color:#555}',
        'dialog.sair-modal div{display:flex;gap:10px}',
        'dialog.sair-modal button{flex:1;min-height:44px;margin:0;padding:0 18px;border:1px solid #cfcfcf;border-radius:999px;',
        'background:#fff;color:#111;font:700 .9rem "Helvetica Neue",Helvetica,Arial,sans-serif;cursor:pointer}',
        'dialog.sair-modal button:hover{border-color:#111}',
        'dialog.sair-modal button.sair-sim{background:#111;border-color:#111;color:#fff}',
        'dialog.sair-modal button.sair-sim:hover{background:#2a2a2a}',
        'dialog.sair-modal button:focus-visible{outline:2px solid #111;outline-offset:2px}'
    ].join('');

    var modal = null;
    var destino = '';

    function criar() {
        var estilo = document.createElement('style');
        estilo.textContent = CSS;
        document.head.appendChild(estilo);

        modal = document.createElement('dialog');
        modal.className = 'sair-modal';
        modal.setAttribute('aria-labelledby', 'sair-titulo');
        modal.innerHTML =
            '<h2 id="sair-titulo">Tem certeza que deseja sair?</h2>' +
            '<p>Você precisará entrar novamente para acessar a sua conta.</p>' +
            '<div><button type="button" class="sair-nao" autofocus>Cancelar</button>' +
            '<button type="button" class="sair-sim">Sim, sair</button></div>';
        document.body.appendChild(modal);

        modal.querySelector('.sair-nao').addEventListener('click', function () { modal.close(); });
        modal.querySelector('.sair-sim').addEventListener('click', function () {
            modal.close();
            window.location.href = destino;
        });
        modal.addEventListener('click', function (e) {
            var r = modal.getBoundingClientRect();
            var fora = e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            if (e.target === modal && fora) modal.close();
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('a[href$="logout.php"]') : null;
        if (!link) return;
        e.preventDefault();
        destino = link.href;

        if (typeof HTMLDialogElement !== 'function') {
            if (window.confirm('Tem certeza que deseja sair?')) window.location.href = destino;
            return;
        }
        if (!modal) criar();
        modal.showModal();
    });
})();
