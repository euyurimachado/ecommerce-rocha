# Arquitetura white label e atualizações centralizadas

## Estado atual

Rocha Sports e Apex Prisma são instalações Laravel independentes. A base Rocha já foi generalizada parcialmente por `StoreSetting`, `IntegrationSetting`, contratos de pagamento/entrega e pelo instalador, mas não existe um package central versionado. Na prática, correções ainda precisam ser transportadas entre repositórios e cada instalação pode divergir.

Esta etapa não transforma um dos projetos em upstream do outro e não executa self-update. Ela cria apenas fronteiras, leitura de versão e consulta segura de metadados para permitir uma extração progressiva.

## Mapa técnico dos acoplamentos

| Classificação | Estado atual | Destino proposto |
| --- | --- | --- |
| CORE | Modelos e fluxos de catálogo, carrinho, checkout, pedidos, SEO, PWA, contratos e drivers em `app/`; componentes genéricos em `resources/`; rotas e testes funcionais | Package `euyurimachado/ecommerce-core`, dividido internamente por domínio |
| BRANDING | Logos, favicon, ícone PWA, nome, slogan, cores e fonte em `StoreSetting`; fallbacks Rocha em `public/images` | Dados da instalação e assets em `storage/app/public/branding`; fallbacks neutros no core |
| THEME | Blade/Tailwind em `resources/views/storefront` e `resources/css/app.css`; tokens ainda chamados `rocha-*` | Tema padrão no package e overrides explícitos em `resources/views/vendor/ecommerce-core` |
| STORE CONFIG | `StoreSetting`, `IntegrationSetting`, `.env`, configurações de origem, e-mail e providers | Permanecem na instalação; contratos e telas compartilhadas pertencem ao core |
| STORE DATA | Produtos, categorias, marcas, banners, clientes, pedidos, pagamentos, webhooks e uploads | Permanecem exclusivamente no banco/storage de cada loja |
| STORE-SPECIFIC FEATURES | Curadoria e seeder Rocha, catálogo JSON Rocha, conteúdo editorial e banners específicos; equivalentes Apex ficam no projeto Apex | Módulos/seeds da instalação, nunca executados por atualização do core |
| LEGACY COUPLING | `RochaSportsCatalogSeeder`, `database/data/rocha_sports_products.json`, `public/sw.js` com chave Rocha, fallbacks `logo-rocha-sports.webp`, classes `rocha-*`, defaults de domínio/e-mail/descriptor e alguns textos comerciais | Remover gradualmente por configuração, tokens semânticos e assets neutros, com testes de regressão |

### Auditoria por diretório

- `app/`: majoritariamente candidato a core. `StoreSetting` e `IntegrationSetting` são modelos compartilháveis, mas seus registros são dados da loja. Drivers devem sair em módulos do core.
- `resources/`: storefront e componentes são core/theme. Conteúdo, layout ou imagens específicos devem usar configuração, slots ou override explícito.
- `routes/`: rotas de catálogo, checkout, webhooks, instalador e painel podem ser registradas pelo package. A instalação mantém apenas extensões próprias.
- `config/`: schemas/defaults genéricos pertencem ao core; endpoints, canais e credenciais são configuração da instalação e ambiente.
- `database/`: migrations estruturais compartilhadas podem migrar para o package. Catálogos e seeds de marca nunca migram para o core.
- `public/`: build do tema padrão pode ser publicado pelo package ou produzido no pipeline. Logos e uploads do cliente não podem ser sobrescritos.
- `tests/`: testes de contrato/domínio migram com o core. Cenários, catálogo e identidade de cada marca continuam em suas instalações.

## Fronteira proposta do core

O package Composer privado deve começar pequeno, com namespaces próprios e providers explícitos:

```text
ecommerce-core/
├── src/
│   ├── Catalog/
│   ├── Cart/
│   ├── Checkout/
│   ├── Orders/
│   ├── Payments/
│   ├── Shipping/
│   ├── Storefront/
│   ├── Setup/
│   └── Updates/
├── database/migrations/
├── resources/views/
├── resources/css/
├── config/
└── tests/
```

O core pode definir contratos, implementação padrão, migrations aditivas, páginas Filament e componentes. A instalação fornece configuração, dados, credenciais, branding, seeds e extensões registradas em interfaces públicas.

Integrações de pagamento e entrega são módulos do core porque sua lógica e segurança são compartilhadas; suas credenciais e o provider ativo continuam em `IntegrationSetting` de cada loja.

## Composer

Um package Composer privado é adequado porque resolve dependências e versões de forma determinística pelo `composer.lock`, permite restrições SemVer e evita cópia arbitrária de diretórios. Cada loja deverá declarar uma faixa intencional, por exemplo:

```json
{
    "require": {
        "euyurimachado/ecommerce-core": "^1.0"
    }
}
```

Enquanto o package não existe, `CoreVersion` consulta `composer.lock` e usa `StoreSetting.installation_version` apenas como compatibilidade legada. Depois da migração, a versão do package no lock passa a ser a única fonte efetiva.

## Git e releases

Organização recomendada:

```text
euyurimachado/ecommerce-core
euyurimachado/ecommerce-rocha
euyurimachado/apex-prisma
```

O core publica tags imutáveis `vMAJOR.MINOR.PATCH`, artefatos/release notes e um manifesto por canal. Tags nunca são reutilizadas. Rocha e Apex recebem Pull Requests automatizados que atualizam a restrição/lock, executam testes e só então seguem para deploy.

SemVer adotado:

- PATCH: correção compatível e segurança.
- MINOR: funcionalidade compatível.
- MAJOR: contrato ou migration potencialmente incompatível.

Módulos opcionais devem declarar a faixa de core compatível em suas dependências Composer. Não haverá marketplace nesta fase.

## Branding, tema e customizações

- Branding é dado: `StoreSetting` e arquivos no disco público da instalação.
- Theme é código versionado: tema padrão do core mais tokens semânticos.
- Overrides são explícitos em `resources/views/vendor/ecommerce-core/...` e nunca publicados com `--force` durante um update comum.
- Extensões usam contratos, eventos, slots/componentes e service providers documentados.
- Uma loja não deve editar um arquivo dentro de `vendor/`.
- Assets do core têm namespace próprio; assets e uploads do cliente ficam fora dele.

## Serviço de updates desta etapa

`CoreVersion` identifica a versão instalada. `ReleaseChecker` consulta uma URL configurada por canal, valida o payload por `ReleaseManifest`, compara SemVer, verifica requisitos de PHP/Laravel e mantém o resultado em cache. Falha HTTP, canal divergente ou manifesto inválido retorna estado indisponível sem derrubar o painel.

O manifesto aceito contém somente metadados conhecidos:

```json
{
    "latest": "1.5.0",
    "channel": "stable",
    "minimum_php": "8.3.0",
    "minimum_laravel": "13.0.0",
    "released_at": "2026-09-16T12:00:00-03:00",
    "security": false,
    "breaking": false,
    "release_notes": "Novidades e correções.",
    "checksum": "SHA-256 com 64 caracteres hexadecimais"
}
```

Campos remotos de shell, Artisan, SQL ou URLs executáveis são ignorados. Nesta fase o checksum é validado como metadado; a verificação do artefato só existirá quando houver um executor de pipeline autorizado.

Os canais previstos são `stable` e `beta`; a política inicial é `manual`. Políticas futuras possíveis são `security_only` e `patch_auto`. MINOR automático depende de rollback comprovado e MAJOR nunca será silencioso.

## Filament

`Sistema > Atualizações` mostra versão instalada e sua fonte, versão disponível, canal, data, release notes, flags de segurança/breaking, compatibilidade e checksum. Somente administradores acessam a página. Não existe botão que execute update local.

`Configurações > Reconfigurar loja` reutiliza regras e persistência do instalador, não cria usuários, não altera `installed_at` e não reabre `/install`. Segredos existentes não são hidratados no browser; campo vazio preserva e valor novo rotaciona. Providers candidatos são testados antes da transação que os ativa.

## Segurança e confiança

- O endpoint do manifesto é configurado no servidor e deve apontar para GitHub Releases ou endpoint controlado.
- Token de package/GitHub, se necessário, fica apenas no servidor/secret store do CI e nunca em Blade, Livewire, JavaScript, manifesto ou log.
- O manifesto não define procedimentos de execução.
- Artefatos futuros exigem SHA-256 e, preferencialmente, assinatura verificável.
- O deployment usa allowlist de etapas definida no workflow, revisão/autorização e permissões mínimas.
- Release notes são exibidas como texto escapado; Markdown/HTML remoto não é executado.
- A consulta agendada ocorre uma vez por dia e usa cache, nunca em toda request pública.

## CI/CD futuro

Fluxo recomendado:

```text
admin solicita atualização
→ backend registra solicitação autenticada
→ workflow autorizado recebe versão fixa
→ preflight
→ backup
→ maintenance
→ instalação do lock/artefato verificado
→ migrations
→ caches
→ health check
→ aplicação online
→ registro do resultado
```

O PHP da loja não deve sobrescrever a própria aplicação. Na infraestrutura atual, GitHub Actions pode conectar por SSH e alternar releases imutáveis. Nenhuma credencial ou workflow de deploy é criado nesta etapa.

### Preflight obrigatório

Validar PHP, extensões, versão Laravel, espaço em disco, conexão com banco/cache, permissões, versão atual/alvo e compatibilidade dos módulos. Qualquer incompatibilidade bloqueia antes do backup/maintenance.

### Backup e maintenance

O contrato `BackupProvider` reserva o ponto obrigatório de restauração, mas ainda não possui implementação nem é executado nesta etapa. A implementação futura deve criar, no mínimo, dump consistente do banco e, idealmente, snapshot dos uploads. Só entrar em maintenance depois de backup confirmado. Um bloco de finalização precisa executar `artisan up` mesmo quando uma etapa posterior falhar.

### Migrations e dados

Migrations do core devem ser aditivas, preservar dados e ser compatíveis com rollback quando possível. Updates jamais executam seeds da Rocha/Apex nem sobrescrevem `StoreSetting`, `IntegrationSetting`, catálogo, clientes, pedidos, branding, credenciais ou uploads.

### Health check e histórico

Antes do update real, criar health check interno autenticado para boot, banco, cache e storage, sem expor versões/segredos publicamente. Quando existir execução, criar `system_updates` para versão anterior/nova, ator, timestamps, status e diagnóstico sanitizado.

### Rollback

Releases da aplicação devem ser diretórios imutáveis com symlink `current`, permitindo retorno ao release anterior. Rollback de banco só é automático para migrations comprovadamente reversíveis; migrations destrutivas exigem estratégia explícita/restauração. A UI não prometerá rollback total sem essa garantia.

## Plano de migração incremental

### Fase A — contratos e utilitários

1. Criar o repositório/package e pipeline de testes.
2. Extrair DTOs, contratos, version/release checker e regras de setup sem mover modelos/tabelas.
3. Instalar uma versão `0.x` em ambiente de homologação Rocha e Apex.

### Fase B — pagamentos e entregas

1. Extrair managers/drivers e testes de contrato.
2. Manter `IntegrationSetting` e segredos em cada banco.
3. Validar webhooks, OAuth e checkout nas duas lojas.

### Fase C — domínio e storefront

1. Extrair catálogo, carrinho, pedidos e migrations compartilhadas em lotes pequenos.
2. Mover o tema padrão, criar tokens semânticos e hierarquia de overrides.
3. Eliminar fallbacks e chaves `rocha-*` do core.

### Fase D — Rocha

1. Fixar a versão do package no `composer.lock`.
2. Manter `RochaSportsCatalogSeeder`, JSON, conteúdo e assets no repositório Rocha.
3. Executar suíte, smoke tests e deploy por release imutável.

### Fase E — Apex

1. Substituir cópias compartilhadas pelo mesmo package/versão validada na Rocha.
2. Manter seed, catálogo, branding e overrides Apex no repositório Apex.
3. Comparar contratos e eliminar patches locais no código do core.

### Fase F — publicação central

1. Publicar releases SemVer e manifesto `stable` no GitHub Release.
2. Automatizar PRs de bump do package para cada loja.
3. Implementar solicitação autenticada do painel para o CI/CD somente após backup, health check e rollback estarem operacionais.

## Próxima etapa exata

Criar o repositório privado `ecommerce-core`, extrair primeiro `App\Support\CoreUpdates` e `App\Support\Setup` como package `0.1.0`, configurar autenticação Composer exclusivamente no CI/servidor e instalar essa versão em branches de homologação de Rocha e Apex. A publicação da primeira release real só deve ocorrer depois que ambas as suítes consumirem o package sem cópias locais.
