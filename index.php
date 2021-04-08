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

$content = file_get_contents('php://input');
$update = json_decode($content, TRUE);
$mes = $update['message'];

$chat_id = $mes['chat']['id'];
$text = $update['message']['text'];
$name = makeName($chat_id);

if($text == '/start') { 
    $message = 'Use command /setinfo to set all needed information!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/setinfo') {
    $message = 'Enter your budget this way: add 5000 (example). If your budget got bigger, you can add new amount of money to your old budget, just type it!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}elseif($text == '/addcosts') {
    $message = 'Type your data this way for a more secure: how you spent your money, how much did you spend it. Example => clothes 100';
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/getdata') { // if user wants to get all information
    $query = pg_query($link, "SELECT * FROM {$name} WHERE month = '{$month}';");
    $result = pg_fetch_array($query);
    foreach($result as $key => $value) {
	$message = "$key: $value";
	if(!is_numeric(substr($message, 0, 1))) {
	    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	}
    }

    $budget_query = pg_query($link, "SELECT budget FROM {$name} WHERE month='{$month}';");
    $food_query = pg_query($link, "SELECT food FROM {$name} WHERE month='{$month}';");
    if($budget_query && $food_query) {
	   $budget = pg_fetch_result($budget_query, 0, 0);
	   $food = pg_fetch_result($food_query, 0, 0);
	   $result = $budget / $food;
	   if($result < 2) {
		  $message = 'Be careful with your food!';
		  sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	   }
     }
}	



if(substr($text, 0, 1) != '/') { //if user is sending his own information
$info = explode(' ', $text);

if($info[0] == 'add' && is_numeric($info[1])) { // if user is sending his budget
    $budget = $info[1];
    $query = pg_query($link, "CREATE TABLE {$name} (month VARCHAR (15) NOT NULL, budget INTEGER, remainder INTEGER);");
    
    if($query) { // if there is no table with name '$name'
	$query = pg_query($link, "INSERT INTO {$name} (budget, remainder, month) VALUES ('{$budget}', '{$budget}', '{$month}');");
	$message = 'You can use command /addcosts to add some costs.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	
    }else { // if there is table with name '$name'
	    
	$budget_query = pg_query($link, "SELECT budget FROM {$name} WHERE month='{$month}';");
	$old_budget = pg_fetch_result($budget_query, 0, 0);
	$new_budget = $old_budget + $budget; // update budget

        $remainder_query = pg_query($link, "SELECT remainder FROM {$name} WHERE month = '{$month}';");
        $remainder = pg_fetch_result($remainder_query, 0, 0);
        $new_remainder = $remainder + $budget; // update remainder 

	$query1 = pg_query($link, "UPDATE {$name} SET budget = {$new_budget};"); // set new budget
	$query2 = pg_query($link, "UPDATE {$name} SET remainder = {$new_remainder};"); // set new remainder

	$message = 'Your data was updated!';	
        sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
    }

}elseif(is_string($info[0]) && $info[0] != 'add' && is_numeric($info[1])) { // if user is sending some costs
	
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

    $query2 = pg_query($link, "UPDATE {$name} SET remainder = {$new_remainder};"); // set new remainder

    $message = 'Your costs were added!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}else {
    $message = 'Try again!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
}

}
?>
