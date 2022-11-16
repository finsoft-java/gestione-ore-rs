<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

//TODO scommenta
// require_logged_user_JWT();

$cod_commessa = isset($_GET['cod_commessa']) ? $con->escape_string($_GET['cod_commessa']) : null;
$dataInizio = isset($_GET['DATA_INIZIO']) ? $con->escape_string($_GET['DATA_INIZIO']) : null;
$dataFine = isset($_GET['DATA_FINE']) ? $con->escape_string($_GET['DATA_FINE']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    //==========================================================
    $lista = $commesseManager->get_commesse_periodo($dataInizio, $dataFine);

    header('Content-Type: application/json');
    echo json_encode(['data' => $lista]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    
    $object = $commesseManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);

    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }

    $object = $commesseManager->get_commessa($json_data->COD_COMMESSA);
    if (!$object) {
        print_error(404, 'Not found');
    }
    $object = $commesseManager->aggiorna($object, $json_data);

    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$dataInizio) {
        print_error(400, 'Missing DATA_INIZIO');
    }
    if (!$dataFine) {
        print_error(400, 'Missing DATA_FINE');
    }
    $commesseManager->elimina_periodo($dataInizio, $dataFine);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>