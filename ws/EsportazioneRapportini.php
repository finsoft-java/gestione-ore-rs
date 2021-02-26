<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM

include("include/all.php");    
$con = connect();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit(); 
}
    
require_logged_user_JWT();

function carica_dati_da_db($anno, $mese) {
    $primo = "DATE('$anno-$mese-01')";

    $query_matr_wp = "SELECT p.ID_PROGETTO, p.ACRONIMO, p.TITOLO AS TITOLO_P, p.GRANT_NUMBER, p.MATRICOLA_SUPERVISOR, " .
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
        $wp = $map_progetti_matricole_wp[$idprogetto][$matr][$idwp];
        if (! isset($wp["ORE_LAVORATE"])) $wp["ORE_LAVORATE"] = array();
        $wp["ORE_LAVORATE"][$data->format('j')] = $row["ORE_LAVORATE"];
        $map_progetti_matricole_wp[$idprogetto][$matr][$idwp] = $wp;
    }
    
    return $map_progetti_matricole_wp;
}

function creaFileExcel($idProgetto, $matr, $anno, $mese, $map_wp_wp, $zip, $tempfiles) {
    
    $wp = $map_wp_wp[array_keys($map_wp_wp)[0]]; // uno a caso
    
    global $panthera;
    $nomecognome = $panthera->getUtente($matr);
    $nomecognome_super = $panthera->getUtente($wp['MATRICOLA_SUPERVISOR']);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $curRow = 1;
    
    creaIntestazione($sheet, $curRow, $wp, $anno, $mese, $matr, $nomecognome);
    creaLegenda($sheet, $curRow, $map_wp_wp);
    creaTabella($sheet, $curRow, $map_wp_wp, $anno, $mese, $nomecognome);
    creaFooter($sheet, $curRow, $wp, $nomecognome, $nomecognome_super);

    $writer = new Xlsx($spreadsheet);
    $xlsxfilename = tempnam(null, "Rapportini");
    $writer->save($xlsxfilename);
    $xlsxfilename_final = 'Rapportini_' . $idProgetto . '_' . $matr . '.xlsx';
    
    // addFile() non salva il file su disco, viene salvato al close()
    $zip->addFile($xlsxfilename, $xlsxfilename_final);

    $tempfiles[] = $xlsxfilename;
}

function creaIntestazione($sheet, &$curRow, $wp, $anno, $mese, $matr, $nomecognome) {
    
    $sheet->setCellValue('H' . $curRow, $wp['TITOLO_P']);
    $sheet->getStyle('H' . $curRow)->getFont()->setBold(true);
    $sheet->getStyle('H' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->setCellValue('I' . $curRow, 'project');
    $sheet->setCellValue('O' . $curRow, 'Grant');
    $sheet->getStyle('O' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->setCellValue('P' . $curRow, $wp['GRANT_NUMBER']);
    $sheet->setCellValue('U' . $curRow, $wp['ACRONIMO']);
    $curRow++;
    $curRow++;
    $sheet->setCellValue('A' . $curRow, 'TIMESHEET');
    $sheet->setCellValue('G' . $curRow, 'year');
    $sheet->setCellValue('J' . $curRow, "$anno");
    $sheet->getStyle('J' . $curRow)->setQuotePrefix(true);
    
    creaLogo($sheet, './images/logo.png', 50, 'Logo', 'X2');
}

function creaLogo($sheet, $path, $height, $caption, $coordinates) {
    // see https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#add-a-drawing-to-a-worksheet
    $drawing = new Drawing();
    $drawing->setName($caption);
    $drawing->setDescription($caption);
    $drawing->setPath($path);
    $drawing->setHeight($height);
    $drawing->setCoordinates($coordinates);
    $drawing->setWorksheet($sheet);
}

function creaLegenda($sheet, &$curRow, $map_wp_wp) {
    $curRow += 3;
    $sheet->setCellValue('A' . $curRow, 'Activities description');
    $sheet->setCellValue('I' . $curRow, 'Start');
    $sheet->setCellValue('J' . $curRow, 'End');
    foreach($map_wp_wp as $idwp => $wp) {
        $curRow++;
        $sheet->setCellValue('A' . $curRow, $wp["TITOLO"]);
        $sheet->getStyle('A' . $curRow)->getFont()->setBold(true);
        $sheet->setCellValue('B' . $curRow, $wp["DESCRIZIONE"]);
        $sheet->setCellValue('I' . $curRow, '??');
        $sheet->setCellValue('J' . $curRow, '??');
    }
}

function creaTabella($sheet, &$curRow, $map_wp_wp, $anno, $mese, $nomecognome) {
    $curRow += 3;
    $sheet->setCellValue('A' . $curRow, 'Activity details and daily working hours on ADIR project');
    $sheet->setCellValue('P' . $curRow, 'Full name of the person working in the action:');
    $sheet->setCellValue('Z' . $curRow, $nomecognome);
    $first_day = new DateTime("$anno-$mese-01"); 
    $num_days = $first_day->format('t');

    $OFFSET = 2;
    
    // Imposto la width delle colonne
    for ($i = 0; $i < 32; ++$i) {
        $sheet->getColumnDimensionByColumn($i + $OFFSET)->setWidth(5);
    }
    // In alto i giorni
    $curRow++;
    for ($i = 0; $i < $num_days; ++$i) {
        $curCol = $i + $OFFSET;
        $sheet->setCellValueByColumnAndRow($curCol, $curRow, $i);
        $sheet->getStyleByColumnAndRow($curCol, $curRow)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
    }
    $sheet->setCellValueByColumnAndRow($num_days + $OFFSET, $curRow, 'TOT');

    // day of week
    $curRow++;
    for ($i = 0; $i < $num_days; ++$i) {
        $d = new DateTime("$anno-$mese-$i");
        $curCol = $i + $OFFSET;
        $sheet->setCellValueByColumnAndRow($curCol, $curRow, $d->format('D'));
        $sheet->getStyleByColumnAndRow($curCol, $curRow)->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow($curCol, $curRow)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
    }
    
    // TODO assenze / festivita

    // A sinistra le WP
    $row_prima_riga = $curRow + 1;
    foreach($map_wp_wp as $idwp => $wp) {
        ++$curRow;
        $sheet->setCellValue('A' . $curRow, $wp["TITOLO"]);
        $sheet->getStyle('A' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $curRow)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
        // In mezzo, le ore consuntivate
        if (isset($wp["ORE_LAVORATE"]) && ! empty($wp["ORE_LAVORATE"])) {
            for ($i = 0; $i < $num_days; ++$i) {
                $sheet->getStyleByColumnAndRow($i + $OFFSET, $curRow)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                if (isset($wp["ORE_LAVORATE"][$i])) {
                    $val = $wp["ORE_LAVORATE"][$i];
                    $sheet->setCellValueByColumnAndRow($i + $OFFSET, $curRow, $val);
                }
            }
        }
        // A destra, totale di riga
        $letter_last = Coordinate::stringFromColumnIndex($num_days - 1 + $OFFSET, $curRow);
        $formula = '=SUM(B'.$curRow.':'.$letter_last.$curRow.')';
        $sheet->setCellValueByColumnAndRow($num_days + $OFFSET, $curRow, $formula);
        $sheet->getStyleByColumnAndRow($num_days + $OFFSET, $curRow)->getFont()->setBold(true);
    }
    $row_ultima_riga = $curRow;

    // Totali di colonna
    ++$curRow;
    $sheet->setCellValue('A' . $curRow, "TOT");
    for ($i = 0; $i <= $num_days; ++$i) {
        $letter = Coordinate::stringFromColumnIndex($i + $OFFSET, $curRow);
        $formula = '=SUM('.$letter.$row_prima_riga.':'.$letter.$row_ultima_riga.')';
        $sheet->setCellValueByColumnAndRow($i + $OFFSET, $curRow, $formula);
        $sheet->getStyleByColumnAndRow($i + $OFFSET, $curRow)->getFont()->setBold(true);
    }
}

function creaFooter($sheet, &$curRow, $wp, $nomecognome, $nomecognome_super) {
    $curRow += 3;
    $sheet->setCellValue('E' . $curRow, 'Working person:');
    $sheet->setCellValue('J' . $curRow, $nomecognome);
    $sheet->setCellValue('P' . $curRow, 'Signature');
    $sheet->setCellValue('S' . $curRow, '(FIXME Data firma)');
    $curRow++;
    $sheet->setCellValue('E' . $curRow, 'Supervisor:');
    $sheet->setCellValue('J' . $curRow, $nomecognome_super);
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