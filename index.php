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


function getData($column) {
	global $link, $name, $month;
	$query = pg_query($link, "SELECT {$column} FROM {$name} WHERE month='{$month}';");
	if($query) {
	$result = pg_fetch_result($query, 0, 0);
	return $result;
        }
}


$content = file_get_contents('php://input');
$update = json_decode($content, TRUE);
$mes = $update['message'];

$chat_id = $mes['chat']['id'];
$text = $update['message']['text'];
$name = makeName($chat_id);


$months = array(
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July ',
    'August',
    'September',
    'October',
    'November',
    'December',
);

if($text == '/start') { 
    $message = 'Use command /setinfo to set all needed information!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/setinfo') {
    $message = 'Enter your budget this way: add 5000 (example)
If your budget got bigger, you can add new amount of money to your old budget, just type it again!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}elseif($text == '/addcosts') {
    $message = 'Type your data this way for a more secure: how you spent your money, how much did you spend it.
Example: clothes 100';
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

}elseif($text == '/getinfo') { // if user wants to get all information
    $message = 'If you want to get all information about your budget, just type the name of month: april. 
If you want to know only about your remainder or something else (for current month), type it this way: remainder';
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	
}elseif($text == '/report') {
	$message = 'What year do you want to know about?
Type it: 2021 (example)';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

}


//if user is sending his own information
$info = explode(' ', $text);

if($info[0] == 'add'  && isset($info[1]) && is_numeric($info[1])) { // if user is sending his budget
    $budget = $info[1];
    $query = pg_query($link, "CREATE TABLE {$name} (month VARCHAR (15) NOT NULL, year INTEGER, budget INTEGER, remainder INTEGER);");
    
    if($query) { // if there is no table with name '$name'
	$query = pg_query($link, "INSERT INTO {$name} (month, year, budget, remainder) VALUES ('{$month}', '{$year}', '{$budget}', '{$budget}');");
	$message = 'You can use command /addcosts to add some costs.';
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);	

    }else { // if there is table with name '$name'
        $month_db = getData('month');

        if($month_db) {
            $new_budget = getData('budget') + $budget;
            $new_remainder = getData('remainder') + $budget;

            $query1 = pg_query($link, "UPDATE {$name} SET budget = {$new_budget} WHERE month='{$month}' AND year={$year};");
            // set new budget
            $query2 = pg_query($link, "UPDATE {$name} SET remainder = {$new_remainder} WHERE month='{$month}' AND year={$year};");
            // set new remainder

	    $message = 'Your data was updated!';	
            sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
        }else {
            $query = pg_query($link, "INSERT INTO {$name} (month, year, budget, remainder) VALUES ('{$month}', '{$year}', '{$budget}', '{$budget}');");
            $message = 'Your data was updated!';	
            sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
        }
    }

}elseif(is_string($info[0]) && $info[0] != 'add' && isset($info[1]) && is_numeric($info[1])) {
    $get_month = ucfirst($info[0]);
    $get_year = $info[1];
    $m = 0;

    foreach($months as $value) {
       if($value == $get_month) {
	   $m = $value;
       }
    }

    $user_month = $m; 

    if($user_month) { // if user wants to get info about month
	$query = pg_query($link, "SELECT * FROM {$name} WHERE month='{$user_month}' AND year={$get_year};");
        $result = pg_fetch_array($query);

        if($result) {
            foreach($result as $key => $value) {
	        if(!is_numeric(substr($key, 0, 1))) {
	      	    $message = "$key: $value";
	      	    if($key != 'remainder' && $value == 0) {
	      	 	    continue;
	      	    }
  	      	    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
                }
            }

            if(getData('budget') && getData('food')) {
	        $result = getData('budget') / getData('food');
	        if($result < 2) {
	            $message = 'Be careful with food!';
	            sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
                }
            }

        }else {
            $message = 'Try again!';
	    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
        }

    }else { // if user is sending some costs
	$costs = $info[0];
        $money = $info[1];

        $costs_check = getData($costs);
    // checking if there already are some costs '$costs'
    if(!$costs_check) { // if there aren't
        $query1 = pg_query($link, "ALTER TABLE {$name} ADD COLUMN {$costs} INTEGER DEFAULT(0);");
        $query2 = pg_query($link, "UPDATE {$name} SET {$costs} = {$money} WHERE month='{$month}' AND year={$year};");

    }else { // if there are
        $new_money = $costs_check + $money; // adding new amount of money to costs '$costs'
        $query_costs = pg_query($link, "UPDATE {$name} SET {$costs} = {$new_money} WHERE month='{$month}' AND year={$year};");        
    }

    $new_remainder = getData('remainder') - $money; // update remainder 
    $query_remainder = pg_query($link, "UPDATE {$name} SET remainder = {$new_remainder} WHERE month='{$month}' AND year={$year};");
    // set new remainder
    $message = 'Your costs were added!';	
    sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);

    }

}elseif(!is_string($info[0]) && !isset($info[1]) && substr($text, 0, 1) != '/') { // if user wants to get some info
    $data = getData($text);

    if($data) {
	$message = "$text: $data";
	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
    }else { 
	    
	if($text == 'remainder') { // only remainder can be 0
            $message = 'remainder: 0';
            sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
	}else {
	    $message = 'Try again!';	
            sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
        }

    }
	
}elseif(is_numeric($info[0]) && !isset($info[1])) {
    $year = $text;
    $query1 = pg_query($link, "SELECT SUM(budget) as sum1 FROM table565088 WHERE year={$year};");
    $budget = pg_fetch_result($query1, 0, 0);
    if($budget) {
	$query2 = pg_query($link, "SELECT SUM(remainder) as sum2 FROM table565088 WHERE year={$year};");
	$remainder = pg_fetch_result($query2, 0, 0);
	$message = "budget: $budget
remainder: $remainder";
    	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
    }else {
    	$message = 'Try again!';
    	sendRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $message]);
    }
}
?>
