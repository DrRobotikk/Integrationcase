<?php
$envFile = __DIR__. '/.env';

if (file_exists($envFile)){
    $envVariables = parse_ini_file($envFile);

    foreach($envVariables as $key => $value){
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

function createOrganisation(array $organisation){
    $api_token = $_ENV['APIKEY'];
    $company_domain = $_ENV['DOMAIN']; 
    
    $url = 'https://' . $company_domain . '.pipedrive.com/api/v2/organizations?api_token=' . $api_token;

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
        echo 'Organization added successfully!' .PHP_EOL;
    }
}

$data = array(
    'name' => 'Hypertech AS'
);

createOrganisation($data);

?>