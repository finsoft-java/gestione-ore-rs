<?php

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
if (isset($_GET['progressivo_domanda'])) {
    $progressivo_domanda = $con->escape_string($_GET['progressivo_domanda']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (!$id_questionario) {
        print_error(400, 'Missing id_questionario');
    }
    if (!$progressivo_sezione) {
        print_error(400, 'Missing progressivo_sezione');
    }
    $questionario = $questionariManager->get_questionario($id_questionario);
    if (!$questionario) {
        print_error(404, 'Not found');
    }
    $sezione = $questionario->get_sezione($progressivo_sezione);
    if (!$sezione) {
        print_error(404, 'Not found');
    }
    if ($progressivo_domanda) {
        $domanda = $sezione->get_domanda($progressivo_domanda);
        if (!$domanda) {
            print_error(404, 'Not found');
        }
        //==========================================================
        header('Content-Type: application/json');
        echo json_encode(['value' => $domanda]);
        
    } else {
        //==========================================================
        $domande = $sezione->get_domande();
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $domande]);
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
    $sezione = $questionario->get_sezione($json_data->progressivo_sezione);
    $domanda = $sezioniManager->creaDomandaERisposte($sezione, $json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $domanda]);
    
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
    $domanda = $sezione->get_domanda($json_data->progressivo_domanda);
    $domanda = $sezioniManager->aggiornaDomandaERisposte($domanda, $json_data);

    header('Content-Type: application/json');
    echo json_encode(['value' => $domanda]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    $id_questionario =$_GET['id_questionario'];
    $progressivo_sezione =$_GET['progressivo_sezione'];
    $progressivo_domanda =$_GET['progressivo_domanda'];
    
    if (!$id_questionario) {
        print_error(404, 'Missing id_questionario');
    }
    if (!$progressivo_sezione) {
        print_error(400, 'Missing progressivo_sezione');
    }
    if (!$progressivo_domanda) {
        print_error(400, 'Missing progressivo_domanda');
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
    $sezioniManager->eliminaDomandaERisposte($id_questionario, $progressivo_sezione, $progressivo_domanda);
    $sezione = $questionario->get_sezione($progressivo_sezione);
    if(count($sezione->domande) == 0){
        $questionariManager->cambia_stato($questionario, '0');
    }
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>