<?php

// Prevedo le seguenti richieste:
// OPTIONS
// GET Progetti  -> lista di tutti i progetti
// GET Progetti?id_progetto=xxx  -> singolo progetto
// PUT Progetti -> creazione nuovo progetto
// POST Progetti -> update progetto esistente
// DELETE Progetti?id_progetto=xxx -> elimina progetto esistente
include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$id_progetto = isset($_GET['id_progetto']) ? $con->escape_string($_GET['id_progetto']) : null;
$id_spesa = isset($_GET['id_spesa']) ? $con->escape_string($_GET['id_spesa']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id_progetto) {
        //==========================================================
        $progetto = $progettiSpesaManager->get_progetto($id_progetto);
        if (!$progetto) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $progetto]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_spesa = '';
    
    if(isset($json_data->ID_SPESA)){
        $id_spesa = $json_data->ID_SPESA;
    }
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if ($id_spesa) {
        print_error(400, "id_spesa must be null when creating new project");
    }
    $progetto = $progettiSpesaManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $progetto]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_spesa = '';
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }

    $progetto_su_db = $progettiSpesaManager->get_progetto($json_data->ID_PROGETTO);
    if (!$progetto_su_db) {
        print_error(404, 'Not found');
    }
    $progettiSpesaManager->aggiorna($progetto_su_db, $json_data);
    
    $progetto_su_db = $progettiSpesaManager->get_progetto_byspesa($json_data->ID_SPESA);
    header('Content-Type: application/json');
    echo json_encode(['value' => $progetto_su_db]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_spesa) {
        print_error(400, 'Missing id_spesa');
    }
    $progettiSpesaManager->elimina($id_spesa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>