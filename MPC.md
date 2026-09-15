# MPC — Manual de Integração Frete Click API

Documento de referência da API Frete Click para o plugin WooCommerce `freteclick`.
Baseado na análise do código-fonte em `api/` (Symfony + ApiPlatform). Não altere este documento sem atualizar o conhecimento do código-fonte.

---

## 1. Visão geral do fluxo

```
1. Cotaçao (quote)              → POST /quotes                    → retorna order.id + quotes[]
2. Contratação (choose-quote)   → PUT /purchasing/orders/{id}/choose-quote → víncula cotação ao pedido
3. Acompanhamento               → GET /purchasing/orders/{id}/detail/status
```

No WordPress:
- **Cotaçao**: `fc_calculate_shipping()` → `fc_get_quotes()` (via `SDK\Service\FreteClick::quote()`).
- **Contratação**: `fc_pedido_alterado()` no hook `woocommerce_order_status_changed` (ou ação manual "Contratar na Frete Click").

---

## 2. Autenticação

Todas as chamadas utilizam header:

```
api-token: <API_KEY>
```

- A chave é configurada no WooCommerce em `Configurações → Frete Click` (opção `FC_API_KEY`).
- O endpoint `GET /geo_places` aceita acesso **anônimo** (`IS_AUTHENTICATED_ANONYMOUSLY`); os demais exigem `ROLE_CLIENT`.

---

## 3. Endpoints usados pelo plugin

| Método | Endpoint | Uso |
|--------|----------|-----|
| GET | `/geo_places?input={cep}` | Busca endereço por CEP (viacep/gmaps). Retorna `district`, `street`, `city`, `state`, `postal_code`. |
| GET | `/people/me` | Identifica a loja (remetente). Retorna `data.peopleId` e `data.companyId`. |
| GET | `/email/find?email={email}` | Busca cliente por e-mail. Retorna `data.people_id`. |
| POST | `/people/customer` | Cria cliente destinatário. Corpo: `{name, alias, type, document, email, address}`. Retorna `data.peopleId`. |
| POST | `/quotes` | Cria a cotação (pede cotações de transportadoras). Retorna `order.id` + `quotes[]`. |
| GET | `/quotations/{id}` | Total da cotação (`data.total`). |
| PUT | `/purchasing/orders/{id}/choose-quote` | Contrata: escolhe a cotação para o pedido. Endpoint principal da contratação. |
| GET | `/purchasing/orders/{id}/detail/status` | Status do pedido de frete. |
| GET | `/purchasing/orders/{id}/detail/summary` | Resumo do pedido de frete. |

---

## 4. Endereços — regra CRÍTICA (`AddressService::isFullAddress`)

No `choose-quote`, endereços de **retrieve** e **delivery** enviados como array **devem ter todos** os campos abaixo não-vazios, nesta ordem de validação:

```php
// Order of checks (AddressService.php:58-84)
1. country     → não vazio
2. state       → não vazio
3. city        → não vazio
4. district    → não vazio        ← causa comum de erro: bairro "" 
5. street      → não vazio
6. postal_code → regex ^[0-9]{8}$  (8 dígitos exatos)
7. number      → is_numeric($number)  ← "SN"/"s/n"/"" falham; usar "0"
```

Exemplo de erro real observado:
> `Parameter "address district" is missing` — bairro vazio no endereço de entrega.

### Falhas típicas do plugin a evitar

- `district` vazio: pedido não captura bairro (`_shipping_neighborhood`/`_billing_neighborhood` vazios).
  → **Fallback**: consultar `GET /geo_places?input={CEP}` e preencher `district`/`street`/`city`/`state`.
- `number` vazio/não numérico: → usar `"0"`.

### Regras de vínculo geográfico (`ChooseQuoteAction::updateOrder`)

- A cidade/estado do endereço de **origem** deve **igualar** a cidade/estado de origem do pedido de cotação.
- A cidade/estado do endereço de **destino** deve **igualar** a cidade/estado de destino da cotação.
  Erros: `Origin city can not be different from order origin city`, `Destination state can not be different from order destination state`.

---

## 5. Payload do `choose-quote`

```jsonc
{
  "quote": "32913599",            // id da cotação escolhida (de quotes[])
  "price": 59.41,                 // preço (é sobrescrito pelo total da cotação no servidor)
  "payer": "2",                   // peopleId do pagador (loja)
  "retrieve": {                   // remetente (loja)
    "id": "2",                    // peopleId da loja
    "address": { "...todos os 7 campos completos..." },
    "contact": "3227"             // peopleId de contato da loja
  },
  "delivery": {                   // destinatário (cliente)
    "id": "66625",                // peopleId do cliente
    "address": { "...todos os 7 campos completos..." },
    "contact": "66625"
  }
}
```

### Regras adicionais (`ChooseQuoteAction::paramsAreValid`)

- `retrieve.address` como array → exige endereço completo.
- `retrieve.contact` deve **existir** como chave (pode ser vazio).
- `payer` pode ser numérico (peopleId) ou array de contato com endereço completo.
- O pedido só pode ser atualizado se estiver em `quote`, `analysis` ou `waiting client invoice tax` (`PurchasingOrder::justOpened()`). Caso contrário:
  > `This order was already updated`

---

## 6. Formato de resposta

Sucesso: HTTP 200 com `{"@id": <order_id>}` (e `delivery_people`/`retrieve_people` nos controllers parciais).

Erro: HTTP 200 com `{"response":{"data":null,"error":"<mensagem>","success":false}}`.

> O SDK faz `finishCheckout` e lança `Exception` com `status` + `body` quando não-200 ou `success=false`, para que o plugin registre o erro real em vez de engolir.

---

## 7. Códigos de status do pedido de frete

`PurchasingOrder` nasce em `quote`. Transições principais negociadas pelo fluxo:

| Status | Observação |
|--------|------------|
| `quote` | Aguardando escolha de transportadora (justOpened). |
| `analysis` | Cotação escolhida, em análise (justOpened). |
| `waiting client invoice tax` | Aguardando guia/NF (justOpened). |
| `automatic analysis` | Contrato com pedido de coleta iniciado. |
| demais | Pedido já atualizado/contratado — `choose-quote` recusa. |

---

## 8. Contratos parciais

Além do `choose-quote` completo, existem endpoints por parte (`PUT /purchasing/orders/choose/{id}/{payment|retrieve|delivery|payer}`) e `PUT /purchasing/orders/{id}/update-status`. Atualmente o plugin usa apenas o `choose-quote` completo.

---

## 9. Cuidados com PHP 8 e rede

- `json_decode()` NUNCA deve receber `array` — validar `is_string()` antes (tipagem estrita em PHP 8 lança `TypeError`).
- `catch (\Throwable)` para cobrir `TypeError`/`Error`, não só `Exception`.
- Cliente HTTP (Guzzle/cURL) deve usar `http_errors => false`, `connect_timeout => 5`, `timeout => 10`.
- NUNCA `echo` em respostas AJAX do WooCommerce (`update_order_review` é JSON). Usar `error_log()` / `fc_log()`.

---

## 10. Checklist para alterações na integração

1. Alterou campos de endereço? Confira `AddressService::isFullAddress` e o vínculo cidade/estado.
2. Alterou o fluxo de contratação? Mantenha `justOpened()` respeitado (estado `quote`/`analysis`/`waiting client invoice tax`).
3. Adicionou chamada HTTP nova? Reutilize padrões com `http_errors => false` + timeouts.
4. Retorno de API pode ser array? Proteja com `is_string()` antes de `json_decode`.
5. Faça log (`fc_log()`) em todos os ramos de `fc_pedido_alterado` — evita regressão "silenciosa".
6. Atualize a versão em `freteclick.php`, `README.txt`, `README.md` e no `wp_enqueue_style`.