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

$username = '';
if (isset($_GET['username'])) {
    $username = $con->escape_string($_GET['username']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if (!utente_admin()) {
        print_error(403, "Solo gli Amministratori possono modificare la tabella Utenti.");
    }

   $utenteManager->insert_password_utente($json_data->pwd,$json_data->username);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>