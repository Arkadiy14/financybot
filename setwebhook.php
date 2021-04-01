<?php
const TOKEN = '1631861651:AAF8cxTry8RoZhB2nIFeLIXO6S5KzesuGJg';
$method = 'setWebhook';
$url = 'https://api.telegram.org/bot'.TOKEN.'/'.$method;
$options = [
	'url' => 'https://financybot.herokuapp.com/'
];

$response = file_get_contents($url.'?'.http_build_query($options));

var_dump($response);
?>