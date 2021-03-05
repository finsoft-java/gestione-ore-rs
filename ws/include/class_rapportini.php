<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

$rapportini = new RapportiniManager();

class RapportiniManager {

    function carica_wp_da_db($anno, $mese) {
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

    function creaZip($anno, $mese) {
        global $lul;
        
        // REPERIRE DATI DA DB
        $map_progetti_matricole_wp = $this->carica_wp_da_db($anno, $mese);
        $map_matr_ore = $lul->carica_da_db($anno, $mese);
        
        if (empty($map_progetti_matricole_wp)) {
            print_error(404, 'Nessun dato trovato.');
        }

        // CREA FILE ZIP VUOTO
        $zip = new ZipArchive;
        $zipfilename = tempnam(null, "export");
        if (! $zip->open($zipfilename, ZipArchive::CREATE)) {
            print_error(500, 'Cannot create ZIP file');
        }
        $tempfiles = [];
        
        // MAIN LOOP
        foreach ($map_progetti_matricole_wp as $idProgetto => $map_matricole_wp) {
            foreach ($map_matricole_wp as $matr => $map_wp_wp) {
                $xlsxfilename = $this->creaFileExcel($idProgetto, $matr, $anno, $mese, $map_wp_wp, $map_matr_ore);
                $acronimo = $map_wp_wp[array_keys($map_wp_wp)[0]]['ACRONIMO'];
                $xlsxfilename_final = 'Rapportini_' . $acronimo . '_' . $matr . '.xlsx';
                $zip->addFile($xlsxfilename, $xlsxfilename_final); // NON esegue il salvataggio
                $tempfiles[] = $xlsxfilename;
            }
        }

        $zip->close();
        
        // ELIMINO FILE TEMPORANEI (tutti tranne lo zip!)
        foreach($tempfiles as $t) unlink($t);
        
        return $zipfilename;
    }

    function creaFileExcel($idProgetto, $matr, $anno, $mese, $map_wp_wp, $map_matr_ore) {
        
        $wp = $map_wp_wp[array_keys($map_wp_wp)[0]]; // uno a caso
        
        global $panthera;
        $nomecognome = $panthera->getUtente($matr);
        $nomecognome_super = $panthera->getUtente($wp['MATRICOLA_SUPERVISOR']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $curRow = 1;
        
        $this->creaIntestazione($sheet, $curRow, $wp, $anno, $mese, $matr, $nomecognome);
        $this->creaLegenda($sheet, $curRow, $map_wp_wp);
        $this->creaTabella($sheet, $curRow, $map_wp_wp, $anno, $mese, $matr, $nomecognome, $map_matr_ore);
        $this->creaFooter($sheet, $curRow, $wp, $nomecognome, $nomecognome_super);

        $sheet->getPageSetup()->setPrintArea('A1:AH' . $curRow);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $xlsxfilename = tempnam(null, "Rapportini");
        $writer->save($xlsxfilename);
        
        return $xlsxfilename;
    }

    function creaIntestazione($sheet, &$curRow, $wp, $anno, $mese, $matr, $nomecognome) {
        
        $data = new DateTime("$anno-$mese-01");
        
        $sheet->setCellValue('H' . $curRow, $wp['TITOLO_P']);
        $sheet->getStyle('H' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle('H' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('I' . $curRow, 'project');
        $sheet->setCellValue('O' . $curRow, 'Grant');
        $sheet->getStyle('O' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('P' . $curRow, $wp['GRANT_NUMBER']);
        $sheet->setCellValue('T' . $curRow, $wp['ACRONIMO']);
        $curRow++;
        $curRow++;
        $sheet->setCellValue('A' . $curRow, 'TIMESHEET');
        $sheet->setCellValue('I' . $curRow, 'year');
        $sheet->getStyle('I' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('J' . $curRow, "$anno");
        $sheet->setCellValue('M' . $curRow, 'month');
        $sheet->getStyle('M' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('N' . $curRow, $data->format('F'));
        $sheet->getStyle('J' . $curRow)->setQuotePrefix(true);
        
        $this->insertImage($sheet, './images/logo.png', 50, 'Logo', 'X2');

        $curRow += 2;
    }

    function insertImage($sheet, $path, $height, $caption, $coordinates) {
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
        $curRow += 2;
    }

    function creaTabella($sheet, &$curRow, $map_wp_wp, $anno, $mese, $matricola, $nomecognome, $map_matr_ore) {
        $sheet->setCellValue('A' . $curRow, 'Activity details and daily working hours on ADIR project');
        $sheet->setCellValue('W' . $curRow, 'Full name of the person working in the action:');
        $sheet->getStyle('W' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('X' . $curRow, $matricola);
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
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, $i + 1);
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
        
        // A sinistra le WP
        $row_prima_riga = $curRow + 1;
        foreach($map_wp_wp as $idwp => $wp) {
            ++$curRow;
            $sheet->setCellValue('A' . $curRow, $wp["TITOLO"]);
            $sheet->getStyle('A' . $curRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $curRow)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            // In mezzo, le ore consuntivate
            if (isset($wp["ORE_LAVORATE"]) && ! empty($wp["ORE_LAVORATE"])) {
                $OFFSET2 = 1;
                for ($i = 0; $i <= $num_days; ++$i) {
                    $sheet->getStyleByColumnAndRow($i + $OFFSET2, $curRow)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    if (isset($wp["ORE_LAVORATE"][$i])) {
                        $val = $wp["ORE_LAVORATE"][$i];
                        //$asd = $i + $OFFSET2;
                        //echo 'colonna -> '.$asd.'<-';
                        //echo 'VALORE -> '.$val.'<-';
                        $sheet->setCellValueByColumnAndRow($i + $OFFSET2, $curRow, $val);
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
        
        // Dati dei LUL
        ++$curRow;
        $sheet->setCellValue('A' . $curRow, "LUL");
        for ($i = 0; $i <= $num_days; ++$i) {
            if (!isset($map_matr_ore[$i])) $map_matr_ore[$i] = 0;
            $sheet->setCellValueByColumnAndRow($i + $OFFSET, $curRow, $map_matr_ore[$i]);
        }

        $curRow += 3;
    }

    function creaFooter($sheet, &$curRow, $wp, $nomecognome, $nomecognome_super) {
        $sheet->setCellValue('E' . $curRow, 'Working person:');
        $sheet->setCellValue('J' . $curRow, $nomecognome);
        $sheet->setCellValue('P' . $curRow, 'Signature');
        $sheet->setCellValue('S' . $curRow, '(FIXME Data firma)');
        $curRow++;
        $sheet->setCellValue('E' . $curRow, 'Supervisor:');
        $sheet->setCellValue('J' . $curRow, $nomecognome_super);
        $sheet->setCellValue('P' . $curRow, 'Signature');
        $sheet->setCellValue('S' . $curRow, '(FIXME Data firma)');
        $curRow += 2;
    }

    function importExcel($filename, &$message) {
        global $con;

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadSheet = $reader->load($filename);
        $excelSheet = $spreadSheet->getActiveSheet();
        $spreadSheetAry = $excelSheet->toArray();
        
        $numRows = count($spreadSheetAry);
        if(isset($spreadSheetAry[0][7])){
            $titolo_progetto = $spreadSheetAry[0][7];
        } else {
            $message .= 'Bad file. Non riesco a identificare le corrette colonne del file.<br/>';
            return;
        }
        $id_progetto = select_single_value("SELECT ID_PROGETTO FROM PROGETTI WHERE TITOLO='$titolo_progetto'"); // FIXME chiave unica?!?        
        if (empty($id_progetto)) {
            $message .= 'Bad file. Non riesco a identificare il titolo del progetto.<br/>';
            return;
        }
        
        $anno = $spreadSheetAry[2][9];
        if (empty($anno)) {
            $message .= 'Bad file. Non riesco a identificare l\'anno del rapportino.<br/>';
            return;
        }
        $mese = $spreadSheetAry[2][13]; // e.g. February
        if (empty($mese)) {
            $message .= 'Bad file. Non riesco a identificare il mes del rapportino.<br/>';
            return;
        }
        $mese = date_parse($mese)['month'];

        $matricola = '';
        // skip first rows
        for ($i = 0; $i <= $numRows; ++$i) {
            if (strpos($spreadSheetAry[$i][0], 'Activity details') !== false) {
                break;
            }
        }
        
        if ($i == $numRows) {
            $message .= 'Bad file. Non trovo la stringa "Activity details".<br/>';
            return;
        }
        
        $matricola = $spreadSheetAry[$i][23];
        if (empty($matricola)) {
            $message .= 'Bad file. Non riesco a identificare la matricola utente.</br>';
            return;
        }
        
        ++$i;
        $riga_date = $i;
        ++$i;
        
        for ( ; $i <= $numRows; ++$i) {
            if (empty($spreadSheetAry[$i][0])) {
                continue;
            }
            if ($spreadSheetAry[$i][0] === 'TOT') {
                break;
            }
            $titolo_wp = mysqli_real_escape_string($con, $spreadSheetAry[$i][0]);
            $id_wp = select_single_value("SELECT ID_WP FROM PROGETTI_WP WHERE ID_PROGETTO=$id_progetto AND TITOLO='$titolo_wp'");
            
            for ($day = 1; $day < count($spreadSheetAry[$riga_date]); ++$day) { // OFFSET == 0
                if ($spreadSheetAry[$riga_date][$day] === 'TOT') {
                    break;
                }
                $data = "$anno-$mese-$day";
                $ore = $spreadSheetAry[$i][$day];

                if ($ore > 0) {
                    $query = "replace into ore_consuntivate(MATRICOLA_DIPENDENTE,DATA,ID_PROGETTO,ID_WP,ORE_LAVORATE) values('$matricola','$data',$id_progetto,$id_wp,$ore)";
                    execute_update($query);
                }
            }
        }
        
        $message .= 'Fatto.</br>';
    }

}
?>