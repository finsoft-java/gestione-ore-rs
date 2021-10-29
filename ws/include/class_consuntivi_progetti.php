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

        // Questa query fa una FULL JOIN di tutte le commesse/persone/date associate al progetto
        // L'ordinamento per PCT_COMPATIBILITA serve dopo, per decurtare i LUL
        $query_caricamenti = "SELECT
                c.COD_COMMESSA,c.PCT_COMPATIBILITA,
                p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO,
                oc.DATA,NVL(oc.ORE_LAVORATE,0) as ORE_LAVORATE,oc.RIF_DOC,oc.RIF_RIGA_DOC,
                FLOOR(p.PCT_IMPIEGO*NVL(oc.ORE_LAVORATE,0)*4/100)/4 as ORE_COMPATIBILI,
                0 as ORE_INUTILIZZABILI
            FROM progetti_commesse c
            JOIN progetti_persone p ON c.ID_PROGETTO=p.ID_PROGETTO
            JOIN progetti pr ON pr.ID_PROGETTO=p.ID_PROGETTO
            LEFT JOIN ore_consuntivate_commesse oc ON oc.COD_COMMESSA=c.COD_COMMESSA 
                AND oc.MATRICOLA_DIPENDENTE=p.MATRICOLA_DIPENDENTE
            WHERE pr.ID_PROGETTO=$idProgetto AND (oc.DATA IS NULL OR (oc.DATA > pr.DATA_ULTIMO_REPORT and oc.DATA < $dataLimite))
            ORDER BY c.PCT_COMPATIBILITA,c.COD_COMMESSA,p.MATRICOLA_DIPENDENTE";
        $caricamenti = select_list($query_caricamenti);

        $query_commesse_p = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA>=100";
        $commesse_p = select_column($query_commesse_p);
        $query_commesse_c = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA<100";
        $commesse_c = select_column($query_commesse_c);
        $query_dipendenti = "SELECT DISTINCT MATRICOLA_DIPENDENTE FROM progetti_persone WHERE id_progetto=$idProgetto";
        $dipendenti = select_column($query_dipendenti);

        $message->success .= "Commesse di progetto assegnate: " . implode(", ", $commesse_p) . NL;
        $tot_di_progetto = 0.0;
        foreach($commesse_p as $comm) {
            $tot = 0.0;
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c['ORE_LAVORATE'];
                }
            }
            $message->success .= "  $comm: trovate $tot ore" . NL;
            $tot_di_progetto += $tot;
        }
        $message->success .= "<b>Tot. $tot_di_progetto ore di progetto</b>". NL;
        
        $message->success .= "Commesse compatibili assegnate: " . implode(", ", $commesse_c) . NL;
        $tot_compat = 0.0;
        foreach($commesse_c as $comm) {
            $tot = 0.0;
            $compatibili = 0.0;
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c['ORE_LAVORATE'];
                    $compatibili += $c['ORE_COMPATIBILI'];
                }
            }
            $message->success .= "  $comm: trovate $tot ore * $c[PCT_COMPATIBILITA]% = $compatibili ore compatibili" . NL;
            $tot_compat += $compatibili;
        }

        $message->success .= "<b>Tot. $tot_compat ore su commesse compatibili</b>". NL;

        // VERIFICA LUL
        // ATTENZIONE controllo la differenza ore solo sul progetto corrente!
        // SECONDO, trovo una discrepanza tra la somma ore e il LUL, ma a volte non so a quale commessa imputare tale differenza
        $query_lul = "SELECT
            oc.MATRICOLA_DIPENDENTE,
            oc.DATA as DATA,
            SUM(NVL(oc.ORE_LAVORATE,0)) as SUM_ORE_LAVORATE,
            SUM(NVL(l.ORE_PRESENZA_ORDINARIE,0)) as SUM_ORE_PRESENZA_ORDINARIE
            FROM ore_consuntivate_commesse oc
            JOIN progetti_commesse c ON c.COD_COMMESSA=oc.COD_COMMESSA
            JOIN progetti pr ON c.id_progetto=pr.id_progetto
            LEFT JOIN ore_presenza_lul l ON l.MATRICOLA_DIPENDENTE=oc.MATRICOLA_DIPENDENTE AND l.DATA=oc.DATA
            WHERE c.ID_PROGETTO=$idProgetto AND (oc.DATA IS NULL OR (oc.DATA > pr.DATA_ULTIMO_REPORT and oc.DATA < $dataLimite))
            GROUP BY oc.MATRICOLA_DIPENDENTE,oc.DATA
            HAVING SUM_ORE_LAVORATE>SUM_ORE_PRESENZA_ORDINARIE";
        $lul = select_list($query_lul);
        $tot_eccesso = 0.0;
        if (count($lul) == 0) {
            $message->success .= "Verifica LUL: ok." . NL;
        } else {
            $message->success .= "Verifica LUL" . NL;
            foreach($lul as $r) {
                $diff = $r['SUM_ORE_LAVORATE'] - $r['SUM_ORE_PRESENZA_ORDINARIE'];
                $message->success .= "$r[MATRICOLA_DIPENDENTE] il $r[DATA] ha $diff ore in eccesso" . NL;
                $tot_eccesso += $diff;
                if ($diff > 0) {
                    foreach($caricamenti as $id => $c) {
                        if ($c['DATA'] == $r['DATA'] && $c['MATRICOLA_DIPENDENTE'] == $r['MATRICOLA_DIPENDENTE']) {
                            // decurto da qui le ore in eccesso
                            if ($c['ORE_LAVORATE'] - $c['ORE_INUTILIZZABILI'] <= $diff) {
                                $caricamenti[$id]['ORE_INUTILIZZABILI'] += $c['ORE_LAVORATE'];
                                $diff -= $c['ORE_LAVORATE'];
                            } else {
                                $caricamenti[$id]['ORE_INUTILIZZABILI'] += $diff;
                                $diff = 0;
                                break;
                            }
                        }
                    }
                    if ($diff > 0) {
                        $message->error .= "Qualcosa non va, non dovrei essere qui" . NL;
                    }
                }
            }
            $message->success .= "Tot. $tot_eccesso ore non utilizzabili." . NL;

            $tot_di_progetto = 0.0;
            foreach($commesse_p as $comm) {
                $tot = 0.0;
                foreach($caricamenti as $id => $c) {
                    if ($c['COD_COMMESSA'] == $comm) {
                        $tot += $c['ORE_LAVORATE'] - $c['ORE_INUTILIZZABILI'];
                    }
                }
                $tot_di_progetto += $tot;
            }
            $message->success .= "<b>Tot. $tot_di_progetto ore di progetto 'pulite'</b>". NL;

            $tot_compat = 0.0;
            foreach($commesse_c as $comm) {
                $tot = 0.0;
                $compatibili = 0.0;
                foreach($caricamenti as $id => $c) {
                    if ($c['COD_COMMESSA'] == $comm) {
                        $tot += $c['ORE_LAVORATE'] - $c['ORE_INUTILIZZABILI'];
                        $compatibili += round($tot * $c['PCT_COMPATIBILITA'] *4 / 100, PHP_ROUND_HALF_DOWN) / 4;
                    }
                }
                $tot_compat += $compatibili;
            }

            $message->success .= "<b>Tot. $tot_compat ore su commesse compatibili 'pulite'</b>". NL;
        }


        $message->success .= "<b>Tot. " . ($tot_di_progetto + $tot_compat - $tot_eccesso) . " ore assegnate</b>". NL;
        
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