<?php
require_once 'pipedrive_connector.php';

$envFile = __DIR__. '/.env';

if (file_exists($envFile)){
    $envVariables = parse_ini_file($envFile);

    foreach($envVariables as $key => $value){
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

$domain = $_ENV['DOMAIN'];
$apiKey = $_ENV['APIKEY'];


function createOrganisation(array $organisation){

    
    $url = 'https://' . $domain . '.pipedrive.com/api/v2/organizations?api_token=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type: application/json'));
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($organisation));

    echo 'Sending request...' .PHP_EOL;

    $output = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($output, true);

    if (!empty($result['data']['id'])){
        $this -> organization_id = $result['data']['id'];
        echo 'Organization added successfully!' .PHP_EOL;
    }
}

function createPerson(array $person){

    $url = 'https://' . $domain . '.pipedrive.com/api/v2/persons?api_token=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type: application/json'));
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($person));

    echo 'Sending request...' .PHP_EOL;

    $output = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($output, true);

    if (!empty($result['data']['id'])){
        echo 'Person added successfully!' .PHP_EOL;
    }
    
}

function createLead(array $lead){

    $url = 'https://' . $domain . '.pipedrive.com/api/v2/leads?api_token=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type: application/json'));
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($lead));

    echo 'Sending request...' .PHP_EOL;

    $output = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($output, true);

    if (!empty($result['data']['id'])){
        echo 'Lead added successfully!' .PHP_EOL;
    }
    
}





?>