<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

include("include/all.php");    
$con = connect();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


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
    $primo = "DATE('$anno-$mese-01')";

    $query_matr_wp = "SELECT p.ID_PROGETTO, p.ACRONIMO, p.TITOLO, p.GRANT_NUMBER, p.MATRICOLA_SUPERVISOR, " .
                "wp.ID_WP, wp.TITOLO, wp.DESCRIZIONE, wp.DATA_INIZIO, wp.DATA_FINE, " .
                "r.MATRICOLA_DIPENDENTE " .
                "FROM progetti p " .
                "JOIN progetti_wp wp ON wp.ID_PROGETTO=p.ID_PROGETTO " .
                "JOIN progetti_wp_risorse r ON wp.ID_PROGETTO=r.ID_PROGETTO AND wp.ID_WP=r.ID_WP " .
                "WHERE wp.DATA_FINE >= $primo AND wp.DATA_INIZIO <= LAST_DAY($primo)";
    $matricole_wp = select_list($query_matr_wp);

    $query_consuntivo = "SELECT ID_PROGETTO,ID_WP,MATRICOLA_DIPENDENTE,DATA,ORE_LAVORATE " .
                "FROM ore_consuntivate " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo)";
    $consuntivo = select_list($query_consuntivo);
    
    // trasformo i due array $matricole_wp e $consuntivo in una struttura $map_progetti_matricole_wp
    $map_progetti_matricole_wp = array();
    foreach($matricole_wp as $row) {
        $idprogetto = $row["ID_PROGETTO"];
        $matr = $row["MATRICOLA_DIPENDENTE"];
        $idwp = $row["ID_WP"];
        if (! array_key_exists($idprogetto, $map_progetti_matricole_wp)) $map_progetti_matricole_wp[$idprogetto] = array();
        if (! array_key_exists($matr, $map_progetti_matricole_wp[$idprogetto])) $map_progetti_matricole_wp[$idprogetto][$matr] = array();
        $map_progetti_matricole_wp[$idprogetto][$matr][$idwp] = $row;
    }
    foreach ($consuntivo as $row) {
        $idprogetto = $row["ID_PROGETTO"];
        $matr = $row["MATRICOLA_DIPENDENTE"];
        $idwp = $row["ID_WP"];
        $wp = $map_progetti_matricole_wp[$idprogetto][$matr][$idwp];
        if (! array_key_exists($wp["ORE_LAVORATE"], $wp)) $wp["ORE_LAVORATE"] = array();
        $wp["ORE_LAVORATE"][] = $row;
    }
    
    $zip = new ZipArchive;
    $zipfilename = tempnam(null, "export");
    if (! $zip->open($zipfilename, ZipArchive::CREATE)) {
        print_error(500, 'Cannot create ZIP file');
    }
    $tempfiles = [$zipfilename];
    
    // MAIN LOOP
    foreach ($map_progetti_matricole_wp as $idProgetto => $map_matricole_wp) {
        foreach ($map_matricole_wp as $matr => $map_matr_wp) {
            
            // TODO Qui devo creare un file Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Hello World !');
            $writer = new Xlsx($spreadsheet);
            $xlsxfilename = tempnam(null, "Rapportini");
            $writer->save($xlsxfilename);
            $xlsxfilename_final = 'Rapportini_' . $idProgetto . '_' . $matr . '.xlsx';
            
            // addFile() non salva il file su disco, viene salvato al close()
            $zip->addFile($xlsxfilename, $xlsxfilename_final);

            $tempfiles[] = $xlsxfilename;
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