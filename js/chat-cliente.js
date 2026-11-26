(function () {
    const widget = document.getElementById('chatWidget');
    const launcher = document.getElementById('chatLauncher');
    if (!widget || !launcher) return;

    const API = widget.dataset.api;
    const CSRF = widget.dataset.csrf;
    const MODO = widget.dataset.modo;

    const el = {
        abrirPagina: document.getElementById('abrirChat'),
        badge: document.getElementById('chatBadge'),
        fechar: document.getElementById('chatFechar'),
        encerrar: document.getElementById('chatEncerrar'),
        status: document.getElementById('chatStatus'),
        mensagens: document.getElementById('chatMensagens'),
        erro: document.getElementById('chatErro'),
        form: document.getElementById('chatForm'),
        input: document.getElementById('chatInput'),
        enviar: document.getElementById('chatEnviar'),
        encerrada: document.getElementById('chatEncerrada'),
        nova: document.getElementById('chatNova')
    };

    let aberto = false;
    let conversaId = null;
    let ultimoId = 0;
    let novaIniciada = false;
    let ocupado = false;
    let temporizador = null;
    let temporizadorErro = null;

    function horaCurta(data) {
        if (!data) return '';
        const dia = data.slice(0, 10);
        const hora = data.slice(11, 16);
        const h = new Date();
        const hoje = h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0') + '-' + String(h.getDate()).padStart(2, '0');
        return dia === hoje ? hora : dia.slice(8, 10) + '/' + dia.slice(5, 7) + ' ' + hora;
    }

    function pertoDoFim() {
        return el.mensagens.scrollHeight - el.mensagens.scrollTop - el.mensagens.clientHeight < 60;
    }

    function rolarParaFim() {
        el.mensagens.scrollTop = el.mensagens.scrollHeight;
    }

    function mostrarErro(texto) {
        if (!el.erro) return;
        el.erro.textContent = texto;
        el.erro.hidden = false;
        clearTimeout(temporizadorErro);
        temporizadorErro = setTimeout(function () { el.erro.hidden = true; }, 5000);
    }

    function tratarErro(json) {
        if (json.rede) {
            mostrarErro('Sem conexão com o servidor. Tente de novo.');
            return;
        }
        mostrarErro(json.erro || 'Algo deu errado. Tente novamente.');
        if (json.login) {
            setTimeout(function () { window.location.href = 'login.php'; }, 1500);
        }
    }

    async function chamar(params, post) {
        try {
            let url = API;
            const opcoes = { credentials: 'same-origin' };
            if (post) {
                const fd = new FormData();
                fd.append('csrf', CSRF);
                Object.keys(params).forEach(function (k) { fd.append(k, params[k]); });
                opcoes.method = 'POST';
                opcoes.body = fd;
            } else {
                url += '?' + new URLSearchParams(params).toString();
            }
            const resp = await fetch(url, opcoes);
            return await resp.json();
        } catch (e) {
            return { ok: false, rede: true };
        }
    }

    function renderBoasVindas() {
        el.mensagens.innerHTML = '';
        const balao = document.createElement('div');
        balao.className = 'chat-msg chat-msg--outro';

        const autor = document.createElement('span');
        autor.className = 'chat-msg__autor';
        autor.textContent = 'Equipe CICRO';

        balao.appendChild(autor);
        balao.appendChild(document.createTextNode(
            'Olá! Como podemos ajudar? Escreva sua dúvida e nossa equipe responde por aqui.'
        ));
        el.mensagens.appendChild(balao);
    }

    function adicionarMensagem(m) {
        const minha = m.de === 'cliente';
        const balao = document.createElement('div');
        balao.className = 'chat-msg ' + (minha ? 'chat-msg--eu' : 'chat-msg--outro');

        if (!minha) {
            const autor = document.createElement('span');
            autor.className = 'chat-msg__autor';
            autor.textContent = m.autor || 'Equipe CICRO';
            balao.appendChild(autor);
        }

        balao.appendChild(document.createTextNode(m.conteudo));

        const hora = document.createElement('span');
        hora.className = 'chat-msg__hora';
        hora.textContent = horaCurta(m.data);
        balao.appendChild(hora);

        el.mensagens.appendChild(balao);
    }

    function limparConversa() {
        conversaId = null;
        ultimoId = 0;
        renderBoasVindas();
    }

    function aplicarStatus(status) {
        const encerrada = status !== 'Aberta';
        if (el.encerrada) el.encerrada.hidden = !encerrada;
        if (el.form) el.form.hidden = encerrada;
        el.encerrar.hidden = encerrada || conversaId === null;
        el.status.textContent = encerrada ? 'Conversa encerrada' : 'Responderemos o quanto antes';
    }

    function atualizarBadge(n) {
        if (n > 0 && !aberto) {
            el.badge.textContent = n > 99 ? '99+' : String(n);
            el.badge.hidden = false;
        } else {
            el.badge.hidden = true;
        }
    }

    async function buscarMensagens() {
        if (conversaId === null) return;
        const json = await chamar({ acao: 'mensagens', conversa_id: conversaId, depois_id: ultimoId });

        if (!json.ok) {
            if (!json.rede) {
                if (json.erro === 'Conversa não encontrada.') {
                    limparConversa();
                    aplicarStatus('Aberta');
                } else {
                    tratarErro(json);
                }
            }
            return;
        }

        const noFim = pertoDoFim();
        json.mensagens.forEach(function (m) {
            if (m.id > ultimoId) {
                adicionarMensagem(m);
                ultimoId = m.id;
            }
        });
        if (json.mensagens.length > 0 && noFim) rolarParaFim();
        aplicarStatus(json.conversa.status);
    }

    async function carregarConversaInicial() {
        const json = await chamar({ acao: 'estado' });
        if (!json.ok) {
            tratarErro(json);
            renderBoasVindas();
            return;
        }

        const conversa = json.conversa;
        if (conversa && !(novaIniciada && conversa.status !== 'Aberta')) {
            if (conversaId !== conversa.id) {
                conversaId = conversa.id;
                ultimoId = 0;
                el.mensagens.innerHTML = '';
            }
            await buscarMensagens();
        } else if (conversaId === null) {
            renderBoasVindas();
            aplicarStatus('Aberta');
        }
    }

    async function sincronizar() {
        if (MODO !== 'cliente') return;

        if (!aberto) {
            const json = await chamar({ acao: 'estado' });
            if (json.ok) atualizarBadge(json.nao_lidas);
            return;
        }

        if (conversaId === null && !novaIniciada) {
            const json = await chamar({ acao: 'estado' });
            if (json.ok && json.conversa && json.conversa.status === 'Aberta') {
                conversaId = json.conversa.id;
                ultimoId = 0;
                el.mensagens.innerHTML = '';
            }
        }
        await buscarMensagens();
    }

    function agendar() {
        clearTimeout(temporizador);
        if (MODO !== 'cliente') return;
        const espera = document.hidden ? 20000 : (aberto ? 3000 : 15000);
        temporizador = setTimeout(async function () {
            await sincronizar();
            agendar();
        }, espera);
    }

    async function abrir() {
        aberto = true;
        widget.hidden = false;
        launcher.hidden = true;
        launcher.setAttribute('aria-expanded', 'true');
        atualizarBadge(0);

        if (MODO === 'cliente') {
            await carregarConversaInicial();
            rolarParaFim();
            if (el.input && !el.form.hidden) el.input.focus();
            agendar();
        } else {
            renderBoasVindas();
            el.fechar.focus();
        }
    }

    function fechar() {
        aberto = false;
        widget.hidden = true;
        launcher.hidden = false;
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
        agendar();
    }

    launcher.addEventListener('click', abrir);
    if (el.abrirPagina) el.abrirPagina.addEventListener('click', abrir);
    el.fechar.addEventListener('click', fechar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && aberto) fechar();
    });

    if (MODO === 'cliente') {
        el.form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const texto = el.input.value.trim();
            if (texto === '' || ocupado) return;

            ocupado = true;
            el.enviar.disabled = true;
            try {
                const json = await chamar({ acao: 'enviar', conteudo: texto }, true);
                if (!json.ok) {
                    tratarErro(json);
                    return;
                }
                el.input.value = '';

                if (conversaId !== json.conversa_id) {
                    conversaId = json.conversa_id;
                    ultimoId = 0;
                    el.mensagens.innerHTML = '';
                }
                novaIniciada = false;
                await buscarMensagens();
                rolarParaFim();
            } finally {
                ocupado = false;
                el.enviar.disabled = false;
                el.input.focus();
            }
        });

        el.encerrar.addEventListener('click', async function () {
            if (conversaId === null) return;
            if (!window.confirm('Encerrar esta conversa?')) return;

            const json = await chamar({ acao: 'encerrar', conversa_id: conversaId }, true);
            if (!json.ok) {
                tratarErro(json);
                return;
            }
            aplicarStatus('Encerrada');
        });

        el.nova.addEventListener('click', function () {
            novaIniciada = true;
            limparConversa();
            aplicarStatus('Aberta');
            el.input.focus();
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                sincronizar();
                agendar();
            }
        });

        sincronizar();
        agendar();
    }
})();
