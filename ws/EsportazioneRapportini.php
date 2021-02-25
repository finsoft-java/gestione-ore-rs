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

function carica_dati_da_db($anno, $mese) {
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
        $data = new DateTime($row["DATA"]);
        $curCol = $i + 2;
        $wp = $map_progetti_matricole_wp[$idprogetto][$matr][$idwp];
        if (! array_key_exists($wp["ORE_LAVORATE"], $wp)) $wp["ORE_LAVORATE"] = array();
        $wp["ORE_LAVORATE"][$data->format('j')] = $row;
    }
    
    return $map_progetti_matricole_wp;
}

function creaFileExcel($idProgetto, $matr, $anno, $mese, $map_wp_wp, $zip, $tempfiles) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $curRow = 1;
    
    creaIntestazione($sheet, $curRow, $map_wp_wp, $anno, $mese, $matr);
    creaLegenda($sheet, $curRow, $map_wp_wp);
    creaTabella($sheet, $curRow, $map_wp_wp, $anno, $mese);
    creaFooter($sheet, $curRow, $map_wp_wp);

    $writer = new Xlsx($spreadsheet);
    $xlsxfilename = tempnam(null, "Rapportini");
    $writer->save($xlsxfilename);
    $xlsxfilename_final = 'Rapportini_' . $idProgetto . '_' . $matr . '.xlsx';
    
    // addFile() non salva il file su disco, viene salvato al close()
    $zip->addFile($xlsxfilename, $xlsxfilename_final);

    $tempfiles[] = $xlsxfilename;
}

function creaIntestazione($sheet, &$curRow, $map_wp_wp, $anno, $mese, $matr) {
    $curRow++;
    $sheet->setCellValue('A' . $curRow, 'OSAI');
    $curRow++;
    $sheet->setCellValue('A' . $curRow, 'TIMESHEET');
    // FIXME Mi manca il "Full name of the person working in the action:"
}

function creaLegenda($sheet, &$curRow, $map_wp_wp) {
    $curRow += 3;
    $sheet->setCellValue('A' . $curRow, 'Activities description');
    foreach($map_wp_wp as $idwp => $wp) {
        $curRow++;
        $sheet->setCellValue('A' . $curRow, $wp["TITOLO"]);
        $sheet->setCellValue('B' . $curRow, $wp["DESCRIZIONE"]);
    }
}

function creaTabella($sheet, &$curRow, $map_wp_wp, $anno, $mese) {
    $curRow += 3;
    $sheet->setCellValue('A' . $curRow, 'Activity details and daily working hours on ADIR project');
    $first_day = new DateTime("$anno-$mese-01"); 
    $num_days = $first_day->format('t');

    // Imposto la width delle colonne
    for ($i = 0; $i < 32; ++$i) {
        $sheet->getColumnDimensionByColumn($i + 2)->setWidth(4);
    }
    // In alto i giorni
    $curRow++;
    for ($i = 0; $i < $num_days; ++$i) {
        $curCol = $i + 2;
        $sheet->setCellValueByColumnAndRow($curCol, $curRow, $i);
    }
    $sheet->setCellValueByColumnAndRow($i + 2, $curRow, 'TOT');

    // day of week
    $curRow++;
    for ($i = 0; $i < $num_days; ++$i) {
        $d = new DateTime("$anno-$mese-$i");
        $curCol = $i + 2;
        $sheet->setCellValueByColumnAndRow($curCol, $curRow, $d->format('D'));
    }
    
    // TODO assenze / festivita

    // A sinistra le WP
    foreach($map_wp_wp as $idwp => $wp) {
        ++$curRow;
        $sheet->setCellValue('A' . $curRow, $wp["TITOLO"]);
        // In mezzo, le ore consuntivate
        if (isset($wp["ORE_LAVORATE"]) && ! empty($wp["ORE_LAVORATE"])) {
            for ($i = 0; $i < $num_days; ++$i) {
                if (isset($wp["ORE_LAVORATE"][$i]))
                $curCol = $i + 2;
                $sheet->setCellValueByColumnAndRow($curCol, $curRow, $wp["ORE_LAVORATE"][$i]);
            }
        }
    }
    // TODO Totali
}

function creaFooter($sheet, &$curRow, $map_wp_wp) {
    $curRow += 3;
    $sheet->setCellValue('E' . $curRow, 'Working person:');
    $sheet->setCellValue('J' . $curRow, '(FIXME nome cognome)');
    $sheet->setCellValue('P' . $curRow, 'Signature');
    $sheet->setCellValue('S' . $curRow, '(FIXME Data firma)');
    $curRow++;
    $sheet->setCellValue('E' . $curRow, 'Supervisor:');
    $sheet->setCellValue('J' . $curRow, '(FIXME nome cognome)');
    $sheet->setCellValue('P' . $curRow, 'Signature');
    $sheet->setCellValue('S' . $curRow, '(FIXME Data firma)');
}



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
    $map_progetti_matricole_wp = carica_dati_da_db($anno, $mese);
    
    $zip = new ZipArchive;
    $zipfilename = tempnam(null, "export");
    if (! $zip->open($zipfilename, ZipArchive::CREATE)) {
        print_error(500, 'Cannot create ZIP file');
    }
    $tempfiles = [$zipfilename];
    
    // MAIN LOOP
    foreach ($map_progetti_matricole_wp as $idProgetto => $map_matricole_wp) {
        foreach ($map_matricole_wp as $matr => $map_wp_wp) {
            creaFileExcel($idProgetto, $matr, $anno, $mese, $map_wp_wp, $zip, $tempfiles);
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