# CICRO v5 — o que mudou e onde

## 0. Antes de tudo: rode a migração
phpMyAdmin → banco `cicro` → aba **SQL** → cole o conteúdo de `migracao_v5.sql` → Executar.
Ela adiciona em `pedido`: `fk_endereco`, `endereco_entrega`, `forma_pagamento` (+ índices),
e corrige `item_carrinho` (coluna `fk_imagem` que o `carrinho.php` já usava e o seu banco não tinha).

## 1. Relatórios gerais e PDF
| Arquivo | O que é |
|---|---|
| `php/relatorio_dados.php` (novo) | Todas as consultas do relatório. Tela e PDF usam a mesma função → números sempre iguais. |
| `admin-pages/relatorio.php` (era vazio) | Tela do relatório: filtro de período, atalhos (hoje, 7/30 dias, mês), resumo, tabelas. |
| `admin-pages/relatorio-pdf.php` (novo) | Versão A4. Abre a janela de impressão → destino "Salvar como PDF". |
| `admin-pages/vendas.php?id=N` | Botão "Imprimir / PDF" no detalhe de um pedido. |
Regra: pedido **Cancelado não conta** como venda.

## 2. Gráficos de vendas
`js/relatorio-graficos.js` (novo) desenha 5 gráficos com Chart.js (carregado do cdnjs, versão 4.4.1):
faturamento por dia · pedidos por status · receita por pagamento · produtos mais vendidos · receita por categoria.
Os dados vêm do PHP num `<script type="application/json">` dentro de `relatorio.php`.

## 3. Aba de finalização de compra
- `pages-user/checkout.php` (novo): escolhe endereço, forma de pagamento, revisa itens, confirma.
- `php/finalizar_pedido.php`: agora recebe `endereco_id` e `forma_pagamento`, valida que o endereço é DO cliente,
  grava o endereço "congelado" no pedido, e redireciona para a página certa
  (antes apontava para `pedido-confirmado.php`, que não existe — o arquivo é `pedido_confirmado.php`).
- `pages-user/pedido_confirmado.php`: mostra endereço e pagamento.
- O botão "Finalizar compra" do `carrinho.php` já apontava para `checkout.php` — não precisou mexer.

## 4. Editar endereço e escolher o de entrega
- `pages-user/perfil.php`: cada endereço ganhou **Editar**, **Tornar padrão**, **Excluir**.
  "Editar" abre o mesmo formulário já preenchido (`perfil.php?editar=ID`).
- `php/salvar_endereco.php`: se vier `id`, atualiza em vez de inserir (só endereço do próprio cliente).
- `php/endereco_acoes.php` (novo): tornar padrão / excluir.
- A escolha do endereço de entrega acontece no `checkout.php` (item 3). Pedidos antigos mantêm o texto do endereço original.

## 5. Compras do usuário para o Admin
- `admin-pages/cadastros.php`: nova coluna **Compras** com o botão "Ver compras" (só para clientes).
- `admin-pages/compras-usuario.php?id=N` (novo): todos os pedidos do cliente, itens, entrega, pagamento, status e totais.
- `admin-pages/vendas.php` (era vazio): lista de todos os pedidos, filtros, troca de status
  (cancelar devolve o estoque, uma vez só; pedido cancelado não reativa) e detalhe.

## Outros arquivos
`php/pedido_funcoes.php` (novo: constantes, status, helpers) · `css/checkout.css` · `css/admin-vendas.css` ·
`php/auth_admin.php` e `php/salvar_endereco.php` (redirecionavam para `login.html`, que não existe → `login.php`).

---
# Atualização: pagamento (Pix real + cartão/boleto simulados) e tela de confirmação


Fluxo novo: Carrinho → `checkout.php` → *Confirmar pedido* → **`pagamento.php`** → `pedido_confirmado.php`.

| Arquivo | O que faz |
|---|---|
| `php/config_pagamento.php` (novo) | **Único arquivo para configurar**: chave Pix da loja, nome/cidade, `PAGAMENTO_MODO_DEMO`, parcelas. |
| `php/pagamento_funcoes.php` (novo) | Pix copia-e-cola (BR Code com CRC16), validação de cartão (Luhn, validade, bandeira), boleto de demonstração, aprovar pagamento. |
| `pages-user/pagamento.php` (novo) | Tela de pagamento: Pix (QR + copia e cola), cartão (formulário), boleto (linha digitável). Permite trocar a forma. |
| `php/pagamento_acao.php` (novo) | Trocar forma / pagar com cartão / simular pagamento. Valida dono do pedido e CSRF. |
| `pages-user/boleto.php` (novo) | Boleto de demonstração para imprimir (com marca d'água). |
| `pages-user/pedido_confirmado.php` | **Refeita**: banner de status, trilha (Pedido → Pago → Enviado → Entregue), itens, entrega e pagamento. |
| `php/finalizar_pedido.php` | Cria o registro de pagamento e envia para `pagamento.php`. |
| `admin-pages/vendas.php` | Detalhe do pedido mostra os pagamentos. Mudar para **Pago** confirma o Pix. |

Modo demonstração (`PAGAMENTO_MODO_DEMO = true`): cartão aprova na hora (final 0002 simula recusa), Pix/boleto têm botão "Simular pagamento".
Produção (`false`): só Pix fica disponível (e precisa da chave); o admin confirma o Pix em Vendas. Cartão e boleto reais exigem um gateway (Mercado Pago, Asaas…).
Dados do cartão nunca são gravados: só bandeira e 4 últimos dígitos.
