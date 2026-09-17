# Entregas

O checkout depende de `ShippingProvider`. Os drivers atuais são taxa fixa e Melhor Envio. Cada cotação é normalizada como `ShippingQuote` e o pedido recebe um snapshot imutável do serviço, preço, transportadora e prazo selecionados.

## Taxa fixa

Configure nome, valor em centavos, prazo mínimo/máximo e limiar opcional de frete grátis. Esse driver não declara capacidades de etiqueta ou rastreamento.

## Produtos físicos

Produtos com `requires_shipping` precisam de peso em kg e largura, altura e comprimento em cm, todos positivos. O painel exige esses campos e o driver interrompe a cotação com mensagem amigável se dados inválidos escaparem da validação.

## Melhor Envio

1. Crie um aplicativo no sandbox ou produção.
2. Cadastre a callback exibida na configuração: `/integrations/melhor-envio/callback`.
3. Cadastre o webhook `/webhooks/shipping/melhor-envio`.
4. Informe Client ID/Client Secret e use **Conectar Melhor Envio**.

O OAuth armazena access/refresh tokens criptografados. A renovação acontece cinco minutos antes da expiração sob lock, evitando refreshes concorrentes. Falhas marcam a integração como `reconnect_required` sem registrar tokens.

A cotação usa `/api/v2/me/shipment/calculate`, cache de cinco minutos e prefere `custom_price`/`custom_delivery_time`. O snapshot também preserva os pacotes calculados, que são reutilizados na criação da etiqueta. Serviços incompatíveis com múltiplos pacotes em uma única etiqueta são descartados quando a cotação retorna mais de um pacote.

O checkout solicita CPF/CNPJ do destinatário quando o Melhor Envio está ativo. Configure CPF/CNPJ e, para pessoa jurídica, inscrição estadual da loja. Para remessa comercial, preencha no pedido a chave de 44 dígitos da NF-e antes de criar o envio; sem ela, o payload não declara a remessa como comercial. Após o pagamento aprovado, o admin pode criar o envio, comprar e gerar a etiqueta e abrir sua URL. A compra automática começa desativada.

O rastreamento usa webhook HMAC-SHA256 em Base64 (`X-ME-Signature`) e consulta manual como fallback. Eventos são idempotentes. `posted`/trânsito avançam o pedido para enviado; `delivered` avança para entregue e reutiliza as notificações existentes.
