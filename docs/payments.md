# Pagamentos

O checkout depende de `PaymentGateway`; drivers atuais: `MercadoPagoGateway` e `AsaasGateway`. Uma loja mantém apenas um gateway principal ativo. Capacidades determinam os métodos exibidos. PayPal não foi implementado: um futuro driver poderá implementar o mesmo contrato e ser registrado no `PaymentGatewayManager`.

## Mercado Pago

Cadastre Public Key, Access Token, ambiente e o segredo de webhook. PIX e cartão usam a Orders API. Cartão é tokenizado no navegador pelo Card Payment Brick/MercadoPago.js; PAN, CVV e validade não chegam ao Laravel. Toda tentativa usa `X-Idempotency-Key` e uma linha única em `payments`.

Webhook: `/webhooks/payments/mercado-pago`. A assinatura `x-signature` é validada por HMAC com `x-request-id`, timestamp e ID do recurso. Sem segredo configurado, notificações são rejeitadas fora de testes.

O fluxo Checkout Pro anterior permanece somente como compatibilidade para instalações antigas que ainda usam as variáveis `MERCADO_PAGO_*`.

## Asaas

Cadastre API key, token próprio do webhook e ambiente. PIX retorna QR Code/copia e cola. Cartão usa a `invoiceUrl` hospedada do Asaas para reduzir exposição PCI; o backend não coleta nem persiste dados completos do cartão. Boleto permanece desabilitado. Retentativas são serializadas por tentativa e reconciliadas pela `externalReference` antes de criar outra cobrança.

Webhook: `/webhooks/payments/asaas`. O header `asaas-access-token` deve coincidir com o token criptografado da integração.

## Estados e idempotência

Estados externos são traduzidos para `pending`, `processing`, `paid`, `failed`, `cancelled` e `refunded`. Reenvios são deduplicados em `webhook_events`, e estoque/notificações só avançam quando o estado interno realmente muda.

Use **Testar conexão** no Filament; a operação consulta a conta e não cria cobrança.
