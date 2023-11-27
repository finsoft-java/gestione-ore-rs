<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();
$dataInizio = isset($_GET['dataInizio']) ? $con->escape_string($_GET['dataInizio']) : null;
$dataFine = isset($_GET['dataFine']) ? $con->escape_string($_GET['dataFine']) : null;
$month = isset($_GET['month']) ? $con->escape_string($_GET['month']) : null;
$matricola = isset($_GET['matricola']) ? $con->escape_string($_GET['matricola']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;
$dettagli = isset($_GET['dettagli']) ? $con->escape_string($_GET['dettagli']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    if($dettagli != null ){
        [$countComm, $countDip, $countPart] = $rapportini->get_ore_commesseDettagli($month, $matricola, $dataInizio, $dataFine);
        header('Content-Type: application/json');
        echo json_encode(['data' => [$countComm, $countDip, $countPart], 'count' => $null]);
    } else {
        [$objects, $count] = $rapportini->get_ore_commesse($skip, $top, $orderby, $month, $matricola, $dataInizio, $dataFine);
        header('Content-Type: application/json');
        echo json_encode(['data' => $objects, 'count' => $count]);
    }
        
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>