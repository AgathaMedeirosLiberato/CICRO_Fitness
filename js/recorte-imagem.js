(function () {
    'use strict';

    var VIEW_W = 360, VIEW_H = 450;
    var OUT_W = 1000, OUT_H = 1250;
    var cssInjetado = false;

    function injetarCss() {
        if (cssInjetado) return;
        cssInjetado = true;
        var st = document.createElement('style');
        st.textContent =
            '.rc-fundo{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.72);display:flex;align-items:center;justify-content:center;padding:16px;font-family:Arial,Helvetica,sans-serif}' +
            '.rc-caixa{background:#fff;border-radius:16px;padding:20px;width:100%;max-width:420px;max-height:96vh;overflow:auto;box-shadow:0 20px 50px rgba(0,0,0,.4)}' +
            '.rc-caixa h3{margin:0 0 4px;font-size:1.1rem;color:#111}' +
            '.rc-caixa p.rc-dica{margin:0 0 12px;font-size:.8rem;color:#666;line-height:1.35}' +
            '.rc-canvas{display:block;width:100%;height:auto;border-radius:10px;background:#fff;border:1px solid #ddd;cursor:grab;touch-action:none}' +
            '.rc-canvas.rc-arrastando{cursor:grabbing}' +
            '.rc-zoom{display:flex;align-items:center;gap:10px;margin:12px 0 8px;font-size:.8rem;color:#444}' +
            '.rc-zoom input{flex:1}' +
            '.rc-linha{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}' +
            '.rc-btn{padding:9px 14px;border-radius:99px;border:1px solid #bbb;background:#fff;color:#222;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit}' +
            '.rc-btn:hover{background:#f1f1f1}' +
            '.rc-btn--escuro{background:#111;color:#fff;border-color:#111}' +
            '.rc-btn--escuro:hover{background:#333}' +
            '.rc-rodape{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}' +
            '.rc-btn-recortar{position:absolute;left:12px;bottom:12px;z-index:2;padding:8px 14px;border:none;border-radius:99px;background:#111;color:#fff;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:0 2px 8px rgba(0,0,0,.25)}' +
            '.rc-btn-recortar:hover{background:#333}';
        document.head.appendChild(st);
    }

    function el(tag, cls, texto) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (texto) e.textContent = texto;
        return e;
    }

    window.abrirRecorteImagem = function (file) {
        injetarCss();
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(file);
            var img = new Image();

            img.onerror = function () {
                URL.revokeObjectURL(url);
                alert('Não foi possível abrir "' + file.name + '". Use um arquivo JPG, PNG ou WEBP.');
                resolve(null);
            };

            img.onload = function () {
                var iw = img.naturalWidth, ih = img.naturalHeight;
                var cover = Math.max(VIEW_W / iw, VIEW_H / ih);
                var contain = Math.min(VIEW_W / iw, VIEW_H / ih);
                var minS = contain, maxS = cover * 4;
                var s = cover, left = 0, top = 0;

                var fundo = el('div', 'rc-fundo');
                fundo.setAttribute('role', 'dialog');
                fundo.setAttribute('aria-modal', 'true');
                var caixa = el('div', 'rc-caixa');
                caixa.appendChild(el('h3', '', 'Recortar e centralizar a foto'));
                caixa.appendChild(el('p', 'rc-dica', 'Arraste a foto para posicionar e use o zoom. O quadro é do mesmo tamanho que aparece no catálogo. As linhas tracejadas marcam o centro.'));

                var canvas = el('canvas', 'rc-canvas');
                canvas.width = VIEW_W; canvas.height = VIEW_H;
                caixa.appendChild(canvas);

                var zoomBox = el('label', 'rc-zoom');
                zoomBox.appendChild(document.createTextNode('Zoom'));
                var zoom = document.createElement('input');
                zoom.type = 'range'; zoom.min = '0'; zoom.max = '1000'; zoom.step = '1';
                zoomBox.appendChild(zoom);
                caixa.appendChild(zoomBox);

                var linha = el('div', 'rc-linha');
                var bCentro = el('button', 'rc-btn', 'Centralizar'); bCentro.type = 'button';
                var bPreencher = el('button', 'rc-btn', 'Preencher quadro'); bPreencher.type = 'button';
                var bInteira = el('button', 'rc-btn', 'Foto inteira (fundo branco)'); bInteira.type = 'button';
                linha.appendChild(bCentro); linha.appendChild(bPreencher); linha.appendChild(bInteira);
                caixa.appendChild(linha);

                var rodape = el('div', 'rc-rodape');
                var bCancelar = el('button', 'rc-btn', 'Cancelar'); bCancelar.type = 'button';
                var bOriginal = el('button', 'rc-btn', 'Usar original'); bOriginal.type = 'button';
                var bOk = el('button', 'rc-btn rc-btn--escuro', 'Aplicar recorte'); bOk.type = 'button';
                rodape.appendChild(bCancelar); rodape.appendChild(bOriginal); rodape.appendChild(bOk);
                caixa.appendChild(rodape);

                fundo.appendChild(caixa);
                document.body.appendChild(fundo);

                function limitar() {
                    var sw = iw * s, sh = ih * s;
                    left = sw >= VIEW_W ? Math.min(0, Math.max(VIEW_W - sw, left)) : Math.min(VIEW_W - sw, Math.max(0, left));
                    top  = sh >= VIEW_H ? Math.min(0, Math.max(VIEW_H - sh, top))  : Math.min(VIEW_H - sh, Math.max(0, top));
                }
                function centralizar() {
                    left = (VIEW_W - iw * s) / 2;
                    top = (VIEW_H - ih * s) / 2;
                }
                function atualizarSlider() {
                    zoom.value = String(Math.round((s - minS) / (maxS - minS) * 1000));
                }
                function desenhar() {
                    var g = canvas.getContext('2d');
                    g.fillStyle = '#fff';
                    g.fillRect(0, 0, VIEW_W, VIEW_H);
                    g.drawImage(img, left, top, iw * s, ih * s);
                    g.save();
                    g.strokeStyle = 'rgba(255,0,80,.75)';
                    g.lineWidth = 1;
                    g.setLineDash([6, 5]);
                    g.beginPath();
                    g.moveTo(VIEW_W / 2, 0); g.lineTo(VIEW_W / 2, VIEW_H);
                    g.moveTo(0, VIEW_H / 2); g.lineTo(VIEW_W, VIEW_H / 2);
                    g.stroke();
                    g.restore();
                }
                function aplicarZoom(novoS, ax, ay) {
                    novoS = Math.min(maxS, Math.max(minS, novoS));
                    if (ax === undefined) { ax = VIEW_W / 2; ay = VIEW_H / 2; }
                    left = ax - (ax - left) * (novoS / s);
                    top  = ay - (ay - top)  * (novoS / s);
                    s = novoS;
                    limitar(); atualizarSlider(); desenhar();
                }

                centralizar(); limitar(); atualizarSlider(); desenhar();

                zoom.addEventListener('input', function () {
                    aplicarZoom(minS + (maxS - minS) * (zoom.value / 1000));
                });
                bCentro.addEventListener('click', function () { centralizar(); limitar(); desenhar(); });
                bPreencher.addEventListener('click', function () { s = cover; centralizar(); limitar(); atualizarSlider(); desenhar(); });
                bInteira.addEventListener('click', function () { s = contain; centralizar(); limitar(); atualizarSlider(); desenhar(); });

                var arrastando = false, ux = 0, uy = 0;
                function fator() { return VIEW_W / canvas.getBoundingClientRect().width; }
                canvas.addEventListener('pointerdown', function (e) {
                    arrastando = true; ux = e.clientX; uy = e.clientY;
                    canvas.setPointerCapture(e.pointerId);
                    canvas.classList.add('rc-arrastando');
                });
                canvas.addEventListener('pointermove', function (e) {
                    if (!arrastando) return;
                    var f = fator();
                    left += (e.clientX - ux) * f; top += (e.clientY - uy) * f;
                    ux = e.clientX; uy = e.clientY;
                    limitar(); desenhar();
                });
                function soltar() { arrastando = false; canvas.classList.remove('rc-arrastando'); }
                canvas.addEventListener('pointerup', soltar);
                canvas.addEventListener('pointercancel', soltar);
                canvas.addEventListener('wheel', function (e) {
                    e.preventDefault();
                    var r = canvas.getBoundingClientRect(), f = VIEW_W / r.width;
                    aplicarZoom(s * (e.deltaY < 0 ? 1.08 : 1 / 1.08), (e.clientX - r.left) * f, (e.clientY - r.top) * f);
                }, { passive: false });

                function fechar(resultado) {
                    document.removeEventListener('keydown', teclas);
                    fundo.remove();
                    URL.revokeObjectURL(url);
                    resolve(resultado);
                }
                function teclas(e) {
                    if (e.key === 'Escape') { fechar(null); return; }
                    var passo = 6, mexeu = true;
                    if (e.key === 'ArrowLeft') left -= passo;
                    else if (e.key === 'ArrowRight') left += passo;
                    else if (e.key === 'ArrowUp') top -= passo;
                    else if (e.key === 'ArrowDown') top += passo;
                    else mexeu = false;
                    if (mexeu) { e.preventDefault(); limitar(); desenhar(); }
                }
                document.addEventListener('keydown', teclas);

                bCancelar.addEventListener('click', function () { fechar(null); });
                bOriginal.addEventListener('click', function () { fechar(file); });
                bOk.addEventListener('click', function () {
                    var k = OUT_W / VIEW_W;
                    var out = document.createElement('canvas');
                    out.width = OUT_W; out.height = OUT_H;
                    var g = out.getContext('2d');
                    g.imageSmoothingQuality = 'high';
                    g.fillStyle = '#fff';
                    g.fillRect(0, 0, OUT_W, OUT_H);
                    g.drawImage(img, left * k, top * k, iw * s * k, ih * s * k);
                    out.toBlob(function (blob) {
                        if (!blob) { alert('Não foi possível gerar a foto recortada.'); return; }
                        var base = (file.name || 'foto').replace(/\.[^.]+$/, '');
                        fechar(new File([blob], base + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', 0.92);
                });

                bOk.focus();
            };

            img.src = url;
        });
    };

    window.criarBotaoRecortar = function (texto) {
        injetarCss();
        var b = el('button', 'rc-btn-recortar', texto || 'Recortar / centralizar');
        b.type = 'button';
        return b;
    };
})();