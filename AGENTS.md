# AGENTS.md — Frete Click Plugin for WooCommerce

## Visão geral

Plugin de cotação de fretes para WooCommerce que consome a API da Frete Click (`https://api.freteclick.com.br/`).
A cotação é feita por uma rota REST (`/wp-json/freteclick/get_shipping`) e pela classe de cálculo de frete do WooCommerce.

## Estrutura

- `freteclick.php` — arquivo principal; contém a versão do plugin no header.
- `includes/class-wc-freteclick.php` — método de entrega do WooCommerce (`calculate_shipping`).
- `includes/class-wc-freteclick-shipping-simulator.php` — lógica de cotação, CEP, configurações e contratação de pedidos.
- `includes/class-wc-freteclick-helper.php` — helpers.
- `vendor/freteclick/sdk/` — SDK (commitado no repositório; alterar junto quando necessário).
- `views/templates/`, `views/css/`, `views/img/` — templates e assets.
- `.github/workflows/wordpress-svn-deploy.yml` — deploy automático no WordPress.org ao criar tag.

## Requisitos

- PHP 7.2+ (atenção especial ao PHP 8.x: arrays em parâmetros de funções nativas geram `TypeError`).
- WooCommerce.
- Plugin "Brazilian Market on WooCommerce" para campos de endereço.

## Regras críticas (PHP 8)

- NUNCA passar array para `json_decode()` — sempre validar com `is_string()` antes:
  ```php
  $resposta = self::fc_get_quotes($request);
  if (!is_string($resposta)) {
      error_log('Frete Click: falha na cotação: ' . wp_json_encode($resposta));
      return null;
  }
  return json_decode($resposta, false);
  ```
- Usar `catch (\Throwable $ex)` em vez de `catch (Exception $ex)` para cobrir `TypeError`/`Error`.
- NUNCA usar `echo` dentro de fluxos AJAX do WooCommerce (`update_order_review` responde JSON). Use `error_log()`.
- Sempre definir timeouts em chamadas externas:
  - cURL: `CURLOPT_CONNECTTIMEOUT => 5`, `CURLOPT_TIMEOUT => 10`.
  - Guzzle: `'connect_timeout' => 5, 'timeout' => 10, 'http_errors' => false`.
- Remover `curl_close()`: obsoleto desde o PHP 8.0 e removed em versões futuras; o PHP gerencia o recurso.
- Validar resposta da API antes de acessar propriedades aninhadas: `empty($resposta->response->data->order->quotes)`.

## Endereço completo no choose-quote (obrigatório)

A API exige `country`, `state`, `city`, `district`, `street`, `postal_code` (8 dígitos) e `number` (numérico) preenchidos
nos endereços `retrieve` e `delivery` da contratação (`AddressService::isFullAddress`). Bairro/rua vazios são um erro comum
(`Parameter "address district" is missing`). Usar `fc_complete_address()` para completar via `geo_places` (CEP)
e `number` ausente vira `"0"`. Cidade/estado dos endereços devem coincidir com origem/destino da cotação.
Consulte `MPC.md` para o contrato completo da API.

## Falhas de API não podem derrubar o checkout

O padrão correto é: falha na cotação do Frete Click apenas exclui as tarifas do Frete Click,
mantendo as demais transportadoras visíveis no checkout. Nunca retornar `true` de uma função de cotação.

## Versionamento

Ao publicar uma nova versão, atualizar TODOS estes locais (sempre a mesma versão):

- `freteclick.php` → `Version: x.y.z`
- `README.txt` → `Version:` e `Stable tag:`
- `README.md` → `Version:` e `Stable tag:`
- Versão do CSS no `wp_enqueue_style(...)` de `enqueue_scripts()` (`'x.y.z'`).

## Publicação

1. Comitar as mudanças em branch `master`.
2. `git push origin master`.
3. Criar tag com prefixo `v`: `git tag vX.Y.Z`.
4. `git push origin vX.Y.Z` — o workflow `.github/workflows/wordpress-svn-deploy.yml` publica no WordPress.org automaticamente.

## Logs

Existe uma função `fc_log($label, $data)` que grava em `logs/freteclick.log` (o diretório `logs/` é ignorado pelo git).
Preferir `error_log()`/`fc_log()` para diagnósticos em vez de `echo`.

## Teste rápido

Reprodução de falha da API: forçar `throw new Exception('teste');` dentro do `try` de `fc_get_quotes()` e
informar um CEP válido no checkout. A cotação do Frete Click deve ficar vazia, o checkout deve continuar OK
e a mensagem deve ir para o log sem `TypeError`.