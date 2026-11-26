<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario = (int)($_SESSION['usuario_id'] ?? 0);
if ($id_usuario <= 0) {
    exit('Sessão inválida. Faça login novamente.');
}

$pasta_fisica   = dirname(__DIR__, 2) . '/admin-pages/uploads/perfil/';
$pasta_banco    = 'admin-pages/uploads/perfil/';
$avatar_padrao  = '../../images/Support.png';
$tamanho_maximo = 2 * 1024 * 1024;

if (empty($_SESSION['csrf_perfil'])) {
    $_SESSION['csrf_perfil'] = bin2hex(random_bytes(32));
}

function apagar_foto_antiga(?string $caminho_banco, string $pasta_banco, string $pasta_fisica): void {
    if (!$caminho_banco || strpos($caminho_banco, $pasta_banco) !== 0) {
        return;
    }
    $arquivo = $pasta_fisica . basename($caminho_banco);
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

$stmt = $conn->prepare("SELECT nome, email, foto_perfil FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    exit('Usuário não encontrado.');
}

$nome_form  = $admin['nome'];
$email_form = $admin['email'];
$mensagem   = '';
$tipo_msg   = '';

if (isset($_GET['ok'])) {
    $mensagem = ($_GET['ok'] === 'removida') ? 'Foto removida com sucesso.' : 'Perfil atualizado com sucesso!';
    $tipo_msg = 'ok';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'salvar';

    if (!hash_equals($_SESSION['csrf_perfil'], $_POST['csrf'] ?? '')) {
        $mensagem = 'Sessão expirada. Recarregue a página e tente de novo.';
        $tipo_msg = 'erro';

    } elseif ($acao === 'remover_foto') {
        try {
            $stmt = $conn->prepare("UPDATE usuario SET foto_perfil = NULL WHERE id = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();

            apagar_foto_antiga($admin['foto_perfil'], $pasta_banco, $pasta_fisica);
            header('Location: perfil-admin.php?ok=removida');
            exit;
        } catch (Exception $e) {
            $mensagem = 'Não foi possível remover a foto.';
            $tipo_msg = 'erro';
        }

    } else {
        $nome_form  = trim($_POST['adminName'] ?? '');
        $email_form = trim($_POST['adminEmail'] ?? '');
        $senha_atual = $_POST['senhaAtual'] ?? '';
        $nova_senha  = $_POST['novaSenha'] ?? '';
        $erro = '';
        $novo_caminho = null;
        $arquivo_novo = null;

        try {
            if ($nome_form === '' || mb_strlen($nome_form) > 150) {
                $erro = 'Informe um nome com até 150 caracteres.';
            } elseif (!filter_var($email_form, FILTER_VALIDATE_EMAIL) || mb_strlen($email_form) > 150) {
                $erro = 'Informe um e-mail válido.';
            } else {
                $stmt = $conn->prepare("SELECT id FROM usuario WHERE email = ? AND id <> ? LIMIT 1");
                $stmt->bind_param("si", $email_form, $id_usuario);
                $stmt->execute();
                if ($stmt->get_result()->fetch_assoc()) {
                    $erro = 'Este e-mail já está em uso por outro usuário.';
                }
                $stmt->close();
            }

            if ($erro === '' && !empty($nova_senha)) {
                if (empty($senha_atual)) {
                    $erro = 'Digite a senha atual para alterar sua senha.';
                } else {
                    $stmt = $conn->prepare("SELECT senha FROM usuario WHERE id = ? LIMIT 1");
                    $stmt->bind_param("i", $id_usuario);
                    $stmt->execute();
                    $user_db = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!password_verify($senha_atual, $user_db['senha'])) {
                        $erro = 'A senha atual informada está incorreta.';
                    } elseif (mb_strlen($nova_senha) < 6) {
                        $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
                    }
                }
            }

            $foto = $_FILES['avatar'] ?? null;
            if ($erro === '' && $foto && $foto['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($foto['error'] !== UPLOAD_ERR_OK) {
                    $erro = ($foto['error'] === UPLOAD_ERR_INI_SIZE || $foto['error'] === UPLOAD_ERR_FORM_SIZE)
                        ? 'A foto é grande demais (máximo 2 MB).'
                        : 'Falha ao enviar a foto (código ' . (int)$foto['error'] . ').';
                } elseif ($foto['size'] > $tamanho_maximo) {
                    $erro = 'A foto deve ter no máximo 2 MB.';
                } else {
                    $info = @getimagesize($foto['tmp_name']);
                    $tipos = [
                        IMAGETYPE_JPEG => 'jpg',
                        IMAGETYPE_PNG  => 'png',
                        IMAGETYPE_WEBP => 'webp',
                    ];
                    if (!$info || !isset($tipos[$info[2]])) {
                        $erro = 'Use uma imagem JPG, PNG ou WEBP.';
                    } else {
                        if (!is_dir($pasta_fisica) && !mkdir($pasta_fisica, 0755, true)) {
                            throw new Exception('Não foi possível criar a pasta de fotos.');
                        }
                        $nome_arquivo = 'admin_' . $id_usuario . '_' . uniqid() . '.' . $tipos[$info[2]];
                        $arquivo_novo = $pasta_fisica . $nome_arquivo;

                        if (!move_uploaded_file($foto['tmp_name'], $arquivo_novo)) {
                            $arquivo_novo = null;
                            throw new Exception('Não foi possível salvar a foto.');
                        }
                        $novo_caminho = $pasta_banco . $nome_arquivo;
                    }
                }
            }

            if ($erro === '') {
                $hash_senha = !empty($nova_senha) ? password_hash($nova_senha, PASSWORD_DEFAULT) : null;

                if ($novo_caminho !== null && $hash_senha !== null) {
                    $stmt = $conn->prepare("UPDATE usuario SET nome = ?, email = ?, foto_perfil = ?, senha_hash = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $nome_form, $email_form, $novo_caminho, $hash_senha, $id_usuario);
                } elseif ($novo_caminho !== null) {
                    $stmt = $conn->prepare("UPDATE usuario SET nome = ?, email = ?, foto_perfil = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $nome_form, $email_form, $novo_caminho, $id_usuario);
                } elseif ($hash_senha !== null) {
                    $stmt = $conn->prepare("UPDATE usuario SET nome = ?, email = ?, senha_hash = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $nome_form, $email_form, $hash_senha, $id_usuario);
                } else {
                    $stmt = $conn->prepare("UPDATE usuario SET nome = ?, email = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $nome_form, $email_form, $id_usuario);
                }

                $stmt->execute();
                $stmt->close();

                if ($novo_caminho !== null) {
                    apagar_foto_antiga($admin['foto_perfil'], $pasta_banco, $pasta_fisica);
                }
                header('Location: perfil-admin.php?ok=salvo');
                exit;
            }
        } catch (Exception $e) {
            $erro = 'Não foi possível salvar. Tente novamente.';
            if ($arquivo_novo && is_file($arquivo_novo)) {
                @unlink($arquivo_novo);
            }
        }

        $mensagem = $erro;
        $tipo_msg = 'erro';
    }
}

$foto_atual = !empty($admin['foto_perfil']) ? '../../' . ltrim($admin['foto_perfil'], './') : $avatar_padrao;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel do Administrador | Perfil</title>
  <link rel="stylesheet" href="../../css/style9.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/notificacoes-admin.css">
</head>
<body class="admin-page">

  <header class="site-header">
    <a class="site-header__logo" href="inicio.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="inicio.php">Painel</a></li>
        <li><a href="../../controllers/logout.php" class="site-header__cta">Sair</a></li>
      </ul>
    </nav>
  </header>

  <main class="dashboard-container">
    <div class="admin-wrapper">
      
      <!-- Cabeçalho do Perfil -->
      <div class="profile-header">
        <div class="profile-header-info">
          <h1> Perfil Administrador(a)</h1>
        </div>
      </div>

      <?php if ($mensagem !== ''): ?>
        <div class="toast-alert alert-<?php echo $tipo_msg; ?>" role="alert">
          <i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
          <span><?php echo htmlspecialchars($mensagem); ?></span>
        </div>
      <?php endif; ?>

      <div class="profile-grid">
        
        <!-- Coluna da Esquerda: Foto do Usuário -->
        <aside class="avatar-card">
          <div class="avatar-preview-wrapper">
            <div class="avatar-preview" id="avatarPreview">
              <img src="<?php echo htmlspecialchars($foto_atual); ?>" alt="Foto de perfil" id="avatarImg"
                   onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($avatar_padrao); ?>';">
              
              <label for="avatarInput" class="avatar-overlay" title="Alterar foto">
                <i class="fa-solid fa-camera"></i>
                <span>Trocar Foto</span>
              </label>
            </div>
            <span class="user-status-online">
              <span class="status-dot"></span> Administrador Ativo
            </span>
          </div>

          <div class="avatar-actions">
            <label for="avatarInput" class="btn btn-outline">
            Selecionar Imagem
            </label>
            <input type="file" id="avatarInput" name="avatar" form="perfilForm"
                   accept="image/jpeg,image/png,image/webp" style="display:none">
            
            <?php if (!empty($admin['foto_perfil'])): ?>
              <button type="submit" form="formRemover" class="btn btn-danger-link">
                Remover Foto
              </button>
            <?php endif; ?>
          </div>

          <div class="avatar-help">
            <i class="fa-solid fa-circle-info"></i>
            <p>Formatos suportados: JPG, PNG ou WEBP. Tamanho máximo permitido: 2 MB.</p>
          </div>
        </aside>

        <!-- Coluna da Direita: Formulário -->
        <section class="form-card">
          <form id="perfilForm" class="perfil-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_perfil']); ?>">
            <input type="hidden" name="acao" value="salvar">

            <div class="form-section-title">
              <i class="fa-solid fa-user-gear"></i> Dados Pessoais
            </div>

            <div class="form-group">
              <label for="adminName">Nome </label>
              <div class="input-icon-wrapper">
                <i class="fa-regular fa-user"></i>
                <input type="text" id="adminName" name="adminName" placeholder="Seu nome completo" maxlength="150" required
                       value="<?php echo htmlspecialchars($nome_form); ?>">
              </div>
            </div>

            <div class="form-group">
              <label for="adminEmail">E-mail Corporativo</label>
              <div class="input-icon-wrapper">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" id="adminEmail" name="adminEmail" placeholder="exemplo@admin.com" maxlength="150" required
                       value="<?php echo htmlspecialchars($email_form); ?>">
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
              </button>
              <button type="button" class="btn btn-secondary" id="cancelBtn">
                <i class="fa-solid fa-xmark"></i> Cancelar
              </button>
            </div>
          </form>
        </section>

      </div>
    </div>
  </main>

  <form id="formRemover" method="POST" onsubmit="return confirm('Tem certeza que deseja remover sua foto de perfil?');">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_perfil']); ?>">
    <input type="hidden" name="acao" value="remover_foto">
  </form>

  <script>
    const input = document.getElementById('avatarInput');
    const img = document.getElementById('avatarImg');
    const formPerfil = document.getElementById('perfilForm');
    const fotoOriginal = img.getAttribute('src');
    const TAMANHO_MAXIMO = 2 * 1024 * 1024;
    const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    input.addEventListener('change', function () {
      const arquivo = input.files[0];
      if (!arquivo) { img.src = fotoOriginal; return; }

      if (!TIPOS.includes(arquivo.type)) {
        alert('Por favor, selecione uma imagem nos formatos JPG, PNG ou WEBP.');
        input.value = '';
        img.src = fotoOriginal;
        return;
      }
      if (arquivo.size > TAMANHO_MAXIMO) {
        alert('A foto selecionada ultrapassa o tamanho máximo permitido de 2 MB.');
        input.value = '';
        img.src = fotoOriginal;
        return;
      }
      img.src = URL.createObjectURL(arquivo);
    });

    document.getElementById('cancelBtn').addEventListener('click', function () {
      formPerfil.reset();
      img.src = fotoOriginal;
    });
  </script>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>