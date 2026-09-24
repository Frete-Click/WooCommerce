== Frete Click ==
Donate link: https://www.freteclick.com.br
Tags: woocommerce, frete, freteclick, cotacao-frete, shipping
Requires at least: 3.5
Tested up to: 6.9.1
Version: 1.1.42
Stable tag: 1.1.42
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cotacao de fretes com multiplas transportadoras, prazos e precos em tempo real direto no checkout da sua loja WooCommerce.

== Descricao ==

O **Frete Click** e um plugin para WooCommerce que conecta sua loja a dezenas de transportadoras nacionais, exibindo cotações com prazo e valor em tempo real durante o checkout do cliente.

**O que o plugin faz:**

* **Cotacao no checkout**: O cliente informa o CEP de destino e ve as opcoes de frete com prazo estimado e valor ao lado das transportadoras.

* **Simulador na pagina do produto**: Exibe um widget de calculo de frete diretamente na pagina do produto, permitindo que o visitante veja o custo do frete antes de adicionar ao carrinho (ativa opicional).

* **Contratacao automatica**: Ao finalizar o pagamento, o plugin contrata o frete automaticamente na Frete Click sem intervenção manual.

* **Contratacao manual**: Na tela do pedido, utilize a acao "Contratar na Frete Click" para contratar ou recadastrar o frete manualmente.

* **Multiplas transportadoras**: Diversas transportadoras sao exibidas ao mesmo tempo — o cliente escolhe a que preferir.

* **Cotacao simples ou completa**: Escolha entre cotacao simples (transportadoras diretas) ou completa (inclui trasbordos e consolidacoes).

**Configuracoes disponiveis:**

* Endereço de origem completo (CEP, rua, numero, bairro, cidade, estado).
* Cotação simples ou completa.
* Prazo extra (dias uteis adicionais ao prazo calculado).
* Prazo variado (exibe "ate X dias" no prazo).
* Exibir ou ocultar transportadoras sem coleta.
* Restricao de transportadoras (por ID).
* Incluir valor do frete na nota fiscal.
* Exibir calculo de frete na pagina do produto.

**Requisitos:**

* [WooCommerce](https://br.wordpress.org/plugins/woocommerce/) instalado e ativo.
* Plugin [Brazilian Market on WooCommerce](https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) instalado e ativo (necessario para campos de endereco: bairro, CPF/CNPJ).
* Conta ativa no [Frete Click](https://www.freteclick.com.br) com chave de API.

== Instalacao ==

### INSTALACAO E CONFIGURACAO ###

1. Crie uma conta em nosso painel: [Frete Click - Cota Facil](https://cotafacil.freteclick.com.br).
2. Obtenha sua chave de API no painel do Frete Click.
3. Instale o plugin pela tela de **Plugins** no WordPress, ou envie os arquivos para `/wp-content/plugins/freteclick`.
4. Ative o plugin na tela de Plugins.
5. Acesse **Configuracoes -> Frete Click** e preencha a **Chave da API**.
6. Configure a **origem** da loja (CEP, rua, numero, bairro, cidade, estado) dentro de **WooCommerce -> Configuracoes -> Zonas de envio -> Frete Click -> Editar**.
7. (Opcional) Ative o calculo de frete na pagina do produto em **Configuracoes -> Frete Click**.

## Configuracoes Necessarias no WooCommerce

**WooCommerce -> Configuracoes -> Envio -> Unidades de medida:**

* Unidade de peso: **Kg**
* Unidades de medida: **cm**

## Exemplo de uso

O cliente adiciona produtos ao carrinho, informa o CEP no checkout e ve as opcoes de frete com prazo e valor.
Ao concluir o pagamento, o frete e contratado automaticamente na Frete Click.

== Changelog ==

= 1.1.42 =
* Eliminados os avisos "PHP Deprecated: Implicitly marking parameter $x as nullable" emitidos pelo Guzzle empacotado (guzzlehttp/promises 1.x) em toda requisicao em ambientes PHP 8.4/8.5. O vendor foi atualizado para Guzzle 7.15 e o SDK para v1.2.34 (linha 7.x do Guzzle, timeouts e PSR-7). Sem mudanca de comportamento no checkout.

= 1.1.41 =
* Correcao critica: v1.1.40 causava fatal error (Call to undefined method WC_Session_Handler::get_data()) em qualquer pagina com itens no carrinho. O metodo get_data() nao existe na API de sessao do WooCommerce. O cache de tarifas foi corrigido para usar apenas metodos publicos da sessao (get/set/__unset) e limpar o cache apenas quando itens sao adicionados/removidos/restaurados no carrinho, mantendo os quote IDs estaveis entre requisições AJAX do checkout.
* Corrigidos os hooks de limpeza de cache que disparavam em toda requisicao e apagavam o cache imediatamente apos gravado.

= 1.1.39 =
* Correcao: selecao de transportadora nao persistia no carrinho/checkout — IDs das tarifas agora seguem padrao WooCommerce (method_id:instance_id:quote_id).

= 1.1.38 =
* Descricoes do plugin aprimoradas no README.txt e README.md com detalhes completos de funcionalidades, requisitos e instalacao.
* Descricao curta do plugin no WordPress (header do freteclick.php) atualizada.

= 1.1.37 =
* Correcao: contratacao falhava quando endereco de entrega nao tinha bairro (district) ou numero — agora o plugin completa automaticamente via CEP.
* Melhoria: log completo de todos os passos da contratacao para facilitar diagnostico.
* Melhoria: acao "Contratar na Frete Click" na tela do pedido para retry manual.
* Melhoria: SDK com timeouts e tratamento de erro visivel (antes os erros eram engolidos silenciosamente).
* Documentacao: MPC.md com contrato completo da API.

= 1.1.36 =
* Correcao critica: fatal error no PHP 8+ quando a API de cotacao falhava — json_decode() recebia array em vez de string.
* Correcao: get_address() usava echo que corrompia a resposta AJAX do checkout — substituido por error_log().
* Melhoria: timeouts em chamadas HTTP (cURL e Guzzle).

= 1.1.35 =
* Contratacao automatica de frete na Frete Click ao mudar status do pedido.