<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

if (isset($_GET['id_progetto'])) {
    $id_progetto = $con->escape_string($_GET['id_progetto']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$progettiManager->utente_puo_creare_progetti()) {
        print_error(403, "Utente non autorizzato a creare progetti.");
    }
    
    $progetto = $progettiManager->get_progetto($id_progetto);
    if (!$progetto) {
        print_error(404, 'Not found');
    }
    $nuovo_progetto = $progettiManager->duplica($progetto);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $nuovo_progetto]);
} else {
    //===========================================================
    print_error(406, "Unsupported method: " . $_SERVER['REQUEST_METHOD']);
}


?>