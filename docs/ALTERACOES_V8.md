
# Alterações da versão 8
- CPF duplicado: `php/usuarios.php` não usa mais `INSERT IGNORE` para cliente (o erro de CPF repetido agora aparece) e limpa cadastros nunca confirmados que prendem e-mail/CPF; `php/cadastrar.php` usa isso. Rode `banco/migracao_v8.sql` para garantir o índice único.
- E-mail: `php/config_email.php` (nova opção `SMTP_VERIFICAR_CERTIFICADO`) e `php/email_funcoes.php` (TLS no XAMPP, senha de app com espaços). Para enviar de verdade: `EMAIL_MODO = 'smtp'` + usuário/senha de app.
