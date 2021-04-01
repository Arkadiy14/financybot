<?php
const TOKEN = '1631861651:AAF8cxTry8RoZhB2nIFeLIXO6S5KzesuGJg';
const BASE_URL = "https://api.telegram.org/bot".TOKEN."/";

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
	$message = 'Use the command /setbudget to enter your amount of money.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}elseif($text == '/setbudget') {
	$message = 'Enter your amount of money.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}elseif(is_numeric($text)) {
	$message = $text;
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}
?>