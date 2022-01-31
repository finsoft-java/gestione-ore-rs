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
    $idProgetto = $json_data->idProgetto;
    if (! $idProgetto) {
        print_error(400, "Missing parameter: idProgetto");
    }
    $periodo = $json_data->periodo;
    if (! $periodo) {
        print_error(400, "Missing parameter: periodo");
    }
    if (strlen($periodo) != 10) {
        print_error(400, "Bad parameter: Il periodo di lancio deve essere nella forma YYYY-MM-DD");
    }
    $date = DateTime::createFromFormat('Y-m-d', $periodo);
    
    $message = (object) [
        'error' => '',
        'success' => '',
      ];
    $consuntiviProgettiManager->run_assegnazione($idProgetto, $date, $message);

    header('Content-Type: application/json');
    echo json_encode(['value' => $message]);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>