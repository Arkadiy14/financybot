<?php
const TOKEN = '1631861651:AAF8cxTry8RoZhB2nIFeLIXO6S5KzesuGJg';
const BASE_URL = "https://api.telegram.org/bot".TOKEN."/";
$link = pg_connect("dbname=d84pot3p9gld95 host=ec2-54-247-158-179.eu-west-1.compute.amazonaws.com port=5432 user=exkbqcvcpnvduz password=b2210c4a962c2b5d3826407c668631eac689a24dc9560d82138aeb38051a5b78 sslmode=require");


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
    $message = 'Use command /setinfo to set all needed information!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/setinfo') {
	$message = 'Type information this way: name, budget, month. Example => Bob, 1000, March';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}else { // if user sending his name, budget and month
	$info = explode(', ', $text);
	if(is_string($info[0]) && is_numeric($info[1]) && is_string($info[2])) {
	$name = $info[0];
	$budget = $info[1];
	$month = $info[2];
	$query = pg_query($link, "CREATE TABLE {$name} (budget INTEGER, remainder INTEGER, month VARCHAR (15) NOT NULL);");
		if(true) {
		    $query = pg_query($link, "INSERT INTO {$name} (budget, remainder, month) VALUES ({$budget}, {$budget}, {$month});");
			$message = 'You can use command /addcosts to add some costs.';
			sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $month]);	
		}
	}else {
		$message = 'Try again!';	
        sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	}
}

?>