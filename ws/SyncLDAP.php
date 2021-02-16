<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================
    
    $count = $utenteManager->sync();
    $msg = "$count[0] utenti aggiornati, $count[1] nuovi utenti inseriti.";
    if ($count[2] > 0) {
        $msg .= " $count[2] utenti non risultano presenti su LDAP.";
    }
    header('Content-Type: application/json');
    echo json_encode(["msg" => $msg]);
    //FIXME propongo di aggiungere un nuovo stato valido / annullato alla tabella degli utenti
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>