<?php

// get input
$dialogflow_post = file_get_contents('php://input');
$dialogflow_post_data = json_decode($dialogflow_post, true);
$query_result = $dialogflow_post_data['queryResult'];
$intent_name = $query_result['intent']['displayName'];
$parameters = $query_result['parameters'];
$user_text = $query_result['queryText'];

// log input
$log_file = "/var/www/html/bot/bot.log";
$content = date('Y-m-d H:i:s') . " DialogFlow Post:\n" . $dialogflow_post . "\n";
#$content .= "Parameters = " . var_export($parameters, true) . "\n";
#$content .= "Intent = " . $intent_name . "\n";
file_put_contents($log_file, $content, FILE_APPEND);

// get output
$answer = getAnswer($intent_name, $parameters, $user_text);
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

function getAnswer($intent_name, $parameters, $user_text) {
	$answer = "Puoi ripetere la richiesta?";

	switch ($intent_name){
        case ('prenotazione - username'):
            {
                if ($parameters['any'] == ''){
                    $answer = "allora scegli un username per registrarti";
                }
                else {
                    $answer = "ciao " . $user_text . ". bentornato";
                }
            }
            break;
        default : $answer = "yes";
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


function getSimNumber($company) {
	if (strcmp('Techcompany', $company) == 0) return 200;
	if (strcmp('Datacompany', $company) == 0) return 100;
	return -1;
}

function getSimExpence($company) {
        if (strcmp('Techcompany', $company) == 0) return 4000;
        if (strcmp('Datacompany', $company) == 0) return 2500;
        return -1;
}

