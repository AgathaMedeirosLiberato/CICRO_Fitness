<?php require_once __DIR__ . '/../../config/config_google.php'; $google_ativo = google_configurado(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CICRO - Login</title>
    <link rel="stylesheet" href="../../css/style1.css">
  <link rel="stylesheet" href="../../css/header.css">
</head>
<body>
    <?php if (!empty($_GET['conta_excluida'])): ?>
    <div role="status" style="position:fixed;top:84px;left:50%;transform:translateX(-50%);z-index:50;background:#111;color:#fff;padding:12px 22px;border-radius:99px;font-size:.9rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.25)">
        Sua conta foi excluída e seus dados pessoais foram apagados. Esperamos você de volta!
    </div>
    <?php endif; ?>
    <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <li><a href="login.php" class="is-active">Entrar</a></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

    <main>
        <div class="frase">
            <p><b>CONFORTO E QUALIDADE EM <br> UMA SÓ PEÇA.</b></p>
        </div>

        <div class="conteudo-direita modo-login" id="conteudo-container">
            <div class="login">
                
                <!-- FORMULÁRIO DE LOGIN -->
                <form action="../../controllers/login.php" method="POST" class="form-section login-section">
                    <h3>Email:</h3>
                    <input type="email" name="email" placeholder="Digite seu E-mail" required>
                    
                    <h3>Senha:</h3>
                    <input type="password" name="senha" placeholder="Mínimo de 6 caracteres" minlength="6" required>
                    <br><br>
                    
                    <input type="submit" value="Entrar">
                    <?php if (!empty($google_ativo)): ?>
                    <a href="../../controllers/google_login.php" class="btn-google" style="display:block;margin:10px 0 14px;padding:11px;border:1px solid #ccc;border-radius:10px;text-align:center;color:#222;background:#fff;text-decoration:none;font-weight:600">
                        <span style="color:#4285F4;font-weight:800">G</span> Entrar com o Google
                    </a>
                    <?php endif; ?>
                    <p>Não possui uma conta?</p>
                    <p><a href="#" id="btn-ir-cadastro">Cadastrar-se</a></p>
                    <p><a href="verifique_seu_email.php">Não recebeu o e-mail de confirmação?</a></p>
                    <p><a href="esqueci_senha.php">Esqueceu a senha?</a></p>
                   </form>
                   
                <!-- FORMULÁRIO DE CADASTRO -->
                <form action="../../controllers/cadastrar.php" method="POST" class="form-section cadastro-section">

                    <h3>Nome:</h3>
                    <input type="text" name="nome" placeholder="Digite seu nome completo" required>
                    
                    <h3>Email:</h3>
                    <input type="email" name="email" placeholder="Digite seu E-mail" required>

                    <h3>CPF:</h3>
                    <input type="text" name="cpf" placeholder="000.000.000-00" inputmode="numeric" maxlength="14"
                           data-mask="cpf" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" title="Digite um CPF válido: 000.000.000-00" required>

                    <h3>Senha:</h3>
                    <input type="password" name="senha" placeholder="Mínimo de 6 caracteres" minlength="6" required>
                    
                    <h3>Confirmar Senha:</h3>
                    <input type="password" name="confirmar_senha" placeholder="Repita sua senha" minlength="6" required>

                    <label class="aceite-termos">
                        <input type="checkbox" name="aceite_termos" value="1" required>
                        <span>Li e aceito os <a href="termos.php" target="_blank" rel="noopener">Termos de Uso e a Política de Privacidade</a>.</span>
                    </label>
                    
                    <input type="submit" value="Cadastrar">
                    <p>Já tem uma conta?</p>
                    <p><a href="#" id="btn-ir-login">Fazer Login</a></p>
                </form>


            </div>
        </div> 
    </main>

    <script src="../../js/script.js"></script>
</body>
</html>