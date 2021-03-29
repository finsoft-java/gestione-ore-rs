<?php

include("include/all.php");    
$con = connect();
$panthera->connect();


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
//require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    $result = $panthera->getTipiCosto();

    header('Content-Type: application/json');
    echo json_encode(['data' => $result]);

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>