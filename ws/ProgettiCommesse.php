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
$cod_commessa = isset($_GET['cod_commessa']) ? $con->escape_string($_GET['cod_commessa']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id_progetto) {
        //==========================================================
        $lista = $progettiCommesseManager->get_commesse($id_progetto);
        if (!$lista) {
            $lista = [];
        }
        header('Content-Type: application/json');
        echo json_encode(['data' => $lista]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    
    $progetto = $progettiCommesseManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $progetto]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);

    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }

    $obj_su_db = $progettiCommesseManager->get_commessa($json_data->ID_PROGETTO, $json_data->COD_COMMESSA);
    if (!$obj_su_db) {
        print_error(404, 'Not found');
    }
    $wp = $progettiCommesseManager->aggiorna($obj_su_db, $json_data);

    header('Content-Type: application/json');
    echo json_encode(['value' => $wp]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$cod_commessa) {
        print_error(400, 'Missing cod_commessa');
    }
    $progettiCommesseManager->elimina($id_progetto, $cod_commessa);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>