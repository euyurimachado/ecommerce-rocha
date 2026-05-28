# Rocha Sports Ecommerce

Ecommerce mobile-first para a Rocha Sports, loja de suplementos em Campos dos Goytacazes, RJ. A base usa Laravel, Livewire, Alpine.js, Tailwind CSS e Filament, com arquitetura preparada para PWA, app mobile e futura evolução whitelabel.

## Stack

- Laravel 13
- Livewire 4
- Filament 5
- Tailwind CSS 4
- Vite
- SQLite local por padrão
- MySQL/MariaDB recomendado para Hostinger compartilhada

## Primeira instalação

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

No Windows PowerShell, use `npm.cmd` se `npm` estiver bloqueado pela policy:

```bash
npm.cmd install
npm.cmd run build
```

## Rodar localmente

```bash
php artisan serve
npm run dev
```

Loja: `http://127.0.0.1:8000`  
Admin Filament: `http://127.0.0.1:8000/admin`

Usuário seed local:

- E-mail: `admin@rochasports.com.br`
- Senha: `password`

Troque essa senha antes de qualquer ambiente público.

## Verificação

```bash
php artisan test
npm run build
vendor/bin/pint --dirty
```

## Documentação de produto

A especificação funcional e técnica está em:

```text
docs/rocha-sports-ecommerce-spec.md
```

## Módulos iniciados

- Catálogo: categorias, marcas, produtos e banners.
- Loja: home app-like, categorias, produto, carrinho placeholder e finalização de compra placeholder.
- Admin: Filament resources para produtos, categorias, marcas e banners.
- SEO inicial: title, meta description, canonical, Open Graph e JSON-LD em home/produto.
- LGPD inicial: páginas de privacidade e cookies.

## Próximos incrementos

- Carrinho persistente com Livewire.
- Finalização de compra real com entrega/retirada.
- Gateway de pagamento.
- Busca com autocomplete.
- Políticas completas e consentimento de cookies.
- API `/api/v1` para app mobile.
- Preparação multitenant/whitelabel.
