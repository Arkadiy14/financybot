<?php
const TOKEN = '1631861651:AAF8cxTry8RoZhB2nIFeLIXO6S5KzesuGJg';
const BASE_URL = "https://api.telegram.org/bot".TOKEN."/";
$link = "dbname=d84pot3p9gld95 host=ec2-54-247-158-179.eu-west-1.compute.amazonaws.com port=5432 user=exkbqcvcpnvduz password=b2210c4a962c2b5d3826407c668631eac689a24dc9560d82138aeb38051a5b78 sslmode=require";


function sendRequest($method, $params = []) {

	if(!empty($params)) {
		$url = BASE_URL.$method.'?'.http_build_query($params);
	}else {
		$url = BASE_URL.$method;
	}

	return json_decode(file_get_contents($url), JSON_OBJECT_AS_ARRAY);
}

$update = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

$chat_id = $update['message']['chat']['id'];
$text = $update['message']['text'];

if($text == '/start') {
	$message = 'Type command /setname.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}elseif($text == '/setname') {
	$message = 'Tell me your name.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	
}elseif($message != '/start' && $text != '/setname' && $text != '/setbudget' && is_string($text)) {
	$message = 'Use command /setbudget to set your amount of money';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	
	$table_name = $text;
	$query = pg_query($link, "CREATE TABLE {$table_name} (whole_amount integer NOT NULL);");
}elseif($text == '/setbudget') {
	$message = 'Enter your amount of money.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}elseif(is_numeric($text)) {
	$query = pg_query($link, "INSERT INTO {$table_name} (whole_amount) VALUES ({$text});");
}
?>