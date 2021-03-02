<?php

$budget = new ReportBudgetManager();

class ReportBudgetManager {

    function get_consuntivi($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT id_progetto, id_wp, matricola_dipendente, data, ore_lavorate, costo_orario " . $partial;
        return select_list($sql);
    }

    function get_consuntivi_per_matricola($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT id_progetto, matricola_dipendente, sum(ore_lavorate) as ore_lavorate, " .
            "sum(ore_lavorate*costo_orario) as costo " .
            $partial .
            "GROUP BY matricola_dipendente";
        return select_list($sql);
    }

    function get_consuntivi_per_wp($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT id_progetto, id_wp, sum(ore_lavorate) as ore_lavorate, " .
            "sum(ore_lavorate*costo_orario) as costo " .
            $partial .
            "GROUP BY id_progetto, id_wp";
        return select_list($sql);
    }

    function get_consuntivi_per_progetto($id_progetto, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT id_progetto, sum(ore_lavorate) as ore_lavorate, " .
            "sum(ore_lavorate*costo_orario) as costo " .
            $partial .
            "GROUP BY id_progetto";
        return select_single($sql);
    }

    function _create_where_condiction_consuntivi($id_progetto=null, $anno=null, $mese=null) {
        $sql = "FROM ore_consuntivate WHERE 1=1 ";
        if (!empty($id_progetto)) {
            $sql .= "AND id_progetto = '$id_progetto' ";
        }
        if (!empty($anno) and !empty($mese)) {
            $primo = "DATE('$anno-$mese-01')";
            $sql .= "AND DATA >= $primo AND DATA <= LAST_DAY($primo) ";
        }
        return $sql;
    }

    function update_costi_progetto($idprogetto, $anno=null, $mese=null) {
        global $panthera;
        
        if (!empty($anno) and !empty($mese)) {
            $dataInizio = "$anno-$mese-01";
            $dataFine = (new DateTime($dataInizio))->format( 'Y-m-t' );
        } else {
            $dataInizio = $progetto["DATA_INIZIO"];
            $dataFine = $progetto["DATA_FINE"];
        }
        
        // ELIMINO I COSTI PREESISTENTI
        $query = "UPDATE consuntivi_ore SET COSTO_ORARIO=NULL WHERE ID_PROGETTO=$idprogetto AND DATA >= '$dataInizio' AND DATA <= '$dataFine' ";
        
        // AGGIORNO I COSTI
        $costi = $panthera->getMatriceCosti($dataInizio, $dataFine);
        foreach ($costi as $c) {
            $query = "UPDATE consuntivi_ore SET COSTO_ORARIO=" . $c["COSTO"] . " WHERE ID_PROGETTO=$idprogetto AND MATRICOLA_DIPENDENTE='" . $c["ID_RISORSA"] . "' ";
            if (!empty($c["DATA_COSTO"])) {
                $query .= "AND DATA >= '" . $c["DATA_COSTO"] . "' ";
            }
            if (!empty($c["DATA_FINE_COSTO"])) {
                $query .= "AND DATA >= '" . $c["DATA_COSTO"] . "' ";
            }
            execute_update($query);
        }
        
        // VERIFICO COSTI MANCANTI
        $query = "SELECT DISTINCT MATRICOLA_DIPENDENTE FROM consuntivi_ore " .
                "WHERE ID_PROGETTO=$idprogetto " .
                "AND DATA >= '$dataInizio' AND DATA <= '$dataFine' AND COSTO_ORARIO IS NULL";
        $mancanti = select_column($query);
        $msg = "";
        if (count($mancanti) > 0) {
            $msg = "Impossibile i costi per le matricole: " . implode(", ", $mancanti);
        }
        
        return $msg;
    }

    function carica_totali_progetto($progetto, $anno=null, $mese=null) {
        
        // Devo generare i totali
        // - per progetto
        // - per WP
        // - per WP/matricola/giorno
        // in ogni caso, ho bisogno del dettaglio WP/matricola/giorno per farei i calcoli,
        // perchè i costi cambiano giornalmente
        
        
        // TODO
        
    }
    
    function sendReport($idprogetto, $anno, $mese, $completo) {
        // anno e mese sono facoltativi
        // see http://www.fpdf.org/en/doc/index.php

        global $progettiManager;
        
        $progetto = $progettiManager->get_progetto($idprogetto);
        $totali_ore = $rapportini->carica_totali_progetto($progetto, $anno, $mese);

        if (empty($progetto)) {
            print_error(404, 'Wrong idProgetto');
        }
        $progetto = $progetto[0]; //FIXME non dovrebbe esserci

        global $panthera;
        $nomecognome_super = $panthera->getUtente($progetto['MATRICOLA_SUPERVISOR']);

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
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Da-a:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["DATA_INIZIO"] . '-' . $progetto["DATA_FINE"], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Monte ore:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["MONTE_ORE_TOT"], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Budget EUR:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, sprintf("%.2f", $progetto["MONTE_ORE_TOT"] * $progetto["COSTO_MEDIO_UOMO"]), 0, 1);
        
        $pdf->Ln();
        
        $pdf->SetFont('Arial', 'B', 12);
        if (empty($anno) || empty($mese)) {
            $pdf->Cell($COLUMN_WIDTH * 2, $ROW_HEIGHT, 'Situazione consuntivo');
        } else {
            $pdf->Cell($COLUMN_WIDTH * 2, $ROW_HEIGHT, 'Situazione consuntivo per la mensilità ' . $anno . "-" . $mese);
        }
        
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, 'Monte ore:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell($COLUMN_WIDTH, $ROW_HEIGHT, $progetto["MONTE_ORE_TOT"], 0, 1);
        
        $pdf->Output('Budget.pdf', 'I'); // Download
    }
  
}
?>