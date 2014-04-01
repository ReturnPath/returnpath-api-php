<?php
require_once dirname(__FILE__) . '/class.returnpath.php';
$api = new ReturnPath('', '');
$response = $api->get('/ecm/usage');
var_dump($response);
var_dump($api->getLastResponse());
?>