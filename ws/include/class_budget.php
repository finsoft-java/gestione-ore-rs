<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

$budget = new ReportBudgetManager();

class ReportBudgetManager {

    function creaReport($idprogetto, $anno, $mese, $completo) {
        // anno e mese sono facoltativi
        
        global $progettiManager;
        
        $progetto = $progettiManager->get_progetto($idprogetto);
        
        if (empty($progetto)) {
            print_error(404, 'Wrong idProgetto');
        }

        //global $panthera;
        //$nomecognome = $panthera->getUtente($matr);
        //$nomecognome_super = $panthera->getUtente($wp['MATRICOLA_SUPERVISOR']);

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

    
}
?>