# CICRO – Pagamento por Pix com biblioteca PHP (sem API)

## 1. O que mudou, em uma frase
O cliente paga por **Pix** (QR Code + "copia e cola") gerado **dentro do seu servidor** por bibliotecas PHP.
Não existe API, gateway, token, webhook nem internet envolvida: o dinheiro cai direto na conta da chave Pix
da loja e o **admin confirma** o recebimento em *Vendas*. **Cartão e boleto ficam para o futuro** (aparecem no checkout como "Em breve").

## 2. Bibliotecas usadas (pasta `vendor/`, já incluída — não precisa de Composer)
| Biblioteca | Para quê | Versão |
|---|---|---|
| `piggly/php-pix` | Monta o código Pix "copia e cola" (BR Code do Banco Central): chave, valor, nome, cidade, identificador (txid) e o CRC16 | 2.0.2 |
| `chillerlan/php-qrcode` | Desenha o QR Code desse código, em SVG | 3.4.1 |
| `chillerlan/php-settings-container` | Dependência da anterior | 1.2.3 |

Escolhi estas versões porque funcionam no **PHP 8.0 do seu XAMPP**; não exigem `gd`, `curl` nem internet.
O `vendor/autoload.php` é um carregador simples feito à mão. Se um dia quiser usar o Composer, o `composer.json`
já está na raiz: `composer install` e ele troca o autoload sozinho.

## 3. Como funciona (fluxo)
1. **Checkout** (`pages-user/checkout.php`): o cliente escolhe endereço e **Pix** (cartão/boleto aparecem cinza, "Em breve").
2. **Confirmar pedido** (`php/finalizar_pedido.php`): grava o pedido (`Pendente`, forma `pix`), os itens, baixa o estoque e esvazia o carrinho.
3. **Tela do Pix** (`pages-user/pagamento.php`): chama `pix_gerar()` e mostra o QR Code e o "copia e cola" com o **valor do pedido** e o identificador `CICRO000061` (pedido 61).
4. O cliente paga no app do banco. O comprovante dele mostra o identificador.
5. O admin vê o Pix no extrato e, em **Vendas > pedido**, troca o status para **Pago** (`php/pedido_funcoes.php > alterar_status_pedido`). Isso grava `pedido.data_pagamento`.
6. **Prazo de 24 h:** pedido Pendente que passa disso é cancelado sozinho e o estoque volta (`PEDIDO_PRAZO_PAGAMENTO_HORAS` em `php/config_pagamento.php`).
7. Pago → Enviado → Entregue como antes. Cancelar devolve o estoque (se já tinha pago, o estorno do Pix é feito no app do banco).

Onde está o código da biblioteca em uso: **`php/pagamento_funcoes.php`** → `pix_gerar($id_pedido, $valor)`.

```php
$payload = (new StaticPayload())
    ->setPixKey($tipo, $chave)   // valida CPF/CNPJ, e-mail, telefone ou chave aleatória
    ->setMerchantName(PIX_NOME)->setMerchantCity(PIX_CIDADE)
    ->setAmount($valor)->setTid('CICRO000061');
$codigo = $payload->getPixCode();                 // "copia e cola"
$qr = (new QRCode(new QROptions([...SVG...])))->render($codigo);   // <img src="data:image/svg+xml;base64,...">
```

## 4. Como configurar (1 minuto)
Abra **`php/config_pagamento.php`** e preencha:
```php
const PIX_TIPO_CHAVE = 'email';              // 'document' (CPF/CNPJ), 'email', 'phone' ou 'random'
const PIX_CHAVE      = 'sualoja@email.com';  // a chave cadastrada no seu banco
const PIX_NOME       = 'CICRO';              // até 25 letras, sem acento
const PIX_CIDADE     = 'CRICIUMA';           // até 15 letras, sem acento
```
Enquanto `PIX_CHAVE` estiver vazia, o checkout avisa "Pix ainda não configurado" e bloqueia o botão (nada quebra).
Use uma chave **sua** e faça um Pix de R$ 0,01 para testar de ponta a ponta antes de divulgar.

## 5. Banco de dados: a tabela `pagamento` (versão 6)
A tabela `pagamento` **continua**, só adaptada ao Pix (uma linha por pedido):
| Coluna | Uso |
|---|---|
| `fk_pedido` | pedido pago (único: 1 pagamento por pedido) |
| `metodo` | `pix` (cartão/boleto no futuro) |
| `status` | aguardando · pago · cancelado · expirado · estornado |
| `valor` | valor que vai dentro do Pix |
| `txid` | identificador do Pix (`CICRO000012`), único |
| `codigo` | texto "copia e cola" |
| `simulado` | 1 = modo demonstração (`PIX_DEMONSTRACAO`) |
| `data_criacao` / `data_pagamento` | quando foi gerado / quando foi pago |

Saíram `parcelas`, `cartao_bandeira` e `cartao_final`. `pedido.forma_pagamento` continua (cópia para exibição) e `pedido.status` manda no fluxo; `pagamento.status` é atualizado junto, sempre dentro da mesma transação.
**Modo demonstração:** com `PIX_DEMONSTRACAO = true` o QR usa uma chave de exemplo e a tela de pagamento mostra "Simular pagamento", que marca o pedido como Pago. Em produção: `false` + sua chave; a confirmação passa a ser feita pelo admin em *Vendas*.
**Como aplicar** (em `banco/`): `cicro.sql` (banco completo, apaga e recria) ou `migracao_v6.sql` (mantém seus dados; faça backup antes).

## 6. Relatórios
Como o Pix é confirmado à mão, **pedido Pendente não é venda**. Os relatórios, gráficos, PDF e "compras do usuário" agora somam só **Pago, Enviado e Entregue**.

## 7. Arquivos
- **Novos:** `vendor/` (bibliotecas), `composer.json`, `banco/*.sql`, este guia.
- **Reescritos:** `php/config_pagamento.php`, `php/pagamento_funcoes.php`, `pages-user/pagamento.php`.
- **Editados:** `pages-user/checkout.php` (Pix + "Em breve"), `php/finalizar_pedido.php` (cria a linha em `pagamento`), `pages-user/pedido_confirmado.php`, `php/pedido_funcoes.php` (status/`data_pagamento`, regra de venda), `admin-pages/vendas.php` (bloco "Pagamento (Pix)"), `admin-pages/compras-usuario.php`, `admin-pages/relatorio.php`, `admin-pages/relatorio-pdf.php`, `php/relatorio_dados.php`, `css/checkout.css`.
- **Removidos:** `php/pagamento_acao.php` (cartão/boleto simulados), `pages-user/boleto.php`.

## 8. Limites (para saber antes de apresentar)
- **A confirmação é manual**: o sistema não sabe sozinho que o Pix caiu. Isso é inerente ao Pix "estático" sem API. Quando quiser automático, é preciso um provedor (Mercado Pago, Asaas, banco com API Pix) — essa parte é a "implementação futura" junto com cartão/boleto.
- O mesmo QR pode ser pago mais de uma vez; o admin deve conferir **valor e identificador** no extrato.
- Testado aqui: geração do código (CRC16 conferido por cálculo independente), QR lido por leitor e igual ao "copia e cola", todos os tipos de chave, fluxo completo de pedido/confirmação/cancelamento/relatório. **Não** foi testado pagando de verdade em um banco, nem rodando os SQLs num MariaDB (use um backup antes).
