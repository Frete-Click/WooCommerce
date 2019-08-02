<?php
if ($_POST["calc_shipping_postcode"]){
    require_once "functions.php";

    $calc_shipping_postcode = $_POST["calc_shipping_postcode"];

    $cepData = fc_get_cep_data($calc_shipping_postcode);

    echo json_encode($cepData);
}
?>