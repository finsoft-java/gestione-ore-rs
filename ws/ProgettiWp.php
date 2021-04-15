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
$id_wp = isset($_GET['id_wp']) ? $con->escape_string($_GET['id_wp']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id_progetto) {
        //==========================================================
        $lista_wp = $progettiWpManager->get_wp_progetto($id_progetto);
        if (!$lista_wp) {
            $lista_wp = [];
        }
        header('Content-Type: application/json');
        echo json_encode(['data' => $lista_wp]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_wp = '';
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    
    if(isset($json_data->ID_WP)) {
        $id_wp = $json_data->ID_WP;
    }
    if ($id_wp) {
        print_error(400, "id_wp must be null when creating new object");
    }
    $progetto = $progettiWpManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $progetto]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);

    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }

    $wp_su_db = $progettiWpManager->get_wp($json_data->ID_PROGETTO, $json_data->ID_WP);
    if (!$wp_su_db) {
        print_error(404, 'Not found');
    }
    $wp = $progettiWpManager->aggiorna($wp_su_db, $json_data);

    header('Content-Type: application/json');
    echo json_encode(['value' => $wp]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$id_wp) {
        print_error(400, 'Missing id_wp');
    }
    $progettiWpManager->elimina($id_wp, $id_progetto);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>