<?php

// Prevedo le seguenti richieste:
// OPTIONS
// GET User  -> lista di tutti gli utenti
// GET User?username=xxx  -> singolo utente
// PUT User -> creazione nuovo utente
// POST User -> update utente esistente
// DELETE User?username=xxx -> elimina utente esistente

include("include/all.php");    
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

$username = isset($_GET['username']) ? $con->escape_string($_GET['username']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$search = isset($_GET['search']) ? $con->escape_string($_GET['search']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($username) {
        
        //==========================================================
        $utente = $utenteManager->get_utente($username);
        if (!$utente) {
            print_error(404, 'Not found');
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $utente]);
    } else {
        //==========================================================
        [$utenti, $count] = $utenteManager->get_utenti($top, $skip, $orderby, $search);
          
        header('Content-Type: application/json');
        echo json_encode(['data' => $utenti, 'count' => $count]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    
    if (!utente_admin()) {
        print_error(403, "Solo gli Amministratori possono modificare la tabella Utenti.");
    }
    $utente = $utenteManager->crea($json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $utente]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if (!utente_admin()) {
        print_error(403, "Solo gli Amministratori possono modificare la tabella Utenti.");
    }
    $utente = $utenteManager->get_utente($json_data->username);
    if (!$utente) {
        print_error(404, 'Not found');
    }
    $utenteManager->aggiorna($utente, $json_data);
    
    header('Content-Type: application/json');
    echo json_encode(['value' => $utente]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$username) {
        print_error(400, 'Missing username');
    }
    if (!utente_admin()) {
        print_error(403, "Solo gli Amministratori possono modificare la tabella Utenti.");
    }
    $utenteManager->elimina($username);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>