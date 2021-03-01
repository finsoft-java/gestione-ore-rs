<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

include("include/all.php");    
$con = connect();

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

    // REPERIRE DATI DA DB
    $map_progetti_matricole_wp = $rapportini->carica_dati_da_db($anno, $mese);
    
    $zip = new ZipArchive;
    $zipfilename = tempnam(null, "export");
    if (! $zip->open($zipfilename, ZipArchive::CREATE)) {
        print_error(500, 'Cannot create ZIP file');
    }
    $tempfiles = [$zipfilename];
    
    // MAIN LOOP
    foreach ($map_progetti_matricole_wp as $idProgetto => $map_matricole_wp) {
        foreach ($map_matricole_wp as $matr => $map_wp_wp) {
            $rapportini->creaFileExcel($idProgetto, $matr, $anno, $mese, $map_wp_wp, $zip, $tempfiles);
        }
    }

    $zip->close();
    
    //DOWNLOAD ZIP
    $zipfilename_final = "export.zip";

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipfilename_final . '"');
    header('Content-Length: ' . filesize($zipfilename));
    flush();
    readfile($zipfilename);

    // DELETE TEMP FILES
    foreach($tempfiles as $t) unlink($t);
    
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>