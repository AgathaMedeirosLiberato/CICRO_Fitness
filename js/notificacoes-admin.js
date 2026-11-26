(function () {
    var cfg = window.CICRO_NOTIF || {};
    var querPedidos = !!cfg.pedidos, querCpf = cfg.cpf !== false;
    var API = '../../controllers/notificacoes_admin.php';
    var INTERVALO = 5000;
    var KEY_PED = 'cicro_pedido_visto_v2', KEY_CPF = 'cicro_cpf_visto';
    var parado = false, caixa = null;

    var memoria = {};
    function guarda(k) { return k === KEY_PED ? window.localStorage : window.sessionStorage; }
    function lerNum(k) {
        try { var v = guarda(k).getItem(k); if (v !== null) return parseInt(v, 10); } catch (e) {}
        return Object.prototype.hasOwnProperty.call(memoria, k) ? memoria[k] : null;
    }
    function gravar(k, v) { memoria[k] = v; try { guarda(k).setItem(k, String(v)); } catch (e) {} }
    function brl(v) { return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }

    function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) e.className = cls; if (txt != null) e.textContent = txt; return e; }

    function garantirCaixa() {
        if (caixa) return caixa;
        caixa = el('div'); caixa.id = 'cicro-toasts'; caixa.setAttribute('aria-live', 'polite'); caixa.setAttribute('role', 'status');
        document.body.appendChild(caixa);
        return caixa;
    }

    function toast(o) {
        var t = el('div', 'nt' + (o.tipo ? ' nt--' + o.tipo : ''));
        t.appendChild(el('div', 'nt__icone', o.icone));
        var c = el('div', 'nt__corpo');
        c.appendChild(el('p', 'nt__titulo', o.titulo));
        c.appendChild(el('p', 'nt__texto', o.texto));
        var ac = el('div', 'nt__acoes');
        (o.botoes || []).forEach(function (b) {
            var x = b.href ? el('a', 'nt__btn' + (b.claro ? ' nt__btn--claro' : ''), b.rotulo) : el('button', 'nt__btn' + (b.claro ? ' nt__btn--claro' : ''), b.rotulo);
            if (b.href) x.href = b.href; else x.type = 'button';
            if (b.acao) x.addEventListener('click', b.acao);
            ac.appendChild(x);
        });
        if (ac.children.length) c.appendChild(ac);
        t.appendChild(c);
        var f = el('button', 'nt__fechar', '×'); f.type = 'button'; f.setAttribute('aria-label', 'Fechar aviso');
        t.appendChild(f);
        var dur = o.duracao || 30000;
        var barra = el('div', 'nt__barra'); barra.style.animationDuration = dur + 'ms'; t.appendChild(barra);
        function fechar() { if (!t.parentNode) return; t.classList.add('nt--saindo'); setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 260); }
        f.addEventListener('click', fechar);
        var timer = setTimeout(fechar, dur);
        t.addEventListener('mouseenter', function () { clearTimeout(timer); });
        t.addEventListener('mouseleave', function () { timer = setTimeout(fechar, 4000); });
        garantirCaixa().appendChild(t);
        return t;
    }

    function avisarPedidos(r) {
        var visto = lerNum(KEY_PED);
        if (visto === null) {
            gravar(KEY_PED, Math.max(0, r.ultimo_id - 1));
            if (r.ultimo_id > 0) consultar();
            return;
        }
        if (!r.pedidos.length) { if (r.ultimo_id !== visto) gravar(KEY_PED, r.ultimo_id); return; }
        var recarregar = cfg.recarregar ? [{ rotulo: 'Atualizar lista', claro: true, acao: function () { location.reload(); } }] : [];
        if (r.total_novos > 3) {
            toast({ icone: '🛒', titulo: r.total_novos + ' novos pedidos', texto: 'Clientes fizeram compras desde a sua última visita.',
                    botoes: [{ rotulo: 'Ver vendas', href: 'vendas.php' }].concat(recarregar) });
        } else {
            r.pedidos.forEach(function (p) {
                toast({ icone: '🛒', titulo: 'Novo pedido de ' + p.nome,
                        texto: 'Comprou ' + p.pecas + (p.pecas === 1 ? ' peça' : ' peças') + ' · ' + brl(p.total) + ' (' + p.hora + ')',
                        botoes: [{ rotulo: 'Ver pedido', href: 'vendas.php?id=' + p.id }].concat(recarregar) });
            });
        }
        gravar(KEY_PED, r.ultimo_id);
    }

    function avisarCpf(r) {
        if (!r.cpf_pendentes) return;
        var visto = lerNum(KEY_CPF) || 0;
        if (r.cpf_ultimo_id <= visto) return;
        gravar(KEY_CPF, r.cpf_ultimo_id);
        toast({ tipo: 'cpf', icone: '🪪', titulo: 'Troca de CPF aguardando você',
                texto: r.cpf_pendentes === 1 ? 'Um cliente pediu para alterar o CPF.' : r.cpf_pendentes + ' clientes pediram para alterar o CPF.',
                botoes: [{ rotulo: 'Analisar', href: 'solicitacoes-cpf.php' }] });
    }

    function sessaoPerdida() {
        if (parado) return;
        parado = true;
        if (!querPedidos) return;
        toast({ icone: '⚠️', titulo: 'Avisos de compra pausados',
                texto: 'Este navegador não está mais logado como admin. Entre de novo. Para testar como cliente, use outro navegador ou uma janela anônima.',
                botoes: [{ rotulo: 'Entrar', href: '../user/login.php' }] });
    }

    function consultar() {
        if (parado) return;
        var url = API;
        if (querPedidos) { var v = lerNum(KEY_PED); if (v !== null) url += '?desde=' + v; }
        fetch(url, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) { if (res.status === 401) { sessaoPerdida(); return null; } return res.json(); })
            .then(function (r) {
                if (!r) return;
                if (!r.ok) { if (window.console) console.warn('Avisos do painel: resposta com erro (veja o log do PHP).'); return; }
                if (querPedidos) avisarPedidos(r);
                if (querCpf) avisarCpf(r);
            })
            .catch(function (e) { if (window.console) console.warn('Avisos do painel:', e); });
    }

    consultar();
    setInterval(function () { if (!document.hidden) consultar(); }, INTERVALO);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) consultar(); });
})();
