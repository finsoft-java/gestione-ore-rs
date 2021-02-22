<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$id_progetto = '';
if (isset($_GET['id_progetto'])) {
    $id_progetto = $con->escape_string($_GET['id_progetto']);
}
if (isset($_GET['id_questionario'])) {
    $id_questionario = $con->escape_string($_GET['id_questionario']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($id_progetto) {
        
        //==========================================================
        $pq = $progettiManager->get_progetto_questionari($id_progetto, $id_questionario);
        if (!$pq) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $pg[0]]);
    } else {
        print_error(400, "Sono obbligatori id_progetto e id_questionario");
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $progetto_su_db = $progettiManager->get_progetto($json_data->id_progetto);
    if (!$progetto_su_db) {
        print_error(404, 'Not found');
    }
    if (!$progetto_su_db->utente_puo_modificarlo()) {
        print_error(403, "Utente non autorizzato a modificare questo Progetto.");
    }
    /*
    if ($progetto_su_db->is_gia_compilato()) {
        print_error(403, "Esistono questionari già compilati, le uniche modifiche permesse sono la conferma e la riapertura");
    }
    */
    $pq = $progettiQuestionariManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $pq]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    $progetto_su_db = $progettiManager->get_progetto($json_data->id_progetto);
    if (!$progetto_su_db) {
        print_error(404, 'Not found');
    }
    if (!$progetto_su_db->utente_puo_modificarlo()) {
        print_error(403, "Utente non autorizzato a modificare questo Progetto.");
    }
    if ($progetto_su_db->is_gia_compilato()) {
        print_error(403, "Esistono questionari già compilati, le uniche modifiche permesse sono la conferma e la riapertura");
    }
/*
    if ($aggiorna_solo_lo_stato) {
        $progettiManager->cambia_stato($progetto_su_db, $json_data->stato);
    } else {
        $progettiManager->aggiorna($progetto_su_db, $json_data);
    }
*/
    $pq = $progettiQuestionariManager->get_progetto_questionari($json_data->id_progetto, $json_data->id_questionario);
    $progettiQuestionariManager->aggiorna($pq, $json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $pq]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_progetto = $json_data->id_progetto;
    $id_questionario = $json_data->id_questionario;
    
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
    }
    if (!$id_questionario) {
        print_error(400, 'Missing id_questionario');
    }
    $progetto_su_db = $progettiManager->get_progetto($id_progetto);
    if (!$progetto_su_db) {
        print_error(404, 'Not found');
    }
    if (!$progetto_su_db->utente_puo_modificarlo()) {
        print_error(403, "Utente non autorizzato a modificare questo Progetto.");
    }
    if ($progetto_su_db->is_gia_compilato()) {
        print_error(403, "Non e' possibile eliminare un progetto con questionari già compilati.");
    }
    
    $progettiQuestionariManager->elimina($id_progetto, $id_questionario);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>