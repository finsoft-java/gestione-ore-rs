<?php

// Prevedo le seguenti richieste:
// OPTIONS
// GET Progetti  -> lista di tutti i progetti
// GET Progetti?id_tipologia_spesa=xxx  -> singolo progetto
// PUT Progetti -> creazione nuovo progetto
// POST Progetti -> update progetto esistente
// DELETE Progetti?id_tipologia_spesa=xxx -> elimina progetto esistente
include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$id_tipologia_spesa = isset($_GET['id_tipologia_spesa']) ? $con->escape_string($_GET['id_tipologia_spesa']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id_tipologia_spesa) {
        //==========================================================
        $tipologiaSpesa = $tipologiaManager->get_tipologia($id_tipologia_spesa);
        if (!$tipologiaSpesa) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $tipologiaSpesa]);
    } else {
        //==========================================================
        [$progetti, $count] = $tipologiaManager->get_progetti($top, $skip, $orderby);
          
        header('Content-Type: application/json');
        echo json_encode(['data' => $progetti, 'count' => $count]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_tipologia_spesa = '';
    
    if(isset($json_data->ID_PROGETTO)){
        $id_tipologia_spesa = $json_data->ID_PROGETTO;
    }
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if ($id_tipologia_spesa) {
        print_error(400, "id_tipologia_spesa must be null when creating new project");
    }
    $tipologiaSpesa = $tipologiaManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $tipologiaSpesa]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $tipologiaSpesa_su_db = $tipologiaManager->get_tipologia($json_data->ID_PROGETTO);
    if (!$tipologiaSpesa_su_db) {
        print_error(404, 'Not found');
    }
    $tipologiaManager->aggiorna($tipologiaSpesa_su_db, $json_data);
    
    $tipologiaSpesa_su_db = $tipologiaManager->get_tipologia($json_data->ID_PROGETTO);
    header('Content-Type: application/json');
    echo json_encode(['value' => $tipologiaSpesa_su_db]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_tipologia_spesa) {
        print_error(400, 'Missing id_tipologia_spesa');
    }
    $tipologiaSpesa_su_db = $tipologiaManager->get_tipologia($id_tipologia_spesa);
    if (!$tipologiaSpesa_su_db) {
        print_error(404, 'Not found');
    }
    
    $tipologiaManager->elimina($id_tipologia_spesa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>