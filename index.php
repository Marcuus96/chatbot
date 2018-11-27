<?php

// get input
$dialogflow_post = file_get_contents('php://input');
$dialogflow_post_data = json_decode($dialogflow_post, true);
$query_result = $dialogflow_post_data['queryResult'];
$intent_name = $query_result['intent']['displayName'];
$parameters = $query_result['parameters'];
$user_text = $query_result['queryText'];
$old_parameters = $query_result['outputContexts'][0]['parameters'];

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
                    $answer = "ciao " . $user_text . ", dimmi la tua password.";
                }
            }
            break;
        case ('prenotazione - username - password') :
            {
                // The data to send to the API
                $login = array(
                    'username' => $old_parameters['any'],
                    'password' => $parameters['anypas']
                );

                // Setup cURL
                $ch = curl_init('http://itesla.quinary.it/phpScheduleIt/Web/Services/Authentication/Authenticate');
                curl_setopt_array($ch, array(
                    CURLOPT_POST => TRUE,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json"
                    ),
                    CURLOPT_POSTFIELDS => json_encode($login),
                    CURLOPT_FAILONERROR => TRUE
                ));

                // Send the request
                $response = curl_exec($ch);

                // Check for errors
                if ($response === FALSE) {
                    $answer = curl_error($ch) . ' code: ' . curl_errno($ch);
                    break;
                }

                if ($response != null) {
                    // Decode the response
                    $responseData = json_decode($response, TRUE);
                    if ($responseData === NULL)
                        $answer = "ERROR" . json_last_error();
                    else {
                        if ($responseData['isAuthenticated'] === TRUE)
                            $answer = 'login effettuato con successo. per che giorno vuoi prenotare?';
                        else
                            $answer = 'errore di login. username o password errati.';
                    }
                }
                curl_close($ch);
            }
            break;
        case('prenotazione - username - password - day - room - hour - end') :
            {

                $postData = array(
                    "accessories" => '',
                    "customAttributes" => '',
                    "description" => "new reservation",
                    "endDateTime" => "2018-11-27T18:35:50+0100",
                    "invitees" => '',
                    "participants" => '',
                    "recurrenceRule" => '',
                    "resourceId" => '1',
                    "resources" => '',
                    "startDateTime" => "2018-11-27T15:35:50+0100",
                    "title" => "new res",
                    "userId" => 1,
                    "startReminder" => '',
                    "endReminder" => 'null',
                    "allowParticipation" => 'true'
                );


                // Setup cURL
                $ch = curl_init('http://itesla.quinary.it/phpScheduleIt/Web/Services/Reservations/');
                curl_setopt_array($ch, array(
                    CURLOPT_POST => TRUE,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json"
                    ),
                    CURLOPT_POSTFIELDS => json_encode($postData),
                    CURLOPT_FAILONERROR => TRUE
                ));

                // Send the request
                $response = curl_exec($ch);

                // Check for errors
                if ($response === FALSE) {
                    $answer = curl_error($ch) . ' code: ' . curl_errno($ch);
                    break;
                }

                if ($response != null) {
                    // Decode the response
                    $responseData = json_decode($response, TRUE);
                    if ($responseData === NULL)
                        $answer = "ERROR" . json_last_error();
                    else {
                        if ($responseData['isPendingApproval'] === TRUE)
                            $answer = 'prenotazione avvenuta con successo grazie';
                        else
                            $answer = 'errore nella prenotazione!';
                    }
                }
                curl_close($ch);
            }
            break;

    }




    return $answer;
}

