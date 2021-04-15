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
        $lista_spese = $progettiSpesaManager->get_spese_progetto($id_progetto);
        if (!$lista_spese) {
            $lista_spese = [];
        }
        header('Content-Type: application/json');
        echo json_encode(['data' => $lista_spese]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_spesa = '';
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    
    if(isset($json_data->ID_SPESA)){
        $id_spesa = $json_data->ID_SPESA;
    }
    if (!$id_spesa) {
        print_error(400, "Missing id_spesa");
    }
    $spesa_old = $progettiSpesaManager->get_progetto_byspesa($json_data->ID_PROGETTO, $json_data->ID_SPESA);
    if (!$spesa_old) {
        print_error(404, "La spesa non esiste");
    }
    $spesa = $progettiSpesaManager->aggiorna($spesa_old, $json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $spesa]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_spesa = '';
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    
    if(isset($json_data->ID_SPESA)){
        $id_spesa = $json_data->ID_SPESA;
    }
    if ($id_spesa) {
        print_error(400, "id_spesa must be null when creating new object");
    }

    $progetto_su_db = $progettiManager->get_progetto($json_data->ID_PROGETTO);
    if (!$progetto_su_db) {
        print_error(404, "Il progetto $json_data->ID_PROGETTO non esiste");
    }
    $spesa = $progettiSpesaManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $spesa]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$id_spesa) {
        print_error(400, 'Missing id_spesa');
    }
    $progettiSpesaManager->elimina($id_progetto, $id_spesa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>