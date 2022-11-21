<?php

include("include/all.php");    
$con = connect();
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    // CONTROLLO PARAMETRI

    $dataInizio = $_REQUEST['dataInizio'];
    if (! $dataInizio) {
        print_error(400, "Missing parameter: dataInizio");
    }
    $dataFine = $_REQUEST['dataFine'];
    if (! $dataFine) {
        print_error(400, "Missing parameter: dataFine");
    }
    if (strlen($dataInizio) != 10 || strlen($dataFine) != 10) {
        print_error(400, "Bad parameter: dataInizio e dataFine devono essere nella forma YYYY-MM-DD");
    }

    $data = $consuntiviProgettiManager->get_progetti_attivi($dataInizio, $dataFine);

    header('Content-Type: application/json');
    echo json_encode(['data' => $data]);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>