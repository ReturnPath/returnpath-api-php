<?php
require_once dirname(__FILE__) . '/class.returnpath.php';
$api = new ReturnPath('', '');
$api->setProduct('ecm');
$response = $api->get('usage');
var_dump($response);
var_dump($api->getLastResponse());
?>