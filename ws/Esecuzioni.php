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

$idEsecuzione = isset($_GET['idEsecuzione']) ? $con->escape_string($_GET['idEsecuzione']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    [$objects, $count] = $consuntiviProgettiManager->get_esecuzioni($skip, $top, $orderby);
        
    header('Content-Type: application/json');
    echo json_encode(['data' => $objects, 'count' => $count]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$idEsecuzione) {
        print_error(400, 'Missing idEsecuzione');
    }
    $oggetto_su_db = $consuntiviProgettiManager->get_esecuzione($idEsecuzione);
    if (!$oggetto_su_db) {
        print_error(404, 'Not found');
    }
    if ($oggetto_su_db['IS_ASSEGNATE'] == '1') {
        print_error(400, 'Esecuzione assegnata a progetti, prima eseguire la disassociazione');
    }
    
    $consuntiviProgettiManager->elimina_esecuzione($idEsecuzione);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>