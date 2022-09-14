<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

include("include/all.php");    
$con = connect();
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $periodo = $json_data->periodo;
    if (! $periodo) {
        print_error(400, "Missing parameter: periodo");
    }
    if (strlen($periodo) != 7) {
        print_error(400, "Bad parameter: Il periodo di lancio deve essere nella forma YYYY-MM");
    }
    
    $dateFirma = $lul->getDateFirma($periodo);

    header('Content-Type: application/json');
    echo json_encode(['data' => $dateFirma]);
    
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $annoMese = $json_data->data_firma[count($json_data->data_firma)-1]->ANNO_MESE;
    
    $lul->salvaDateFirma($annoMese, $json_data);

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>