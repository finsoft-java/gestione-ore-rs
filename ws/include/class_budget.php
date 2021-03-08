<?php

$budget = new ReportBudgetManager();

class ReportBudgetManager {

    function get_consuntivi($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT c.ID_PROGETTO, p.TITOLO, c.MATRICOLA_DIPENDENTE, c.ID_WP, wp.TITOLO as TITOLO_WP, c.DATA, c.ORE_LAVORATE, c.COSTO_ORARIO " . $partial;
        return select_list($sql);
    }

    /*function get_consuntivi_per_matricola_wp($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT c.ID_PROGETTO, p.TITOLO, c.MATRICOLA_DIPENDENTE, c.ID_WP, wp.TITOLO as TITOLO_WP, SUM(c.ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(c.ORE_LAVORATE * c.COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY c.ID_PROGETTO, c.MATRICOLA_DIPENDENTE, c.ID_WP";
        return select_list($sql);
    }

    function get_consuntivi_per_matricola($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT c.ID_PROGETTO, p.TITOLO, c.MATRICOLA_DIPENDENTE, SUM(c.ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(c.ORE_LAVORATE * c.COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY c.ID_PROGETTO, c.MATRICOLA_DIPENDENTE";
        return select_list($sql);
    }

    function get_consuntivi_per_wp($id_progetto=null, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT c.ID_PROGETTO, c.ID_WP, sum(c.ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(c.ORE_LAVORATE * c.COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY c.ID_PROGETTO, c.ID_WP";
        return select_list($sql);
    }*/

    function get_consuntivi_per_progetto($id_progetto, $anno=null, $mese=null) {
        $partial = $this->_create_where_condiction_consuntivi($id_progetto, $anno, $mese);
        $sql = "SELECT c.ID_PROGETTO, SUM(c.ORE_LAVORATE) as ORE_LAVORATE, " .
            "SUM(c.ORE_LAVORATE * c.COSTO_ORARIO) as COSTO " .
            $partial .
            "GROUP BY c.ID_PROGETTO";
        return select_single($sql);
    }

    function _create_where_condiction_consuntivi($id_progetto=null, $anno=null, $mese=null) {
        $sql = "FROM ore_consuntivate c " .
                "JOIN PROGETTI_WP wp ON wp.id_progetto=c.id_progetto AND wp.id_wp=c.id_wp ".
                "JOIN PROGETTI p ON wp.id_progetto=p.id_progetto " .
                "WHERE 1=1 ";
        if (!empty($id_progetto)) {
            $sql .= "AND p.ID_PROGETTO = '$id_progetto' ";
        }
        if (!empty($anno) and !empty($mese)) {
            $primo = "DATE('$anno-$mese-01')";
            $sql .= "AND c.DATA >= $primo AND c.DATA <= LAST_DAY($primo) ";
        }
        return $sql;
    }

    function get_matricole_progetto($id_progetto) {
        $sql = "SELECT MATRICOLA_DIPENDENTE FROM progetti_wp_risorse WHERE ID_PROGETTO = '$id_progetto' ORDER BY 1";
        return select_list($sql); // voglio proprio una lista di oggetti, non una colonna
    }

    function get_wp_matricola($id_progetto, $matricola) {
        $sql = "SELECT wp.ID_WP, wp.TITOLO FROM progetti_wp wp " .
                "JOIN progetti_wp_risorse r ON r.id_progetto=wp.id_progetto AND r.id_wp=wp.id_wp " .
                "WHERE wp.ID_PROGETTO = '$id_progetto' AND r.MATRICOLA_DIPENDENTE='$matricola' " .
                "ORDER BY wp.ID_WP";
        return select_list($sql);
    }

    function get_consuntivi_matricola_wp($id_progetto, $matricola, $id_wp, $anno=null, $mese=null) {
        $sql = "SELECT c.DATA, c.ORE_LAVORATE, (c.ORE_LAVORATE * c.COSTO_ORARIO) as COSTO " .
                "FROM ore_consuntivate c " .
                "WHERE ID_PROGETTO=$id_progetto AND MATRICOLA_DIPENDENTE='$matricola' AND ID_WP=$id_wp ";
        if (!empty($anno) and !empty($mese)) {
            $primo = "DATE('$anno-$mese-01')";
            $sql .= "AND c.DATA >= $primo AND c.DATA <= LAST_DAY($primo) ";
        }
        $sql .= "ORDER BY c.DATA";
        return select_list($sql);
    }

    function get_matrice_consuntivi_progetto($progetto, $anno=null, $mese=null) {
        
        $lista_matricole = $this->get_matricole_progetto($progetto['ID_PROGETTO']);
        // TODO dovrei decodificarle, in $lista_nomi_utenti

        foreach($lista_matricole as $key => $matricola) {
            $lista_wp = $this->get_wp_matricola($progetto['ID_PROGETTO'], $matricola['MATRICOLA_DIPENDENTE']);
            $totali_per_data = [ 'TITOLO' => 'TOT.' ];
            $totali_per_matricola = [ 'MATRICOLA_DIPENDENTE' => 'TOT.', 'ORE_LAVORATE' => 0, 'COSTO' => 0.0 ];
            foreach ($lista_wp as $key_wp => $wp) {
                $consuntivi = $this->get_consuntivi_matricola_wp($progetto['ID_PROGETTO'], $matricola['MATRICOLA_DIPENDENTE'], $wp['ID_WP']);
                $lista_wp[$key_wp]['DETTAGLI'] = $consuntivi;
                
                // totali riga
                $tot_ore = 0;
                $tot_costo = 0.0;
                foreach($consuntivi as $c) {
                    $tot_ore += $c['ORE_LAVORATE'];
                    $tot_costo += $c['COSTO'];
                }
                $lista_wp[$key_wp]['DETTAGLI']['TOT'] = [ 'ORE_LAVORATE' => $tot_ore, 'COSTO' => $tot_costo ];
                
                // totali colonna
                foreach($consuntivi as $c) {
                    if (!isset($totali_per_data[$c['DATA']])) $totali_per_data[$c['DATA']] = [ 'ORE_LAVORATE' => 0, 'COSTO' => 0.0 ];
                    $totali_per_data[$c['DATA']]['ORE_LAVORATE'] += $c['ORE_LAVORATE'];
                    $totali_per_data[$c['DATA']]['COSTO'] += $c['COSTO'];
                }
                
                // totali matricola
                foreach($consuntivi as $c) {
                    $totali_per_matricola['ORE_LAVORATE'] += $c['ORE_LAVORATE'];
                    $totali_per_matricola['COSTO'] += $c['COSTO'];
                }
            }
            $lista_matricole[$key]['WP'] = $lista_wp;
            $lista_matricole[$key]['WP']['TOT'] = $totali_per_data;
            $lista_matricole['TOT'] = $totali_per_matricola;
        }
        
        // var_dump($lista_matricole); die();
        return $lista_matricole;
    }

    function update_costi_progetto($progetto, $anno=null, $mese=null) {
        global $panthera;
        $idprogetto = $progetto['ID_PROGETTO'];
        
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
    
    /**
    Genera report PDF
    ATTUALMENTE NON USATA!!!!!!!!!!!!!!!!!!!!!!!!
    */
    function sendReport($idprogetto, $anno, $mese, $completo) {
        // anno e mese sono facoltativi
        // see http://www.fpdf.org/en/doc/index.php

        global $progettiManager;
        
        $progetto = $progettiManager->get_progetto($idprogetto);
        $warning = $this->update_costi_progetto($progetto, $anno, $mese);
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

        $report['progetto'] = $progettiManager->get_progetto($idprogetto);

        if (empty($report['progetto'])) {
            print_error(404, 'Wrong idProgetto');
        }

        $report['warning'] = $this->update_costi_progetto($report['progetto'], $anno, $mese);
        $report['progetto']['NOME_COGNOME_SUPERVISOR'] = $panthera->getUtente($report['progetto']['MATRICOLA_SUPERVISOR']);
        
        $report['budget'] = $report['progetto']['MONTE_ORE_TOT'] * $report['progetto']['COSTO_MEDIO_UOMO'];

        $report['consuntivi'] = $this->get_consuntivi_per_progetto($idprogetto, $anno, $mese);
        // ha due campi, ORE_LAVORATE e COSTO
        
        if (empty($report['progetto']['MONTE_ORE_TOT'])) {
            // should not happen
            $report['consuntivi']['PCT_SCARTO_TEMPI'] = null;
        } else {
            $monte_ore = $report['progetto']['MONTE_ORE_TOT'];
            $report['consuntivi']['PCT_SCARTO_TEMPI'] = ($report['consuntivi']['ORE_LAVORATE'] - $monte_ore) / $monte_ore * 100;
        }

        if (empty($report['budget'])) {
            // this may happen
            $report['consuntivi']['PCT_SCARTO_COSTI'] = null;
        } else {
            $budget = $report['budget'];
            $report['consuntivi']['PCT_SCARTO_COSTI'] = ($report['consuntivi']['COSTO'] - $budget) / $budget * 100;
        }
        
        if ($completo) {
            $report['consuntivi']['dettagli'] = $this->get_matrice_consuntivi_progetto($report['progetto'], $anno, $mese);
        }

        return $report;
    }
    
    function get_range_temporale($progetto, $anno=null, $mese=null) {
        if (!empty($anno) and !empty($mese)) {
            $dataInizio = new DateTime("$anno-$mese-01");
            $dataFine = new DateTime($dataInizio->format( 'Y-m-t' ));
        } else {
            $dataInizio = new DateTime($progetto["DATA_INIZIO"]);
            $dataFine = new DateTime($progetto["DATA_FINE"]);
        }
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($dataInizio, $interval, $dataFine);
        $result = [];
        foreach($period as $day) {
            $result[] = $day->format('Y-m-d');
        }
        return $result;
    }

    function getReportHtml($idprogetto, $anno, $mese, $completo) {
        $data = $this->getReportData($idprogetto, $anno, $mese, $completo);
        $progetto = $data['progetto'];
        $consuntivi = $data['consuntivi'];
        
        $subreport_dettaglio = '';
        
        if ($completo) {
            $datePeriod = $this->get_range_temporale($progetto);
            
            $subreport_dettaglio = "
        <h3>Dettaglio</h3>
        ";
            foreach($data['consuntivi']['dettagli'] as $dettagli_matricola) {
                $subreport_dettaglio .= "
            <div class='row'>
                <div class='col-md-2 title'>
                Matricola:
                </div>
                <div class='col-md-8'>
                $dettagli_matricola[MATRICOLA_DIPENDENTE]
                </div>
            </div>
";
                if (isset($dettagli_matricola['WP'])) {
                    foreach($dettagli_matricola['WP'] as $dettagli_wp) {
                        $subreport_dettaglio .= "
            <div class='row'>
                <div class='title calendario'>
                $dettagli_wp[TITOLO]
                </div>";
                        foreach($datePeriod as $date) {
                            $ore = isset($dettagli_wp[$date]) ? $dettagli_wp[$date]['ORE_LAVORATE'] : '-';
                            $subreport_dettaglio .= "
                <div class='calendario'>
                $ore
                </div>";
                        }
                        $subreport_dettaglio .= "
            </div><!-- row -->
";
                    }
                }
            }
        }
        
        $report="<html>
    <head>
        <title>Report $progetto[ACRONIMO]</title>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css'/>
        <style>
        .title { font-weight: bold  }
        .calendario { width: 3%; float: left }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Consuntivo di progetto</h2>
            <div class='row'>
                <div class='col-md-2 title'>
                Titolo:
                </div>
                <div class='col-md-8'>
                $progetto[TITOLO]
                </div>
            </div>
            <div class='row'>
                <div class='col-md-2 title'>
                Acronimo:
                </div>
                <div class='col-md-8'>
                $progetto[ACRONIMO]
                </div>
            </div>
            <div class='row'>
                <div class='col-md-2 title'>
                Grant number:
                </div>
                <div class='col-md-8'>
                $progetto[GRANT_NUMBER]
                </div>
            </div>
            <div class='row'>
                <div class='col-md-2 title'>
                Monte ore previsto:
                </div>
                <div class='col-md-8'>
                $progetto[MONTE_ORE_TOT]
                </div>
            </div>
            <div class='row'>
                <div class='col-md-2 title'>
                Budget previsto &euro;:
                </div>
                <div class='col-md-8'>
                " . sprintf("%.2f", $data['budget']) . "
                </div>
            </div>" . ($data['warning'] ? "
            <div class='row'>
                <div class='col-md-2 title'>
                Warning:
                </div>
                <div class='col-md-8'>
                $data[warning]
                </div>
            </div>" : '') . "
            
            <h3>Consuntivi" . ((!empty($anno) and !empty($mese)) ? " nel mese $anno-$mese" : '') ."</h3>
            <div class='row'>
                <div class='col-md-2 title'>
                Monte ore consumato:
                </div>
                <div class='col-md-2'>
                $consuntivi[ORE_LAVORATE]
                </div>
                <div class='col-md-2'>
                " . ($consuntivi['PCT_SCARTO_TEMPI'] > 0 ? '+' : ''). "$consuntivi[PCT_SCARTO_TEMPI]%
                </div>
            </div>
            <div class='row'>
                <div class='col-md-2 title'>
                Costi sostenuti &euro;:
                </div>
                <div class='col-md-2'>
                " . sprintf("%.2f", $consuntivi['COSTO']) . "
                </div>
                <div class='col-md-2'>
                " . ($consuntivi['PCT_SCARTO_COSTI'] > 0 ? '+' : ''). "$consuntivi[PCT_SCARTO_COSTI]%
                </div>
            </div>
$subreport_dettaglio
        </div>
    </body>
</html>";
        return $report;
    }
  
}
?>