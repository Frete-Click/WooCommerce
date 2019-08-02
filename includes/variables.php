<?php
$pluginId = __("freteclick");
$pluginName = __("Frete Click");
$pluginDescription = __( 'Cálculo do frete com o serviço da web Frete Click' );
$pluginCountries = array('BR');
$pluginSupports = array(
    'shipping-zones',
    'instance-settings',
    'instance-settings-modal',
);

/*Variáveis globais*/
$url_shipping_quote = "https://api.freteclick.com.br/sales/shipping-quote.json";
$url_origin_company = "https://app.freteclick.com.br/sales/add-quote-origin-company.json";
$url_destination_client = "https://app.freteclick.com.br/sales/add-quote-destination-client.json";

