<?php
header("Content-type: text/html; charset=utf-8");
if ($_POST["calc_shipping_postcode"]){
    require_once "variables.php";
    require_once "functions.php";

    $api_key = $_GET["k"];
    $cep_orign = $_POST["cep_orign"];
    $street_orign = utf8_encode($_POST["street_orign"]);
    $number_orign = $_POST["number_orign"];
    $complement_orign = utf8_encode($_POST["complement_orign"]);
    $district_orign = utf8_encode($_POST["district_orign"]);
    $city_orign = utf8_encode($_POST["city_orign"]);
    $state_orign = $_POST["state_orign"];
    $contry_orign = utf8_encode($_POST["contry_orign"]);

    $product_id = $_POST["product_id"];
    $product_name = utf8_encode($_POST["product_name"]);
    $product_price = $_POST["product_price"];
    $product_weight = $_POST["product_weight"];
    $product_height = $_POST["product_height"];
    $product_width = $_POST["product_width"];
    $product_length = $_POST["product_length"];
    $product_quantity = $_POST["product_quantity"];
    
    $calc_shipping_postcode = $_POST["calc_shipping_postcode"];
    
    $result = fc_calculate_shipping(array(
        "cart_subtotal" => $product_price * $product_quantity,
        "destination" => array(
            "postcode" => $calc_shipping_postcode
        ),
        "contents" => array(
            array(
                "product_id" => $product_id,
                "quantity" => $product_quantity,
                "data" => array(
                    "name" => $product_name,
                    "weight" => $product_weight,
                    "height" => $product_height,
                    "width" => $product_width,
                    "length" => $product_length
                )
            )
        )
    ), array(
        "api_key" => $api_key,
        "FC_CITY_ORIGIN" => $city_orign,
        "FC_CEP_ORIGIN" => $cep_orign,
        "FC_STREET_ORIGIN" => $street_orign,
        "FC_NUMBER_ORIGIN" => $number_orign,
        "FC_COMPLEMENT_ORIGIN" => $complement_orign,
        "FC_DISTRICT_ORIGIN" => $district_orign,
        "FC_STATE_ORIGIN" => $state_orign,
        "FC_CONTRY_ORIGIN" => $contry_orign
    ));
    
    echo json_encode($result);
}
?>