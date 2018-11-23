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
    "source" => "itesla.quinary.it/phpScheduleIt"
), JSON_PRETTY_PRINT);

// log output
$content = date('Y-m-d H:i:s') . " Result:\n" . $output_json . "\n";
file_put_contents($log_file, $content, FILE_APPEND);

// return output
header('Content-type: application/json; charset=utf-8');
echo $output_json;

function getAnswer($intent_name, $parameters, $user_text, $old_parameters)
{
    $answer = "Puoi ripetere la richiesta?";

    switch ($intent_name) {
        case ('prenotazione - username'):
            {
                if ($parameters['any'] == '') {
                    $answer = "allora scegli un username per registrarti";
                } else {
                    $username = $parameters['any'];
                    $answer = "ciao " . $user_text . ", dimmi la tua password.";
                }
            }
            break;
        case ('prenotazione - username - password') :
            {
                $username = $old_parameters['any.original'];
                $password = $parameters['anypas'];

                // The data to send to the API
                $postData = array(
                    'username' => $username,
                    'password' => $password
                );

                // Setup cURL
                $ch = curl_init('http://itesla.quinary.it/phpScheduleIt/Web/Services/Authentication/Authenticate');
                curl_setopt_array($ch, array(
                    CURLOPT_POST => TRUE,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_POSTFIELDS => json_encode($postData)
                ));

                // Send the request
                $response = curl_exec($ch);

                // Check for errors
                if ($response === FALSE) {
                    $answer = curl_error($ch);
                    break;
                }

                if ($response !== null) {
                    // Decode the response
                    $responseData = json_decode($response, TRUE);
                    $answer = "login effettuato: " . $responseData['isAuthenticated'];
                }
                curl_close($ch);
            }
            break;


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
