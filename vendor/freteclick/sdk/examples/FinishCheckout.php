<?php
require_once ('../vendor/autoload.php');

// data from shop

$pedido = [
  'shipping_zone'      => 'SP',
  'shipping_city'      => 'São Paulo',
  'shipping_postcode'  => '01010000',
  'shipping_district'  => 'Centro',
  'shipping_street'    => 'Rua São Bento',
  'shipping_numero'    => '5500',
  'customer_name'      => 'Nome cliente 1',
  'customer_alias'     => 'Sobrenome cliente 1',
  'customer_email'     => 'email@yahoo.com',
  'customer_document'  => '07548662222',
  'cotacao_quote_id'   => '1302940', // rate selected
  'cotacao_id'         => '235848',  // order number
  'cotacao_price'      => 5000,
];

$API    = new SDK\Core\Client\API('TOKEN');
$People = new SDK\Client\People($API);
$Order  = new SDK\Client\Order($API);

// get customer

$customerId      = $People->getIdByEmail($pedido['customer_email']);
$deliveryAddress = [
    'country'     => 'Brasil',
    'state'       => $pedido['shipping_zone'],
    'city'        => $pedido['shipping_city'],
    'district'    => $pedido['shipping_district'],
    'street'      => $pedido['shipping_street'],
    'number'      => $pedido['shipping_numero'],
    'postal_code' => $pedido['shipping_postcode']
];

if ($customerId === null) {
    $customerId = $People->createCustomer([
        'name'     => $pedido['customer_name'],
        'alias'    => $pedido['customer_alias'],
        'type'     => 'F',
        'email'    => $pedido['customer_email'],
        'document' => $pedido['customer_document'],
        'address'  => $deliveryAddress
    ]);

    if ($customerId === null) {
        echo 'Cliente nao foi criado' . PHP_EOL;
        exit;
    }
}

// from

$shopOwner    = $People->getMe();
$retrieveFrom = [
    'id'      => $shopOwner->companyId,
    'address' => [
        'country'     => 'Brasil',
        'state'       => 'PR',
        'city'        => 'Umuarama',
        'district'    => 'Zona Iii',
        'street'      => 'Rua Mandaguari',
        'number'      => '1645',
        'postal_code' => '87502110'
    ],
    'contact' => $shopOwner->peopleId,
];

// to

$deliveryTo = [
    'id'      => $customerId,
    'address' => $deliveryAddress,
    'contact' => $customerId,
];

// mount payload

$payload   = [
    'quote'    => $pedido['cotacao_quote_id'],
    'price'    => $pedido['cotacao_price'],
    'payer'    => $shopOwner->companyId,
    'retrieve' => $retrieveFrom,
    'delivery' => $deliveryTo,
];

try {
  if ($Order->finishCheckout($pedido['cotacao_id'], $payload) === true) {
    echo 'OK' . PHP_EOL;
  }
} catch (\Exception $e) {
  echo $e->getMessage() . PHP_EOL;
}
