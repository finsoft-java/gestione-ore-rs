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
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$search = isset($_GET['search']) ? $con->escape_string($_GET['search']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;
$mostra_solo_validi = isset($_GET['mostra_solo_validi']) ? ($con->escape_string($_GET['mostra_solo_validi']) === 'true' ? true : false) : false;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($id_progetto) {
        
        //==========================================================
        $progetto = $progettiManager->get_progetto($id_progetto);
        if (!$progetto) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $progetto]);
    } else {
        //==========================================================
        [$progetti, $count] = $progettiManager->get_progetti($top, $skip, $orderby, $search, $mostra_solo_validi);
          
        header('Content-Type: application/json');
        echo json_encode(['data' => $progetti, 'count' => $count]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    $id_progetto = '';
    
    if(isset($json_data->id_progetto)){
        $id_progetto = $json_data->id_progetto;
    }
    
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if ($id_progetto) {
        print_error(400, "id_progetto must be null when creating new project");
    }
    if (!$progettiManager->utente_puo_creare_progetti()) {
        print_error(403, "Utente non autorizzato a creare progetti.");
    }
    $progetto = $progettiManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $progetto]);
    
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
    
    $aggiorna_solo_lo_stato = false;
    
    if ($progetto_su_db->is_gia_compilato()) {
        if ($json_data->stato == '3' ) {
            $aggiorna_solo_lo_stato = true;
        } elseif($progetto_su_db->stato == '3' and $json_data->stato == '1') {
            $aggiorna_solo_lo_stato = true;
        } else {
            $aggiorna_solo_lo_stato = true;
            //print_error(403, "Non e' possibile modificare un progetto con questionari già compilati.");
        }
    }

    if ($aggiorna_solo_lo_stato) {
        $progettiManager->cambia_stato($progetto_su_db, $json_data->stato);
    } else {
        $progettiManager->aggiorna($progetto_su_db, $json_data);
    }
    
    $progetto_su_db = $progettiManager->get_progetto($json_data->id_progetto);
    header('Content-Type: application/json');
    echo json_encode(['value' => $progetto_su_db]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$id_progetto) {
        print_error(400, 'Missing id_progetto');
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
    
    $progettiManager->elimina($id_progetto);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>