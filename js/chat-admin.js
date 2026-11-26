(function () {
    const raiz = document.getElementById('atendimento');
    if (!raiz) return;

    const API = raiz.dataset.api;
    const CSRF = raiz.dataset.csrf;
    const TITULO_PAGINA = document.title;

    const el = {
        abas: raiz.querySelectorAll('.atendimento__abas button'),
        contagemAbertas: document.getElementById('contagemAbertas'),
        lista: document.getElementById('listaConversas'),
        semConversa: document.getElementById('semConversa'),
        area: document.getElementById('areaConversa'),
        voltarLista: document.getElementById('btnVoltarLista'),
        clienteNome: document.getElementById('clienteNome'),
        info: document.getElementById('conversaInfo'),
        encerrar: document.getElementById('btnEncerrar'),
        mensagens: document.getElementById('adminMensagens'),
        erro: document.getElementById('adminErro'),
        form: document.getElementById('adminForm'),
        input: document.getElementById('adminInput'),
        enviar: document.getElementById('adminEnviar'),
        avisoEncerrada: document.getElementById('avisoEncerrada')
    };

    let statusAba = 'Aberta';
    let conversaId = null;
    let ultimoId = 0;
    let ocupado = false;
    let temporizadorMsgs = null;
    let temporizadorLista = null;
    let temporizadorErro = null;

    function horaCurta(data) {
        if (!data) return '';
        const dia = data.slice(0, 10);
        const hora = data.slice(11, 16);
        const h = new Date();
        const hoje = h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0') + '-' + String(h.getDate()).padStart(2, '0');
        return dia === hoje ? hora : dia.slice(8, 10) + '/' + dia.slice(5, 7) + ' ' + hora;
    }

    function dataCompleta(data) {
        if (!data) return '';
        return data.slice(8, 10) + '/' + data.slice(5, 7) + '/' + data.slice(0, 4) + ' ' + data.slice(11, 16);
    }

    function pertoDoFim() {
        return el.mensagens.scrollHeight - el.mensagens.scrollTop - el.mensagens.clientHeight < 80;
    }

    function rolarParaFim() {
        el.mensagens.scrollTop = el.mensagens.scrollHeight;
    }

    function mostrarErro(texto) {
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
            setTimeout(function () { window.location.href = '../user/login.php'; }, 1500);
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

    function desenharLista(conversas) {
        el.lista.innerHTML = '';

        if (conversas.length === 0) {
            const vazio = document.createElement('div');
            vazio.className = 'atendimento__vazio-lista';
            vazio.textContent = statusAba === 'Aberta'
                ? 'Nenhuma conversa aberta no momento.'
                : 'Nenhuma conversa encerrada.';
            el.lista.appendChild(vazio);
            return;
        }

        conversas.forEach(function (c) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'conversa-item' + (c.conversa_id === conversaId ? ' is-ativa' : '');

            const linha = document.createElement('div');
            linha.className = 'conversa-item__linha';

            const nome = document.createElement('span');
            nome.className = 'conversa-item__nome';
            nome.textContent = c.cliente_nome;

            const hora = document.createElement('span');
            hora.className = 'conversa-item__hora';
            hora.textContent = horaCurta(c.atividade);

            linha.appendChild(nome);
            linha.appendChild(hora);

            const linha2 = document.createElement('div');
            linha2.className = 'conversa-item__linha';

            const previa = document.createElement('span');
            previa.className = 'conversa-item__previa';
            previa.textContent = c.ultima || '';
            linha2.appendChild(previa);

            if (c.nao_lidas > 0) {
                const nl = document.createElement('span');
                nl.className = 'conversa-item__nao-lida';
                nl.textContent = c.nao_lidas > 99 ? '99+' : String(c.nao_lidas);
                nl.title = c.nao_lidas + ' mensagem(ns) não lida(s)';
                linha2.appendChild(nl);
            }

            item.appendChild(linha);
            item.appendChild(linha2);
            item.addEventListener('click', function () { abrirConversa(c); });
            el.lista.appendChild(item);
        });
    }

    async function carregarLista() {
        const json = await chamar({ acao: 'listar', status: statusAba });
        if (!json.ok) {
            if (!json.rede) tratarErro(json);
            return;
        }
        desenharLista(json.conversas);

        const total = json.total_nao_lidas;
        el.contagemAbertas.textContent = total > 99 ? '99+' : String(total);
        el.contagemAbertas.hidden = total <= 0;
        document.title = total > 0 ? '(' + total + ') ' + TITULO_PAGINA : TITULO_PAGINA;
    }

    function agendarLista() {
        clearTimeout(temporizadorLista);
        temporizadorLista = setTimeout(async function () {
            if (!document.hidden) await carregarLista();
            agendarLista();
        }, 4000);
    }

    function adicionarMensagem(m) {
        const minha = m.de === 'equipe';
        const balao = document.createElement('div');
        balao.className = 'chat-msg ' + (minha ? 'chat-msg--eu' : 'chat-msg--outro');

        const autor = document.createElement('span');
        autor.className = 'chat-msg__autor';
        autor.textContent = m.autor;
        if (minha) autor.style.color = '#cfcfcf';
        balao.appendChild(autor);

        balao.appendChild(document.createTextNode(m.conteudo));

        const hora = document.createElement('span');
        hora.className = 'chat-msg__hora';
        hora.textContent = horaCurta(m.data);
        balao.appendChild(hora);

        el.mensagens.appendChild(balao);
    }

    function aplicarStatus(status) {
        const encerrada = status !== 'Aberta';
        el.form.hidden = encerrada;
        el.encerrar.hidden = encerrada;
        el.avisoEncerrada.hidden = !encerrada;
    }

    async function carregarMensagens() {
        if (conversaId === null) return;
        const idAtual = conversaId;
        const json = await chamar({ acao: 'mensagens', conversa_id: idAtual, depois_id: ultimoId });

        if (idAtual !== conversaId) return;
        if (!json.ok) {
            if (!json.rede) tratarErro(json);
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

    function agendarMensagens() {
        clearTimeout(temporizadorMsgs);
        temporizadorMsgs = setTimeout(async function () {
            if (!document.hidden) await carregarMensagens();
            agendarMensagens();
        }, 3000);
    }

    async function abrirConversa(c) {
        conversaId = c.conversa_id;
        ultimoId = 0;

        el.clienteNome.textContent = c.cliente_nome;
        el.info.textContent = 'Iniciada em ' + dataCompleta(c.data_criacao) +
            (c.atendente_nome ? ' · Atendente: ' + c.atendente_nome : '');
        el.mensagens.innerHTML = '';
        el.erro.hidden = true;

        el.semConversa.hidden = true;
        el.area.hidden = false;
        raiz.classList.add('mostrando-conversa');
        aplicarStatus(c.status);

        await carregarMensagens();
        rolarParaFim();
        if (!el.form.hidden) el.input.focus();

        agendarMensagens();
        carregarLista();
    }

    el.abas.forEach(function (aba) {
        aba.addEventListener('click', function () {
            statusAba = aba.dataset.status;
            el.abas.forEach(function (b) {
                const ativa = b === aba;
                b.classList.toggle('is-active', ativa);
                b.setAttribute('aria-selected', ativa ? 'true' : 'false');
            });
            carregarLista();
        });
    });

    el.voltarLista.addEventListener('click', function () {
        raiz.classList.remove('mostrando-conversa');
    });

    el.form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const texto = el.input.value.trim();
        if (texto === '' || conversaId === null || ocupado) return;

        ocupado = true;
        el.enviar.disabled = true;
        try {
            const json = await chamar({ acao: 'enviar', conversa_id: conversaId, conteudo: texto }, true);
            if (!json.ok) {
                tratarErro(json);
                return;
            }
            el.input.value = '';
            await carregarMensagens();
            rolarParaFim();
            carregarLista();
        } finally {
            ocupado = false;
            el.enviar.disabled = false;
            el.input.focus();
        }
    });

    el.encerrar.addEventListener('click', async function () {
        if (conversaId === null) return;
        if (!window.confirm('Encerrar esta conversa? O cliente poderá iniciar uma nova depois.')) return;

        const json = await chamar({ acao: 'encerrar', conversa_id: conversaId }, true);
        if (!json.ok) {
            tratarErro(json);
            return;
        }
        aplicarStatus('Encerrada');
        carregarLista();
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            carregarLista();
            carregarMensagens();
        }
    });

    carregarLista();
    agendarLista();
})();
