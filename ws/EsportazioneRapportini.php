<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

include("include/all.php");    
$con = connect();
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit(); 
}

require_logged_user_JWT();


$dataInizio = isset($_GET['dataInizio']) ? $con->escape_string($_GET['dataInizio']) : null;
$dataFine = isset($_GET['dataFine']) ? $con->escape_string($_GET['dataFine']) : null;
$periodo = isset($_GET['periodo']) ? $con->escape_string($_GET['periodo']) : null;
$isEsploso = isset($_GET['isEsploso']) ? $con->escape_string($_GET['isEsploso']) === "true" : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    // CONTROLLO PARAMETRI

    if ($dataInizio == null || $dataFine == null) {
        print_error(400, "Missing parameter: periodo");
    }

    $zipfilename = $rapportini->creaZip($dataInizio, $dataFine, $isEsploso);
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="export.zip"');
    header('Content-Length: ' . filesize($zipfilename));
    flush();
    readfile($zipfilename);
    unlink($zipfilename);
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>