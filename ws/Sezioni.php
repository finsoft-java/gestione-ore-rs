<?php

// In un'ottica REST, questi URL sarebbero meglio: /Questionari(123)/Sezioni ma col PHP è un casino...
//
// Prevedo le seguenti richieste:
// GET Sezioni?id_questionario=xxx                              -> lista di tutti i Sezioni
// GET Sezioni?id_questionario=xxx&progressivo_sezione=yyy      -> singola Sezione
// PUT Sezioni                                                  -> creazione nuova Sezione
// POST Sezioni                                                 -> update Sezione esistente
// DELETE Sezioni?id_questionario=xxx&progressivo_sezione=yyy   -> elimina Sezione esistente

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
if (isset($_GET['progressivo_sezione'])) {
    $progressivo_sezione = $con->escape_string($_GET['progressivo_sezione']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (!$id_questionario) {
        print_error(400, 'Missing id_questionario');
    }
    if ($progressivo_sezione) {
        
        //==========================================================
        $questionario = $questionariManager->get_questionario($id_questionario);
        if (!$questionario) {
            print_error(404, 'Not found');
        }
        $sezione = $questionario->get_sezione($progressivo_sezione);
        if (!$sezione) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $sezione]);
        
    } else {
        //==========================================================
        $sezioni = $questionariManager->get_questionario($id_questionario)->get_sezioni();
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $sezioni]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
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
    if ($questionario->is_gia_compilato()) {
        print_error(403, "Questionario non modificabile perchè già compilato.");
    }
    
    // Il progressivo_sezione può essere impostato dall'interfaccia, ma anche no
    if (!$json_data->progressivo_sezione) {
        $json_data->progressivo_sezione = $questionario->get_prossima_sezione();
    }
    
    $sezione = $sezioniManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $sezione]);
    
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
        print_error(403, "Questionario non modificabile perchè già compilato.");
    }
    */
    $sezione = $questionario->get_sezione($json_data->progressivo_sezione);
    if (!$sezione) {
        print_error(404, 'Not found');
    }
    $sezioniManager->aggiorna($sezione, $json_data);

    header('Content-Type: application/json');
    echo json_encode(['value' => $sezione]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_questionario) {
        print_error(404, 'Missing id_questionario');
    }
    if (!$progressivo_sezione) {
        print_error(400, 'Missing progressivo_sezione');
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
    $sezione = $questionario->get_sezione($progressivo_sezione);
    if (!$sezione) {
        print_error(404, 'Not found');
    }
    $sezioniManager->elimina($id_questionario,$progressivo_sezione);

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>