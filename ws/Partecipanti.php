<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$id_dipendente = isset($_GET['id_dipendente']) ? $con->escape_string($_GET['id_dipendente']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id_dipendente) {
        //==========================================================
        $progetto = $partecipantiManager->get_partecipante($id_dipendente);
        if (!$progetto) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $progetto]);
    } else {
        //==========================================================
        [$progetti, $count] = $partecipantiManager->get_partecipanti($top, $skip, $orderby);
          
        header('Content-Type: application/json');
        echo json_encode(['data' => $progetti, 'count' => $count]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $object = $partecipantiManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $object = $partecipantiManager->get_partecipante($json_data->id_dipendente);
    if (!$object) {
        print_error(404, 'Not found');
    }
    $partecipantiManager->aggiorna($object, $json_data);
    
    $object = $partecipantiManager->get_partecipante($json_data->id_dipendente);
    header('Content-Type: application/json');
    echo json_encode(['value' => $object]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_dipendente) {
        print_error(400, 'Missing id_dipendente');
    }
    $progetto_su_db = $partecipantiManager->get_partecipante($id_dipendente);
    if (!$progetto_su_db) {
        print_error(404, 'Not found');
    }
    
    $partecipantiManager->elimina($id_dipendente);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>