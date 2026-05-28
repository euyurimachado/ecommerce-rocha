# Especificacao do Ecommerce Rocha Sports

Versao: 1.0  
Projeto: rochasports.com.br  
Repositorio previsto: https://github.com/euyurimachado/ecommerce-rocha  
Stack recomendada: Laravel + Livewire + Alpine.js + Tailwind CSS + Filament

## 1. Visao do Produto

A nova Rocha Sports deve ser um ecommerce local de suplementos com experiencia app-like, mobile-first, rapida, segura e orientada a conversao. A referencia conceitual e a praticidade de apps de delivery: busca forte, categorias claras, cards de produto, carrinho sempre acessivel, checkout curto, entrega local e retirada na loja.

O posicionamento desejado e: primeiro ecommerce local de suplementos em Campos dos Goytacazes com experiencia moderna, confiavel e especializada.

Objetivos principais:

- Encontrar produtos em ate 10 segundos.
- Adicionar produtos ao carrinho com um clique.
- Comprar com poucos passos.
- Entregar experiencia excelente em mobile, desktop, tablet e PWA.
- Garantir SEO tecnico forte para categorias e produtos.
- Proteger dados pessoais e cumprir LGPD.
- Permitir gestao operacional simples via painel administrativo.
- Manter arquitetura preparada para app iOS/Android e futura oferta whitelabel.

## 2. Stack Ideal Para Hostinger Compartilhada

### Stack principal

- Backend: Laravel 11 ou 12, conforme suporte do ambiente no deploy.
- Frontend web: TALL Stack, com Blade, Livewire, Alpine.js e Tailwind CSS.
- Admin: Filament 3.
- Banco inicial: MySQL/MariaDB, por melhor compatibilidade com hospedagem compartilhada.
- Cache inicial: file cache/database cache.
- Filas iniciais: database queue, com cron executando `php artisan schedule:run`.
- Busca inicial: Laravel Scout com driver database ou Meilisearch externo no futuro.
- Pagamentos: gateway que suporte Pix, cartao e boleto, sem armazenamento local de dados sensiveis de cartao.
- Deploy: GitHub como fonte de verdade, build local/CI, envio para Hostinger via Git/SSH/SFTP conforme plano.

### Justificativa

A Hostinger informa que Composer esta disponivel nos planos Web Premium, Web Business e Cloud, e recomenda Composer 2 para PHP 8 ou superior. Tambem documenta acesso SSH em planos Premium ou superiores e uso de cron para comandos Laravel, incluindo `artisan schedule:run`. Isso torna Laravel/TALL/Filament uma escolha realista para hospedagem compartilhada, desde que a arquitetura comece enxuta e nao dependa de servicos residentes como Redis local, workers permanentes ou Node em producao.

Fontes oficiais consultadas:

- Composer na Hostinger: https://support.hostinger.com/en/articles/5792078-how-to-use-composer
- SSH na Hostinger: https://support.hostinger.com/pt/articles/1583645-como-habilitar-o-acesso-ssh
- Laravel na Hostinger: https://support.hostinger.com/en/articles/1583301-which-laravel-versions-are-supported-at-hostinger

### Evolucao recomendada

Fase 1, hospedagem compartilhada:

- Laravel monolito modular.
- Filament para admin.
- Livewire para ecommerce.
- MySQL/MariaDB.
- Cron para scheduler.
- Queue database processada por cron.
- Cache em arquivo/database.

Fase 2, mais escala:

- Upgrade para VPS/Cloud.
- Redis para cache, sessoes, filas e rate limiting.
- Meilisearch dedicado.
- CDN com imagens otimizadas.
- Workers permanentes.
- Laravel Horizon.

Fase 3, app iOS/Android:

- API REST versionada sob `/api/v1`.
- Autenticacao com Laravel Sanctum.
- App em React Native/Expo ou Flutter.
- Push notifications.
- Reuso das mesmas regras de negocio, catalogo, carrinho, pedido e pagamento.

Fase 4, whitelabel/multitenant:

- Separacao por tenant/loja.
- Temas configuraveis.
- Dominios personalizados.
- Catalogos, marcas, banners, politicas, entregas e gateways por tenant.
- Planos comerciais SaaS.

## 3. Principios de Arquitetura

O projeto deve ser um monolito modular no inicio, com fronteiras claras:

- Catalogo: produtos, categorias, marcas, atributos, variacoes e imagens.
- Comercio: carrinho, cupons, promocoes, checkout, pagamentos e pedidos.
- Logistica: entrega, retirada, horarios, regioes, taxas e status.
- Cliente: conta, enderecos, favoritos, historico e recompra.
- Conteudo: banners, paginas institucionais, blog e SEO.
- Administracao: Filament resources, permissoes, auditoria e relatorios.
- Privacidade: consentimentos, cookies, solicitacoes LGPD e logs.
- API: endpoints versionados para futuro app mobile.

Regra de ouro: a interface web pode ser Livewire, mas as regras centrais de negocio devem ficar em services/actions reutilizaveis pela API futura.

## 4. Mapa do Site

Publico:

- `/`
- `/buscar`
- `/categorias`
- `/categorias/{slug}`
- `/marcas`
- `/marcas/{slug}`
- `/produto/{slug}`
- `/ofertas`
- `/mais-vendidos`
- `/combos-e-kits`
- `/lojas-parceiras` ou `/marcas-parceiras`
- `/carrinho`
- `/checkout`
- `/pedido/{codigo}/status`
- `/minha-conta`
- `/minha-conta/pedidos`
- `/minha-conta/favoritos`
- `/minha-conta/enderecos`
- `/blog`
- `/blog/{slug}`
- `/politica-de-privacidade`
- `/politica-de-cookies`
- `/termos-de-uso`
- `/trocas-e-devolucoes`
- `/entrega`
- `/pagamento`
- `/contato`

Administrativo:

- `/admin`
- `/admin/produtos`
- `/admin/categorias`
- `/admin/marcas`
- `/admin/pedidos`
- `/admin/clientes`
- `/admin/estoque`
- `/admin/cupons`
- `/admin/banners`
- `/admin/entregas`
- `/admin/relatorios`
- `/admin/configuracoes`
- `/admin/lgpd`
- `/admin/auditoria`

API futura:

- `/api/v1/auth`
- `/api/v1/catalog`
- `/api/v1/products`
- `/api/v1/categories`
- `/api/v1/cart`
- `/api/v1/checkout`
- `/api/v1/orders`
- `/api/v1/customer`
- `/api/v1/search`
- `/api/v1/consents`

## 5. Fluxo Completo de Compra

1. Usuario acessa a home.
2. Informa ou confirma localizacao: "Entrega em Campos dos Goytacazes, RJ".
3. Busca produto, toca em uma categoria ou acessa oferta/banner.
4. Visualiza cards com preco, disponibilidade, avaliacao e entrega/retirada.
5. Adiciona ao carrinho com um clique.
6. Carrinho sticky/flutuante confirma item, subtotal e CTA.
7. Usuario revisa carrinho, aplica cupom e ve sugestoes complementares.
8. Checkout permite entrar, cadastrar ou comprar como convidado.
9. Usuario escolhe entrega local ou retirada na loja.
10. Informa dados minimos necessarios.
11. Escolhe Pix, cartao ou boleto.
12. Confirma pedido.
13. Recebe tela de sucesso, WhatsApp/e-mail e codigo de acompanhamento.
14. Pode acompanhar status e recomprar futuramente.

Status sugeridos:

- Pedido recebido.
- Pagamento aprovado.
- Em separacao.
- Saiu para entrega.
- Pronto para retirada.
- Entregue.
- Cancelado.

## 6. Wireframe Textual da Home Desktop

Topo:

- Barra superior fina: entrega local, WhatsApp, horarios e link para retirada.
- Header fixo com logo Rocha Sports, busca ampla central, conta, favoritos e carrinho.
- Localizacao abaixo ou junto da busca: "Entrega em Campos dos Goytacazes, RJ".

Navegacao:

- Menu horizontal: Whey Protein, Creatina, Pre-treino, Vitaminas, Snacks, Acessorios, Combos, Ofertas.

Primeira dobra:

- Banner principal grande com campanha.
- Coluna lateral com ofertas rapidas ou beneficios: entrega rapida, produtos originais, retirada na loja.

Conteudo:

- Categorias rapidas com icone/foto.
- "Mais pedidos em Campos".
- "Ofertas para voce".
- "Combos e kits".
- "Marcas parceiras".
- "Objetivos": ganho de massa, emagrecimento, energia, performance, recuperacao, saude.
- Blocos de confianca.
- Conteudo SEO local: loja de suplementos em Campos dos Goytacazes.

Rodape:

- Institucional, politicas, atendimento, redes sociais, formas de pagamento e dados da loja.

## 7. Wireframe Textual da Home Mobile

Topo sticky:

- Linha 1: logo reduzida, localizacao e icone de conta.
- Linha 2: busca grande com placeholder "Buscar suplementos, marcas e lojas".

Navegacao principal:

- Categorias horizontais rolaveis.
- Banner carrossel.
- Atalhos: Ofertas, Mais vendidos, Combos, Retirada.

Feed:

- Secoes em lista vertical com cards 2 por linha ou cards horizontais compactos.
- Botao rapido de adicionar em cada card.
- Selo de entrega rapida/retirada.
- Carrinho sticky inferior quando houver itens.

Bottom navigation:

- Inicio.
- Busca.
- Ofertas.
- Pedidos.
- Conta.

## 8. Pagina de Produto

Estrutura:

- Breadcrumb.
- H1 com nome completo otimizado.
- Galeria de imagens otimizadas.
- Marca, categoria, peso/volume e disponibilidade.
- Preco, preco promocional e parcelamento se aplicavel.
- Avaliacao media.
- Estimativa de entrega e retirada.
- Variações: sabor, tamanho, embalagem.
- CTA principal: "Comprar agora".
- CTA secundario: "Adicionar ao carrinho".
- Descricao curta.
- Beneficios.
- Modo de uso.
- Ingredientes/tabela nutricional.
- Selos: produto original, pagamento seguro, entrega local.
- Avisos legais para suplementos.
- Avaliacoes.
- Produtos relacionados.
- Compre junto.
- Schema Product/Offer/AggregateRating.

## 9. Carrinho

Requisitos:

- Persistente para visitante e cliente logado.
- Suportar sessao, cookie seguro e carrinho associado ao usuario apos login.
- Lista de produtos com imagem, nome, variacao, quantidade e preco.
- Edicao de quantidade.
- Remocao.
- Subtotal.
- Cupom.
- Frete/retirada.
- Estimativa de entrega.
- Sugestoes complementares.
- CTA claro para checkout.
- Alertas de estoque e mudanca de preco.

## 10. Checkout

Modelo recomendado: checkout em pagina unica com blocos progressivos.

Blocos:

- Identificacao: e-mail/telefone, login opcional, compra como convidado.
- Entrega: endereco ou retirada na loja.
- Pagamento: Pix, cartao, boleto.
- Resumo: produtos, frete, cupom, total.
- Confirmacao: aceite de termos e politicas.

Regras:

- Validacao frontend e backend.
- Mensagens de erro claras.
- SSL obrigatorio.
- CSRF.
- Rate limiting.
- Nunca armazenar dados sensiveis de cartao.
- Pedido criado com status "Pedido recebido".
- Pagamento tratado por gateway seguro.

## 11. Painel Administrativo com Filament

Recursos essenciais:

- Dashboard com vendas, pedidos, ticket medio, pedidos pendentes e baixo estoque.
- Produtos: cadastro, imagens, SEO, variacoes, estoque, preco, promocao.
- Categorias: arvore, icone, conteudo SEO, ordem.
- Marcas: logo, descricao, destaque.
- Pedidos: status, pagamento, entrega, historico e observacoes.
- Clientes: dados, pedidos, consentimentos e enderecos.
- Estoque: entradas, saidas, reservas e alertas.
- Cupons: tipo, validade, limite, primeira compra, frete gratis.
- Banners: posicao, periodo, dispositivo, link.
- Entregas: regioes, taxas, horarios, retirada.
- Avaliacoes: moderacao.
- Blog/conteudo.
- LGPD: solicitacoes, consentimentos e exportacao/exclusao.
- Relatorios: vendas, produtos, clientes, canais e estoque.
- Usuarios admin: papeis e permissoes.
- Auditoria: log de alteracoes administrativas.

Permissoes sugeridas:

- Super admin.
- Gerente.
- Atendimento.
- Estoque.
- Marketing.
- Entregador/operacao.

## 12. Funcionalidades Essenciais

- Home mobile-first.
- Busca por produto, marca, categoria e objetivo.
- Categorias horizontais.
- Cards de produto.
- Carrinho persistente.
- Checkout simples.
- Entrega local e retirada.
- Pagamento por Pix, cartao e boleto.
- Admin Filament.
- SEO tecnico.
- Politicas legais.
- Consentimento de cookies.
- Controle de estoque.
- Cupons.
- Banners.
- Avaliacoes.
- Historico de pedidos.
- Recompra rapida.
- WhatsApp integrado.

## 13. Funcionalidades Futuras

- PWA completo com push notification.
- App iOS/Android.
- Programa de fidelidade.
- Clube de assinatura.
- Cashback/pontos.
- Recuperacao automatica de carrinho abandonado.
- Recomendada por IA.
- Multiloja/parceiros.
- Marketplace local.
- Whitelabel SaaS.
- Meilisearch/Algolia.
- Redis e filas permanentes.
- Integracao com ERP.
- Integracao com motoboy/logistica.
- Roteirizacao de entregas.

## 14. SEO Tecnico

Implementar:

- URLs amigaveis.
- Titles e descriptions unicos.
- H1 unico por pagina.
- Hierarquia correta de H2/H3.
- Breadcrumbs.
- Canonical.
- Sitemap automatico.
- Robots.txt.
- Open Graph.
- Schema.org: Product, Offer, BreadcrumbList, Organization, LocalBusiness.
- Alt text em imagens.
- Lazy loading.
- Imagens WebP/AVIF.
- Conteudo descritivo em categorias.
- Blog para conteudo organico.
- Paginas de categoria indexaveis.
- Core Web Vitals otimizados.

Categorias SEO prioritarias:

- Whey Protein em Campos dos Goytacazes.
- Creatina em Campos dos Goytacazes.
- Pre-treino em Campos dos Goytacazes.
- Loja de suplementos em Campos dos Goytacazes.
- Suplementos com entrega rapida.
- Suplementos originais.

## 15. Seguranca

Obrigatorio:

- HTTPS em todas as paginas.
- SSL valido.
- Protecao CSRF.
- Escape de output contra XSS.
- Sanitizacao e validacao de inputs.
- Rate limiting em login, cadastro, recuperacao de senha, carrinho e checkout.
- Senhas com hash seguro padrao Laravel.
- Recuperacao de senha com token temporario.
- Controle de acesso por papel/permissao.
- Logs administrativos.
- Auditoria de alteracoes criticas.
- Gateway de pagamento seguro.
- Nao armazenar dados sensiveis de cartao.
- Backups automaticos.
- Monitoramento de erros.
- Paginas de erro sem stack trace em producao.
- 2FA para administradores, se possivel.
- Politica de senha forte para admins.

## 16. Privacidade e LGPD

Implementar:

- Politica de privacidade clara.
- Politica de cookies.
- Banner de consentimento.
- Preferencias de cookies por categoria: essenciais, analiticos e marketing.
- Registro de consentimento.
- Coleta minima de dados.
- Finalidade explicita por dado.
- Opt-in claro para newsletter, WhatsApp e promocoes.
- Exportacao de dados pessoais.
- Solicitacao de exclusao/anonimizacao.
- Protecao de dados de clientes.
- Contratos/base legal para ferramentas terceiras.
- Retencao controlada de logs e pedidos.

Dados tratados:

- Nome.
- E-mail.
- Telefone.
- Endereco.
- Historico de pedidos.
- Preferencias de compra.
- Consentimentos.

## 17. Performance

Metas:

- LCP abaixo de 2,5s.
- CLS abaixo de 0,1.
- INP otimizado.
- Carregamento bom em 4G.
- Imagens WebP/AVIF.
- Lazy loading.
- CSS e JS minificados.
- Build com Vite.
- Componentes Livewire bem segmentados.
- Cache de paginas publicas quando possivel.
- Cache de consultas frequentes.
- Evitar scripts terceiros desnecessarios.
- CDN para assets quando viavel.

Cuidados em hospedagem compartilhada:

- Evitar jobs pesados em tempo real.
- Processar rotinas por cron.
- Otimizar queries.
- Usar indices corretos.
- Reduzir dependencias externas.
- Usar imagens comprimidas antes do upload.

## 18. Componentes de UI

Principais:

- AppHeader.
- LocationSelector.
- SearchBar com autocomplete.
- CategoryRail.
- PromoCarousel.
- ProductCard.
- ProductQuickAdd.
- PartnerBrandCard.
- TrustBlock.
- StickyCart.
- BottomNavigation.
- FilterDrawer.
- SortControl.
- ProductGallery.
- VariationSelector.
- QuantityStepper.
- CheckoutStep.
- OrderStatusTimeline.
- EmptyState.
- LoadingSkeleton.
- CookieConsent.

Diretrizes visuais:

- Azul, branco, cinza e prata como base.
- Visual esportivo, premium e tecnologico.
- Cards limpos e escaneaveis.
- Contraste adequado.
- Botoes grandes no mobile.
- Icones consistentes.
- Feedback visual imediato.
- Nao copiar visualmente o iFood.

## 19. Entidades Principais do Banco

Base:

- users
- admin_users ou users com papeis
- roles
- permissions
- customers
- customer_addresses
- categories
- brands
- products
- product_variants
- product_images
- product_attributes
- inventory_movements
- carts
- cart_items
- coupons
- promotions
- orders
- order_items
- order_status_history
- payments
- shipments
- pickup_locations
- delivery_zones
- reviews
- favorites
- banners
- pages
- blog_posts
- consent_records
- cookie_preferences
- lgpd_requests
- audit_logs
- settings

Preparacao whitelabel:

- tenants
- tenant_domains
- tenant_settings
- tenant_themes
- tenant_payment_methods
- tenant_delivery_zones

Mesmo que o MVP comece single-store, e recomendavel incluir `tenant_id` de forma planejada em tabelas de dominio ou isolar a camada de tenant para migracao futura.

## 20. API Para Futuro App

Desde o MVP, criar services/actions independentes de Livewire:

- AddToCartAction.
- ApplyCouponAction.
- CreateOrderAction.
- CalculateShippingAction.
- StartPaymentAction.
- UpdateOrderStatusAction.
- SearchProductsAction.

Endpoints futuros:

- `GET /api/v1/home`
- `GET /api/v1/products`
- `GET /api/v1/products/{slug}`
- `GET /api/v1/categories`
- `GET /api/v1/search`
- `POST /api/v1/cart/items`
- `PATCH /api/v1/cart/items/{id}`
- `DELETE /api/v1/cart/items/{id}`
- `POST /api/v1/checkout`
- `GET /api/v1/orders`
- `GET /api/v1/orders/{id}`

Autenticacao:

- Laravel Sanctum.
- Tokens por dispositivo.
- Rate limiting.
- Revogacao de sessoes.

## 21. Backlog por Epicos

Epico 1: Fundacao tecnica

- Instalar Laravel, Livewire, Tailwind e Filament.
- Configurar GitHub.
- Configurar ambientes local, staging e producao.
- Configurar deploy.
- Criar roles/permissoes.

Epico 2: Catalogo

- CRUD de categorias, marcas, produtos, variacoes e imagens.
- Estoque.
- SEO por produto/categoria.

Epico 3: Experiencia de compra

- Home.
- Busca.
- Cards.
- Carrinho persistente.
- Checkout.
- Status do pedido.

Epico 4: Operacao

- Pedidos.
- Entregas.
- Retirada.
- Cupons.
- Relatorios.

Epico 5: Seguranca e LGPD

- Politicas.
- Consentimentos.
- Rate limiting.
- Auditoria.
- Solicitacoes LGPD.

Epico 6: SEO e conteudo

- Sitemap.
- Schema.
- Blog.
- Conteudo de categorias.

Epico 7: App e whitelabel readiness

- API v1.
- Services/actions.
- Configuracoes por loja.
- Tema configuravel.

## 22. Criterios de Aceite

Produto e conversao:

- Usuario encontra produto em ate 10 segundos.
- Produto pode ser adicionado ao carrinho com um clique.
- Carrinho permanece acessivel durante a navegacao.
- Checkout pode ser concluido em poucos passos.
- Usuario pode escolher entrega ou retirada.
- Usuario recebe confirmacao e acompanha status.

Admin:

- Administrador cadastra produto completo sem suporte tecnico.
- Administrador altera estoque e preco promocional.
- Administrador gerencia status do pedido.
- Administrador ve relatorios basicos.

SEO:

- Toda pagina importante possui title, description, canonical e headings corretos.
- Produtos possuem schema Product/Offer.
- Sitemap e robots ativos.
- Categorias possuem conteudo indexavel.

Seguranca:

- HTTPS ativo.
- CSRF ativo.
- Rate limiting configurado.
- Dados sensiveis de cartao nao sao armazenados.
- Logs administrativos existem.
- Erros de producao nao exibem detalhes tecnicos.

LGPD:

- Politicas publicadas.
- Cookies separados por categoria.
- Consentimento registrado.
- Cliente pode solicitar dados ou exclusao.

Performance:

- LCP menor que 2,5s nas principais paginas.
- CLS menor que 0,1.
- Imagens otimizadas.
- Experiencia fluida em mobile.

## 23. Versionamento e GitHub

Fluxo recomendado:

- Branch principal: `main`.
- Branch de desenvolvimento: `develop`, opcional para equipe maior.
- Features: `feature/nome-da-feature`.
- Correcoes: `fix/nome-do-problema`.
- Releases: tags `v1.0.0`, `v1.1.0`.

Commits:

- `feat: adiciona carrinho persistente`
- `fix: corrige calculo de frete`
- `chore: configura filament`
- `docs: adiciona especificacao do projeto`

Protecoes recomendadas:

- Pull request obrigatorio para `main`.
- Checks de testes antes de merge.
- Secrets fora do repositorio.
- `.env` nunca versionado.
- `.env.example` versionado.

Observacao local: a pasta atual ainda nao estava inicializada como reposititorio Git no momento desta especificacao. O proximo passo tecnico e inicializar ou clonar `euyurimachado/ecommerce-rocha`, criar o projeto Laravel e subir o primeiro commit.

## 24. Roadmap MVP

MVP 1:

- Laravel/TALL/Filament configurados.
- Catalogo completo.
- Home app-like.
- Busca simples.
- Carrinho persistente.
- Checkout com Pix/manual ou gateway inicial.
- Painel de pedidos.
- SEO basico.
- Politicas LGPD.

MVP 2:

- Gateway completo.
- Avaliacoes.
- Cupons avancados.
- Recompra.
- WhatsApp.
- Blog.
- Melhorias de performance.

MVP 3:

- API v1.
- PWA.
- Push.
- Busca avancada.
- Preparacao app.

MVP 4:

- App mobile.
- Multitenant/whitelabel.
- Infra VPS/Cloud.
