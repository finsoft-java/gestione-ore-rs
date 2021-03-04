<?php

$budget = new ReportBudgetManager();

class ReportBudgetManager {

    function get_consuntivi($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT ID_PROGETTO, ID_WP, MATRICOLA_DIPENDENTE, DATA, ORE_LAVORATE, costo_orario " . $partial;
        return select_list($sql);
    }

    function get_consuntivi_per_matricola($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT ID_PROGETTO, MATRICOLA_DIPENDENTE, SUM(ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(ORE_LAVORATE*COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY MATRICOLA_DIPENDENTE";
        return select_list($sql);
    }

    function get_consuntivi_per_wp($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT ID_PROGETTO, ID_WP, sum(ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(ORE_LAVORATE*COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY ID_PROGETTO, ID_WP";
        return select_list($sql);
    }

    function get_consuntivi_per_progetto($id_progetto, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT ID_PROGETTO, SUM(ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(ORE_LAVORATE*COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY ID_PROGETTO";
        return select_single($sql);
    }

    function _create_where_condiction_consuntivi($id_progetto=null, $anno=null, $mese=null) {
        $sql = "FROM ore_consuntivate WHERE 1=1 ";
        if (!empty($id_progetto)) {
            $sql .= "AND ID_PROGETTO = '$id_progetto' ";
        }
        if (!empty($anno) and !empty($mese)) {
            $primo = "DATE('$anno-$mese-01')";
            $sql .= "AND DATA >= $primo AND DATA <= LAST_DAY($primo) ";
        }
        return $sql;
    }

    function update_costi_progetto($idprogetto, $anno=null, $mese=null) {
        global $panthera;
        
        $query_tipo_costo = "SELECT COD_TIPO_COSTO_PANTHERA FROM PROGETTI WHERE ID_PROGETTO=$idprogetto";
        $tipoCosto = select_single_value($query_tipo_costo);

        if (!empty($anno) and !empty($mese)) {
            $dataInizio = "$anno-$mese-01";
            $dataFine = (new DateTime($dataInizio))->format( 'Y-m-t' );
        } else {
            $dataInizio = $progetto["DATA_INIZIO"];
            $dataFine = $progetto["DATA_FINE"];
        }
        
        // ELIMINO I COSTI PREESISTENTI
        $query = "UPDATE ore_consuntivate SET COSTO_ORARIO=NULL WHERE ID_PROGETTO=$idprogetto AND DATA >= '$dataInizio' AND DATA <= '$dataFine' ";
        
        // AGGIORNO I COSTI
        $costi = $panthera->getMatriceCosti($dataInizio, $dataFine, $tipoCosto);
        foreach ($costi as $c) {
            $query = "UPDATE ore_consuntivate SET COSTO_ORARIO=" . $c["COSTO"] . " WHERE ID_PROGETTO=$idprogetto AND MATRICOLA_DIPENDENTE='" . $c["ID_RISORSA"] . "' ";
            if (!empty($c["DATA_COSTO"])) {
                $query .= "AND DATA >= '" . $c["DATA_COSTO"] . "' ";
            }
            if (!empty($c["DATA_FINE_COSTO"])) {
                $query .= "AND DATA <= '" . $c["DATA_FINE_COSTO"] . "' ";
            }
            execute_update($query);
        }
        
        // VERIFICO COSTI MANCANTI
        $query = "SELECT DISTINCT MATRICOLA_DIPENDENTE FROM ore_consuntivate " .
                "WHERE ID_PROGETTO=$idprogetto " .
                "AND DATA >= '$dataInizio' AND DATA <= '$dataFine' AND COSTO_ORARIO IS NULL";
        $mancanti = select_column($query);
        $msg = "";
        if (count($mancanti) > 0) {
            $msg = "Costi su Panthera non trovati per le matricole: " . implode(", ", $mancanti);
        }
        
        return $msg;
    }
    
    function sendReport($idprogetto, $anno, $mese, $completo) {
        // anno e mese sono facoltativi
        // see http://www.fpdf.org/en/doc/index.php

        global $progettiManager;
        
        $progetto = $progettiManager->get_progetto($idprogetto);
        $warning = $this->update_costi_progetto($idprogetto, $anno, $mese);
        $totali = $this->get_consuntivi_per_progetto($idprogetto, $anno, $mese);
        // TODO decidere quali parziali prendere
        
        if (empty($progetto)) {
            print_error(404, 'Wrong idProgetto');
        }

        global $panthera;
        $nomecognome_super = $panthera->getUtente($progetto['MATRICOLA_SUPERVISOR']);
        
        $budget = $progetto["MONTE_ORE_TOT"] * $progetto["COSTO_MEDIO_UOMO"];
        $scarto_ore = ($totali["ORE_LAVORATE"] - $progetto["MONTE_ORE_TOT"]) / $progetto["MONTE_ORE_TOT"] * 100;
        $scarto_costi = ($totali["COSTO"] - $budget) / $budget * 100;

        $ROW_HEIGHT = 10;
        $COLUMN_WIDTH = 50;
        $pdf = new FPDF(); 
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Titolo');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["TITOLO"], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Acronimo');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["ACRONIMO"], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Grant n.');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["GRANT_NUMBER"], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Supervisor');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $nomecognome_super, 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Data inizio:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["DATA_INIZIO"], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Data fine:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["DATA_FINE"], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Monte ore:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["MONTE_ORE_TOT"], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Budget EUR:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, sprintf("%.2f", $budget), 0, 1);
        
        $pdf->Ln();
        
        $pdf->SetFont('Arial', 'B', 12);
        if (empty($anno) || empty($mese)) {
            $pdf->Cell($COLUMN_WIDTH * 2, $ROW_HEIGHT, 'Situazione consuntivo');
        } else {
            $pdf->Cell($COLUMN_WIDTH * 2, $ROW_HEIGHT, 'Situazione consuntivo per il mese ' . $anno . "-" . $mese);
        }
        
        $pdf->Ln();
        
        if ($scarto_ore > 0) $scarto_ore = '+' . sprintf("%.2f", $scarto_ore) . '%';
        else if ($scarto_ore < 0) $scarto_ore = sprintf("%.2f", $scarto_ore) . '%';
        else $scarto_ore = '';
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Ore lavorate:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH / 2, $ROW_HEIGHT, $totali["ORE_LAVORATE"], 0, 0);
        $pdf->Cell($COLUMN_WIDTH / 2, $ROW_HEIGHT, $scarto_ore, 0, 1);
        
        if (empty($totali["COSTO"])) { $totali["COSTO"] = '-'; $scarto_costi = ''; }
        else if ($scarto_costi > 0) $scarto_costi = '+' . sprintf("%.2f", $scarto_costi) . '%';
        else if ($scarto_costi < 0) $scarto_costi = sprintf("%.2f", $scarto_costi) . '%';
        else $scarto_costi = '';
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Costo EUR:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH / 2, $ROW_HEIGHT, $totali["COSTO"], 0, 0);
        $pdf->Cell($COLUMN_WIDTH / 2, $ROW_HEIGHT, $scarto_costi, 0, 1);
        
        if (!empty($warning)) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Warning:');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $warning, 0, 1);
        }
        
        $pdf->Output('Budget.pdf', 'I'); // Download
    }
    
    function getReportData($idprogetto, $anno, $mese, $completo) {

        global $progettiManager, $panthera;

        $report = [];

        $report["progetto"] = $progettiManager->get_progetto($idprogetto);

        if (empty($report["progetto"])) {
            print_error(404, 'Wrong idProgetto');
        }

        $report["warning"] = $this->update_costi_progetto($idprogetto, $anno, $mese);
        $report["progetto"]["NOME_COGNOME_SUPERVISOR"] = $panthera->getUtente($progetto['MATRICOLA_SUPERVISOR']);
        
        $report["budget"] = $progetto["MONTE_ORE_TOT"] * $progetto["COSTO_MEDIO_UOMO"];

        $report["consuntivi"] = $this->get_consuntivi_per_progetto($idprogetto, $anno, $mese);
        // ha due campi, ORE_LAVORATE e COSTO
        
        if (empty($progetto["MONTE_ORE_TOT"])) {
            // should not happen
            $report["consuntivi"]["PCT_SCARTO_TEMPI"] = null;
        } else {
            $monte_ore = $report["progetto"]["MONTE_ORE_TOT"];
            $report["consuntivi"]["PCT_SCARTO_TEMPI"] = ($report["consuntivi"]["ORE_LAVORATE"] - $monte_ore) / $monte_ore * 100;
        }

        if (empty($report["budget"])) {
            // this may happen
            $report["consuntivi"]["SCARTO_COSTI"] = null;
        } else {
            $budget = $report["budget"];
            $report["consuntivi"]["SCARTO_COSTI"] = ($report["consuntivi"]["COSTO"] - $budget) / $budget * 100;
        }
        
        if ($completo) {
            $dettagli = [];
            
            
            
            $report["consuntivi"]["dettagli"] = $dettagli;
        }

        return $report;
    }
  
}
?>