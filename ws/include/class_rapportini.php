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

        // Con questa query cerco di stampare solo i rapportini dei dipendenti che mi interessano
        $query_dipendenti = "SELECT DISTINCT r.MATRICOLA_DIPENDENTE " .
            "FROM progetti p " .
            "JOIN progetti_wp wp ON wp.ID_PROGETTO=p.ID_PROGETTO " .
            "JOIN progetti_wp_risorse r ON wp.ID_PROGETTO=r.ID_PROGETTO AND wp.ID_WP=r.ID_WP " .
            "WHERE wp.DATA_FINE >= $primo AND wp.DATA_INIZIO <= LAST_DAY($primo)";
        $matricole = select_column($query_dipendenti);

        // Per questi dipendenti, però, voglio vedere tutti i progetti e tutte le WP
        $query_wp = "SELECT p.ID_PROGETTO, p.ACRONIMO, p.TITOLO AS TITOLO_P, p.GRANT_NUMBER, p.DATA_INIZIO as DATA_INIZIO_P, p.MATRICOLA_SUPERVISOR, " .
                    "wp.ID_WP, wp.TITOLO, wp.DESCRIZIONE, wp.DATA_INIZIO, wp.DATA_FINE " .
                    "FROM progetti p " .
                    "JOIN progetti_wp wp ON wp.ID_PROGETTO=p.ID_PROGETTO " .
                    "WHERE wp.DATA_FINE >= $primo AND wp.DATA_INIZIO <= LAST_DAY($primo)";
        $array_wp = select_list($query_wp);

        $query_consuntivo = "SELECT ID_PROGETTO,ID_WP,MATRICOLA_DIPENDENTE,DATA,ORE_LAVORATE " .
                    "FROM ore_consuntivate " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo)";
        $consuntivo = select_list($query_consuntivo);
        
        // trasformo i vari array in una struttura $map_progetti_matricole_wp
        $map_progetti_matricole_wp = array();
        foreach($matricole as $matr) {
            $map_progetti_matricole_wp[$matr] = array();
            foreach($array_wp as $row) {
                $idprogetto = $row["ID_PROGETTO"];
                $idwp = $row["ID_WP"];
                if (! array_key_exists($idprogetto, $map_progetti_matricole_wp[$matr])) $map_progetti_matricole_wp[$matr][$idprogetto] = array();
                $map_progetti_matricole_wp[$matr][$idprogetto][$idwp] = $row;
            }
        }
        foreach ($consuntivo as $row) {
            $idprogetto = $row["ID_PROGETTO"];
            $idwp = $row["ID_WP"];
            $matr = $row["MATRICOLA_DIPENDENTE"];
            $data = new DateTime($row["DATA"]);
            $wp = $map_progetti_matricole_wp[$matr][$idprogetto][$idwp];
            if (! isset($wp["ORE_LAVORATE"])) $wp["ORE_LAVORATE"] = array();
            $wp["ORE_LAVORATE"][$data->format('j')] = $row["ORE_LAVORATE"];
            $map_progetti_matricole_wp[$matr][$idprogetto][$idwp] = $wp;
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
        foreach ($map_progetti_matricole_wp as $matr => $map_matricole_wp) {
            $xlsxfilename = $this->creaFileExcel($matr, $anno, $mese, $map_matricole_wp, $map_matr_ore);
            $xlsxfilename_final = "Rapportini_${matr}_$anno$mese.xlsx";
            $zip->addFile($xlsxfilename, $xlsxfilename_final); // NON salva su disco
            $tempfiles[] = $xlsxfilename;
        }

        $zip->close(); // Questo esegue il salvataggio su disco
        
        // ELIMINO FILE TEMPORANEI (tutti tranne lo zip!)
        foreach($tempfiles as $t) unlink($t);
        
        return $zipfilename;
    }

    function creaFileExcel($matr, $anno, $mese, $map_matricole_wp/*$map_wp_wp*/, $map_matr_ore) {
        
        // Sto assumento che ci sia un unico supervisor per tutti i progetti...
        // E le stesse data firma!
        // cfr. email Gabriele / Alice del 25/05/2021
        $unIdProgettoACaso = array_keys($map_matricole_wp)[0];
        $unIdWpACaso = array_keys($map_matricole_wp[$unIdProgettoACaso])[0];
        $unWpACaso = $map_matricole_wp[$unIdProgettoACaso][$unIdWpACaso];
        
        $data_firma = $this->get_data_firma($unIdProgettoACaso, $matr, $anno, $mese);

        global $panthera;
        $nomecognome = $panthera->getUtente($matr);
        $nomecognome_super = $panthera->getUtente($unWpACaso['MATRICOLA_SUPERVISOR']);

        $data_inizio_progetto = $unWpACaso['DATA_INIZIO_P'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(date('M', strtotime("$anno-$mese-01")));
        
        $curRow = 1;
        $rigaTotali = 1;

        $this->adjustWidth($sheet);
        $this->creaIntestazione($sheet, $curRow, $anno, $mese, $matr, $nomecognome, $nomecognome_super);
        $this->creaTabellaPresenze($sheet, $curRow, $anno, $mese, $matr, $map_matr_ore, $rigaTotali);

        //$this->creaLegenda($sheet, $curRow, $map_wp_wp);

        foreach ($map_matricole_wp as $idProgetto => $map_wp) {
            $this->creaTabella($sheet, $curRow, $idProgetto, $map_wp, $anno, $mese, $matr, $nomecognome, $map_matr_ore, $data_inizio_progetto);
        }

        $this->aggiornaRigaTotali($sheet, $curRow, $rigaTotali);

        $this->creaFooter($sheet, $curRow, $nomecognome, $nomecognome_super, $data_firma);

        $sheet->getPageSetup()->setPrintArea('A1:AH' . $curRow);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $xlsxfilename = tempnam(null, "Rapportini");
        $writer->save($xlsxfilename);
        
        return $xlsxfilename;
    }
    
    function get_data_firma($idProgetto, $matr, $anno, $mese) {
        $sql = "SELECT DATA_FIRMA FROM date_firma WHERE MATRICOLA_DIPENDENTE='$matr' AND ANNO_MESE='$anno-$mese' AND ID_PROGETTO='$idProgetto'";
        return select_single_value($sql);
    }

    function adjustWidth($sheet) {
        // see https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#setting-a-columns-width
        $sheet->getColumnDimensionByColumn(1)->setWidth(35);
        $sheet->getColumnDimensionByColumn(2)->setWidth(5);
        for ($i = 1; $i <= 31; ++$i) {
            $sheet->getColumnDimensionByColumn($i + 2)->setWidth(4);
        }
    }

    function creaIntestazione($sheet, &$curRow, $anno, $mese, $matr, $nomecognome, $nomecognome_super) {
        
        $data = new DateTime("$anno-$mese-01");
        
        $sheet->setCellValue('A' . $curRow, 'Working person:  ');
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('B' . $curRow, $nomecognome);
        $sheet->getStyle('B' . $curRow)->getFont()->setBold(true);
        $sheet->setCellValue('O' . $curRow, 'Supervisor:  ');
        $sheet->getStyle('O' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('P' . $curRow, $nomecognome_super);
        $sheet->getStyle('P' . $curRow)->getFont()->setBold(true);
        $sheet->getRowDimension($curRow)->setRowHeight(25);
        $curRow++;
        
        $this->insertImage($sheet, './images/logo.png', 50, 'Logo', 'AB1');

        $sheet->setCellValue('A' . $curRow, 'TIMESHEET');
        $sheet->getRowDimension($curRow)->setRowHeight(25);
        $curRow ++;
    }

    function creaTabellaPresenze($sheet, &$curRow, $anno, $mese, $matr, $map_matr_ore, &$rigaTotali) {

        $first_row = $curRow;
        // In alto i giorni
        $mese = strtoupper(date('F', strtotime("$anno-$mese-01")));
        $sheet->setCellValue('A' . $curRow, "$mese $anno");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('B' . $curRow, 'day');
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($i = 1; $i <= 31; ++$i) {
            $curCol = $i + 2;
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, $i);
        }

        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setSize(12);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setBold(true);

        $curRow++;

        // Riga con le ore di presenza
        $sheet->setCellValue('A' . $curRow, "Working hours *");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFECFF');
        $sheet->setCellValue('B' . $curRow, "=SUM(C$curRow:AG$curRow)");
        for ($i = 1; $i <= 31; ++$i) {
            $curCol = $i + 2;
            if (isset($map_matr_ore[$i])) {
                $ore = $map_matr_ore[$i];
            } else {
                $ore = 'A';
                $map_matr_ore[$i] = 0;
            }
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, $ore);
        }
        $curRow++;

        // RIGA TOTALI
        $sheet->setCellValue('A' . $curRow, "TOTAL PROJECTS");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCECFF');
        $sheet->setCellValue('B' . $curRow, "=SUM(C$curRow:AG$curRow)");
        for ($i = 1; $i <= 31; ++$i) {
            $curCol = $i + 2;
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, "=0");
        }
        $rigaTotali = $curRow;

        $sheet->getStyle("A$first_row:AG$curRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $curRow++;
        $curRow++;
    }

    function aggiornaRigaTotali($sheet, $curRow, $rigaTotali) {
        $startRow = $rigaTotali + 2;
        for ($i = 1; $i <= 31; ++$i) {
            $curCol = $i + 2;
            $letter = Coordinate::stringFromColumnIndex($i + 2, $curRow);;
            // Divido per due perchè ci sono anche i totali 
            $sheet->setCellValueByColumnAndRow($curCol, $rigaTotali, "=SUM($letter$startRow:$letter$curRow)/2");
        }
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

    function creaTabella($sheet, &$curRow, $idProgetto, $map_wp, $anno, $mese, $matricola, $nomecognome, $map_matr_ore, $data_inizio_progetto) {

        // la riga azzurra piccola
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCECFF');
        $sheet->getRowDimension($curRow)->setRowHeight(5);

        $curRow++;

        // prima riga intestazione di progetto
        $nummese = $this->getMeseProgetto($anno, $mese, $data_inizio_progetto);
        $sheet->setCellValue('A' . $curRow, "M$nummese");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $unaWpACaso = $map_wp[array_keys($map_wp)[0]];
        $title = "$unaWpACaso[ACRONIMO] - Grant $unaWpACaso[GRANT_NUMBER] - $unaWpACaso[TITOLO_P]"; 
        $sheet->setCellValue('B' . $curRow, $title);
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells("B$curRow:AG$curRow");

        $sheet->getStyle("A$curRow:B$curRow")->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:B$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

        $row_prima_riga = $curRow + 1;
        foreach($map_wp as $idwp => $wp) {
            ++$curRow;
            // A sinistra le WP
            $sheet->setCellValue('A' . $curRow, $wp['ACRONIMO'] . ' - ' . $wp['TITOLO']);
            // Poi, totale di riga
            $formula = "=SUM(C$curRow:AG$curRow)";
            $sheet->setCellValue('B' . $curRow, $formula);
            // Infine, le ore consuntivate
            if (isset($wp["ORE_LAVORATE"]) && ! empty($wp["ORE_LAVORATE"])) {
                for ($i = 1; $i <= 31; ++$i) {
                    if (isset($wp["ORE_LAVORATE"][$i])) {
                        $sheet->setCellValueByColumnAndRow($i + 2, $curRow, $wp["ORE_LAVORATE"][$i]);
                    }
                }
            }
        }
        $row_ultima_riga = $curRow;

        // Totali di colonna
        $curRow +=2;
        $sheet->setCellValue('A' . $curRow, "TOT - $unaWpACaso[ACRONIMO]");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $formula = "=SUM(C$curRow:AG$curRow)";
        $sheet->setCellValue('B' . $curRow, $formula);
        for ($i = 1; $i <= 31; ++$i) {
            $letter = Coordinate::stringFromColumnIndex($i + 2, $curRow);
            $formula = '=SUM('.$letter.$row_prima_riga.':'.$letter.$row_ultima_riga.')';
            $sheet->setCellValueByColumnAndRow($i + 2, $curRow, $formula);
        }
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setBold(true);

        $sheet->getStyle("A$row_prima_riga:AG$curRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $curRow ++;
    }

    function creaFooter($sheet, &$curRow, $nomecognome, $nomecognome_super, $data_firma) {
        $curRow++;
        $sheet->setCellValue('B' . $curRow, 'Working person:  ');
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle("C$curRow:L$curRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->setCellValue('S' . $curRow, 'Supervisor:  ');
        $sheet->getStyle('S' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('S' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle("T$curRow:AC$curRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $curRow++;
        $sheet->setCellValue('B' . $curRow, 'Date:');
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . $curRow)->getFont()->setBold(true);
        $sheet->setCellValue('C' . $curRow, $data_firma);
        $sheet->setCellValue('S' . $curRow, 'Date:');
        $sheet->getStyle('S' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('S' . $curRow)->getFont()->setBold(true);
        $sheet->setCellValue('T' . $curRow, $data_firma);
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
            $titolo_progetto = $con->escape_string($spreadSheetAry[0][7]);
        } else {
            $message->error .= 'Bad file. Non riesco a identificare le corrette colonne del file.<br/>';
            return;
        }
        $id_progetto = select_single_value("SELECT ID_PROGETTO FROM progetti WHERE TITOLO='$titolo_progetto'"); // FIXME chiave unica?!?        
        if (empty($id_progetto)) {
            $message->error .= 'Bad file. Non riesco a identificare il titolo del progetto.<br/>';
            return;
        }
        $anno = $con->escape_string($spreadSheetAry[2][9]);
        if (empty($anno)) {
            $message->error .= 'Bad file. Non riesco a identificare l\'anno del rapportino.<br/>';
            return;
        }
        $mese = $spreadSheetAry[2][13]; // e.g. February
        if (empty($mese)) {
            $message->error .= 'Bad file. Non riesco a identificare il mes del rapportino.<br/>';
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
            $message->error .= 'Bad file. Non trovo la stringa "Activity details".<br/>';
            return;
        }
        
        $matricola = $con->escape_string($spreadSheetAry[$i][23]);
        if (empty($matricola)) {
            $message->error .= 'Bad file. Non riesco a identificare la matricola utente.</br>';
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
            $titolo_wp = $con->escape_string($spreadSheetAry[$i][0]);
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
        
        $message->success .= 'Caricamento Effettuato correttamente.</br>';
    }

    function getMeseProgetto($anno, $mese, $data_inizio_progetto) {
        $d1 = date_create($data_inizio_progetto);
        $d2 = date_create("$anno-$mese-01");
        return date_diff($d2, $d1)->format('%m') + 1;
    }
}
?>