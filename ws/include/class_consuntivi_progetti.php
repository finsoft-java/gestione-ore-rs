<?php

$consuntiviProgettiManager = new ConsuntiviProgettiManager();

define('NL', "<br/>");
define('PRE', "1");
define('POST', "2");

class ConsuntiviProgettiManager {
    
    /**
     * Main procedure
     */
    function run_assegnazione($idProgetto, $dataLimite, &$message) {
        global $esecuzioniManager;
        try {
            $message->success .= "Lancio assegnazione ore progetto n.$idProgetto alla data " . $dataLimite->format('d/m/Y') . NL;
            
            $query_monte_ore = "select MONTE_ORE_TOT-ORE_GIA_ASSEGNATE from progetti p where id_progetto=$idProgetto";
            $monte_ore = select_single_value($query_monte_ore);
            if ($monte_ore > 0) {
                $message->success .= "Monte ore residuo $monte_ore ore." . NL;
            }

            list($commesse_p, $commesse_c, $matricole) = $this->load_commesse_e_dipendenti($idProgetto);

            if (count($commesse_p) == 0 && count($commesse_c) == 0) {
                $message->error .= "Nessuna commessa &egrave; stata configurata su questo progetto!" . NL;
                return;
            }
            if (count($matricole) == 0) {
                $message->error .= "Nessun dipendente &egrave; stato configurato su questo progetto!" . NL;
                return;
            }
            $this->check_commesse_dipendenti($idProgetto, $dataLimite, $message);

            $idEsecuzione = $esecuzioniManager->get_id_esecuzione($idProgetto, $message);

            $affected_rows = $this->estrazione_caricamenti($idEsecuzione, $idProgetto, $dataLimite, $message);
			if ($affected_rows == 0) {
                $message->error .= "Nessun caricamento trovato per questo progetto!" . NL;
                return;
			}

            $ore_progetto_teoriche = $this->show_commesse_progetto($idEsecuzione, $commesse_p, $message);
            $ore_compat_teoriche = $this->show_commesse_compatibili($idEsecuzione, $commesse_c, $message);

            $message->success .= "<strong>Tot. " . ($ore_progetto_teoriche + $ore_compat_teoriche) .
                                                            " ore prelevabili teoriche</strong>". NL;

            $lul = $this->estrazione_lul($idEsecuzione, $message);

            $message->success .= "Verifica LUL...". NL;
            $ore_progetto = $this->prelievo_commesse_progetto($idEsecuzione, $commesse_p, $lul, $message);
            $message->success .= "<strong>Tot. $ore_progetto ore prelevate da commesse di progetto</strong>". NL;

            $monte_ore -= $ore_progetto;

            if ($monte_ore <= 0) {
                $message->success .= "<strong>WARNING</strong> Monte ore esaurito, non saranno prelevate ore dalle commesse compatibili". NL;
                $ore_compat = 0.0;
            } else {
                $message->success .= "Verifica LUL...". NL;
                $max_compat = $this->select_max_per_commesse_compatibili($idEsecuzione, $commesse_c);
                $max_dip = $this->select_max_per_dipendenti($idEsecuzione, $idProgetto, $commesse_c, $message);
                $lul_p = $this->togli_ore_progetto_dai_lul($idEsecuzione);
                $ore_compat = $this->prelievo_commesse_compatibili($idEsecuzione, $commesse_c, $lul_p, $monte_ore, $max_compat, $max_dip, $message);
                $message->success .= "<strong>Tot. $ore_compat ore prelevate da commesse compatibili</strong>". NL;
            }

            $tot_ore_assegnate = $ore_progetto + $ore_compat;
            $message->success .= "<strong>Tot. $tot_ore_assegnate ore assegnate</strong>". NL;
            
            if ($tot_ore_assegnate < 0 ) {
                // in caso di errori evitiamo di sottrarre ore!!! 
                $tot_ore_assegnate = 0.0;
            }

            $this->apply($idEsecuzione, $tot_ore_assegnate);
            $message->success .= "Ore assegnate." . NL;
            
            $message->success .= "Monte ore residuo dopo l'assegnazione: $monte_ore ore" . NL;
            
            $this->log($idEsecuzione, $message);

            $message->success .= "Fine." . NL;
        
        } catch (Exception $exception) {
            $message->error .= $exception->getMessage();
        }
    }

    function load_commesse_e_dipendenti($idProgetto) {
        $query = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA>=100";
        $commesse_p = select_column($query);
        $query = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA>0 and PCT_COMPATIBILITA<100";
        $commesse_c = select_column($query);
        $query = "SELECT DISTINCT MATRICOLA_DIPENDENTE FROM progetti_persone WHERE id_progetto=$idProgetto and PCT_IMPIEGO>0 ";
        $matricole = select_column($query);
        return [$commesse_p, $commesse_c, $matricole];
    }

    /**
     * Controlli preliminari (ore che saranno ignorate a priori)
     */
    function check_commesse_dipendenti($idProgetto, $dataLimite, &$message) {

        $query = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA<=0";
        $commesse = select_column($query);
        if (count($commesse) > 0) {
            $message->success .= "<strong>WARNING</strong>: ci sono commesse con PCT_COMPATIBILITA<=0: " . implode(', ', $commesse). NL;
        }

        $query = "SELECT DISTINCT MATRICOLA_DIPENDENTE FROM progetti_persone WHERE id_progetto=$idProgetto and PCT_IMPIEGO<=0";
        $matricole = select_column($query);
        if (count($matricole) > 0) {
            $message->success .= "<strong>WARNING</strong>: ci sono matricole con PCT_IMPIEGO<=0: " . implode(', ', $matricole). NL;
        }

        $d = "DATE('" . $dataLimite->format('Y-m-d') . "')";
        $query = "SELECT DISTINCT CONCAT(oc.COD_COMMESSA,'-',oc.MATRICOLA_DIPENDENTE)
                FROM ore_consuntivate_residuo oc
                JOIN progetti_commesse c ON c.COD_COMMESSA=oc.COD_COMMESSA
                JOIN progetti pr ON pr.ID_PROGETTO=c.ID_PROGETTO
                WHERE pr.id_progetto=$idProgetto
                AND MATRICOLA_DIPENDENTE NOT IN (SELECT DISTINCT MATRICOLA_DIPENDENTE FROM progetti_persone WHERE id_progetto=$idProgetto)
                AND (oc.DATA IS NULL OR (oc.DATA > pr.DATA_ULTIMO_REPORT and oc.DATA < $d))
                ORDER BY 1";
        $ore = select_column($query);
        if (count($ore) > 0) {
            $message->success .= "<strong>WARNING</strong>: ci sono ore su commesse di progetto o compatibili ma con dipendenti incompatibili: " . implode(', ', $ore). NL;
        }
    }

    /**
     * Questa query fa una FULL JOIN di tutte le commesse/persone/date associate al progetto
     * e poi recupera le ore dalla vista dei residui
     * 
     * Salva il risultato nella tabella di lavoro
     */
    function estrazione_caricamenti($idEsecuzione, $idProgetto, $dataLimite, &$message) {

        $d = "DATE('" . $dataLimite->format('Y-m-d') . "')";

        $query = "INSERT INTO assegnazioni_dettaglio (ID_ESECUZIONE, ID_PROGETTO,
                COD_COMMESSA, PCT_COMPATIBILITA,
                MATRICOLA_DIPENDENTE, PCT_IMPIEGO,
                DATA, RIF_SERIE_DOC, RIF_NUMERO_DOC,RIF_ATV,RIF_SOTTO_COMMESSA,
                NUM_ORE_RESIDUE)
            SELECT
                $idEsecuzione, $idProgetto,
                c.COD_COMMESSA,c.PCT_COMPATIBILITA,
                p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO,
                oc.DATA,oc.RIF_SERIE_DOC,oc.RIF_NUMERO_DOC,oc.RIF_ATV,oc.RIF_SOTTO_COMMESSA,
                NVL(oc.NUM_ORE_RESIDUE,0) as NUM_ORE_RESIDUE
            FROM progetti_commesse c
            JOIN progetti_persone p ON c.ID_PROGETTO=p.ID_PROGETTO
            JOIN progetti pr ON pr.ID_PROGETTO=p.ID_PROGETTO
            JOIN ore_consuntivate_residuo oc ON oc.COD_COMMESSA=c.COD_COMMESSA 
                AND oc.MATRICOLA_DIPENDENTE=p.MATRICOLA_DIPENDENTE
                AND oc.DATA > pr.DATA_ULTIMO_REPORT AND oc.DATA < $d
            WHERE pr.ID_PROGETTO=$idProgetto";
        return execute_update($query);
    }

    /**
     * Restituisce le informazioni derivanti dai LUL
     */
    function estrazione_lul($idEsecuzione, &$message) {
        $query = "SELECT t.MATRICOLA_DIPENDENTE,t.DATA,NVL(l.ORE_PRESENZA_ORDINARIE,0) AS ORE_PRESENZA_ORDINARIE
                FROM (SELECT DISTINCT MATRICOLA_DIPENDENTE,DATA
                    FROM assegnazioni_dettaglio ad
                    WHERE ID_ESECUZIONE=$idEsecuzione) t
                LEFT JOIN ore_presenza_lul l
                ON l.MATRICOLA_DIPENDENTE=t.MATRICOLA_DIPENDENTE AND l.DATA=t.DATA";
        $array = select_list($query);
        return array_group_by($array, ['MATRICOLA_DIPENDENTE', 'DATA']);
    }

    /**
     * Mostra un riepilogo delle ore caricate su commesse di progetto e restituisce il totale
     */
    function show_commesse_progetto($idEsecuzione, $commesse_p, &$message) {
        $commesse_imploded = "'" . implode("','", $commesse_p) . "'";
        $query = "SELECT COD_COMMESSA, SUM(NUM_ORE_RESIDUE) AS ORE
                FROM assegnazioni_dettaglio ad
                WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN($commesse_imploded)
                GROUP BY COD_COMMESSA";
        $totali = select_list($query);

        $message->success .= "Commesse di progetto assegnate: " . implode(", ", $commesse_p) . NL;
        $totale = 0.0;
        foreach($totali as $t) {
            if (((float)$t['ORE']) > 0) {
                $message->success .= "  $t[COD_COMMESSA]: trovati caricamenti per $t[ORE] ore" . NL;
            } else {
                $message->success .= "  $t[COD_COMMESSA]: non sono stati trovati caricamenti" . NL;
            }
            $totale += (float) $t['ORE'];
        }
        
        $message->success .= "<strong>Tot. $totale ore di progetto</strong>". NL;

        return $totale;
    }

    /**
     * Mostra un riepilogo delle ore caricate su commesse compatibili e restituisce il totale
     */
    function show_commesse_compatibili($idEsecuzione, $commesse_c, &$message) {
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        $query = "SELECT COD_COMMESSA,
                    SUM(NUM_ORE_RESIDUE) AS ORE,
                    MAX(PCT_COMPATIBILITA) AS PCT_COMPATIBILITA,
                    SUM(NUM_ORE_RESIDUE*PCT_COMPATIBILITA/100) AS ORE_COMP
                FROM assegnazioni_dettaglio ad
                WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN($commesse_imploded)
                GROUP BY COD_COMMESSA";
        $totali = select_list($query);

        $message->success .= "Commesse compatibili assegnate: " . implode(", ", $commesse_c) . NL;
        $totale = 0.0;
        foreach($totali as $t) {
            if (((float) $t['ORE']) > 0) {
                $message->success .= "  $t[COD_COMMESSA]: trovati caricamenti per $t[ORE] ore * $t[PCT_COMPATIBILITA]% = $t[ORE_COMP] ore compatibili" . NL;
            } else {
                $message->success .= "  $t[COD_COMMESSA]: non sono stati trovati caricamenti" . NL;
            }
            $totale += (float) $t['ORE_COMP'];
        }
        
        $totale = round($totale*100)/100;
        $message->success .= "<strong>Tot. $totale ore su commesse compatibili</strong>". NL;

        return $totale;
    }

    function prelievo_commesse_progetto($idEsecuzione, $commesse_p, $lul, $message) {
        $commesse_imploded = "'" . implode("','", $commesse_p) . "'";
        $query = "SELECT *
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN ($commesse_imploded)
                AND NUM_ORE_RESIDUE >= 0.25
            ORDER BY MATRICOLA_DIPENDENTE, DATA";
        $map = array_group_by(select_list($query), ['MATRICOLA_DIPENDENTE', 'DATA']);

        $data_corrente = "";
        $totale = 0.0;
        
        foreach($map as $matricola => $map1) {
            foreach($map1 as $data => $caricamenti) {
                $totale_data = 0.0;
                $max_data = (float) $lul[$matricola][$data]; // SHOULD BE SET!!!
                foreach($caricamenti as $c) {
                    $ore = (float) $c['NUM_ORE_RESIDUE'];
                    // arrotondo al quarto d'ora per difetto
                    $ore = $this->round_quarter($ore);
                    if ($totale_data + $ore < $max_data) {
                        // in questo caso posso prelevare tutto
                        $this->preleva($idEsecuzione, $c, $ore);
                        $totale_data += $ore;
                    } else {
                        // prelevo quel che posso e poi interrompo
                        $ore = $this->round_quarter($max_data - $totale_data);
                        $this->preleva($idEsecuzione, $c, $ore);
                        $totale_data += $ore;
                        break;
                    }
                }
                $totale += $totale_data;
            }
        }
        return $totale;
    }

    /**
     * Arrotondo le ore al quarto d'ora per difetto
     */
    function round_quarter($ore) {
        return floor($ore * 4) / 4;
    }

    function preleva($idEsecuzione, $caricamento, $ore) {
        $query = "UPDATE assegnazioni_dettaglio ad
            SET NUM_ORE_PRELEVATE = $ore
            WHERE ID_ESECUZIONE=$idEsecuzione
                AND MATRICOLA_DIPENDENTE='$caricamento[MATRICOLA_DIPENDENTE]'
                AND DATA='$caricamento[DATA]'
                AND COD_COMMESSA='$caricamento[COD_COMMESSA]'
                AND RIF_SERIE_DOC='$caricamento[RIF_SERIE_DOC]'
                AND RIF_NUMERO_DOC='$caricamento[RIF_NUMERO_DOC]'
                AND RIF_ATV='$caricamento[RIF_ATV]'
                AND  RIF_SOTTO_COMMESSA ='$caricamento[RIF_SOTTO_COMMESSA]'";
        execute_update($query);
    }

    function select_max_per_commesse_compatibili($idEsecuzione, $commesse_c) {
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        $query = "SELECT COD_COMMESSA,
                    NVL(SUM(NUM_ORE_RESIDUE*PCT_COMPATIBILITA/100),0) AS ORE_PREVISTE
                FROM assegnazioni_dettaglio ad
                WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN($commesse_imploded)
                GROUP BY COD_COMMESSA";
        return array_group_by(select_list($query), ['COD_COMMESSA']);
    }

    function select_max_per_dipendenti($idEsecuzione, $idProgetto, $commesse_c, &$message) {
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        
        $query = "SELECT NVL(SUM(NUM_ORE_RESIDUE*PCT_COMPATIBILITA/100),0) AS ORE_COMP
                FROM assegnazioni_dettaglio ad
                WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN($commesse_imploded)";

        $totale_ore_compatibili_previste = select_single_value($query);

        $query = "SELECT MATRICOLA_DIPENDENTE,PCT_IMPIEGO,
                    ROUND($totale_ore_compatibili_previste*PCT_IMPIEGO/100,2) AS ORE_PREVISTE
                FROM progetti_persone p
                WHERE ID_PROGETTO=$idProgetto";
        $list = select_list($query);

        $message->success .= "  Suddivisione ipotetica delle commesse compatibili tra i dipendenti:" . NL;
        foreach($list as $m) {
            $message->success .= "  $m[MATRICOLA_DIPENDENTE] al $m[PCT_IMPIEGO] % = $m[ORE_PREVISTE] ore" . NL;
        }
        return array_group_by($list, ['MATRICOLA_DIPENDENTE']);
    }

    function togli_ore_progetto_dai_lul($idEsecuzione) {
        // rifaccio l'estrazione dei lul, questa volta tenendo conto delle ore già prelevate
        $query = "SELECT t.MATRICOLA_DIPENDENTE,t.DATA,NVL(l.ORE_PRESENZA_ORDINARIE,0)-ORE_PRELEVATE AS ORE_PRESENZA_ORDINARIE
                FROM (SELECT MATRICOLA_DIPENDENTE,DATA,SUM(NUM_ORE_PRELEVATE) AS ORE_PRELEVATE
                    FROM assegnazioni_dettaglio ad
                    WHERE ID_ESECUZIONE=$idEsecuzione
                    GROUP BY MATRICOLA_DIPENDENTE,DATA) t
                LEFT JOIN ore_presenza_lul l
                ON l.MATRICOLA_DIPENDENTE=t.MATRICOLA_DIPENDENTE AND l.DATA=t.DATA";
        $array = select_list($query);
        return array_group_by($array, ['MATRICOLA_DIPENDENTE', 'DATA']);
    }

    function prelievo_commesse_compatibili($idEsecuzione, $commesse_c, $lul_p, $monte_ore, $max_compat, $max_dip, $message) {
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        $query = "SELECT *
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN ($commesse_imploded)
                AND NUM_ORE_RESIDUE >= 0.25 
            ORDER BY MATRICOLA_DIPENDENTE, DATA";
        $map = array_group_by(select_list($query), ['MATRICOLA_DIPENDENTE', 'DATA']);

        $data_corrente = "";
        $totale = 0.0;
        
        foreach($map as $matricola => $map1) {
            foreach($map1 as $data => $caricamenti) {
                $totale_data = 0.0;
                $max_data = (float) $lul_p[$matricola][$data]; // SHOULD BE SET!!!
                foreach($caricamenti as $c) {
                    $ore = (float) $c['NUM_ORE_RESIDUE'];
                    $ore_max_commessa = (float) $max_compat[$c['COD_COMMESSA']][0]['ORE_PREVISTE'];
                    $ore_max_matricola = (float) $max_dip[$c['MATRICOLA_DIPENDENTE']][0]['ORE_PREVISTE'];
                    if ($ore_max_commessa > 0 && $ore_max_matricola > 0) {

                        $ore = min($ore, $ore_max_commessa, $ore_max_matricola);
                        $ore = $this->round_quarter($ore);

                        if ($totale_data + $ore < $max_data) {
                            // in questo caso posso prelevare tutto
                            $this->preleva($idEsecuzione, $c, $ore);
                            $totale_data += $ore;
                            $max_compat[$c['COD_COMMESSA']][0]['ORE_PREVISTE'] -= $ore;
                            $max_dip[$c['MATRICOLA_DIPENDENTE']][0]['ORE_PREVISTE'] -= $ore;
                        } else {
                            // prelevo quel che posso e poi interrompo
                            $ore = $this->round_quarter($max_data - $totale_data);
                            $this->preleva($idEsecuzione, $c, $ore);
                            $totale_data += $ore;
                            $max_compat[$c['COD_COMMESSA']][0]['ORE_PREVISTE'] -= $ore;
                            $max_dip[$c['MATRICOLA_DIPENDENTE']][0]['ORE_PREVISTE'] -= $ore;
                            break;
                        }
                    }
                }
                $totale += $totale_data;
                if ($totale >= $monte_ore) {
                    $message->success .= "Interrompo i prelievi per raggiungimento del monte ore" . NL;
                    break;
                }
            }
            if ($totale >= $monte_ore) break;
        }
        return $totale;
    }

    /**
     * Copia le rettifiche dalla tabella di lavoro alla ore_consuntivate_progetti
     */
    function apply($idEsecuzione, $tot_ore_assegnate) {
        global $con;

        $con->begin_transaction();
        try {
            $query ="INSERT INTO ore_consuntivate_progetti(ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA, 
                        NUM_ORE_LAVORATE, ID_ESECUZIONE)
                     SELECT ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA,
                        SUM(NUM_ORE_PRELEVATE), $idEsecuzione
                     FROM assegnazioni_dettaglio ad
                     WHERE ID_ESECUZIONE=$idEsecuzione AND NUM_ORE_PRELEVATE>0
                     GROUP BY ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA
                     ";
            execute_update($query);

            if ($tot_ore_assegnate != null) {
                $query = "UPDATE assegnazioni SET TOT_ASSEGNATE=$tot_ore_assegnate,IS_ASSEGNATE=1 WHERE ID_ESECUZIONE=$idEsecuzione";
            } else {
                $query = "UPDATE assegnazioni SET IS_ASSEGNATE=1 WHERE ID_ESECUZIONE=$idEsecuzione";
            }
            execute_update($query);

            $con->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();        
            throw $exception;
        }
    }

    /**
     * Stampa la tabellina di lavoro
     */
    function log($idEsecuzione, &$message) {
        $query = "SELECT *
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            ORDER BY MATRICOLA_DIPENDENTE, DATA, COD_COMMESSA";
        $caricamenti = select_list($query);

        $message->success .= NL . "La seguente tabella viene mostrata a scopo di debug:" . NL;
        $message->success .= "<TABLE BORDER='1'>";
        $message->success .= "<THEAD>
                <TR>
                    <TH>COMMESSA</TH>
                    <TH>PCT. COMPATIBILITA</TH>
                    <TH>MATRICOLA</TH>
                    <TH>PCT. IMPIEGO</TH>
                    <TH>DATA</TH>
                    <TH>ORE RESIDUE</TH>
                    <TH>ORE PRELEVATE</TH>
                </TR>
            </THEAD>";
        $message->success .= "<TBODY>";
        if (count($caricamenti) > 0) {
            foreach($caricamenti as $c) {

                $message->success .= "
                    <TR>
                        <TD>$c[COD_COMMESSA]</TD>
                        <TD>$c[PCT_COMPATIBILITA]</TD>
                        <TD>$c[MATRICOLA_DIPENDENTE]</TD>
                        <TD>$c[PCT_IMPIEGO]</TD>
                        <TD>$c[DATA]</TD>
                        <TD>$c[NUM_ORE_RESIDUE]</TD>
                        <TD>$c[NUM_ORE_PRELEVATE]</TD>
                    </TR>";
            }
        } else {
            $message->success .= "<TR><TD COLSPAN=7>Nessuna riga estratta</TD></TR>";
        }
        $message->success .= "</TBODY>";
        $message->success .= "</TABLE>" . NL;
    }
}
?>