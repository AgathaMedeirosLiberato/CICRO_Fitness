# Criptografia do CPF

## Como ligar
1. Faça um backup do banco.
2. phpMyAdmin → banco `cicro` → aba **SQL** → rode `banco/migracao_v11.sql`.
3. No terminal, dentro da pasta do projeto: `C:\xampp\php\php.exe ferramentas\cifrar_cpf.php`
4. Copie `config/chave_cpf.php` para um lugar seguro.

Banco novo (importando `banco/cicro.sql`): rode só o passo 3 e o 4, antes do primeiro cadastro.

## Como funciona
| Onde | O que fica |
|---|---|
| `cliente.cpf` | CPF cifrado com AES-256-GCM (começa com `v1:`) |
| `cliente.cpf_hash` | HMAC-SHA256 do CPF, para impedir CPF repetido |
| `solicitacao_cpf.cpf_atual` | CPF cifrado |
| `config/chave_cpf.php` | a chave (não vai para o banco) |

O cliente continua vendo o próprio CPF no perfil, e a equipe nas telas em que ele já aparecia.

## Cuidados
- **Perdeu a chave, perdeu os CPFs.** Não existe recuperação.
- Não envie `config/chave_cpf.php` ao GitHub nem junto com o zip do projeto.
- Ao mudar o projeto de computador, leve a chave junto com o banco.
- A cifra protege o CPF em quem só tem o banco (backup, exportação, phpMyAdmin). Quem tiver acesso ao banco **e** aos arquivos do servidor consegue decifrar.
- Backups feitos antes da conversão ainda têm o CPF legível: apague-os ou guarde com cuidado.
