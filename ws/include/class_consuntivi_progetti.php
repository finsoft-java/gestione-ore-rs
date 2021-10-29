<?php

$consuntiviProgettiManager = new ConsuntiviProgettiManager();

define('NL', "<br/>");

class ConsuntiviProgettiManager {
    
    function run_assegnazione($idProgetto, $date, &$message) {
        
        $message->success .= "Lancio assegnazione ore progetto n.$idProgetto alla data " . $date->format('d/m/Y') . NL;

        $dataLimite = "DATE('" . $date->format('Y-m-d') . "')";

        $query_monte_ore = "select MONTE_ORE_TOT-ORE_GIA_ASSEGNATE from progetti p where id_progetto=$idProgetto";
        $monte_ore = select_single_value($query_monte_ore);
        if ($monte_ore <= 0) {
            $message->error .= "Monte ore di progetto esaurito!" . NL;
            return;
        }
        $message->success .= "Monte ore residuo $monte_ore ore." . NL;

        // Questa query fa una FULL JOIN di tutte le commesse/persone associate al progetto
        $query_caricamenti = "SELECT
            c.COD_COMMESSA,c.PCT_COMPATIBILITA,
            p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO,
            oc.DATA,NVL(oc.ORE_LAVORATE,0) as ORE_LAVORATE,oc.RIF_DOC,oc.RIF_RIGA_DOC
            FROM progetti_commesse c
            JOIN progetti_persone p ON c.ID_PROGETTO=p.ID_PROGETTO
            JOIN progetti pr ON pr.ID_PROGETTO=p.ID_PROGETTO
            LEFT JOIN ore_consuntivate_commesse oc ON oc.COD_COMMESSA=c.COD_COMMESSA AND oc.MATRICOLA_DIPENDENTE=p.MATRICOLA_DIPENDENTE
            WHERE pr.ID_PROGETTO=$idProgetto AND (oc.DATA IS NULL OR (oc.DATA > pr.DATA_ULTIMO_REPORT and oc.DATA < $dataLimite))";
        $caricamenti = select_list($query_caricamenti);

        $query_commesse_p = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA>=100";
        $commesse_p = select_column($query_commesse_p);
        $query_commesse_c = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA<100";
        $commesse_c = select_column($query_commesse_c);

        $message->success .= "Commesse di progetto assegnate: " . implode(", ", $commesse_p) . NL;
        $tot_di_progetto = 0.0;
        foreach($commesse_p as $comm) {
            $tot = 0.0;
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c['ORE_LAVORATE'];
                    $caricamenti[$id]['ORE_COMPATIBILI'] = $c['ORE_LAVORATE'];
                }
            }
            $message->success .= "  $comm: trovate $tot ore" . NL;
            $tot_di_progetto += $tot;
        }
        $message->success .= "Tot. $tot_di_progetto ore di progetto". NL;
        
        $message->success .= "Commesse compatibili assegnate: " . implode(", ", $commesse_c) . NL;
        $tot_compat = 0.0;
        foreach($commesse_c as $comm) {
            $tot = 0.0;
            $utilizzabili = 0.0;
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c['ORE_LAVORATE'];
                    $compat = 1.0 * $c['ORE_LAVORATE'] * $c['PCT_COMPATIBILITA'] / 100;
                    // arrotondo al quarto d'ora
                    $caricamenti[$id]['ORE_COMPATIBILI'] = round($compat * 4, 0, PHP_ROUND_HALF_DOWN) / 4;
                    $utilizzabili += $compat;
                }
            }
            $message->success .= "  $comm: trovate $tot ore * $c[PCT_COMPATIBILITA]% = $utilizzabili ore compatibili" . NL;
            $tot_compat += $utilizzabili;
        }

        $message->success .= "Tot. $tot_compat ore su commesse compatibili". NL;

        // TODO ora dovrei controllare i LUL




        
        // SALVO SU DATABASE
        $query_max = "SELECT NVL(MAX(ID_ESECUZIONE),0)+1 FROM `storico_assegnazioni` WHERE 1";
        $idEsecuzione = select_single_value($query_max);

        $message->success .= "Salvo i dati ottenuti con ID_ESECUZIONE=$idEsecuzione" . NL;

        foreach($caricamenti as $c) {
            if (isset($c['ORE_COMPATIBILI']) && $c['ORE_COMPATIBILI'] > 0) {
                $query = "INSERT INTO `storico_assegnazioni` (`ID_ESECUZIONE`, `ID_PROGETTO`, `COD_COMMESSA`, `MATRICOLA_DIPENDENTE`, `DATA`, `RIF_DOC`, `RIF_RIGA_DOC`, `ORE_ASSEGNATE`)
                            VALUES ($idEsecuzione, $idProgetto, '$c[COD_COMMESSA]', '$c[MATRICOLA_DIPENDENTE]', '$c[DATA]', '$c[RIF_DOC]', '$c[RIF_RIGA_DOC]', '$c[ORE_COMPATIBILI]')";
                execute_update($query);
            }
        }

        // TODO: salvare anche la ore_consuntivate_progetti


        $message->success .= "Fine." . NL;
    }

}
?>