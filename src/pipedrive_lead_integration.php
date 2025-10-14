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
        addLog( 'Request successful. added' .print_r($result,true). 'to ' . $end_point . PHP_EOL);
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

function buildPersonWithCustomFields(array $personData){
    global $list_of_contact_types;
    $contact_type_id = "c0b071d74d13386af76f5681194fd8cd793e6020";
    $person = [
        'name' => $personData['name'] ?? '',
        'emails' => $personData['emails'] ?? '',
        'phones' => $personData['phones'] ?? '',
        'org_id' => $personData['org_id'] ?? null,
        'visible_to' => $personData['visible_to'],
        'custom_fields' => [
            $contact_type_id => $list_of_contact_types[$personData['custom_fields']['contact_type']] ?? null
        ]
    ];

    return $person;
}
function buildLeadWithCustomFields(array $leadData){
    global $list_of_housing_types, $list_of_deal_types;
    $housing_type_id = "35c4e320a6dee7094535c0fe65fd9e748754a171";
    $deal_type_id = "761dd27362225e433e1011b3bd4389a48ae4a412";
    $property_size_id = "533158ca6c8a97cc1207b273d5802bd4a074f887";

    $lead = [
        'title' => $leadData['title'] ?? '',
        'value' => $leadData['value'] ?? 0,
        'person_id' => $leadData['person_id'] ?? null,
        'organization_id' => $leadData['organization_id'] ?? null,
        'visible_to' => $leadData['visible_to'],
        $housing_type_id => $list_of_housing_types[$leadData['custom_fields']['housing_type']] ?? null,
        $property_size_id => $leadData['custom_fields']['property_size'] ?? null,
        $deal_type_id => $list_of_deal_types[$leadData['custom_fields']['deal_type']] ?? null
        
    ];

    return $lead;
}

function main(){
    global $jsonFile, $list_of_housing_types, $list_of_deal_types;

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
    $person = buildPersonWithCustomFields($person);

    $person_id = createPerson($person);

    $lead['person_id'] = $person_id;
    $lead['organization_id'] = $org_id;
    $lead = buildLeadWithCustomFields($lead);
    createLead($lead);


    
}

main();

?>