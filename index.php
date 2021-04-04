<?php
const TOKEN = '1631861651:AAF8cxTry8RoZhB2nIFeLIXO6S5KzesuGJg';
const BASE_URL = "https://api.telegram.org/bot".TOKEN."/";
$link = pg_connect("dbname=d84pot3p9gld95 host=ec2-54-247-158-179.eu-west-1.compute.amazonaws.com port=5432 user=exkbqcvcpnvduz password=b2210c4a962c2b5d3826407c668631eac689a24dc9560d82138aeb38051a5b78 sslmode=require");
$month = date("F");


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
	$message = 'Type information this way: name, budget. Example => bob, 1000. Do not use capital letters!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}elseif($message == 'Type information this way: name, budget. Example => Bob, 1000' && substr($text, 0, 1) != '/') {
    // if user sending his name and budget
	$info = explode(', ', $text);
	if(!empty(is_string($info[0])) && !empty(is_numeric($info[1]))) {
	$name = $info[0];
	$budget = $info[1];
	$query = pg_query($link, "CREATE TABLE {$name} (budget INTEGER, remainder INTEGER, month VARCHAR (15) NOT NULL);");
		if(true) {
		    $query = pg_query($link, "INSERT INTO {$name} (budget, remainder, month) VALUES ('{$budget}', '{$budget}', '{$month}');");
			$message = 'You can use command /addcosts to add some costs.';
			sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	
		}
	}else {
		$message = 'Try again!';	
        sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	}

}

if($text == '/addcosts') {
	$message = 'Type your data this way for a more secure: name, how you spent your money, how much did you spend it.
	Example => Arkadiy, clothes, 100';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}elseif($message == 'Type your data this way for a more secure: name, how you spent your money, how much did you spend it.
	Example => bob, clothes, 100' && $text != '/addcosts') { // if user sends some costs
    $info = $explode(', ', $text);
    $message = 'ok';
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	


    if(!empty(is_string($info[0])) && !empty(is_string($info[1])) && !empty(is_numeric($info[2]))) { 
	    $name = $info[0];
	    $costs = $info[1];
	    $money = $info[2];
	    $query = pg_query($link, "ALTER TABLE {$name} ADD COLUMN {$costs} VARCHAR (25);");

	    if(true) {
		    $query = pg_query($link, "INSERT INTO {$name} ('{$costs}') VALUES ('{$money}');");
	    }else {
		    $message = 'Try again!';	
            sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	    }

    }else {
	    $message = 'Try again!';	
        sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
    }
}
?>