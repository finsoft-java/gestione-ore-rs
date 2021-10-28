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


$periodo = isset($_GET['periodo']) ? $con->escape_string($_GET['periodo']) : null;
$isEsploso = isset($_GET['isEsploso']) ? $con->escape_string($_GET['isEsploso']) === "true" : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    // CONTROLLO PARAMETRI

    if ($periodo == null) {
        print_error(400, "Missing parameter: periodo");
    }
    if (strlen($periodo) != 7) {
        print_error(400, "Bad parameter: Il periodo di lancio deve essere nella forma YYYY-MM");
    }
    $anno = substr($periodo, 0, 4);
    $mese = substr($periodo, 5, 2);

    $zipfilename = $rapportini->creaZip($anno, $mese, $isEsploso);
    
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