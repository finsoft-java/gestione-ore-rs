<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$cod_commessa = isset($_GET['cod_commessa']) ? $con->escape_string($_GET['cod_commessa']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    //==========================================================
    $lista = $commesseManager->get_commesse($cod_commessa);

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
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $commesseManager->elimina($cod_commessa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>