<?php
require_once(dirname(__FILE__).'/../vendor/autoload.php');
$pluginId = "freteclick";
$pluginName = "Frete Click";
$pluginDescription = 'Cálculo do frete com o serviço da web Frete Click';
$pluginCountries = array('BR');
$pluginSupports = array(
    'shipping-zones',
    'instance-settings',
    'instance-settings-modal',
);
$fc_errors = array();