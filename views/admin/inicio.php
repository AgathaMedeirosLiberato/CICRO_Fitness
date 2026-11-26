<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario = (int)($_SESSION['usuario_id'] ?? 0);
if ($id_usuario <= 0) {
    header('Location: login.php');
    exit;
}

$avatar_padrao = '../../images/Support.png';

$stmt = $conn->prepare("SELECT nome, foto_perfil FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

$foto_atual = !empty($admin['foto_perfil']) ? '../../' . ltrim($admin['foto_perfil'], './') : $avatar_padrao;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Painel Administrativo | CICRO</title>

    <link rel="stylesheet" href="../../css/style6.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .avatar-circulo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/notificacoes-admin.css">
</head>

<body>

    <!-- CABEÇALHO -->
    <header class="site-header">
    <a class="site-header__logo" href="inicio.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="../../controllers/logout.php" id="logoutBtn" class="site-header__cta">Sair</a></li>
      </ul>
    </nav>
  </header>


    <!-- CONTEÚDO PRINCIPAL -->
    <main class="dashboard">
        <div class="painel">
            <!-- LADO ESQUERDO -->
            <section class="perfil-area">

                <div class="perfil-conteudo">

                    <div class="boas-vindas">
                        <span class="pequeno-titulo">BEM-VINDO(A)</span>

                        <h1>
                            PAINEL DE<br>
                            <strong>CONTROLE</strong>
                        </h1>
                    </div>
                    <!-- AVATAR DINÂMICO -->
                    <div class="avatar">
                        <div class="avatar-circulo">
                            <img src="<?php echo htmlspecialchars($foto_atual); ?>" 
                                 alt="Foto de perfil" 
                                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($avatar_padrao); ?>';">
                        </div>

                        <span class="avatar-status"></span>
                    </div>

                    <div class="perfil-admin">
                        <span>ACESSO</span>
                        <strong><?php echo htmlspecialchars($admin['nome'] ?? 'ADMINISTRADOR'); ?></strong>
                    </div>

                    <a href="perfil-admin.php" class="btn-perfil">
                        <i data-lucide="circle-user-round"></i>
                        <span>Visualizar perfil</span>
                        <i data-lucide="arrow-up-right" class="seta"></i>
                    </a>

                </div>

            </section>
  <!-- LADO DIREITO -->
            <section class="menu-area">

                <div class="menu-cabecalho">
                    <div>
                        <span class="menu-kicker">ADMINISTRAÇÃO</span>
                        <h2>Acesso rápido</h2>
                    </div>
                </div>


                <nav class="menu-admin" aria-label="Menu administrativo">

                    <a href="estoque.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="server"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Estoque</strong>
                        </span>

                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>
                    <a href="cadastros.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="user-plus"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Cadastros</strong>
                        </span>

                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>

                    <?php if (($_SESSION['usuario_tipo'] ?? '') === 'admin'):
                        $cpf_pend = 0;
                        try { $cpf_pend = (int)$pdo->query("SELECT COUNT(*) FROM solicitacao_cpf WHERE status = 'pendente'")->fetchColumn(); } catch (Throwable $e) {}
                    ?>
                    <a href="solicitacoes-cpf.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="id-card"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Troca de CPF<?= $cpf_pend ? ' (' . $cpf_pend . ')' : '' ?></strong>
                        </span>

                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>
                    <?php endif; ?>

                    <a href="vendas.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="shopping-cart"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Vendas</strong>
                        </span>

                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>


                    <a href="produtos-gerenciar.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="store"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Produtos</strong>
                        </span>

                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>


                    <a href="relatorio.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="chart-line"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Relatório</strong>
                        </span>

                        
                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>

                    <a href="chat.php" class="menu-item">
                        <span class="menu-icone">
                            <i data-lucide="send"></i>
                        </span>

                        <span class="menu-texto">
                            <strong>Mensagens</strong>
                        </span>

                        <i data-lucide="arrow-up-right" class="menu-seta"></i>
                    </a>

                </nav>

            </section>

        </div>

    </main>


    <script>
        lucide.createIcons();
    </script>

    <script src="../../js/script-inicio-admin.js"></script>

  <script>window.CICRO_NOTIF = {pedidos: true};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>