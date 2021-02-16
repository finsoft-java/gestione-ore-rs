<?php

// In un'ottica REST, questi URL sarebbero meglio: /Questionario(123)/Copy ma col PHP è un casino...
//
// POST CopyQuestionario?id_questionario=xxx

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

if (isset($_GET['id_questionario'])) {
    $id_questionario = $con->escape_string($_GET['id_questionario']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!$id_questionario) {
        print_error(400, 'Missing id_questionario');
    }
    if (!$questionariManager->utente_puo_creare_questionari()) {
        print_error(403, "Utente non autorizzato a creare questionari.");
    }
    
    $questionario = $questionariManager->get_questionario($id_questionario);
    if (!$questionario) {
        print_error(404, 'Not found');
    }
    $nuovo_questionario = $questionariManager->duplica($questionario);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $nuovo_questionario]);
} else {
    //===========================================================
    print_error(406, "Unsupported method: " . $_SERVER['REQUEST_METHOD']);
}


?>