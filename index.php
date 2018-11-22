<?php

// get input
$dialogflow_post = file_get_contents('php://input');
$dialogflow_post_data = json_decode($dialogflow_post, true);
$query_result = $dialogflow_post_data['queryResult'];
$intent_name = $query_result['intent']['displayName'];
$parameters = $query_result['parameters'];
$user_text = $query_result['queryText'];
$output_context = $query_result['outputContexts'];
$old_parameters = $output_context['parameters'];

// log input
$log_file = "/var/www/html/bot/bot.log";
$content = date('Y-m-d H:i:s') . " DialogFlow Post:\n" . $dialogflow_post . "\n";
#$content .= "Parameters = " . var_export($parameters, true) . "\n";
#$content .= "Intent = " . $intent_name . "\n";
file_put_contents($log_file, $content, FILE_APPEND);

// get output
$answer = getAnswer($intent_name, $parameters, $user_text, $old_parameters);
$output_json = json_encode(array(
		"fulfillmentText" => $answer,
		"fulfillmentMessages" => array(array("text" => array("text" => array($answer)))),
		"source" => "wesenseit.quinary.it"
), JSON_PRETTY_PRINT);

// log output
$content = date('Y-m-d H:i:s') . " Result:\n" . $output_json . "\n";
file_put_contents($log_file, $content, FILE_APPEND);

// return output
header('Content-type: application/json; charset=utf-8');
echo $output_json;

function getAnswer($intent_name, $parameters, $user_text, $old_parameters) {
	$answer = "Puoi ripetere la richiesta?";

	switch ($intent_name) {
        case ('prenotazione - username'): {
            if ($parameters['any'] == '') {
                $answer = "allora scegli un username per registrarti";
            } else {
                $username = $parameters['any'];
                $answer = "ciao " . $user_text . ", dimmi la tua password.";
            }
        }
        break;
        case ('prenotazione - username - password') : {
            $username = $old_parameters['any.original'];
            $password = $parameters['anypas'];
            $url = 'http://itesla.quinary.it/phpScheduleIt/Web/Services/Authentication/Authenticate';
            http_post($url . '?username=' . $username . '&password=' . $password);
        }





    }
    /*
	if (strcmp('SimNumberIntent', $intent_name) == 0) {
        	if (array_key_exists('Company', $parameters) && !empty($parameters['Company'])) {
                	$sim_number = getSimNumber($parameters['Company']);
                	if ($sim_number == -1) {
                        	$answer = "La company " . $parameters['Company'] . " non è censita";
                	} else {
                        	$answer = $parameters['Company'] . " ha " . $sim_number . " SIM disponibili";
                	}
        	} else {
                	$answer = "Non ho capito la company di cui hai richiesto i dati. Puoi ripetere la richiesta?";
        	}
	}
	if (strcmp('SimExpenseIntent', $intent_name) == 0) {
                if (array_key_exists('Company', $parameters) && !empty($parameters['Company'])) {
                        $sim_expence = getSimExpence($parameters['Company']);
                        if ($sim_epence == -1) {
                                $answer = "La company " . $parameters['Company'] . " non è censita";
                        } else {
                                $answer = $parameters['Company'] . " ha speso " . $sim_expence . " euro";
                        }
                } else {
                        $answer = "Non ho capito la company di cui hai richiesto i dati. Puoi ripetere la richiesta?";
                }
        }
    */

	return $answer;
}

function http_post($url){
    $postdata = array();

    if(strpos($url,"?") !== FALSE){
        $qsr = array();
        $url_r = explode("?", $url);
        $url = $url_r[0];
        parse_str($url_r[1], $qsr);
        $postdata = http_build_query($qsr);
    }

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);
    $output = @file_get_contents($url, false, $context);

    if($output === FALSE) {
        if (function_exists('curl_init')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close ($ch);
        }
    }
    return $output;
}
