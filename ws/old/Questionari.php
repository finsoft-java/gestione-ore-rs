<?php

// Prevedo le seguenti richieste:
// OPTIONS
// GET Questionari  -> lista di tutti i questionari
// GET Questionari?id_questionario=xxx  -> singolo questionario
// PUT Questionari -> creazione nuovo questionario
// POST Questionari -> update questionario esistente
// DELETE Questionari?id_questionario=xxx -> elimina questionario esistente

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$id_questionario = isset($_GET['id_questionario']) ? $con->escape_string($_GET['id_questionario']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$search = isset($_GET['search']) ? $con->escape_string($_GET['search']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;
$mostra_solo_validi = isset($_GET['mostra_solo_validi']) ? ($con->escape_string($_GET['mostra_solo_validi']) === 'true' ? true : false) : false;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($id_questionario) {
        
        //==========================================================
        $questionario = $questionariManager->get_questionario($id_questionario);
        if (!$questionario) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $questionario]);
    } else {
        //==========================================================
        [$questionario, $count] = $questionariManager->get_questionari($top, $skip, $orderby, $search, $mostra_solo_validi);
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $questionario, 'count' => $count]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if (!$questionariManager->utente_puo_creare_questionari()) {
        print_error(403, "Utente non autorizzato a creare questionari.");
    }
    
    $questionario = $questionariManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $questionario]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================    
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $questionario = $questionariManager->get_questionario($json_data->id_questionario);
    if (!$questionario) {
        print_error(404, 'Not found');
    }
    if (!$questionario->utente_puo_modificarlo()) {
        print_error(403, "Utente non autorizzato a modificare questo Questionario.");
    }
    /*
    if ($questionario->is_gia_compilato()) {
        print_error(403, "Questionario non modificabile perchè già compilato");
    }
    */
    $questionariManager->aggiorna($questionario, $json_data);

    header('Content-Type: application/json');
    echo json_encode(['value' => $questionario]);

    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_questionario) {
        print_error(400, 'Missing id_questionario');
    }
    $questionario = $questionariManager->get_questionario($id_questionario);
    if (!$questionario) {
        print_error(404, 'Not found');
    }
    if (!$questionario->utente_puo_modificarlo()) {
        print_error(403, "Utente non autorizzato a modificare questo Questionario.");
    }
    if ($questionario->is_gia_compilato()) {
        print_error(403, "Questionario non modificabile perchè già compilato.");
    }
    
    $questionariManager->elimina($id_questionario);
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>