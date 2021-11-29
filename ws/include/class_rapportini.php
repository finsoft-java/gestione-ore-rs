<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

$rapportini = new RapportiniManager();

class RapportiniManager {

    function carica_da_db($anno, $mese) {
        $primo = "DATE('$anno-$mese-01')";

        // Con questa query cerco di stampare solo i rapportini dei dipendenti che mi interessano
        $query_matricole = "SELECT DISTINCT r.MATRICOLA_DIPENDENTE, p.* " .
            "FROM progetti p " .
            "JOIN progetti_persone r ON p.ID_PROGETTO=r.ID_PROGETTO " .
            "WHERE p.DATA_FINE >= $primo AND p.DATA_INIZIO <= LAST_DAY($primo)";
        $matricole = select_list($query_matricole);

        if (count($matricole) == 0) {
            return [];
        }

        $query_consuntivo = "SELECT ID_PROGETTO,MATRICOLA_DIPENDENTE,DATA,NUM_ORE_LAVORATE " .
                    "FROM ore_consuntivate_progetti " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo)";
        $consuntivo = select_list($query_consuntivo);

        // trasformo i vari array in una struttura $map_progetti_matricole
        $map_progetti_matricole = array();

        foreach($matricole as $row) {
            $idprogetto = $row["ID_PROGETTO"];
            $matr = $row["MATRICOLA_DIPENDENTE"];
            if (! isset($map_progetti_matricole[$matr])) {
                $map_progetti_matricole[$matr] = array();
            }
            if (! isset($map_progetti_matricole[$matr][$idprogetto])) {
                $map_progetti_matricole[$matr][$idprogetto] = $row;
                $map_progetti_matricole[$matr][$idprogetto]['DATE'] = array();
            }
        }

        foreach ($consuntivo as $row) {
            $idprogetto = $row["ID_PROGETTO"];
            $matr = $row["MATRICOLA_DIPENDENTE"];
            $data = new DateTime($row["DATA"]);
            $map_progetti_matricole[$matr][$idprogetto]['DATE'][$data->format('j')] = 0.0 + $row["NUM_ORE_LAVORATE"];
        }
    return $map_progetti_matricole;
    }

    function creaZip($anno, $mese, $isEsploso) {
        global $lul;
        
        // REPERIRE DATI DA DB
        $map_progetti_matricole = $this->carica_da_db($anno, $mese);
        $map_matr_ore = $lul->carica_da_db($anno, $mese);
        
        if (empty($map_progetti_matricole)) {
            print_error(404, 'Nessun dato trovato.');
        }

        /*
        $map_progetti_matricole = Array
        (
            [1234] => Array
                (
                    [2] => Array
                        (
                            [ID_PROGETTO] => 2
                            [MATRICOLA_DIPENDENTE] => 1234
                            [ACRONIMO] => aad'
                            [MATRICOLA_SUPERVISOR] => 4321
                            [DATE] => Array
                                (
                                    [6] => 5
                                )

                        )

                )
        )
        $map_matr_ore = Array
        (
            [1234] => Array
                (
                    [6] => 8
                )

        )
        */

        // CREA FILE ZIP VUOTO
        $zip = new ZipArchive;
        $zipfilename = tempnam(null, "export");
        if (! $zip->open($zipfilename, ZipArchive::CREATE)) {
            print_error(500, 'Cannot create ZIP file');
        }
        $tempfiles = [];
        
        // MAIN LOOP
        foreach ($map_progetti_matricole as $matr => $map_progetti) {
            if ($isEsploso) {
                // un report distinto per ogni progetto
                foreach( $map_progetti as $idprogetto => $row) {
                    $xlsxfilename = $this->creaFileExcel($matr, $anno, $mese, [$idprogetto => $row], $map_matr_ore);
                    $matr = trim($matr);
                    $acronimo = trim($row["ACRONIMO"]);
                    $xlsxfilename_final = "Rapportini_${matr}_${acronimo}_$anno$mese.xlsx";
                    $zip->addFile($xlsxfilename, $xlsxfilename_final); // NON salva su disco
                    $tempfiles[] = $xlsxfilename;
                }
            } else {
                // unico report con tutti i progetti
                $xlsxfilename = $this->creaFileExcel($matr, $anno, $mese, $map_progetti, $map_matr_ore);
                $matr = trim($matr);
                $xlsxfilename_final = "Rapportini_${matr}_$anno$mese.xlsx";
                $zip->addFile($xlsxfilename, $xlsxfilename_final); // NON salva su disco
                $tempfiles[] = $xlsxfilename;
            }
        }

        $zip->close(); // Questo esegue il salvataggio su disco
        
        // ELIMINO FILE TEMPORANEI (tutti tranne lo zip!)
        foreach($tempfiles as $t) unlink($t);
        
        return $zipfilename;
    }

    function creaFileExcel($matr, $anno, $mese, $map_matricole_progetti, $map_matr_ore) {
        
        // Sto assumento che ci sia un unico supervisor per tutti i progetti...
        // E le stesse data firma!
        // cfr. email Gabriele / Alice del 25/05/2021
        $unIdProgettoACaso = array_keys($map_matricole_progetti)[0];
        $unProgettoACaso = $map_matricole_progetti[$unIdProgettoACaso];
        $data_firma = $this->get_data_firma($unIdProgettoACaso, $matr, $anno, $mese);

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);

        global $panthera;
        $nomecognome = $panthera->getUtente($matr);

        $nomecognome_super = $panthera->getUtente($unProgettoACaso['MATRICOLA_SUPERVISOR']);

        $data_inizio_progetto = $unProgettoACaso['DATA_INIZIO'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(date('M', strtotime("$anno-$mese-01")));
        
        $curRow = 1;
        $rigaTotali = 1;

        $this->adjustWidth($sheet);
        $this->creaIntestazione($sheet, $curRow, $anno, $mese, $matr, $nomecognome, $nomecognome_super);
        $this->creaTabellaPresenze($sheet, $curRow, $anno, $mese, $matr, $map_matr_ore, $rigaTotali, $daysInMonth);

        $this->creaTabella($sheet, $curRow, -1 /* FIXME */, $map_matricole_progetti, $anno, $mese, $matr, $nomecognome, $map_matr_ore, $data_inizio_progetto, $daysInMonth);

        $this->aggiornaRigaTotali($sheet, $curRow, $rigaTotali, $daysInMonth);

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

    function creaTabellaPresenze($sheet, &$curRow, $anno, $mese, $matr, $map_matr_ore, &$rigaTotali, $daysInMonth) {

        $first_row = $curRow;
        // In alto i giorni
        $mese = strtoupper(date('F', strtotime("$anno-$mese-01")));
        $sheet->setCellValue('A' . $curRow, "$mese $anno");
        $sheet->setCellValue('B' . $curRow, 'day');

        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $curCol = $i + 2;
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, $i);
        }

        $sheet->getStyle("A$curRow:AG$curRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setSize(12);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setBold(true);

        $curRow++;

        // Riga con le ore di presenza
        $sheet->setCellValue('A' . $curRow, "Working hours *");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('B' . $curRow, "=SUM(C$curRow:AG$curRow)");
        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $curCol = $i + 2;
            if (isset($map_matr_ore[$matr][$i])) {
                $ore = $map_matr_ore[$matr][$i];
            } else {
                $ore = 'A';
                $map_matr_ore[$i] = 0;
            }
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, $ore);
        }

        $sheet->getStyle("B$curRow:AG$curRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setSize(10);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFECFF');

        $curRow++;

        // RIGA TOTALI
        $sheet->setCellValue('A' . $curRow, 'TOTAL PROJECTS');
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('B' . $curRow, "=SUM(C$curRow:AG$curRow)");
        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $curCol = $i + 2;
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, "=0");
        }
        $rigaTotali = $curRow;

        $sheet->getStyle("B$curRow:AG$curRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setSize(11);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCECFF');
        $sheet->getStyle("A$first_row:AG$curRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Riga differenza
        $curRow++;
        $sheet->setCellValue('A' . $curRow, 'remaining hours');
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $rowLul = $curRow - 2;
        $rowTot = $curRow - 1;
        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $curCol = $i + 2;
            $letter = Coordinate::stringFromColumnIndex($curCol, $curRow);
            $sheet->setCellValueByColumnAndRow($curCol, $curRow, "=IFERROR($letter$rowLul-$letter$rowTot,\"\")");
        }
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setSize(10);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setItalic(true);
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->getColor()->setARGB('FF777777');

        $curRow++;
    }

    function aggiornaRigaTotali($sheet, $curRow, $rigaTotali, $daysInMonth) {
        $startRow = $rigaTotali + 2;
        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $curCol = $i + 2;
            $letter = Coordinate::stringFromColumnIndex($i + 2, $curRow);
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

    function creaTabella($sheet, &$curRow, $idProgetto, $data, $anno, $mese, $matricola, $nomecognome, $map_matr_ore, $data_inizio_progetto, $daysInMonth) {

        // la riga azzurra piccola
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCECFF');
        $sheet->getRowDimension($curRow)->setRowHeight(5);

        $curRow++;

        // prima riga intestazione di progetto
        $numMese = $this->getMeseProgetto($anno, $mese, $data_inizio_progetto);
        $sheet->setCellValue('A' . $curRow, "M$numMese");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $unProgettoACaso = $data[array_keys($data)[0]];
        $title = "$unProgettoACaso[ACRONIMO] - Grant $unProgettoACaso[GRANT_NUMBER] - $unProgettoACaso[TITOLO]";
        $sheet->setCellValue('B' . $curRow, $title);
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells("B$curRow:AG$curRow");

        $sheet->getStyle("A$curRow:B$curRow")->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:B$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

        $row_prima_riga = $curRow + 1;
        foreach($data as $idprogetto => $wp) {
            ++$curRow;
            // A sinistra le WP
            $sheet->setCellValue('A' . $curRow, $wp['ACRONIMO'] . ' - ' . $wp['TITOLO']);
            // Poi, totale di riga
            $formula = "=SUM(C$curRow:AG$curRow)";
            $sheet->setCellValue('B' . $curRow, $formula);
            // Infine, le ore consuntivate
            if (isset($wp["NUM_ORE_LAVORATE"]) && ! empty($wp["NUM_ORE_LAVORATE"])) {
                for ($i = 1; $i <= 31; ++$i) {
                    if (isset($wp["NUM_ORE_LAVORATE"][$i])) {
                        $sheet->setCellValueByColumnAndRow($i + 2, $curRow, $wp["NUM_ORE_LAVORATE"][$i]);
                    }
                }
            }
        }
        $row_ultima_riga = $curRow;

        $sheet->getStyle("A$row_prima_riga:AG$row_ultima_riga")->getFont()->setSize(10);

        // Totali di colonna
        $curRow +=2;
        $sheet->setCellValue('A' . $curRow, "TOT - $unProgettoACaso[ACRONIMO]");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $formula = "=SUM(C$curRow:AG$curRow)";
        $sheet->setCellValue('B' . $curRow, $formula);
        for ($i = 1; $i <= $daysInMonth; ++$i) {
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
        $spreadSheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filename);
        $numOfSheets = $spreadSheet->getSheetCount();

        // Mi aspetto un unico sheet
        $this->importSheet($spreadSheet->getSheet(0), $message);
    }

    function importSheet($excelSheet, &$message) {
        global $con, $panthera;

        $spreadSheetAry = $excelSheet->toArray();
        
        // salto la header
        $firstRow = 1;
        $numRows = count($spreadSheetAry);
        if ($numRows <= 1) {
            $message->error .= 'Il file deve contenere almeno una riga (header esclusa).<br/>';
            return;
        }

        $idCaricamento = $this->nuovo_caricamento();

        $contatore = 0;
        for ($curRow = $firstRow; $curRow < $numRows; ++$curRow) {
    
            if(!isset($spreadSheetAry[$curRow][0])) {
                continue;
            }

            $serieDoc = $spreadSheetAry[$curRow][COL_SERIE_DOC];
            $nrDoc = $spreadSheetAry[$curRow][COL_NUMERO_DOC];
            $dataDoc = $spreadSheetAry[$curRow][COL_DATA_DOC];
            $matricola = $spreadSheetAry[$curRow][COL_MATRICOLA];
            $codCommessa = $spreadSheetAry[$curRow][COL_COMMESSA];
            $codAtv = $spreadSheetAry[$curRow][COL_ATV];
            $codSottoComm = $spreadSheetAry[$curRow][COL_SOTTO_COMM];
            $numOre = $spreadSheetAry[$curRow][COL_NUM_ORE];

            if (!$codCommessa || !$dataDoc || !$matricola || !$codAtv || !$serieDoc || !$nrDoc || !$codSottoComm) {
                $message->error .= "Campi obbligatori non valorizzati alla riga $curRow<br/>";
                continue;
            }
            if (!$numOre) {
                $numOre = 0.0;
            }

            $dataDocDt = DateTime::createFromFormat(DATE_FORMAT, $dataDoc);
            if (!$dataDocDt) {
                $message->error .= "Formato data errato alla riga $curRow: $dataDoc<br/>";
                continue;
            }
            $dataDoc = $dataDocDt->format('Y-m-d');

            $query = "REPLACE INTO ore_consuntivate_commesse (COD_COMMESSA,MATRICOLA_DIPENDENTE,DATA,RIF_SERIE_DOC,RIF_NUMERO_DOC,RIF_ATV,RIF_SOTTO_COMMESSA,NUM_ORE_LAVORATE,ID_CARICAMENTO) " .
                        "VALUES('$codCommessa','$matricola','$dataDoc','$serieDoc','$nrDoc','$codAtv','$codSottoComm','$numOre',$idCaricamento)";
            execute_update($query);
            ++$contatore;
        }

        $message->success .= "Caricamento concluso. $contatore righe caricate.<br/>";
    }

    function nuovo_caricamento() {
        global $con, $logged_user;
        
        $con->begin_transaction();
        try {
            $query_max = "SELECT NVL(MAX(ID_CARICAMENTO),0)+1 FROM caricamenti ";
            $id = select_single_value($query_max);

            $query ="INSERT INTO caricamenti (ID_CARICAMENTO, UTENTE) VALUES ('$id', '$logged_user->nome_utente')";
            execute_update($query);

            $con->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();        
            throw $exception;
        }
        return $id;
    }
    
    function get_caricamenti($skip=null, $top=null, $orderby=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM caricamenti p ";
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.id_caricamento DESC";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null){
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }        
        $oggetti = select_list($sql1 . $sql);
        
        return [$oggetti, $count];
    }
    
    function get_caricamento($id_esecuzione) {
        $sql = "SELECT * FROM caricamenti WHERE id_caricamento = '$id_esecuzione'";
        return select_single($sql);
    }

    function elimina_caricamento($idCaricamento) {
        // SI PUO' FARE SOLO SE NON E' GIA' STATO UTILIZZATO
        $query = "DELETE FROM ore_consuntivate_commesse WHERE ID_CARICAMENTO=$idCaricamento ";
        execute_update($query);

        $query = "DELETE FROM caricamenti WHERE ID_CARICAMENTO=$idCaricamento ";
        execute_update($query);
    }

    function getMeseProgetto($anno, $mese, $data_inizio_progetto) {
        $d1 = date_create($data_inizio_progetto);
        $d2 = date_create("$anno-$mese-01");
        return date_diff($d2, $d1)->format('%m') + 1;
    }
    
    function get_ore_commesse($skip=null, $top=null, $orderby=null, $month=null, $matricola=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM ore_consuntivate_commesse WHERE 1 ";
        
        if ($month !== null && $month !== '') {
            // in forma YYYY-MM
            $month = substr($con->escape_string($month), 0, 7);
            $first = "DATE('$month-01')";
            $sql .= "AND (data BETWEEN $first AND LAST_DAY($first)) ";
        }
        
        if ($matricola !== null && $matricola !== '') {
            $matricola = $con->escape_string($matricola);
            $sql .= "AND MATRICOLA_DIPENDENTE='$matricola' ";
        }

        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY MATRICOLA_DIPENDENTE, DATA, COD_COMMESSA, RIF_SOTTO_COMMESSA";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null) {
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }        
        $oggetti = select_list($sql1 . $sql);
        
        return [$oggetti, $count];
    }
}
?>