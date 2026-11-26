<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/conta_funcoes.php';
require_once __DIR__ . '/../../models/cpf_cripto.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];
if (empty($_SESSION['csrf_cliente'])) {
    $_SESSION['csrf_cliente'] = bin2hex(random_bytes(16));
}
$flash = $_SESSION['flash_perfil'] ?? null;
unset($_SESSION['flash_perfil']);

function aviso(?array $flash, string $secao): string {
    if (!$flash || $flash['secao'] !== $secao) return '';
    return '<div class="aviso ' . ($flash['ok'] ? 'aviso-ok' : 'aviso-erro') . '">'
         . htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

try {
    $stmt = $pdo->prepare("SELECT nome, email, telefone, tipo_usuario FROM usuario WHERE id = ? AND ativo = 1");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    if (!$usuario) {
        header('Location: ../../controllers/logout.php');
        exit;
    }

    $cpf_cliente = null;
    $sol_cpf = null;
    $tem_google = false;
    $enderecos = [];
    if ($usuario['tipo_usuario'] === 'cliente') {
        $stmt = $pdo->prepare("SELECT cpf FROM cliente WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $cpf_cliente = cpf_exibir($stmt->fetchColumn() ?: null);
        $sol_cpf = ultima_solicitacao_cpf($pdo, $id_usuario);
        $stmt = $pdo->prepare("SELECT google_id IS NOT NULL FROM usuario WHERE id = ?");
        $stmt->execute([$id_usuario]);
        $tem_google = (bool)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT id, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal
             FROM endereco WHERE fk_cliente = ? ORDER BY principal DESC, id DESC"
        );
        $stmt->execute([$id_usuario]);
        $enderecos = $stmt->fetchAll();
    }
    $editando = null;
    $id_editar = (int)($_GET['editar'] ?? 0);
    if ($id_editar > 0) {
        foreach ($enderecos as $end) {
            if ((int)$end['id'] === $id_editar) { $editando = $end; break; }
        }
    }
} catch (PDOException $e) {
    error_log('Erro ao carregar perfil: ' . $e->getMessage());
    die('Erro ao carregar os dados do perfil.');
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$eh_cliente = $usuario['tipo_usuario'] === 'cliente';
$cpf_editavel = $eh_cliente && (!$cpf_cliente || ($sol_cpf && $sol_cpf['status'] === 'aprovada'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CICRO - Meu Perfil</title>
    <link rel="stylesheet" href="../../css/style7.css">
  <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="../../css/perfil-modal.css">
    <style>
        .end-acoes { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .end-acoes form { margin:0; }
        .end-btn { display:inline-block; padding:6px 14px; border:1px solid #bbb; border-radius:99px; background:#fff;
                   color:#222; font-size:.8rem; font-weight:600; text-decoration:none; cursor:pointer; font-family:inherit; }
        .end-btn:hover { background:#111; color:#fff; border-color:#111; }
        .end-btn--perigo { color:#a12622; border-color:#e3b3b1; }
        .end-btn--perigo:hover { background:#a12622; border-color:#a12622; color:#fff; }
        .end-cancelar { margin-left:10px; padding:11px 18px; }
    </style>
</head>
<body>
    <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <li><a href="meus_pedidos.php">Meus pedidos</a></li>
        <li><a href="perfil.php" class="is-active">Meu Perfil</a></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

    <main class="perfil-container">
        <aside class="perfil-sidebar">
            <div class="avatar-fake"><?= h(mb_strtoupper(mb_substr($usuario['nome'], 0, 1))) ?></div>
            <h2><?= h($usuario['nome']) ?></h2>
            <p class="user-email"><?= h($usuario['email']) ?></p>
            <p class="user-phone">📞 <?= h($usuario['telefone'] ?: 'Não informado') ?></p>
            <a href="meus_pedidos.php" class="btn-pedidos">Meus pedidos</a>
            <a href="../../controllers/logout.php" class="btn-logout">Sair da conta</a>
        </aside>

        <section class="perfil-conteudo">
            <div class="meus-enderecos">
                <h3>Meus dados</h3>
                <?= aviso($flash, 'dados') ?>
                <form action="../../controllers/perfil_atualizar.php" method="POST">
                    <input type="hidden" name="acao" value="dados">
                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" id="nome" value="<?= h($usuario['nome']) ?>" maxlength="150" required>
                        </div>
                        <div class="form-grupo">
                            <label for="telefone">Telefone</label>
                            <input type="tel" name="telefone" id="telefone" value="<?= h($usuario['telefone']) ?>" placeholder="(48) 99999-9999" maxlength="15"
                                   inputmode="numeric" autocomplete="tel-national" data-mask="telefone"
                                   pattern="\(\d{2}\) \d{4,5}-\d{4}" title="Use o formato (48) 99999-9999">
                        </div>
                    </div>
                    <?php if ($eh_cliente): ?>
                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="cpf">CPF <?= !$cpf_cliente ? '<small>(obrigatório para comprar)</small>' : ($cpf_editavel ? '<small>(alteração aprovada)</small>' : '') ?></label>
                            <input type="text" name="cpf" id="cpf" value="<?= h($cpf_cliente) ?>" placeholder="000.000.000-00" maxlength="14"
                                   inputmode="numeric" data-mask="cpf" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}"
                                   title="Digite um CPF válido: 000.000.000-00" <?= $cpf_editavel ? '' : 'readonly' ?>>
                            <?php if ($cpf_cliente && $sol_cpf && $sol_cpf['status'] === 'pendente'): ?>
                                <div class="pf-cpf-estado pf-cpf-estado--espera">⏳ Pedido de alteração enviado em <?= h(date('d/m/Y H:i', strtotime($sol_cpf['data_solicitacao']))) ?>. Aguarde a aprovação do administrador.</div>
                            <?php elseif ($cpf_cliente && $sol_cpf && $sol_cpf['status'] === 'aprovada'): ?>
                                <div class="pf-cpf-estado pf-cpf-estado--ok">✅ O administrador aprovou. Digite o novo CPF acima e clique em <strong>Salvar dados</strong>. Só dá para trocar uma vez por aprovação.</div>
                            <?php elseif ($cpf_cliente): ?>
                                <?php if ($sol_cpf && $sol_cpf['status'] === 'recusada'): ?>
                                    <div class="pf-cpf-estado pf-cpf-estado--recusada">Seu último pedido de alteração foi recusado<?= $sol_cpf['resposta'] ? ': ' . h($sol_cpf['resposta']) : '.' ?></div>
                                <?php endif; ?>
                                <div class="pf-cpf-acao"><button type="button" class="pf-btn pf-btn--mini" data-abrir="modal-cpf">Solicitar alteração do CPF</button></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="email">E-mail</label>
                            <input type="email" name="email" id="email" value="<?= h($usuario['email']) ?>" maxlength="150" required>
                        </div>
                        <div class="form-grupo">
                            <label for="senha_atual_dados">Senha atual <small>(só se for trocar o e-mail)</small></label>
                            <input type="password" name="senha_atual" id="senha_atual_dados" autocomplete="current-password">
                        </div>
                    </div>
                    <button type="submit" class="btn-salvar">Salvar dados</button>
                </form>
            </div>

            <div class="meus-enderecos">
                <h3>Alterar senha</h3>
                <?= aviso($flash, 'senha') ?>
                <form action="../../controllers/perfil_atualizar.php" method="POST">
                    <input type="hidden" name="acao" value="senha">
                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="senha_atual">Senha atual</label>
                            <input type="password" name="senha_atual" id="senha_atual" autocomplete="current-password" required>
                        </div>
                    </div>
                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="nova_senha">Nova senha (mín. 6 caracteres)</label>
                            <input type="password" name="nova_senha" id="nova_senha" minlength="6" autocomplete="new-password" required>
                        </div>
                        <div class="form-grupo">
                            <label for="confirmar_senha">Confirmar nova senha</label>
                            <input type="password" name="confirmar_senha" id="confirmar_senha" minlength="6" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-salvar">Alterar senha</button>
                </form>
            </div>

            <?php if ($eh_cliente): ?>
            <div class="meus-enderecos">
                <h3>Seus endereços</h3>
                <?= aviso($flash, 'endereco') ?>
                <?php if (!$enderecos): ?>
                    <p>Você ainda não tem nenhum endereço cadastrado.</p>
                <?php else: ?>
                    <div class="enderecos-grid">
                        <?php foreach ($enderecos as $end): ?>
                            <div class="card-endereco <?= $end['principal'] ? 'principal' : '' ?>">
                                <strong>📍 <?= h($end['apelido']) ?></strong>
                                <?php if ($end['principal']): ?><span class="badge-principal">Padrão</span><?php endif; ?>
                                <p>
                                    <?= h($end['rua'] . ', nº ' . $end['numero']) ?>
                                    <?= $end['complemento'] ? ' - ' . h($end['complemento']) : '' ?><br>
                                    <?= h($end['bairro'] . ' - ' . $end['cidade'] . '/' . $end['estado']) ?><br>
                                    <small>CEP: <?= h($end['cep']) ?></small>
                                </p>
                                <div class="end-acoes">
                                    <a class="end-btn" href="perfil.php?editar=<?= (int)$end['id'] ?>#endereco-form">Editar</a>
                                    <?php if (!$end['principal']): ?>
                                        <form action="../../controllers/endereco_acoes.php" method="POST">
                                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_cliente']) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$end['id'] ?>">
                                            <input type="hidden" name="acao" value="padrao">
                                            <button type="submit" class="end-btn">Tornar padrão</button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="../../controllers/endereco_acoes.php" method="POST" onsubmit="return confirm('Excluir este endereço?');">
                                        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_cliente']) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$end['id'] ?>">
                                        <input type="hidden" name="acao" value="excluir">
                                        <button type="submit" class="end-btn end-btn--perigo">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="novo-endereco-sessao" id="novo-endereco">
                <h3 id="endereco-form"><?= $editando ? 'Editar endereço: ' . h($editando['apelido']) : 'Adicionar novo endereço' ?></h3>
                <form action="../../controllers/salvar_endereco.php" method="POST">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= (int)$editando['id'] ?>">
                    <?php endif; ?>
                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="cep">CEP (preenche o endereço automaticamente)</label>
                            <input type="text" name="cep" id="cep" placeholder="Ex: 88808-052" maxlength="9" inputmode="numeric" required value="<?= h($editando['cep'] ?? '') ?>">
                        </div>
                        <div class="form-grupo">
                            <label for="apelido">Apelido (Casa, Trabalho...)</label>
                            <input type="text" name="apelido" id="apelido" placeholder="Ex: Minha casa" required value="<?= h($editando['apelido'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-linha">
                        <div class="form-grupo grande">
                            <label for="rua">Rua / Logradouro</label>
                            <input type="text" name="rua" id="rua" required value="<?= h($editando['rua'] ?? '') ?>">
                        </div>
                        <div class="form-grupo pequeno">
                            <label for="numero">Número</label>
                            <input type="text" name="numero" id="numero" required value="<?= h($editando['numero'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="complemento">Complemento (opcional)</label>
                            <input type="text" name="complemento" id="complemento" placeholder="Apto, bloco..." value="<?= h($editando['complemento'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-linha">
                        <div class="form-grupo">
                            <label for="bairro">Bairro</label>
                            <input type="text" name="bairro" id="bairro" required value="<?= h($editando['bairro'] ?? '') ?>">
                        </div>
                        <div class="form-grupo">
                            <label for="cidade">Cidade</label>
                            <input type="text" name="cidade" id="cidade" required value="<?= h($editando['cidade'] ?? '') ?>">
                        </div>
                        <div class="form-grupo pequeno">
                            <label for="estado">UF</label>
                            <input type="text" name="estado" id="estado" maxlength="2" required value="<?= h($editando['estado'] ?? '') ?>">
                        </div>
                    </div>

                    <label class="form-checkbox">
                        <input type="checkbox" name="principal" id="principal" <?= (!empty($editando['principal'])) ? 'checked' : '' ?>>
                        Definir como endereço padrão para entregas
                    </label>

                    <button type="submit" class="btn-salvar"><?= $editando ? 'Salvar alterações' : 'Salvar endereço' ?></button>
                    <?php if ($editando): ?>
                        <a href="perfil.php#novo-endereco" class="end-btn end-cancelar">Cancelar edição</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="pf-zona" id="excluir-conta">
                <div>
                    <h3>Excluir minha conta</h3>
                    <p>Apaga seus dados pessoais, endereços, carrinho e conversas de suporte. Essa ação não pode ser desfeita.</p>
                    <?= aviso($flash, 'conta') ?>
                </div>
                <button type="button" class="pf-btn pf-btn--perigo" data-abrir="modal-excluir">Excluir minha conta</button>
            </div>

            <dialog class="pf-modal" id="modal-excluir" aria-labelledby="t-excluir">
                <form action="../../controllers/excluir_conta.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_cliente']) ?>">
                    <div class="pf-modal__corpo">
                        <div class="pf-modal__icone" aria-hidden="true">⚠</div>
                        <h2 id="t-excluir">Tem certeza que deseja excluir a conta?</h2>
                        <p class="pf-modal__texto">Seus dados serão deletados completamente: cadastro, CPF, endereços, carrinho, avaliações e conversas de suporte. <strong>Não dá para desfazer.</strong></p>
                        <p class="pf-modal__nota">Os pedidos que você já fez continuam no histórico da loja apenas com valores e produtos, sem seu nome, e-mail, CPF ou endereço. Pedidos em andamento precisam ser concluídos antes.</p>
                    </div>
                    <div class="pf-modal__campos">
                        <?php if (!$tem_google): ?>
                        <label>Sua senha
                            <input type="password" name="senha" id="ex-senha" autocomplete="current-password" required>
                        </label>
                        <?php endif; ?>
                        <label>Digite EXCLUIR para confirmar
                            <input type="text" name="confirmacao" id="ex-confirma" placeholder="EXCLUIR" autocomplete="off" required>
                        </label>
                    </div>
                    <div class="pf-modal__acoes">
                        <button type="button" class="pf-btn" data-fechar>Cancelar</button>
                        <button type="submit" class="pf-btn pf-btn--perigo-cheio" id="ex-enviar" disabled>Excluir definitivamente</button>
                    </div>
                </form>
            </dialog>

            <dialog class="pf-modal" id="modal-cpf" aria-labelledby="t-cpf">
                <form action="../../controllers/perfil_atualizar.php" method="POST">
                    <input type="hidden" name="acao" value="solicitar_cpf">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_cliente']) ?>">
                    <div class="pf-modal__corpo">
                        <div class="pf-modal__icone pf-modal__icone--info" aria-hidden="true">🪪</div>
                        <h2 id="t-cpf">Solicitar alteração do CPF</h2>
                        <p class="pf-modal__texto">Por segurança, o CPF só muda depois que o administrador aprovar. Conte o motivo e enviaremos o pedido para análise.</p>
                    </div>
                    <div class="pf-modal__campos">
                        <label>Motivo da alteração
                            <textarea name="motivo" maxlength="255" minlength="5" placeholder="Ex.: digitei o CPF errado no cadastro." required></textarea>
                        </label>
                    </div>
                    <div class="pf-modal__acoes">
                        <button type="button" class="pf-btn" data-fechar>Cancelar</button>
                        <button type="submit" class="pf-btn pf-btn--escuro">Enviar pedido</button>
                    </div>
                </form>
            </dialog>
            <?php else: ?>
                <div class="meus-enderecos">
                    <h3>Conta de funcionário</h3>
                    <p>Esta conta acessa o <a href="../admin/inicio.php">painel administrativo</a>.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="../../js/script.js"></script>
    <script>
    (function () {
        document.querySelectorAll('[data-abrir]').forEach(function (b) {
            b.addEventListener('click', function () { var d = document.getElementById(b.dataset.abrir); if (d && d.showModal) d.showModal(); });
        });
        document.querySelectorAll('dialog.pf-modal').forEach(function (d) {
            d.querySelectorAll('[data-fechar]').forEach(function (b) { b.addEventListener('click', function () { d.close(); }); });
            d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
        });
        var conf = document.getElementById('ex-confirma'), senha = document.getElementById('ex-senha'), env = document.getElementById('ex-enviar');
        function checa() { env.disabled = !(conf.value.trim().toUpperCase() === 'EXCLUIR' && (!senha || senha.value !== '')); }
        if (conf) { conf.addEventListener('input', checa); if (senha) senha.addEventListener('input', checa); }
    })();
    </script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>