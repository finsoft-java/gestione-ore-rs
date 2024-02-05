<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$rapportini = new RapportiniManager();

class RapportiniManager {

    function carica_da_db($dataInizio, $dataFine) {
        $dataInizio = trim($dataInizio);
        $dataFine = trim($dataFine);
        // Con questa query cerco di stampare solo i rapportini dei dipendenti che mi interessano
        $query_matricole = "SELECT DISTINCT oc.ID_DIPENDENTE, p.* FROM ore_consuntivate_progetti oc JOIN progetti p ON oc.ID_PROGETTO=p.ID_PROGETTO WHERE DATA BETWEEN '$dataInizio' AND '$dataFine'";
        $matricole = select_list($query_matricole);

        if (count($matricole) == 0) {
            return [];
        }

        $query_consuntivo = "SELECT ID_PROGETTO,ID_DIPENDENTE,DATA,NUM_ORE_LAVORATE
                    FROM ore_consuntivate_progetti
                    WHERE DATA BETWEEN '$dataInizio' AND '$dataFine'";
        $consuntivo = select_list($query_consuntivo);

        // trasformo i vari array in una struttura $map_dipendenti_progetti
        $map_dipendenti_progetti = array();

        foreach($matricole as $row) {
            $idprogetto = $row["ID_PROGETTO"];
            $idDipendente = $row["ID_DIPENDENTE"];
            if (! isset($map_dipendenti_progetti[$idDipendente])) {
                $map_dipendenti_progetti[$idDipendente] = array();
            }
            $map_dipendenti_progetti[$idDipendente][$idprogetto] = $row;
            $map_dipendenti_progetti[$idDipendente][$idprogetto]['DATE'] = array();
        }
        //print_r($map_dipendenti_progetti);
        foreach ($consuntivo as $row) {
            $idprogetto = $row["ID_PROGETTO"];
            $idDipendente = $row["ID_DIPENDENTE"];
            $map_dipendenti_progetti[$idDipendente][$idprogetto]['DATE'][$row["DATA"]] = 0.0 + (float) $row["NUM_ORE_LAVORATE"];
        }
        return $map_dipendenti_progetti;
    }

    function creaZip($dataInizio, $dataFine, $isEsploso) {
        global $lul;
        
        // REPERIRE DATI DA DB
        $map_dipendenti_progetti = $this->carica_da_db($dataInizio, $dataFine);
        $map_matr_ore = $lul->carica_da_db($dataInizio, $dataFine);

        if (empty($map_dipendenti_progetti)) {
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
        
        foreach ($map_dipendenti_progetti as $idDipendente => $map_progetti) {
            if ($isEsploso) {
                // un report distinto per ogni progetto
                foreach( $map_progetti as $idprogetto => $row) {
                    $xlsxfilename = $this->creaFileExcel($idDipendente,$dataInizio, $dataFine, [$idprogetto => $row], $map_matr_ore);
                    if ($xlsxfilename != null) {
                        $idDipendente = trim($idDipendente);
                        $acronimo = trim($row["ACRONIMO"]);
                        $xlsxfilename_final = "Rapportini_${idDipendente}_${acronimo}_$dataInizio$dataFine.xlsx";
                        $zip->addFile($xlsxfilename, $xlsxfilename_final); // NON salva su disco
                        $tempfiles[] = $xlsxfilename;
                    }
                }
            } else {
                // unico report con tutti i progetti
                $xlsxfilename = $this->creaFileExcel($idDipendente, $dataInizio, $dataFine, $map_progetti, $map_matr_ore);
                if ($xlsxfilename != null) {
                    $idDipendente = trim($idDipendente);
                    $xlsxfilename_final = "Rapportini_${idDipendente}_$dataInizio$dataFine.xlsx";
                    $zip->addFile($xlsxfilename, $xlsxfilename_final); // NON salva su disco
                    $tempfiles[] = $xlsxfilename;
                }
            }
        }

        $zip->close(); // Questo esegue il salvataggio su disco
        
        // ELIMINO FILE TEMPORANEI (tutti tranne lo zip!)
        foreach($tempfiles as $t) unlink($t);
        
        return $zipfilename;
    }
    function getMonthsByRange($startDate, $endDate){
        $months = array();
        while (strtotime($startDate) <= strtotime($endDate)) {
            $months[] = array(
                'anno' => date('Y', strtotime($startDate)),
                'mese' => date('m', strtotime($startDate)),
                'giorno' => date('d', strtotime($startDate)),
            );

            // Set date to 1 so that new month is returned as the month changes.
            $startDate = date('01 M Y', strtotime($startDate . '+ 1 month'));
        }
        return $months;
    }
    function creaFileExcel($idDipendente, $dataInizio, $dataFine, $map_progetti, $map_matr_ore) {
        $CicloMesi = $this->getMonthsByRange($dataInizio, $dataFine);
        // Sto assumento che ci sia un unico supervisor per tutti i progetti...
        // E le stesse data firma!
        // cfr. email Gabriele / Alice del 25/05/2021
        $unIdProgettoACaso = array_keys($map_progetti)[0];
        $unProgettoACaso = $map_progetti[$unIdProgettoACaso];
        if ($unProgettoACaso == null || !isset($unProgettoACaso['ID_PROGETTO'])) {
            // qualcosa non va, ma pazienza
            return null;
        }
        
        $spreadsheet = new Spreadsheet();
        for( $i = 0;$i < count($CicloMesi); $i++){
            $anno = $CicloMesi[$i]["anno"];
            $mese = $CicloMesi[$i]["mese"];
            $data_firma = $this->get_data_firma($unIdProgettoACaso, $idDipendente, $anno, $mese);
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);

            global $panthera;
            $nomecognome = $panthera->getUtenteByIdDipendente($idDipendente);
            $nomecognome_super = $panthera->getUtenteByIdDipendente($unProgettoACaso['ID_SUPERVISOR']);

            $data_inizio_progetto = $unProgettoACaso['DATA_INIZIO'];

            if($i > 0) {
                $spreadsheet->createSheet();
                $spreadsheet->setActiveSheetIndex($i);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->setTitle(date('M', strtotime("$anno-$mese-01")));
            
            $curRow = 1;
            $rigaTotali = 1;

            $this->adjustWidth($sheet);
            $this->creaIntestazione($sheet, $curRow, $idDipendente, $nomecognome, $nomecognome_super);
            $this->creaTabellaPresenze($sheet, $curRow, $anno, $mese, $idDipendente, $map_matr_ore, $rigaTotali, $daysInMonth);

            $this->creaTabella($sheet, $curRow, $map_progetti, $anno, $mese, $idDipendente, $nomecognome, $map_matr_ore, $data_inizio_progetto, $daysInMonth);

            $this->aggiornaRigaTotali($sheet, $curRow, $rigaTotali, $daysInMonth);

            $this->creaFooter($sheet, $curRow, $nomecognome, $nomecognome_super, $data_firma);

            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
            $sheet->getPageSetup()->setPrintArea('A1:AG' . $curRow);
            $sheet->getPageSetup()->setFitToPage(true);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $xlsxfilename = tempnam(null, "Rapportini");
            $writer->save($xlsxfilename);
        }        
        return $xlsxfilename;
    }
    
    function get_data_firma($idProgetto, $idDipendente, $anno, $mese) {
        $sql = "SELECT DATA_FIRMA FROM date_firma WHERE ID_DIPENDENTE='$idDipendente' AND ANNO_MESE='$anno-$mese' AND ID_PROGETTO='$idProgetto'";
        return select_single_value($sql);
    }

    function adjustWidth($sheet) {
        // see https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#setting-a-columns-width
        $sheet->getColumnDimensionByColumn(1)->setWidth(45);
        $sheet->getColumnDimensionByColumn(2)->setWidth(10);
        for ($i = 1; $i <= 31; ++$i) {
            $sheet->getColumnDimensionByColumn($i + 2)->setWidth(4);
        }
    }

    function creaIntestazione($sheet, &$curRow, $idDipendente, $nomecognome, $nomecognome_super) {
        
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

    function creaTabellaPresenze($sheet, &$curRow, $anno, $mese, $idDipendente, $map_matr_ore, &$rigaTotali, $daysInMonth) {

        $first_row = $curRow;
        // In alto i giorni
        $meseOld = $mese;
        $meseNew = strtoupper(date('F', strtotime("$anno-$mese-01")));
        $sheet->setCellValue('A' . $curRow, "$meseNew $anno");
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
        $sheet->getStyle("B$curRow:AG$curRow")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $curCol = $i + 2;
            $mesePad0 = str_pad($meseOld, 2, '0', STR_PAD_LEFT); 
            $dayPad0 = str_pad($i, 2, '0', STR_PAD_LEFT); 
            $dataCurr = $anno."-".$mesePad0."-".$dayPad0;
            if (isset($map_matr_ore[$idDipendente][$dataCurr])) {
                $ore = $map_matr_ore[$idDipendente][$dataCurr];
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
        $sheet->getStyle("B$curRow:AG$curRow")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
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
        $sheet->getStyle("C$curRow:AG$curRow")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
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

    function creaTabella($sheet, &$curRow, $map_progetti, $anno, $mese, $matricola, $nomecognome, $map_matr_ore, $data_inizio_progetto, $daysInMonth) {
        $meseOld = $mese;
        // la riga azzurra piccola
        $sheet->getStyle("A$curRow:AG$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCECFF');
        $sheet->getRowDimension($curRow)->setRowHeight(5);

        $curRow++;

        // prima riga intestazione di progetto
        // 2023-01-19 elimino il mese progetto
        // $numMese = $this->getMeseProgetto($anno, $mese, $data_inizio_progetto);
        // $sheet->setCellValue('A' . $curRow, "M$numMese");
        // $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $unProgettoACaso = $map_progetti[array_keys($map_progetti)[0]];
        $title = "Projects list";
        $sheet->setCellValue('B' . $curRow, $title);
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells("B$curRow:AG$curRow");

        $sheet->getStyle("A$curRow:B$curRow")->getFont()->setBold(true);
        $sheet->getStyle("A$curRow:B$curRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

        $row_prima_riga = $curRow + 1;

        foreach($map_progetti as $idprogetto => $row) {
            
            ++$curRow;
            // A sinistra le row
            $sheet->setCellValue('A' . $curRow, $row['ACRONIMO'] . ' - ' . $row['TITOLO']);
            // Poi, totale di riga
            $formula = "=SUM(C$curRow:AG$curRow)";
            $sheet->setCellValue('B' . $curRow, $formula);
            // Infine, le ore consuntivate
            if (isset($row['DATE']) && ! empty($row['DATE'])) {
                //print_r($row);
                for ($i = 1; $i <= 31; ++$i) {
                    $mesePad0 = str_pad($meseOld, 2, '0', STR_PAD_LEFT); 
                    $dayPad0 = str_pad($i, 2, '0', STR_PAD_LEFT); 
                    $dataCurr = $anno."-".$mesePad0."-".$dayPad0;
                    if (isset($row['DATE'][$dataCurr])) {
                        $val = $row['DATE'][$dataCurr];
                        $sheet->setCellValueByColumnAndRow($i + 2, $curRow, $val);                   
                        $sheet->getStyle("B$curRow:AG$curRow")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    }
                }
            }
        }
        
        $row_ultima_riga = $curRow;

        $sheet->getStyle("A$row_prima_riga:AG$row_ultima_riga")->getFont()->setSize(10);

        // Totali di colonna
        $curRow +=2;
        $sheet->setCellValue('A' . $curRow, "TOTAL PROJECTS");
        $sheet->getStyle('A' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $formula = "=SUM(C$curRow:AG$curRow)";
        $sheet->setCellValue('B' . $curRow, $formula);
        for ($i = 1; $i <= $daysInMonth; ++$i) {
            $letter = Coordinate::stringFromColumnIndex($i + 2, $curRow);
            $formula = '=SUM('.$letter.$row_prima_riga.':'.$letter.$row_ultima_riga.')';
            $sheet->setCellValueByColumnAndRow($i + 2, $curRow, $formula);
            $sheet->getStyle("B$curRow:AG$curRow")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        }
        $sheet->getStyle("A$curRow:AG$curRow")->getFont()->setBold(true);

        $sheet->getStyle("A$row_prima_riga:AG$curRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $curRow ++;
    }

    function creaFooter($sheet, &$curRow, $nomecognome, $nomecognome_super, $data_firma) {
        $curRow++;
        /*
        $sheet->setCellValue('B' . $curRow, 'Working person:  ');
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle("C$curRow:L$curRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        */
        $sheet->setCellValue('S' . $curRow, 'Supervisor:  ');
        $sheet->getStyle('S' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('S' . $curRow)->getFont()->setBold(true);
        $sheet->getStyle("T$curRow:AC$curRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $curRow++;
        /*
        $sheet->setCellValue('B' . $curRow, 'Date:');
        $sheet->getStyle('B' . $curRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B' . $curRow)->getFont()->setBold(true);
        $sheet->setCellValue('C' . $curRow, $data_firma);
        */
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
        //questo ($excelSheet->toArray) può dare problemi se file grosso (con questa combinazione non fa conversione e le date funzionano ) modifica fatta per fix errore 
        $spreadSheetAry = $excelSheet->toArray(NULL, TRUE, FALSE);
        
        // salto la header
        $firstRow = 1;
        $numRows = count($spreadSheetAry);
        if ($numRows <= 1) {
            $message->error .= 'Il file deve contenere almeno una riga (header esclusa).<br/>';
            return;
        }

        $idCaricamento = $this->nuovo_caricamento();

        $contatore = 0;
        $arrayMesi = array();
        $msg = "";
        for ($curRow = $firstRow; $curRow < $numRows; ++$curRow) {
    
            if(!isset($spreadSheetAry[$curRow][0])) {
                continue;
            }

            $serieDoc = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_SERIE_DOC]));
            $nrDoc = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_NUMERO_DOC]));
            $dataDoc = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_DATA_DOC]));
            $matricola = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_MATRICOLA]));
            $codCommessa = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_COMMESSA]));
            $codAtv = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_ATV]));
            $codSottoComm = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_SOTTO_COMM]));
            $numOre = mysqli_real_escape_string($con, trim($spreadSheetAry[$curRow][COL_NUM_ORE]));
            //echo $matricola.' '.$codSottoComm.' '.$numOre;
            if (!$codCommessa || !$dataDoc || !$matricola || !$codAtv || !$serieDoc || !$nrDoc || !$codSottoComm) {
                $message->error .= "Campi obbligatori non valorizzati alla riga $curRow<br/>";
                continue;
            }
            if( strlen($codCommessa) > 50) {
                $message->error .= "Campi codCommessa troppo lungo alla riga $curRow<br/>";
                continue;
            }
            if( strlen($matricola) > 50 ) {
                $message->error .= "Campi matricola troppo lungo alla riga $curRow<br/>";
                continue;
            }
            if( strlen($codAtv) > 30 ) {
                $message->error .= "Campi codAtv troppo lungo alla riga $curRow<br/>";
                continue;
            }
            if( strlen($serieDoc) > 10 ) {
                $message->error .= "Campi serieDoc troppo lungo alla riga $curRow<br/>";
                continue;
            }
            if( strlen($nrDoc) > 50) {
                $message->error .= "Campi nrDoc troppo lungo alla riga $curRow<br/>";
                continue;
            }

            if( strlen($codSottoComm) > 50) {
                $message->error .= "Campi codSottoComm troppo lungo alla riga $curRow<br/>";
                continue;
            }

            if (!$numOre) {
                $numOre = 0.0;
            }

            $dataDocDt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dataDoc);
            if (!$dataDocDt) {
                $message->error .= "Formato data errato alla riga $curRow: $dataDoc<br/>";
                continue;
            }
            $dataDoc = $dataDocDt->format('Y-m-d');
            $time=strtotime($dataDoc);
            $mese = date('F',$time);
            if (!in_array($mese, $arrayMesi)) {
                //print_r($arrayMesi);
                //echo 'inserisco '.$mese.' da data: '.$dataDoc;
                array_push($arrayMesi, $mese);
            }            
            $arrayNomiMesi = array("Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre");
            //$mese = $arrayMesi[intval($meseSheet)-1];
           
            $query = "REPLACE INTO ore_consuntivate_commesse (COD_COMMESSA,ID_DIPENDENTE,DATA,RIF_SERIE_DOC,RIF_NUMERO_DOC,RIF_ATV,RIF_SOTTO_COMMESSA,NUM_ORE_LAVORATE,ID_CARICAMENTO) " .
                        "VALUES('$codCommessa','$matricola','$dataDoc','$serieDoc','$nrDoc','$codAtv','$codSottoComm','$numOre',$idCaricamento)";
            //echo '</br>curRow :'.$curRow.' -> '.$query.'</br>';
            execute_update($query);
            
            //echo '</br>curRow :'.$curRow.' -> '.$query.' - result:'.$resultQuery.'</br>';
            
            ++$contatore;
        }
        $mesiText = "";
        foreach ($arrayMesi as $key => $value) {
            $mesiText .= $arrayNomiMesi[$key].' '; 
        }
        $message->success .= "Caricamento concluso.<br/> 
                              $contatore righe caricate.<br/>
                              Mese/i caricati: ".$mesiText;
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

    function nuovo_caricamentoRd() {
        global $con, $logged_user;
        
        $con->begin_transaction();
        try {
            $query_max = "SELECT NVL(MAX(ID_CARICAMENTO),0)+1 FROM caricamenti_rd ";
            $id = select_single_value($query_max);

            $query ="INSERT INTO caricamenti_rd (ID_CARICAMENTO, UTENTE) VALUES ('$id', '$logged_user->nome_utente')";
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
    function get_caricamenti_rd($skip=null, $top=null, $orderby=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM caricamenti_rd p ";
        
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
    function get_progetti_ore_presenza_progetti() {
        global $con;
        $sql = "SELECT DISTINCT PROGETTO FROM ore_presenza_progetti p ORDER BY progetto DESC";
        //echo $sql;
        $oggetti = select_list($sql);        
        return $oggetti;
    }

    function get_ore_presenza_progetti($skip=null, $top=null, $orderby=null, $matricola=null, $month=null, $dataInizio=null, $dataFine=null, $searchProgetto=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM ore_presenza_progetti p WHERE 1 ";
        

        if (($dataInizio !== null && $dataInizio !== "") && ($dataFine !== null && $dataFine !== "")) {
            $sql .= "AND (data BETWEEN '$dataInizio' AND '$dataFine') ";
        } else {
            if ($month !== null && $month !== '') {
                // in forma YYYY-MM
                $month = substr($con->escape_string($month), 0, 7);
                $first = "DATE('$month-01')";
                $sql .= "AND (data BETWEEN $first AND LAST_DAY($first)) ";
            }
        }
        if ($matricola !== null && $matricola !== '') {
            $matricola = $con->escape_string($matricola);
            $sql .= "AND (MATRICOLA_DIPENDENTE='$matricola' or ID_DIPENDENTE='$matricola')";
        }

        if($searchProgetto !== null && $searchProgetto !== ''){
            $sql .= "AND PROGETTO='$searchProgetto'";
        }

        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY data ASC, p.id_caricamento DESC";
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

    function get_caricamento_rd($id_esecuzione) {
        $sql = "SELECT * FROM caricamenti_rd WHERE id_caricamento = '$id_esecuzione'";
        return select_single($sql);
    }

    function elimina_caricamento($idCaricamento) {
        // SI PUO' FARE SOLO SE NON E' GIA' STATO UTILIZZATO
        $query = "DELETE FROM ore_consuntivate_commesse WHERE ID_CARICAMENTO=$idCaricamento ";
        execute_update($query);

        $query = "DELETE FROM caricamenti WHERE ID_CARICAMENTO=$idCaricamento ";
        execute_update($query);
    }

    function elimina_caricamento_rd($idCaricamento) {
        // SI PUO' FARE SOLO SE NON E' GIA' STATO UTILIZZATO
        $query = "DELETE FROM ore_presenza_progetti WHERE ID_CARICAMENTO=$idCaricamento ";
        execute_update($query);

        $query = "DELETE FROM caricamenti_rd WHERE ID_CARICAMENTO=$idCaricamento ";
        execute_update($query);
    }

    function getMeseProgetto($anno, $mese, $data_inizio_progetto) {
        $d1 = date_create($data_inizio_progetto);
        $d2 = date_create("$anno-$mese-01");
        return date_diff($d2, $d1)->format('%m') + 1;
    }
    function get_ore_commesseDettagli($month=null, $matricola=null, $dataInizio=null, $dataFine=null) {
        global $con;
        $sqlplus  = "";
        
        $sqlComm = "SELECT count(*) FROM `ore_consuntivate_commesse` occ JOIN commesse c ON c.COD_COMMESSA = occ.COD_COMMESSA where TIPOLOGIA IN ('Commesse compatibili') ";
        $sqlDip = "SELECT count(DISTINCT ID_DIPENDENTE) FROM `ore_consuntivate_commesse` WHERE 1 ";
        $sqlPart = "SELECT count(DISTINCT ID_DIPENDENTE) FROM `partecipanti_globali` ";

        if($dataFine !== null && $dataInizio !== null) {
            $sqlplus .= "AND (data BETWEEN '$dataInizio' AND '$dataFine') ";
        } else {
            if ($month !== null && $month !== '') {
                // in forma YYYY-MM
                $month = substr($con->escape_string($month), 0, 7);
                $first = "DATE('$month-01')";
                $sqlplus .= "AND (data BETWEEN $first AND LAST_DAY($first)) ";
            }
        }        
        if ($matricola !== null && $matricola !== '') {
            $matricola = $con->escape_string($matricola);
            $sqlplus .= "AND ID_DIPENDENTE='$matricola' ";
        }

        $countComm = select_single_value($sqlComm.$sqlplus);
        $countDip = select_single_value($sqlDip.$sqlplus);
        $countPart = select_single_value($sqlPart);
        return [$countComm, $countDip, $countPart];
    }

    function get_ore_commesse($skip=null, $top=null, $orderby=null, $month=null, $matricola=null, $dataInizio=null, $dataFine=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM ore_consuntivate_commesse WHERE 1 ";
        
        if($dataFine !== null && $dataInizio !== null) {
            $sql .= "AND (data BETWEEN '$dataInizio' AND '$dataFine') ";
        } else {
            if ($month !== null && $month !== '') {
                // in forma YYYY-MM
                $month = substr($con->escape_string($month), 0, 7);
                $first = "DATE('$month-01')";
                $sql .= "AND (data BETWEEN $first AND LAST_DAY($first)) ";
            }
        }
        
        if ($matricola !== null && $matricola !== '') {
            $matricola = $con->escape_string($matricola);
            $sql .= "AND ID_DIPENDENTE='$matricola' ";
        }

        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY ID_DIPENDENTE, DATA, COD_COMMESSA, RIF_SOTTO_COMMESSA";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null) {
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }        
        //echo $sql1 . $sql;
        $oggetti = select_list($sql1 . $sql);
        
        return [$oggetti, $count];
    }

    /**
     * Arrotonda un numero a 0.5
     */
    function arrotonda05($number_or_str) {
        $number = floatval($number_or_str);
        return round($number * 2) / 2;
    }
}
?>