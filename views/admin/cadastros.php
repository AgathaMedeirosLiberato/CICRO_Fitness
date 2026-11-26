<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$avatar_padrao = '../../images/Support.png';

$sql = "SELECT id, nome, email, foto_perfil, tipo_usuario, ativo, data_cadastro 
        FROM usuario 
        WHERE data_exclusao IS NULL 
        ORDER BY id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO</title>
  <link rel="stylesheet" href="../../css/style10.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/notificacoes-admin.css">
</head>
<body>

  <!-- Cabeçalho (Navbar) -->
  <header class="site-header">
    <a class="site-header__logo" href="inicio.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="inicio.php">Painel</a></li>
        <li><a href="../../controllers/logout.php" class="site-header__cta">Sair</a></li>
      </ul>
    </nav>
  </header>

  <!-- Container Principal Centralizado -->
  <main class="dashboard-container">
    <div class="admin-wrapper wide-wrapper">
      
      <div class="profile-header">
        <div class="profile-header-info">
          <h1>Usuários Cadastrados</h1>
          <p>Gerencie e visualize todos os clientes, vendedores e administradores registrados.</p>
        </div>
      </div>

      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Usuário</th>
                <th>E-mail</th>
                <th>Nível</th>
                <th>Status</th>
                <th>Data de Cadastro</th>
                <th>Compras</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($user = $result->fetch_assoc()): ?>
                  <?php 
                    $foto = !empty($user['foto_perfil']) ? '../../' . ltrim($user['foto_perfil'], './') : $avatar_padrao;
                    $data_cad = date('d/m/Y \à\s H:i', strtotime($user['data_cadastro']));
                  ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <div class="avatar-wrapper">
                          <img src="<?php echo htmlspecialchars($foto); ?>" 
                               alt="Foto de <?php echo htmlspecialchars($user['nome']); ?>" 
                               class="table-avatar"
                               onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($avatar_padrao); ?>';">
                        </div>
                        <div class="user-info">
                          <span class="user-name"><?php echo htmlspecialchars($user['nome']); ?></span>
                          <span class="user-id">ID: #<?php echo (int)$user['id']; ?></span>
                        </div>
                      </div>
                    </td>
                    <td class="email-cell"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                      <span class="badge badge-role role-<?php echo htmlspecialchars($user['tipo_usuario']); ?>">
                        <i class="fa-solid <?php 
                          echo $user['tipo_usuario'] === 'admin' ? 'fa-user-shield' : ($user['tipo_usuario'] === 'vendedor' ? 'fa-user-tie' : 'fa-user'); 
                        ?>"></i>
                        <?php echo ucfirst(htmlspecialchars($user['tipo_usuario'])); ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($user['ativo']): ?>
                        <span class="badge badge-active">
                          <span class="status-dot"></span> Ativo
                        </span>
                      <?php else: ?>
                        <span class="badge badge-inactive">
                          <span class="status-dot"></span> Inativo
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="date-cell"><?php echo $data_cad; ?></td>
                    <td>
                      <?php if ($user['tipo_usuario'] === 'cliente'): ?>
                        <a href="compras-usuario.php?id=<?php echo (int)$user['id']; ?>" class="badge badge-role" style="text-decoration:none">
                          <i class="fa-solid fa-bag-shopping"></i> Ver compras
                        </a>
                      <?php else: ?>
                        <span style="color:#aaa">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="empty-table">
                    <i class="fa-solid fa-users-slash"></i>
                    <p>Nenhum usuário cadastrado até o momento.</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    </div>
  </main>

  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>