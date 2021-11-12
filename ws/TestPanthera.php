<?php

include("include/all.php");
$panthera->connect();


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
// DO NOT require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    $query = "SELECT * FROM THIP.UTENTI_AZIENDE_V01 WHERE ID_AZIENDA='001' ORDER BY DENOMINAZIONE ASC";
    $result = $panthera->select_list($query);

    header('Content-Type: application/json');
    echo json_encode(['data' => $result]);

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>