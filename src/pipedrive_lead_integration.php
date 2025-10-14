<?php

$envFile = __DIR__. '/.env';
$jsonFile = __DIR__. '/../test/test_data.json';
$logfile = __DIR__ . '/../logs/integration.log';

function addLog($message) {
    global $logfile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logfile, "[$date] $message" . PHP_EOL, FILE_APPEND);
}


function loadJsonData($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("File not found: $filePath");
    }

    $jsonContent = file_get_contents($filePath);
    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decoding JSON from file: $filePath - " . json_last_error_msg());
    }

    return $data;
}


if (file_exists($envFile)){
    $envVariables = parse_ini_file($envFile);

    foreach($envVariables as $key => $value){
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}



$list_of_contact_types = [
    'Privat' => 27,
    'Borettslag' => 28,
    'Bedrift' => 29
];
$list_of_housing_types = [
    'Enebolig' => 30,
    'Leilighet' => 31,
    'tomannsbolig' => 32,
    'Rekkehus' => 33,
    'Hytte' => 34,
    'Annet' => 35
];
$list_of_deal_types = [
    'aktuelle' => 42,
    'Fastpris' => 43,
    'Spotpris' => 44,
    'kraftforvaltning' => 45,
    'Annet' => 46
];

function sendPipedriveRequest(string $end_point, array $data, string $version = 'v2') {
    $domain = $_ENV['DOMAIN'];
    $apiKey = $_ENV['APIKEY']; 

    $fullUrl = 'https://' . $domain . '.pipedrive.com/api/'. $version .'/' . $end_point . '?api_token=' . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    addLog( 'Sending request to ' . $fullUrl . '...' . PHP_EOL);

    $output = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($output, true);

    if (isset($result['success']) && $result['success'] === true) {
        addLog( 'Request successful. added' . $end_point . PHP_EOL);
        return $result['data'];
    } else {
        addLog( 'Error: ' . ($result['error'] ?? 'Unknown error') . PHP_EOL);
        return null;
    }
}

function createOrganisation(array $organisation){

    $result = sendPipedriveRequest('organizations', $organisation);

    return $org_id = $result['id'] ?? null;
    
    
}

function createPerson(array $person){ 
    $result = sendPipedriveRequest('persons', $person);

    return $person_id = $result['id'] ?? null;

}

function createLead(array $lead){

    $result = sendPipedriveRequest('leads', $lead, 'v1');

} 

function main(){
    global $jsonFile, $list_of_contact_types, $list_of_housing_types, $list_of_deal_types;

    try {
        $data = loadJsonData($jsonFile);
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
        return;
    }

    $person = $data['person'][0] ?? null;
    $organisation = $data['organization'][0] ?? null;
    $lead = $data['lead'][0] ?? null;

    $org_id = createOrganisation($organisation);
    $person['org_id'] = $org_id;
    $person['custom_fields']['contact_type'] = $list_of_contact_types[$person['custom_fields']['contact_type']] ?? null;

    $person_id = createPerson($person);

    $lead['person_id'] = $person_id;
    $lead['organization_id'] = $org_id;
    $lead['custom_fields']['housing_type'] = $list_of_housing_types[$lead['custom_fields']['housing_type']] ?? null;
    $lead['custom_fields']['deal_type'] = $list_of_deal_types[$lead['custom_fields']['deal_type']] ?? null;
    createLead($lead);


    
}

main();




?>