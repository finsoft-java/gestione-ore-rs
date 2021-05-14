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



if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================

    // CONTROLLO PARAMETRI

    if (! isset($_GET['periodo']) or ! $_GET['periodo']) {
        print_error(400, "Missing parameter: periodo");
    }
    $periodo = $_GET['periodo'];
    if (strlen($periodo) != 7) {
        print_error(400, "Bad parameter: Il periodo di lancio deve essere nella forma YYYY-MM");
    }
    $anno = substr($periodo, 0, 4);
    $mese = substr($periodo, 5, 2);

    $zipfilename = $rapportini->creaZip($anno, $mese);
    
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