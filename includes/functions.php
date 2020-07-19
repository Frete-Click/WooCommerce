<?php
function fc_get_cep_data($cep)
{
    /*Obter dados viacep*/
    $data_cep = wp_remote_get('https://viacep.com.br/ws/' . $cep . '/json/', array());
    return json_decode(wp_remote_retrieve_body($data_cep));
}

;
function fc_add_scripts()
{
    $plugin_uri = str_replace('/includes', '', plugin_dir_url(__FILE__));

    //Adicionando estilos
    wp_enqueue_script("freteclick", $plugin_uri . "views/js/Freteclick.js", array('jquery', 'jquery-ui-autocomplete'), "1.0", true);

}

;
function fc_pedido_alterado($order_id, $old_status, $new_status)
{
    $order = new WC_Order($order_id);
    $data = $order->get_data();
    $shipping = $order->get_items('shipping');
    $shipping_data = array();
    $array_data = array();

    foreach ($shipping as $key => $shipping_item) {
        $s_data = $shipping_item->get_data();
        $shipping_data[$key] = $s_data;
    }

    $status_espera = array(
        'pending',
        'processing',
        'on-hold'
    );

    if (in_array($data['status'], $status_espera)) {

    }

    error_log($old_status);
    error_log($new_status);
    error_log(json_encode($shipping_data));
    error_log(json_encode($data));
}

/* Página de Configurações */
function fc_options_register_fields()
{
    add_option("freteclick_quote_type", "0");
    add_option('freteclick_display_product', '0');
    add_option('FC_API_KEY', '');
    add_option('FC_PRAZO_EXTRA', '');
    add_option('FC_PRAZO_VARIADO', '');
    register_setting('freteclick_options_page', 'FC_API_KEY', array(
        "type" => "string",
        "description" => ""
    ));
    register_setting('freteclick_options_page', 'FC_PRAZO_EXTRA', array(
        "type" => "string",
        "description" => ""
    ));
    register_setting('freteclick_options_page', 'FC_PRAZO_VARIADO', array(
        "type" => "string",
        "description" => ""
    ));
    register_setting('freteclick_options_page', 'freteclick_display_product', array(
        "type" => "boolean",
        "description" => "Isso vai adicionar um campo de cálculo de frete nas páginas de produto"
    ));
    register_setting('freteclick_options_page', 'freteclick_quote_type', array(
        "type" => "string",
        "description" => ""
    ));
}

function fc_options_page()
{
    add_options_page("Frete Click", "Frete Click", "manage_options", "freteclick", "fc_options_page_layout");
}

function fc_options_page_layout()
{
    global $pluginDir;

    include $pluginDir . "views/templates/options_page_layout.php";
}

/* Formulário na página de produto */
function rest_get_shipping(WP_REST_Request $request)
{
    $data = $request->get_params();


    $result = fc_calculate_shipping(array(
        "cart_subtotal" => $data["product_price"] * $data["product_quantity"],
        "destination" => array(
            "postcode" => $data["calc_shipping_postcode"]
        ),
        "contents" => array(
            array(
                "product_id" => $data["product_id"],
                "quantity" => $data["product_quantity"],
                "data" => array(
                    "name" => $data["product_name"],
                    "weight" => $data["product_weight"],
                    "height" => $data["product_height"],
                    "width" => $data["product_width"],
                    "length" => $data["product_length"]
                )
            )
        )
    ), array(
        "api_key" => $data["k"],
        "FC_CITY_ORIGIN" => $data["city_orign"],
        "FC_CEP_ORIGIN" => $data["cep_orign"],
        "FC_STREET_ORIGIN" => $data["street_orign"],
        "FC_NUMBER_ORIGIN" => $data["number_orign"],
        "FC_COMPLEMENT_ORIGIN" => $data["complement_orign"],
        "FC_DISTRICT_ORIGIN" => $data["district_orign"],
        "FC_STATE_ORIGIN" => $data["state_orign"],
        "FC_CONTRY_ORIGIN" => $data["contry_orign"],
        "freteclick_quote_type" => $data["freteclick_quote_type"]
    ));

    die(json_encode($result));
}

function fc_display_product_layout()
{
    if (get_option('freteclick_display_product') == 1) {
        global $pluginDir;

        include $pluginDir . "views/templates/display_product_layout.php";
    }
}

function fc_get_mathod()
{
    global $pluginId;
    if (class_exists("WC_Shipping_Zones")) {
        $zones = WC_Shipping_Zones::get_zones();
        foreach ($zones as $zone) {
            $methods = $zone["shipping_methods"];
            foreach ($methods as $method) {
                if ($method->id === $pluginId) {
                    return $method;
                }
            }
        }
    }
    return false;
}

function fc_config($name, $default = array())
{
    global $pluginId;
    $method = fc_get_mathod();
    if ($method) {
        return $method->get_option($name);
    }
    return $default[$name];
}

/* Fazer Cotação */
function fc_calculate_shipping($package = array(), $orign = array())
{
    /**
     * get products
     */
    global $woocommerce;

    session_start();
    $dest = $package['destination'];

    if (!empty($dest['postcode'])) {
        $array_resp = array();
        /*Dados de origem*/
        $array_data = array(
            'quote-type' => isset($orign["freteclick_quote_type"]) ? $orign["freteclick_quote_type"] : get_option("freteclick_quote_type"),
            'city-origin' => fc_config('FC_CITY_ORIGIN', $orign),
            'cep-origin' => fc_config('FC_CEP_ORIGIN', $orign),
            'street-origin' => fc_config('FC_STREET_ORIGIN', $orign),
            'address-number-origin' => fc_config('FC_NUMBER_ORIGIN', $orign),
            'complement-origin' => strlen(fc_config('FC_COMPLEMENT_ORIGIN', $orign)) > 0 ? fc_config('FC_COMPLEMENT_ORIGIN', $orign) : "SEM COMPLEMENTO",
            'district-origin' => fc_config('FC_DISTRICT_ORIGIN', $orign),
            'state-origin' => fc_config('FC_STATE_ORIGIN', $orign),
            'country-origin' => fc_config('FC_CONTRY_ORIGIN', $orign),
            "order" => "total"
        );

        /*Dados do produto*/
        if (class_exists("WC_Product_Factory")) {
            $_pf = new WC_Product_Factory();
        }

        $prod_nomes = array();
        $prodKey = 0;

        $items = isset($woocommerce->cart) ? $woocommerce->cart->get_cart() : [];

        if (count($items) > 0) {
            $array_data['product-total-price'] = 0;
            foreach ($items as $item) {
                $array_data['product-package'][$prodKey]['qtd'] = $item['quantity'];
                $array_data['product-package'][$prodKey]['weight'] = number_format($item['data']->get_weight() / 1000, 10, ',', '');
                $array_data['product-package'][$prodKey]['height'] = number_format($item['data']->get_height() / 100, 10, ',', '');
                $array_data['product-package'][$prodKey]['width'] = number_format($item['data']->get_width() / 100, 10, ',', '');
                $array_data['product-package'][$prodKey]['depth'] = number_format($item['data']->get_length() / 100, 10, ',', '');
                array_push($prod_nomes, $item['data']->get_title());
                $prodKey++;

                $array_data['product-total-price'] += $item['line_total'];
            }
            $array_data['product-total-price'] = number_format($array_data['product-total-price'], 2, ',', '.');
        } else {
            foreach ($package['contents'] as $key => $item) {
                if (class_exists("WC_Product_Factory")) {
                    $product = $_pf->get_product($item['product_id']);
                    $p_data = $product->get_data();
                    if (!$p_data['weight']) {
                        $p_data = $item["data"];
                    }
                } else {
                    $product = $item;
                    $p_data = $item["data"];
                }

                $array_data['product-package'][$prodKey]['qtd'] = $item['quantity'];
                $array_data['product-package'][$prodKey]['weight'] = number_format($p_data['weight'] / 1000, 10, ',', '');
                $array_data['product-package'][$prodKey]['height'] = number_format($p_data['height'] / 100, 10, ',', '');
                $array_data['product-package'][$prodKey]['width'] = number_format($p_data['width'] / 100, 10, ',', '');
                $array_data['product-package'][$prodKey]['depth'] = number_format($p_data['length'] / 100, 10, ',', '');
                array_push($prod_nomes, $p_data['name']);
                $prodKey++;
            }

            $array_data['product-total-price'] = number_format($package['cart_subtotal'], 2, ',', '.');
        }

        $array_data['product-type'] = implode(',', array_values($prod_nomes));

        /*Dados do destino*/

        $data_cep = fc_get_cep_data($dest['postcode']);

        if (!isset($data_cep->erro)) {
            $array_data['city-destination'] = $data_cep->localidade;
            $array_data['street-destination'] = preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $data_cep->logradouro) ?: 'Rua não encontrada';
            $array_data['district-destination'] = $data_cep->bairro ?: 'Bairro não encontrado';
            $array_data['state-destination'] = $data_cep->uf;
            $array_data['country-destination'] = 'Brasil';
            $array_data['complement-destination'] = strlen($data_cep->complemento) ? $data_cep->complemento : "SEM COMPLEMENTO";
        } else {
            $array_data['city-destination'] = $dest['city'];
            $array_data['street-destination'] = preg_replace('/[^A-Z a-z]/', '', preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $dest['address']));
            $array_data['district-destination'] = $dest['address_2'];
            $array_data['state-destination'] = $dest['state'];
            $array_data['country-destination'] = $dest['country'];
            $array_data['complement-destination'] = "SEM COMPLEMENTO";
        }
        $array_data['cep-destination'] = $dest['postcode'];
        $dest_number = preg_replace('/[^0-9]/', '', $dest['address']);
        $array_data['address-number-destination'] = strlen($dest_number) > 0 ? $dest_number : 1;

        /*Fazer cotação*/
        $quote_key = md5(json_encode($array_data));
        if (isset($_SESSION[$quote_key])) {
            $array_resp = json_decode($_SESSION[$quote_key]);
        } else {
            $array_resp = fc_get_quotes($array_data, $orign);
            if ($array_resp->response->data != false) {
                $_SESSION[$quote_key] = json_encode($array_resp);
            }
        }

        return $array_resp;
    }
}

function fc_get_quotes($array_data, $orign = array())
{

    global $url_shipping_quote;
    $array_resp = array();
    try {
        $array_data['api-key'] = !empty($orign) ? $orign["api_key"] : get_option('FC_API_KEY');

        $args = array(
            'method' => 'POST',
            'timeout' => 600,
            'headers' => array(
                'Content-type: application/x-www-form-urlencoded'
            ),
            'sslverify' => false,
            'body' => $array_data
        );

        $resp = wp_remote_post($url_shipping_quote, $args);

        $array_resp = orderByPrice(filterJson(wp_remote_retrieve_body($resp)));
    } catch (Exception $ex) {
        $array_resp = array(
            'response' => array('success' => false, 'error' => $ex->getMessage())
        );
    }

    return $array_resp;
}

function orderByPrice($arrJson)
{
    $quotes = (array)$arrJson->response->data->quote;
    usort($quotes, function ($a, $b) {
        return $a->total > $b->total;
    });
    $arrJson->response->data->quote = $quotes;
    return $arrJson;
}

function filterJson($json)
{
    $arrJson = json_decode($json);
    if (!$arrJson) {
        addError('Erro ao recuperar dados');
    }
    if ($arrJson->response->success === false) {
        if ($arrJson->response->error) {
            foreach ($arrJson->response->error as $error) {
                addError($error->message);
            }
        }
        addError('Erro ao recuperar dados');
    }
    return getErrors() ?: $arrJson;
}

function addError($error)
{
    global $fc_errors;
    array_push($fc_errors, array(
        'code' => md5($error),
        'message' => $error
    ));
    return getErrors();
}

function getErrors()
{
    global $fc_errors;
    return $fc_errors ? array(
        'response' => array(
            'data' => 'false',
            'count' => 0,
            'success' => false,
            'error' => $fc_errors
        )
    ) : false;
}

/* Frete Click Mensagens */
function fc_wc_missing_notice()
{
    printf("<div class='notice notice-warning'><p>O WooCommerce não está intalado, para usar o Frete Click é necessário <a href='https://br.wordpress.org/plugins/woocommerce/' target='blanck'>instalar o WooCommerce</a>.</p></div>");
}

function fc_missing_apikey()
{
    printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe sua Chave de API</p></div>");
}

function fc_is_disabled()
{
    printf("<div class='notice notice-warning is-dismissible'><p>O Frete Click está desabilitado. Ative o Frete Click para voltar a usa-lo.</p></div>");
}

function fc_missing_address()
{
    printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe o endereço completo para a coleta dos produtos.</p></div>");
}
