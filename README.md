# Rocha Sports Ecommerce

Ecommerce mobile-first para a Rocha Sports, loja de suplementos em Campos dos Goytacazes, RJ. A base usa Laravel, Livewire, Alpine.js, Tailwind CSS e Filament, com arquitetura preparada para PWA, app mobile e futura evolucao whitelabel.

## Stack

- Laravel 13
- Livewire 4
- Filament 5
- Tailwind CSS 4
- Vite
- SQLite local por padrao
- MySQL/MariaDB recomendado para Hostinger compartilhada

## Primeira instalacao

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

Home: `http://127.0.0.1:8000`  
Admin Filament: `http://127.0.0.1:8000/admin`

Usuario seed local:

- E-mail: `admin@rochasports.com.br`
- Senha: `password`

Troque essa senha antes de qualquer ambiente publico.

## Verificacao

```bash
php artisan test
npm run build
vendor/bin/pint --dirty
```

## Documentacao de produto

A especificacao funcional e tecnica esta em:

```text
docs/rocha-sports-ecommerce-spec.md
```

## Modulos iniciados

- Catalogo: categorias, marcas, produtos e banners.
- Storefront: home app-like, categorias, produto, carrinho placeholder e checkout placeholder.
- Admin: Filament resources para produtos, categorias, marcas e banners.
- SEO inicial: title, meta description, canonical, Open Graph e JSON-LD em home/produto.
- LGPD inicial: paginas de privacidade e cookies.

## Proximos incrementos

- Carrinho persistente com Livewire.
- Checkout real com entrega/retirada.
- Gateway de pagamento.
- Busca com autocomplete.
- Politicas completas e consentimento de cookies.
- API `/api/v1` para app mobile.
- Preparacao multitenant/whitelabel.
