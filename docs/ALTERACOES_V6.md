# Alterações da versão 6 (banco coerente com o sistema)
Detalhes: `GUIA_DO_PROJETO.md`, seção 11, e `PAGAMENTO_PIX.md`, seção 5.
- Banco: `banco/cicro.sql` (completo) ou `banco/migracao_v6.sql` (mantém dados). Os arquivos antigos de `banco/` foram removidos.
- Código alterado: `php/config_pagamento.php`, `php/pagamento_funcoes.php`, `php/pedido_funcoes.php`, `php/finalizar_pedido.php`, `pages-user/{pagamento,pedido_confirmado,meus_pedidos,catalogo,checkout,index,carrinho}.php`, `admin-pages/{vendas,produtos,produto-editar,perfil-admin,produtos-gerenciar,estoque}.php`.
- Imagem padrão dos produtos: `images/uploads/CICRO.png`.
