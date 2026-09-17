# Instalação white label

> Uma loja já instalada permanece com `/install` bloqueado. Alterações posteriores devem ser feitas por um administrador em **Configurações → Reconfigurar loja**; o assistente não recria o administrador nem altera a data original de instalação.

## Pré-requisitos

Configure o banco, `APP_URL`, `APP_KEY`, e-mail e um `INSTALLER_TOKEN` forte e temporário. Execute as migrations e a build dos assets. Nenhum dado comercial precisa ser colocado no `.env`.

Abra `https://seu-dominio/install?token=SEU_TOKEN`. O token válido é guardado apenas na sessão e nunca é renderizado. O assistente possui seis etapas: administrador, identidade/aparência, empresa/endereço, pagamento, entrega e revisão.

Ao concluir, `store_settings.installed_at` e `installation_version` são gravados. A rota passa a responder 404. Para uma reinstalação é necessário um procedimento administrativo/CLI deliberado; não remova `installed_at` em uma aplicação em uso.

## Branding

Logos e ícones ficam no disco `public`. Cores são validadas como hexadecimal e aplicadas pelos tokens CSS `--brand-primary`, `--brand-primary-dark`, `--brand-secondary`, `--brand-accent`, `--store-background` e `--store-font`. A fonte deve pertencer à allowlist em `StoreSetting::FONTS`; URLs arbitrárias não são aceitas.

Após instalar, altere Loja, Aparência e Empresa no grupo **Configurações** do Filament.

## Segurança

Credenciais vivem em `integration_settings.credentials` usando o cast `encrypted:array` do Laravel e `APP_KEY`. O atributo é oculto na serialização e inputs de segredo nunca recebem o valor persistido. Rotacione um segredo preenchendo somente o novo valor no Filament.
