<?php

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

    // CONTROLLO PARAMETRI

    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $dataInizio = $json_data->dataInizio;
    if (! $dataInizio) {
        print_error(400, "Missing parameter: dataInizio");
    }
    $dataFine = $json_data->dataFine;
    if (! $dataFine) {
        print_error(400, "Missing parameter: dataFine");
    }
    if (strlen($dataInizio) != 10 || strlen($dataFine) != 10) {
        print_error(400, "Bad parameter: dataInizio e dataFine devono essere nella forma YYYY-MM-DD");
    }
    $idProgetto = $json_data->idProgetto;
    if (! $idProgetto) {
        print_error(400, "Missing parameter: idProgetto");
    }
    
    $message = (object) [
        'error' => '',
        'success' => '',
      ];
    $consuntiviProgettiManager->run_assegnazione($dataInizio, $dataFine, $idProgetto, $message);

    header('Content-Type: application/json');
    echo json_encode(['value' => $message]);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>