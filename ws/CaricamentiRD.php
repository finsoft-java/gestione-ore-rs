<?php

include("include/all.php");
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$idCaricamento = isset($_GET['idCaricamento']) ? $con->escape_string($_GET['idCaricamento']) : null;
$top = isset($_GET['top']) ? $con->escape_string($_GET['top']) : null;
$skip = isset($_GET['skip']) ? $con->escape_string($_GET['skip']) : null;
$orderby = isset($_GET['orderby']) ? $con->escape_string($_GET['orderby']) : null;

$matricola = isset($_GET['matricola']) ? $con->escape_string($_GET['matricola']) : null;
$month = isset($_GET['month']) ? $con->escape_string($_GET['month']) : null;
$dataInizio = isset($_GET['dataInizio']) ? $con->escape_string($_GET['dataInizio']) : null;
$dataFine = isset($_GET['dataFine']) ? $con->escape_string($_GET['dataFine']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================
    [$objects, $count] = $rapportini->get_caricamenti_rd($skip, $top, $orderby, $matricola, $month, $dataInizio, $dataFine);
        
    header('Content-Type: application/json');
    echo json_encode(['data' => $objects, 'count' => $count]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //==========================================================
    if (!$idCaricamento) {
        print_error(400, 'Missing idCaricamento');
    }
    $oggetto_su_db = $rapportini->get_caricamento_rd($idCaricamento);
    if (!$oggetto_su_db) {
        print_error(404, 'Not found');
    }
    
    $rapportini->elimina_caricamento_rd($idCaricamento);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>