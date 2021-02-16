<?php

include("include/all.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $resp = [];
    $resp["num_questionari"] = $questionariManager->count();
    $resp["num_progetti"] = $progettiManager->count();
    $resp["num_compilazioni"] = $questionariCompilatiManager->count();
    $resp["num_risposte"] = $questionariCompilatiManager->count_risposte();

    header('Content-Type: application/json');
    echo json_encode(['value' => $resp]);

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}



?>