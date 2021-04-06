<?php
const TOKEN = 'TOKEN';
const BASE_URL = "https://api.telegram.org/bot".TOKEN."/";
$link = pg_connect("CONNECT");
$month = date("F");


function sendRequest($method, $params = []) {

	if(!empty($params)) {
		$url = BASE_URL.$method.'?'.http_build_query($params);
	}else {
		$url = BASE_URL.$method;
	}

	return json_decode(file_get_contents($url), JSON_OBJECT_AS_ARRAY);
}

function makeName($chat_id) {
	return 'table'.substr($chat_id, 0, 3).substr($chat_id, 6, 3);
}

$update = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

$chat_id = $update['message']['chat']['id'];
$text = $update['message']['text'];
$name = makeName($chat_id);

if($text == '/start') { 
    $message = 'Use command /setinfo to set all needed information!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/setinfo') {
    $message = 'Enter your budget this way: 5000 (example).';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}elseif($text == '/addcosts') {
    $message = 'Type your data this way for a more secure: how you spent your money, how much did you spend it. Example => clothes, 100';
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/getdata') {
    $message = 'What do you want to know about? Type it this way: food.';
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}	



if(substr($text, 0, 1) != '/') { //if user is sending his own information
$info = explode(', ', $text);

if(is_numeric($text)) { // if user is sending his budget
    $budget = $text;
    $query = pg_query($link, "CREATE TABLE {$name} (budget INTEGER, remainder INTEGER, month VARCHAR (15) NOT NULL);");
    
    if($query) { // if there is no table with name '$name'
		$query = pg_query($link, "INSERT INTO {$name} (budget, remainder, month) VALUES ('{$budget}', '{$budget}', '{$month}');");
		$message = 'You can use command /addcosts to add some costs.';
		sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	
	}else { // if there is table with name '$name'
		$message = 'Try again!';	
                sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	}

}elseif(is_string($info[0]) && is_numeric($info[1])) {
    // if user is sending some costs
	$costs = $info[0];
	$money = $info[1];

        	$remainder_query = pg_query($link, "SELECT remainder FROM {$name} WHERE month = '{$month}';");
        	$remainder = pg_fetch_result($remainder_query, 0, 0);
        	$new_remainder = $remainder - $money; // update remainder 

        	$costs_query = pg_query($link, "SELECT {$costs} FROM {$name} WHERE month = '{$month}';"); 
        	// checking if there already are some costs '$costs'
        	if(!$costs_query) { // if there aren't
                $query = pg_query($link, "ALTER TABLE {$name} ADD COLUMN {$costs} INTEGER NOT NULL DEFAULT({$money});");
        	}else { // if there are
        		$costs_result = pg_fetch_result($costs_query, 0, 0);
        		$new_money = $costs_result + $money; // adding new amount of money to costs '$costs'
        	  $query = pg_query($link, "UPDATE {$name} SET {$costs} = {$new_money};");
        	}

        	$query2 = pg_query($link, "UPDATE {$name} SET remainder = {$new_remainder};"); // update remainder

        	$message = 'Your costs were added!';	
        	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif(is_string($info[0]) && !isset($info[1])) {
	// if user is sending some data to check his costs
	$costs = $text;
	$query = pg_query($link, "SELECT {$costs} FROM {$name} WHERE month = '{$month}';");
	
	if($query) {
	   $result = pg_fetch_result($query, 0, 0);
	   
	if($costs == 'remainder' || $costs == 'budget') {
	   $message = "Your $costs is $result.";
	   sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	}else {
	   $message = "You spent $result on $costs this month";
	   sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	   }

    }else {
       $message = 'Try again!';
       sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
    }

}else { // if user sent wrong data
    $message = 'Try again!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}

}
?>
