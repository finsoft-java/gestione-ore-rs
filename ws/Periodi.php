<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

//TODO scommenta
// require_logged_user_JWT();


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    $objects = $commesseManager->get_periodi();
        
    header('Content-Type: application/json');
    echo json_encode(['data' => $objects, 'count' => count($objects)]);
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>