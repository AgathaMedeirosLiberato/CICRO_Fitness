# CICRO – Guia do Projeto
**Loja virtual de roupas (e-commerce) em PHP + MariaDB (XAMPP)**
Este guia descreve o que o sistema faz (requisitos), as regras que ele segue (regras de negócio), como ele é organizado e o que ainda fica para o futuro. Tudo aqui corresponde ao que está implementado no código desta versão.

---

## 1. Visão geral
**Objetivo:** vender roupas pela internet, com catálogo, carrinho, finalização de compra com pagamento por Pix, acompanhamento do pedido, atendimento por chat e um painel para a equipe da loja gerenciar produtos, estoque, vendas e relatórios.

**Perfis de usuário**
| Perfil | Quem é | O que faz |
|---|---|---|
| Visitante | Quem entra sem login | Navega pelo catálogo, busca e filtra produtos, lê os termos |
| Cliente | Visitante cadastrado (com CPF) | Compra, acompanha pedidos, gerencia perfil e endereços, fala com o suporte |
| Vendedor | Funcionário | Acessa o painel (produtos, estoque, vendas, relatórios, chat) |
| Administrador | Funcionário com tipo `admin` | Tudo do vendedor |

---

## 2. Tecnologias e organização
- **Linguagem/servidor:** PHP 8 (XAMPP), MariaDB 10.4, HTML/CSS/JavaScript puro.
- **Acesso ao banco:** PDO (`$pdo`) na maior parte do sistema; algumas telas antigas usam MySQLi (`$conn`). Os dois vêm de `php/conexao.php`.
- **Bibliotecas (pasta `vendor/`, sem Composer):**
  - `piggly/php-pix` – monta o código Pix "copia e cola".
  - `chillerlan/php-qrcode` (+ `php-settings-container`) – desenha o QR Code em SVG.
  - `brazanation/documents` – valida o CPF.
  - Chart.js (CDN) – gráficos do relatório.
  - ViaCEP (serviço externo) – preenche o endereço pelo CEP.

**Pastas**
| Pasta | Conteúdo |
|---|---|
| `views/user/` | Telas do cliente: início, catálogo, carrinho, checkout, pagamento, pedido, perfil, suporte, login/cadastro, termos |
| `views/admin/` | Painel da equipe: início, produtos, estoque, vendas, relatório (e PDF), cadastros, compras do usuário, chat, perfil |
| `controllers/` | Ações (recebem formulários/requisições): login, cadastro, finalizar/cancelar pedido, endereços, perfil, chat, Google |
| `models/` | Regras e acesso a dados: pedidos, estoque, pagamento, CPF, usuários, avaliações, relatórios, e-mail |
| `config/` | Conexão com o banco e configurações (e-mail, Google, Pix) |
| `admin-pages/uploads/` | Somente as imagens enviadas (o banco guarda esse caminho; por isso a pasta ficou no lugar) |
| `css/`, `js/`, `images/` | Estilos, scripts e imagens |
| `vendor/` | Bibliotecas de terceiros |
| `banco/` | `cicro.sql` (banco completo v7) e `migracao_v7.sql` (leva um banco v6 com dados para a v7) |

**Banco de dados (18 tabelas):** `usuario`, `cliente`, `funcionario`, `endereco`, `categoria`, `produto`, `variacao_produto`, `imagem_produto`, `carrinho`, `categoria_carrinho`, `item_carrinho`, `pedido`, `item_pedido`, `movimentacao_estoque`, `pagamento`, `avaliacao`, `conversa`, `mensagem`.

---

## 3. Requisitos funcionais (RF)

### 3.1 Conta e acesso
- **RF01** Cadastro de cliente com nome, e-mail, **CPF**, senha (mínimo 6 caracteres) e aceite dos Termos de Uso/Privacidade.
- **RF02** Login por e-mail e senha; cliente vai para a página inicial, funcionário para o painel.
- **RF03** Logout.
- **RF04** Perfil do cliente: editar nome, telefone e e-mail (trocar o e-mail exige a senha atual), informar o CPF (se ainda não tiver), trocar a senha.
- **RF05** Endereços: cadastrar, editar, excluir e escolher o endereço padrão; preenchimento automático pelo CEP.
- **RF06** Termos de Uso e Política de Privacidade em página própria.

### 3.2 Catálogo e carrinho
- **RF07** Catálogo com categorias (Conjuntos, Shorts, Camisas, Cropped, Regatas), busca por nome e filtros por tamanho e faixa de preço.
- **RF08** Janela de detalhes do produto: galeria de imagens, escolha de tamanho (tamanho sem estoque aparece desativado), preço.
- **RF09** **Adicionar ao carrinho** e **Comprar** (vai direto ao checkout).
- **RF10** **Organização do carrinho por categorias é opcional:** depois de adicionar, o cliente pode escolher uma categoria sua, criar uma nova ou apenas clicar em **"Mandar para o carrinho"** (o item fica em "SEM CATEGORIA").
- **RF11** Carrinho: alterar quantidade, remover item, mudar a categoria do item, ver o total.

### 3.3 Compra e pagamento
- **RF12** Finalização de compra em uma tela: escolher endereço de entrega, forma de pagamento e revisar os itens.
- **RF13** Pagamento por **Pix**: QR Code e "copia e cola" com o valor do pedido e um identificador (`CICRO000061`).
- **RF14** Cartão de crédito e boleto aparecem como **"Em breve"** (implementação futura).
- **RF15** Acompanhamento do pedido pelo cliente: itens, endereço, forma e situação do pagamento, linha do tempo (Pedido feito → Pago → Enviado → Entregue).
- **RF16** O cliente é avisado do prazo para pagar; passado o prazo o pedido é cancelado sozinho.

### 3.4 Painel da equipe
- **RF17** Produtos: cadastrar (nome, descrição, preço, categoria, tamanhos, imagens), editar, ativar/desativar e excluir.
- **RF18** Estoque: ver quantidade por tamanho, filtrar (todos, baixo, zerado) e registrar entradas (reposição, devolução, ajuste) e saídas (perda/avaria, ajuste, outro), com histórico de movimentações.
- **RF19** Vendas: lista de pedidos com busca, filtros e paginação; detalhe do pedido (cliente com CPF, entrega, pagamento, itens); mudar o status.
- **RF20** **Confirmar o pagamento Pix** (status "Pago").
- **RF21** Compras de um cliente (histórico e total gasto).
- **RF22** Relatórios: resumo, faturamento por dia, por status e por forma de pagamento, produtos e clientes que mais compram; gráficos; versão para imprimir/PDF.
- **RF23** Cadastros: lista de usuários (clientes e funcionários) com situação ativo/inativo.
- **RF24** Perfil do administrador/funcionário (dados e foto).

### 3.5 Atendimento
- **RF25** Chat de suporte: o cliente abre uma conversa e envia mensagens; a equipe lista conversas abertas/encerradas, responde e encerra; contador de mensagens não lidas.

---

## 4. Requisitos não funcionais (RNF)
- **RNF01 Segurança de senhas:** senhas guardadas com `password_hash` (bcrypt); nunca em texto puro.
- **RNF02 Sessão:** `session_regenerate_id` no login; páginas do cliente e do painel exigem login e perfil adequado.
- **RNF03 Injeção de SQL:** consultas preparadas (PDO/MySQLi) com parâmetros.
- **RNF04 XSS:** saída de dados escapada (`htmlspecialchars`).
- **RNF05 CSRF:** token nos formulários do painel e nas ações do cliente (checkout, endereços).
- **RNF06 Propriedade dos dados:** o cliente só vê/paga os **próprios** pedidos; o servidor confere o dono em toda ação.
- **RNF07 Integridade:** pedido, itens e baixa de estoque na **mesma transação** (ou tudo, ou nada).
- **RNF08 Concorrência:** a última peça não é vendida duas vezes (travamento da linha no banco).
- **RNF09 Privacidade (LGPD):** CPF coletado só para identificar o comprador/nota fiscal; aparece completo apenas para o próprio cliente (perfil) e para a equipe no detalhe do pedido e nas compras do cliente; aceite dos termos com data registrada.
- **RNF10 Usabilidade:** páginas responsivas (celular e computador), máscaras (CPF, CEP, telefone), mensagens de erro em português.
- **RNF11 Manutenção:** regras de negócio centralizadas em `php/*_funcoes.php`; configuração de pagamento em um único arquivo.
- **RNF12 Portabilidade:** roda no XAMPP sem Composer e sem depender de extensões raras (o QR Code é SVG, não precisa de `gd`).

---

## 5. Regras de negócio (RN)

### Conta, CPF e privacidade
- **RN01** E-mail é único: não existem duas contas com o mesmo e-mail.
- **RN02** **CPF é obrigatório para ser cliente** e **único**: um CPF = uma conta.
- **RN03** O CPF é validado pela biblioteca `brazanation/documents`: 11 dígitos, **não pode ser sequência repetida** (111.111.111-11) e os **2 dígitos verificadores** precisam bater. É padronizado como `000.000.000-00`.
- **RN04** Depois de salvo, o cliente **não consegue trocar o CPF** (evita fraude); correção é feita pelo Suporte. Contas antigas sem CPF informam no perfil.
- **RN05** **Sem CPF não finaliza compra:** o checkout mostra o aviso e bloqueia o botão; o servidor também recusa.
- **RN06** O cadastro público **sempre cria cliente**; funcionário/admin nunca se cadastram pela tela pública (o tipo vindo do formulário é ignorado).
- **RN07** É preciso aceitar os Termos para criar a conta; guardamos o aceite e a data.
- **RN08** Trocar o e-mail exige a senha atual.
- **RN09** Só usuários ativos conseguem entrar.

### Produtos e estoque
- **RN10** Cada produto tem **variações** (tamanho/cor) com SKU único e quantidade em estoque própria.
- **RN11** Produto desativado some do catálogo, mas continua no banco.
- **RN12** **Produto que já foi vendido não pode ser excluído** (preserva o histórico de pedidos): só desativar.
- **RN13** Tamanho sem estoque não pode ser escolhido; produto sem nenhum tamanho disponível não entra nos filtros por tamanho.
- **RN14** Estoque **baixo** = até 5 unidades; **zerado** = 0.
- **RN15** Toda entrada/saída de estoque gera uma **movimentação** (venda, cancelamento, ajuste) com data e pedido.
- **RN16** Não se vende mais do que existe: sem estoque, a compra é recusada com mensagem clara.

### Carrinho
- **RN17** O carrinho exige cliente logado.
- **RN18** **Categoria do carrinho é opcional** e criada pelo próprio cliente; item sem categoria fica em "SEM CATEGORIA".
- **RN19** O mesmo produto/tamanho/imagem soma quantidade (não duplica a linha) e a soma não pode passar do estoque.

### Pedido
- **RN20** Pedido nasce **Pendente**. Fluxo: **Pendente → Pago → Enviado → Entregue**; **Cancelado** pode ocorrer a qualquer momento (pela loja ou pelo prazo de pagamento).
- **RN21** Ao confirmar o pedido, as peças **saem do estoque na hora** (reserva) e o carrinho é esvaziado.
- **RN22** O pedido **congela** nome, tamanho, preço e endereço no momento da compra; mudanças posteriores no cadastro não alteram pedidos antigos.
- **RN23** O endereço de entrega precisa ser do próprio cliente.
- **RN24** Total = produtos + frete − desconto. (Frete e cupom ainda são R$ 0,00; ficam como implementação futura.)
- **RN25** **Cancelar devolve as peças ao estoque, uma única vez.** Pedido cancelado **não pode ser reativado** (o cliente faz novo pedido).
- **RN26** **Prazo de pagamento: 24 horas** (`PEDIDO_PRAZO_PAGAMENTO_HORAS`). Pedido **Pendente** que passa do prazo é **cancelado automaticamente** e o estoque volta. A verificação acontece quando alguém cria um pedido, abre o pagamento/pedido ou o admin abre **Vendas** (não precisa de agendador).
- **RN27** Cliente só enxerga/paga o próprio pedido.

### Pagamento (Pix)
- **RN28** Única forma ativa: **Pix**. Cartão e boleto: futuro.
- **RN29** O Pix é gerado na hora a partir do pedido (chave da loja + valor + identificador `CICRO` + nº do pedido com 6 dígitos). Nada é guardado: o código pode ser gerado de novo.
- **RN30** O dinheiro cai **direto na conta da chave Pix da loja**; não há intermediário.
- **RN31** **A confirmação é manual:** o admin confere valor e identificador no extrato do banco e troca o status para **Pago**; isso grava `data_pagamento` (apenas na primeira vez).
- **RN32** Sem chave Pix configurada, o checkout avisa e bloqueia a compra.
- **RN33** Se um pedido **já pago** for cancelado, o estorno do Pix é feito pela equipe no app do banco.

### Vendas e relatórios
- **RN34** **Só conta como venda/faturamento** pedido **Pago, Enviado ou Entregue**. Pendente e Cancelado ficam de fora.
- **RN35** Ticket médio = faturamento ÷ número de pedidos pagos no período (padrão: últimos 30 dias).
- **RN36** Os relatórios mostram, à parte, quantos pedidos foram cancelados e o valor cancelado.

### Atendimento
- **RN37** O cliente conversa com a equipe pelo chat; a equipe (admin e vendedor) responde e **encerra** a conversa.
- **RN38** Mensagem tem até 1000 caracteres; quem fala vem da sessão (ninguém lê conversa alheia).

---

## 6. Fluxo de uma compra (resumo)
1. Cliente entra no **catálogo**, abre um produto, escolhe o tamanho e clica **Adicionar ao carrinho** (ou **Comprar**).
2. Opcional: escolhe/cria uma categoria **ou** clica **Mandar para o carrinho**.
3. No **checkout** confere endereço, Pix e itens → **Confirmar pedido**. (Sem CPF: é levado ao perfil.)
4. O sistema cria o pedido **Pendente**, baixa o estoque e abre a tela do **Pix** com o prazo de 24 h.
5. O cliente paga no app do banco.
6. O admin vê o Pix no extrato → **Vendas → Pago**.
7. Admin muda para **Enviado** e depois **Entregue**; o cliente acompanha em "Pedido".
8. Sem pagamento em 24 h → **Cancelado** automaticamente, estoque devolvido.

---

## 7. Sobre a verificação do CPF (importante)
- **O que o sistema garante:** o CPF tem o formato certo, não é um número "falso óbvio" e os dígitos verificadores batem com o cálculo oficial. Isso barra erros de digitação e a maioria dos CPFs inventados.
- **O que ele NÃO garante:** que o CPF **existe na Receita Federal**, está regular ou pertence à pessoa que digitou. Um CPF matematicamente válido de outra pessoa passa.
- **Por que não há consulta à Receita:** não existe API gratuita e pública para isso. A consulta oficial é feita por serviços credenciados e pagos (por exemplo, Serpro "Consulta CPF" ou Datavalid), em geral exigindo contrato e também a data de nascimento/nome para conferir.
- **Já está preparado:** em `php/cpf_funcoes.php` existe a função `cpf_validar_externo()` (hoje retorna verdadeiro). Quando contratar um serviço, basta implementar a chamada ali; cadastro e perfil já passam por ela.

---

## 8. Segurança – o que existe e o que melhorar
**Já existe:** senhas com hash, consultas preparadas, escape de saída, CSRF nos formulários principais, conferência de dono em pedidos/endereços, transações e travas de estoque, cadastro público que só cria cliente.

**Pontos de atenção / melhorias futuras**
- `php/buscar_cep.php` **desliga a verificação do certificado SSL** (para funcionar no XAMPP) e libera CORS para qualquer origem. Em produção, configure o `curl.cainfo` no `php.ini` e ative a verificação.
- "Esqueci minha senha" ainda não existe (precisa de envio de e-mail).
- Confirmar o e-mail do cadastro (link de verificação).
- Proteção contra tentativas repetidas de login (limitar por IP/tempo).
- Formulário de perfil sem token CSRF (o checkout e os endereços têm).
- Termos de Uso são um **modelo**: peça revisão de um profissional antes de abrir a loja.

---

## 9. Implementações futuras (planejadas)
1. **Cartão de crédito e boleto** (aparecem como "Em breve"): exigem um provedor de pagamento; ao implementar, criar a tabela de pagamentos já desenhada para o provedor escolhido.
2. **Confirmação automática do Pix** (via provedor/API), eliminando a conferência manual.
3. Cálculo de **frete por CEP** e **cupons de desconto** (os campos já existem no pedido).
4. **Avaliações de produtos** (a tabela `avaliacao` já existe, falta a tela).
5. Verificação do CPF em serviço oficial (`cpf_validar_externo`).
6. Recuperação de senha e confirmação de e-mail.
7. Notificações por e-mail/WhatsApp ao mudar o status do pedido.
8. Rastreamento de entrega (código dos Correios).

---

## 10. Como instalar e testar
1. Copie a pasta `novo_cicro` para `htdocs` do XAMPP. Inicie **Apache** e **MySQL**.
2. No phpMyAdmin, crie o banco `cicro` (utf8mb4_general_ci) e importe `banco/cicro.sql` (cria as tabelas e já traz seus dados).
   Se já tem o banco da v6 em uso e quer manter o que cadastrou, faça backup e rode apenas `banco/migracao_v7.sql` (uma vez).
3. Confira `php/conexao.php` (usuário, senha e nome do banco).
4. Pix: o sistema vem em **modo demonstração** (`PIX_DEMONSTRACAO = true` em `php/config_pagamento.php`): o QR é de exemplo e a tela de pagamento tem o botão "Simular pagamento". Para cobrar de verdade, ponha `false` e a **sua chave Pix**.
5. Acesse `http://localhost/novo_cicro/views/user/index.php`.
6. **Roteiro de teste:** cadastrar-se (CPF válido, ex.: um seu) → escolher produto → adicionar → "Mandar para o carrinho" → checkout → confirmar → ver o Pix → entrar como admin → Vendas → marcar **Pago** → Relatório.
7. Acesso da equipe: usuário `admin@cicro.com` (a senha é a que você cadastrou; troque se for publicar).


---

## 11. Banco x Sistema (versão 6)
Regra desta versão: **tudo que o código usa existe no banco e tudo que está no banco o código usa** (conferido com um script que lê todos os SQL dos arquivos PHP e compara com as tabelas).

**Tabela `pagamento` (mantida, adaptada ao Pix de demonstração).** Uma linha por pedido (`fk_pedido` único).
`metodo` ('pix'), `status` (aguardando · pago · cancelado · expirado · estornado), `valor`, `txid` (único, ex. `CICRO000012`), `codigo` (copia e cola), `simulado` (1 = demonstração), `data_criacao`, `data_pagamento`.
Saíram `parcelas`, `cartao_bandeira` e `cartao_final` (cartão/boleto são futuros). O status do pagamento acompanha o do pedido:
Pendente→aguardando · Pago/Enviado/Entregue→pago · Cancelado→cancelado (pedido que passou de 24 h sem pagar→expirado; pedido já pago e cancelado→estornado).

**Entrou no banco (porque o código usa):** `usuario.email_verificado`, `token_verificacao`, `token_expira`, `verificacao_enviada_em`; categoria "Cropped"; ordem do menu em `categoria.ordem_exibicao`; chave estrangeira `movimentacao_estoque.fk_pedido`; regras (CHECK) de status de pedido, conversa, pagamento e nota 1–5; avaliação única por produto em cada pedido.
**Saiu do banco (porque nada usa):** `cliente.data_nascimento`, `categoria.fk_categoria_pai` e `url_imagem_capa`, `conversa.fk_pedido`, `pedido.status_envio_whatsapp`.
**Entrou no código (porque o banco já tinha):** menu de categorias lido do banco (só categorias ativas, na ordem definida), pedido ↔ pagamento, e correção de `senha` → `senha_hash` em `admin-pages/perfil-admin.php` (a coluna `senha` não existe).
**Regras de status do pedido:** não vai para Enviado/Entregue sem estar Pago; pedido pago não volta a Pendente; cancelado não reabre; cancelar devolve o estoque uma única vez.
**Contas antigas:** continuam entrando (marcadas como e-mail confirmado). Quem não tem CPF cadastrado precisa informá-lo no perfil antes de comprar.


---

## 12. Versão 7: o que mudou e como configurar
**E-mail (confirmação de cadastro e recuperação de senha).** O e-mail "não chegava" porque o sistema vinha em `EMAIL_MODO = 'arquivo'` (só grava em `logs/emails.log`) e a função `mail()` do XAMPP não envia nada. Agora existe o modo `'smtp'`, que envia de verdade por qualquer servidor SMTP, sem biblioteca. Em `php/config_email.php`: `EMAIL_MODO = 'smtp'`, preencha `SMTP_USUARIO` e `SMTP_SENHA` (no Gmail: ative a verificação em duas etapas e crie uma **Senha de app** em myaccount.google.com/apppasswords; use o mesmo e-mail em `EMAIL_REMETENTE_EMAIL`) e deixe `EMAIL_MOSTRAR_LINK_NA_TELA = false`. Se o envio falhar, o motivo é gravado em `logs/emails_erro.log` e o cliente vê um aviso. Requisito: extensão `openssl` ligada no `php.ini` (já vem ligada no XAMPP).
**Recuperação de senha.** "Esqueceu a senha?" no login → `esqueci_senha.php` (pede o e-mail) → link com código de uso único, válido por 60 minutos → `redefinir_senha.php` (nova senha, mínimo 6). O banco guarda só o hash do código; a tela responde igual exista a conta ou não; no máximo 1 pedido de link por minuto.
**Login com o Google.** O botão só aparece depois de preencher `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` em `php/config_google.php` (o passo a passo está no próprio arquivo: Google Cloud Console → credenciais OAuth → URI de retorno `.../php/google_callback.php`). Conta nova entra como cliente com e-mail já confirmado; se já existe cliente com aquele e-mail, a conta é ligada ao Google; contas da equipe (admin/vendedor) só entram com senha. Quem entra pelo Google precisa informar o CPF no perfil antes da primeira compra.
**Banco.** `pedido` perdeu `valor_produtos`, `valor_frete` e `valor_desconto` (o total é a soma dos produtos); `endereco` ganhou chave única (cliente + CEP); `avaliacao` passou a ter chave única (cliente + produto); `usuario` ganhou `google_id`, `token_recuperacao`, `recuperacao_expira`, `recuperacao_enviada_em`. `pedido.status_envio_whatsapp` já tinha saído na v6.
**Regras de negócio novas.** Um cliente não tem dois endereços com o mesmo CEP. Só avalia produto que comprou e já recebeu (pedido Entregue), uma avaliação por produto por cliente (comprar de novo permite editar a mesma). O cliente marca no carrinho quais itens comprar; o resto continua no carrinho. Só o cliente cancela pedido (Pendente ou Pago; depois de Enviado não), pelo botão em *Meus pedidos*; o admin em *Vendas* só confirma pagamento, envia e entrega; o sistema continua cancelando sozinho o pedido não pago em 24 h.


## v9 - Exclusão de conta, troca de CPF e avisos ao admin
- **Excluir conta** (perfil do cliente): `controllers/excluir_conta.php` + `models/conta_funcoes.php`. Apaga cadastro, CPF, endereços, carrinho, avaliações e conversas; pedidos antigos ficam sem identificação na conta "Cliente excluído". Bloqueia se houver pedido Pendente/Pago/Enviado.
- **Troca de CPF**: cliente pede no perfil (`solicitacao_cpf`); o administrador aprova em `views/admin/solicitacoes-cpf.php`; o cliente troca uma vez. Rode `banco/migracao_v9.sql` em bancos já existentes.
- **Cor por foto**: no cadastro e na edição do produto, o admin escolhe ou digita a cor de cada foto (`imagem_produto.cor`); o estoque continua por tamanho. Rode `banco/migracao_v10.sql` em bancos já existentes.
- **CPF criptografado**: o número é gravado cifrado e a duplicidade é conferida por `cliente.cpf_hash`. Passo a passo e cuidados com a chave em `docs/CRIPTOGRAFIA_CPF.md`.
- **Avisos no painel**: `controllers/notificacoes_admin.php` + `js/notificacoes-admin.js` mostram pop-up de nova compra (Vendas) e de pedido de troca de CPF (admin).
