<?php

// In un'ottica REST, questi URL sarebbero meglio: /Questionario(123)/Sezioni(2)/Copy ma col PHP è un casino...
//
// POST CopySezione?id_questionario=xxx&progressivo_sezione=yyy

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$id_questionario = '';
$progressivo_sezione = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_body = file_get_contents('php://input');
    $data_payload = json_decode($request_body);
    if (!$data_payload) {
        print_error(400, "Missing JSON data");
    }

    if (!isset($data_payload->id_questionario)) {
        print_error(400, 'Missing id_questionario');
    }else{
        $id_questionario = $con->escape_string($data_payload->id_questionario);
    }

    if (!isset($data_payload->progressivo_sezione)) {
        print_error(400, 'Missing progressivo_sezione');
    }else{
        $progressivo_sezione = $con->escape_string($data_payload->progressivo_sezione);
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
    $nuova_sezione = $sezioniManager->duplica($sezione);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $nuova_sezione]);
    
} else {
    print_error(406, "Unsupported method: " . $_SERVER['REQUEST_METHOD']);
}


?>