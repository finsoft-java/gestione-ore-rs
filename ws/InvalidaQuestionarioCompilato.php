<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

if (isset($_GET['progressivo_quest_comp'])) {
    $progressivo_quest_comp = $con->escape_string($_GET['progressivo_quest_comp']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    
    $questionario_compilato = $questionariCompilatiManager->get_questionario_compilato($progressivo_quest_comp);
    if (!$questionario_compilato) {
        print_error(404, 'Not found');
    }
    
    // L'amministratore può confermarlo oppure annullarlo
    // L'utente può solo confermare il suo lavoro
    if (utente_admin()) {
        $questionariCompilatiManager->cambia_stato($questionario_compilato, '2');
    } else {
        print_error(403, "Utente non autorizzato alla modifica di questo Questionario Compilato");
    }
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>