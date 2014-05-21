<?php
require_once dirname(__FILE__) . '/class.returnpath.php';
$api = new ReturnPath('', '');
$api->setProduct('ecm');
$response = $api->get('usage');

//send a request without additional parameters
$response = $api->get('ips');
var_dump($response);
var_dump($api->getLastResponse());

//send a request with additional parameters
$response = $api->get('ips', array('group_length' => 1));
var_dump($response);
var_dump($api->getLastResponse());
?>