# Frete Click

Cotacao de fretes com multiplas transportadoras, prazos e precos em tempo real direto no checkout da sua loja WooCommerce.

`v1.1.40` · [Instalacao](#instalacao) · [Contribuicao](./AGENTS.md)

## O que o plugin faz

- **Cotacao no checkout**: o cliente informa o CEP de destino e ve as opcoes de frete de dezenas de transportadoras com prazo estimado e valor, lado a lado.

- **Simulador na pagina do produto**: widget de calculo de frete na pagina do produto para o visitante saber o custo antes de comprar (ativa opcional).

- **Contratacao automatica**: ao finalizar o pagamento o frete e contratado automaticamente na Frete Click, sem intervencao manual.

- **Contratacao manual**: na tela do pedido, a acao **"Contratar na Frete Click"** permite contratar ou re-contratar o frete manualmente.

- **Multiplas transportadoras**: todas as transportadoras sao exibidas ao mesmo tempo — o cliente escolhe a preferida.

- **Cotacao simples ou completa**: cotacao simples (transportadoras diretas) ou completa (trasbordos e consolidacoes).

- **Prazo extra e prazo variado**: ajuste fino do prazo exibido ("+ N dias" ou "ate N dias").

- **Restricoes**: escolha transportadoras sem coleta, restrinja por ID e inclua o frete na nota fiscal.

## Requisitos

- [WooCommerce](https://br.wordpress.org/plugins/woocommerce/) ativo.
- Plugin [Brazilian Market on WooCommerce](https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) ativo — fornece os campos de bairro, CPF/CNPJ e demais campos de endereco brasileiros.
- Conta no [Frete Click](https://www.freteclick.com.br) com chave de API.

## Instalacao

1. Crie uma conta no [Frete Click - Cota Facil](https://cotafacil.freteclick.com.br) e obtenha sua chave de API.
2. Instale pela tela de **Plugins** do WordPress, ou envie os arquivos para `/wp-content/plugins/freteclick`.
3. Ative o plugin e acesse **Configuracoes -> Frete Click** para informar a **Chave da API**.
4. Configure a **origem** da loja em **WooCommerce -> Configuracoes -> Zonas de envio -> Frete Click -> Editar**.
5. (Opcional) Habilite o **calculo de frete na pagina do produto** em Configuracoes -> Frete Click.

### Medidas exigidas pelo WooCommerce

- Unidade de peso: **Kg**
- Unidades de medida: **cm**

## Exemplo de uso

O cliente adiciona produtos ao carrinho, informa o CEP no checkout e ve as opcoes de frete com prazo e valor.
Ao concluir o pagamento, o frete e contratado automaticamente na Frete Click.

## Documentacao tecnica

- `MPC.md` — manual de integracao com a API Frete Click (endpoints, payloads, validacoes e gotchas).
- `AGENTS.md` — regras do projeto e fluxo de publicacao.

## Changelog

**1.1.40**
- Correcao: selecao de transportadora nao persistia no carrinho/checkout — cache de taxas na sessao do WooCommerce para manter quote IDs estaveis entre requisicoes AJAX.

**1.1.39**
- Correcao: selecao de transportadora nao persistia no carrinho/checkout — IDs das tarifas agora seguem padrao WooCommerce (method_id:instance_id:quote_id).

**1.1.38**
- Descricoes do plugin aprimoradas no README.txt e README.md com detalhes completos de funcionalidades, requisitos e instalacao.
- Descricao curta do plugin no WordPress (header do freteclick.php) atualizada.

**1.1.37**
- Correcao: contratacao falhava quando o endereco de entrega nao tinha bairro (`district`) ou numero — preenchimento automatico via CEP.
- Log completo de todos os passos da contratacao para facilitar diagnostico.
- Acao "Contratar na Frete Click" na tela do pedido (retry manual).
- SDK com timeouts e erro visivel (antes os erros eram engolidos silenciosamente).

**1.1.36**
- Correcao critica: fatal error no PHP 8+ quando a cotacao falhava (`json_decode()` recebia `array`).
- `get_address()` usava `echo` que corrompia a resposta AJAX do checkout — trocado por `error_log()`.
- Timeouts nas chamadas HTTP (cURL e Guzzle).

**1.1.35**
- Contratacao automatica de frete na Frete Click ao mudar o status do pedido.

## Para mais informações

[Termos e Condicoes - Frete Click](https://www.freteclick.com.br/termos-e-condicoes)

Licenca: GPLv2 ou posterior.