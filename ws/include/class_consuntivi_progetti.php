<?php

$consuntiviProgettiManager = new ConsuntiviProgettiManager();

define('NL', "<br/>");
define('PRE', "1");
define('POST', "2");

class ConsuntiviProgettiManager {
    
    /**
     * Main procedure
     */
    function run_assegnazione($dataInizio, $dataFine, &$message) {
        global $esecuzioniManager, $panthera;

        ini_set('max_execution_time', 300);

        $progetti_attivi = $this->get_progetti_attivi($dataInizio, $dataFine);
        if (count($progetti_attivi) == 0) {
            $message->error .= "Nessun progetto attivo trovato!" . NL;
            return;
        }

        $matricole = $this->load_partecipanti_globali();
        if (count($matricole) == 0) {
            $message->error .= "Nessun dipendente con percentuale utilizzabile configurato!" . NL;
            return;
        }

        $nomiUtenti = array_group_by($panthera->getUtenti(), ['ID_DIPENDENTE']);

        $idEsecuzione = $esecuzioniManager->get_id_esecuzione();

        $nomi_progetti = implode(', ', array_map(function($x){ return $x["ACRONIMO"]; }, $progetti_attivi));
        $message->success .= "Periodo: " . $dataInizio->format('d/m/Y') . " - " . $dataFine->format('d/m/Y') ." progetti attivi trovati: " . $nomi_progetti . NL;
        $message->success .= "Salvo i dati ottenuti con <strong>ID_ESECUZIONE=$idEsecuzione</strong>" . NL;
        $message->success .= "Ore " . date("H:i:s"). NL;

        foreach($progetti_attivi as $progetto) {
            $idProgetto = $progetto["ID_PROGETTO"];
            try {
                $message->success .= "Lancio assegnazione ore <strong>progetto n.$idProgetto - $progetto[ACRONIMO]</strong>" . NL;

                list($commesse_p, $commesse_c) = $this->load_commesse($idProgetto); // FIXME load per che date???

                if (count($commesse_p) == 0 && count($commesse_c) == 0) {
                    $message->error .= "Nessuna commessa &egrave; stata configurata sul progetto n.$idProgetto!" . NL;
                    continue;
                }

                $canGoOn = $this->check_commesse_dipendenti($idProgetto, $dataInizio, $dataFine, $message);
                if (!$canGoOn) {
                    continue;
                }

                $this->estrazione_caricamenti($idEsecuzione, $idProgetto, $dataInizio, $dataFine, $message);

                $ore_progetto_teoriche = $this->show_commesse_progetto($idEsecuzione, $idProgetto, $commesse_p, $message);
                $ore_compat_teoriche = $this->show_commesse_compatibili($idEsecuzione, $idProgetto, $commesse_c, $message);

                $message->success .= "<strong>Tot. " . ($ore_progetto_teoriche + $ore_compat_teoriche) .
                                                                " ore prelevabili teoriche (di progetto+compatibili)</strong>". NL;
                $message->success .= "Ore " . date("H:i:s"). NL;

                $lul = $this->estrazione_lul($idEsecuzione, $message);

                $message->success .= "Verifica LUL...". NL;
                $ore_progetto = $this->prelievo_commesse_progetto($idEsecuzione, $idProgetto, $commesse_p, $lul, $dataInizio, $dataFine, $message);
                $message->success .= "<strong>Tot. $ore_progetto ore prelevate da commesse di progetto</strong>". NL;
                $message->success .= "Ore " . date("H:i:s"). NL;
                if ($ore_progetto > $this->get_ore_previste($idProgetto, $commesse_p)) {
                    $message->success .= "<strong>WARNING</strong> Le ore di progetto consuntivate sono più di quelle previste!";
                }

                $lul_p = $this->togli_ore_progetto_dai_lul($idEsecuzione);
                $max_compat = $this->select_max_per_commesse_compatibili($idEsecuzione, $commesse_c);
                $max_dip = $this->select_max_per_dipendenti($idEsecuzione, $idProgetto, $commesse_c, $lul_p, $nomiUtenti, $message);
                $message->success .= "Verifica LUL...". NL;
                $ore_compat = $this->prelievo_commesse_compatibili($idEsecuzione, $idProgetto, $commesse_c, $lul_p, $max_compat, $max_dip, $dataInizio, $dataFine, $message);
                $message->success .= "<strong>Tot. $ore_compat ore prelevate da commesse compatibili</strong>". NL;

                $tot_ore_assegnate = $ore_progetto + $ore_compat;
                $message->success .= "<strong>Tot. $tot_ore_assegnate ore assegnate</strong>". NL;
                
                if ($tot_ore_assegnate < 0 ) {
                    // in caso di errori evitiamo di sottrarre ore!!! 
                    $message->success .= "<b> Qualcosa non va: ore assegnate negative. Ignoro questo progetto.</b>" . NL;
                    $this->log($idEsecuzione, $message);
                    continue;
                }

                $this->apply($idEsecuzione, $idProgetto, $tot_ore_assegnate);
                $message->success .= "Ore assegnate." . NL;

                $message->success .= "Ore " . date("H:i:s"). NL;

            } catch (Exception $exception) {
                $message->error .= $exception->getMessage();
            }
        }
        $this->riepilogo_per_commessa($idEsecuzione, $message);
        $this->riepilogo_per_commessa_dipendente($idEsecuzione, $message);
        $this->log($idEsecuzione, $message);

        $message->success .= "Fine." . NL;
        $message->success .= "Ore " . date("H:i:s"). NL;
    }

    function load_partecipanti_globali() {
        $query = "SELECT DISTINCT ID_DIPENDENTE
                FROM partecipanti_globali
                WHERE PCT_UTILIZZO>0 ";
        $matricole = select_column($query);
        return $matricole;
    }

    function load_commesse($idProgetto) { // FIXME load per che date ????
        $query = "SELECT DISTINCT pc.COD_COMMESSA
                FROM progetti_commesse pc
                JOIN commesse c ON c.COD_COMMESSA=pc.COD_COMMESSA AND c.DATA_INIZIO=pc.DATA_INIZIO AND c.DATA_FINE=pc.DATA_FINE
                WHERE pc.ID_PROGETTO=$idProgetto and c.PCT_COMPATIBILITA>=100 AND pc.ORE_PREVISTE>0";
        $commesse_p = select_column($query);
        $query = "SELECT DISTINCT pc.COD_COMMESSA
                FROM progetti_commesse pc
                JOIN commesse c ON c.COD_COMMESSA=pc.COD_COMMESSA AND c.DATA_INIZIO=pc.DATA_INIZIO AND c.DATA_FINE=pc.DATA_FINE
                WHERE pc.ID_PROGETTO=$idProgetto and c.PCT_COMPATIBILITA>0 and c.PCT_COMPATIBILITA<100 AND pc.ORE_PREVISTE>0";
        $commesse_c = select_column($query);
        return [$commesse_p, $commesse_c];
    }

    /**
     * Controlli preliminari (ore che saranno ignorate a priori)
     * 
     * @return canGoOn (true/false)
     */
    function check_commesse_dipendenti($idProgetto, $dataInizio, $dataFine, &$message) { // FIXME DATE

        $dataInizio = "DATE('" . $dataInizio->format('Y-m-d') . "')";
        $dataFine = "DATE('" . $dataFine->format('Y-m-d') . "')";

        $canGoOn = true;
        $query = "SELECT DISTINCT pc.COD_COMMESSA
                    FROM progetti_commesse pc
                    JOIN commesse c ON c.COD_COMMESSA=pc.COD_COMMESSA AND c.DATA_INIZIO=pc.DATA_INIZIO AND c.DATA_FINE=pc.DATA_FINE
                    WHERE pc.ID_PROGETTO=$idProgetto AND pc.DATA_INIZIO=$dataInizio AND pc.DATA_FINE=$dataFine
                    and c.PCT_COMPATIBILITA<=0";
        $commesse = select_column($query);
        if (count($commesse) > 0) {
            $message->success .= "<strong>WARNING</strong>: ci sono commesse con PCT_COMPATIBILITA<=0: " . implode(', ', $commesse). NL;
        }

        $query = "SELECT DISTINCT CONCAT(oc.COD_COMMESSA,'-',oc.ID_DIPENDENTE)
                FROM ore_consuntivate_residuo oc
                JOIN progetti_commesse c ON c.COD_COMMESSA=oc.COD_COMMESSA
                JOIN progetti pr ON pr.ID_PROGETTO=c.ID_PROGETTO
                WHERE pr.id_progetto=$idProgetto
                AND ID_DIPENDENTE NOT IN (SELECT DISTINCT ID_DIPENDENTE FROM partecipanti_globali WHERE PCT_UTILIZZO<=0)
                AND (oc.DATA IS NULL OR (oc.DATA >= $dataInizio and oc.DATA <= $dataFine))
                ORDER BY 1";
        $ore = select_column($query);
        if (count($ore) > 0) {
            $message->success .= "<strong>WARNING</strong>: ci sono ore su commesse di progetto o compatibili ma con PCT_UTILIZZO<=0: " . implode(', ', $ore). NL;
        }

        $query = "SELECT count(*)
            FROM progetti_commesse pc
            JOIN partecipanti_globali p ON 1=1
            JOIN progetti pr ON pr.ID_PROGETTO=pc.ID_PROGETTO
            JOIN ore_consuntivate_residuo oc ON oc.COD_COMMESSA=pc.COD_COMMESSA 
                AND oc.ID_DIPENDENTE=p.ID_DIPENDENTE
                AND oc.DATA >= $dataInizio AND oc.DATA <= $dataFine
                AND NUM_ORE_RESIDUE > 0
            WHERE pr.ID_PROGETTO=$idProgetto AND oc.DATA >= $dataInizio and oc.DATA <= $dataFine
            AND pc.DATA_INIZIO=$dataInizio AND pc.DATA_FINE=$dataFine";
        $cnt = select_single_value($query);
        if ($cnt == 0) {
            $message->error .= "Nessun caricamento trovato per questo progetto!" . NL;
            $canGoOn = false;
        }

        return $canGoOn;
    }

    function get_progetti_attivi($dataInizio, $dataFine) {
        $dataInizio = $dataInizio->format('Y-m-d');
        $dataFine = $dataFine->format('Y-m-d');
        $query = "SELECT *
            FROM progetti p
            WHERE p.DATA_FINE >= DATE('$dataInizio') AND p.DATA_INIZIO <= DATE('$dataFine')";
        return select_list($query);
    }

    /**
     * Questa query fa una FULL JOIN di tutte le commesse/persone/date associate al progetto
     * e poi recupera le ore dalla vista dei residui
     * 
     * Salva il risultato nella tabella di lavoro
     */
    function estrazione_caricamenti($idEsecuzione, $idProgetto, $dataInizio, $dataFine, &$message) {

        $dataInizio = $dataInizio->format('Y-m-d');
        $dataFine = $dataFine->format('Y-m-d');

        $query = "INSERT INTO assegnazioni_dettaglio (ID_ESECUZIONE, ID_PROGETTO,
                COD_COMMESSA, PCT_COMPATIBILITA,
                ID_DIPENDENTE, PCT_UTILIZZO,
                DATA, RIF_SERIE_DOC, RIF_NUMERO_DOC,RIF_ATV,RIF_SOTTO_COMMESSA,
                NUM_ORE_RESIDUE)
            SELECT
                $idEsecuzione, $idProgetto,
                pc.COD_COMMESSA,c.PCT_COMPATIBILITA,
                p.ID_DIPENDENTE,p.PCT_UTILIZZO,
                oc.DATA,oc.RIF_SERIE_DOC,oc.RIF_NUMERO_DOC,oc.RIF_ATV,oc.RIF_SOTTO_COMMESSA,
                NVL(oc.NUM_ORE_RESIDUE,0) as NUM_ORE_RESIDUE
            FROM progetti_commesse pc
            JOIN progetti pr ON pr.ID_PROGETTO=pc.ID_PROGETTO
            JOIN commesse c ON c.COD_COMMESSA=pc.COD_COMMESSA AND c.DATA_INIZIO=pc.DATA_INIZIO AND c.DATA_FINE=pc.DATA_FINE
                AND c.PCT_COMPATIBILITA>0 AND pc.ORE_PREVISTE>0
            JOIN partecipanti_globali p ON p.PCT_UTILIZZO>0
            JOIN ore_consuntivate_residuo oc ON oc.COD_COMMESSA=c.COD_COMMESSA 
                AND oc.ID_DIPENDENTE=p.ID_DIPENDENTE
                AND oc.DATA >= DATE('$dataInizio') AND oc.DATA < DATE('$dataFine')
            WHERE pr.ID_PROGETTO=$idProgetto";
        return execute_update($query);
    }

    /**
     * Restituisce le informazioni derivanti dai LUL
     */
    function estrazione_lul($idEsecuzione, &$message) {
        $query = "SELECT t.ID_DIPENDENTE,t.DATA,NVL(l.ORE_PRESENZA_ORDINARIE,0) AS ORE_PRESENZA_ORDINARIE
                FROM (SELECT DISTINCT ID_DIPENDENTE,DATA
                    FROM assegnazioni_dettaglio ad
                    WHERE ID_ESECUZIONE=$idEsecuzione) t
                LEFT JOIN ore_presenza_lul l
                ON l.ID_DIPENDENTE=t.ID_DIPENDENTE AND l.DATA=t.DATA";
        $array = select_list($query);
        return array_group_by($array, ['ID_DIPENDENTE', 'DATA']);
    }

    /**
     * Restituisce il totale ore previste secondo la tabella progetti_commesse
     */
    function get_ore_previste($idProgetto, $commesse, $dataInizio, $dataFine) {
        $commesse_imploded = "'" . implode("','", $commesse) . "'";
        $query = "SELECT SUM(ORE_PREVISTE) AS ORE_PREVISTE
                FROM progetti_commesse pc
                WHERE ID_PROGETTO=$idProgetto AND COD_COMMESSA IN($commesse_imploded)
                    AND DATA_INIZIO=DATE('$dataInizio') AND DATA_FINE=DATE('$dataFine')";
        return select_single_value($query);
    }

    /**
     * Mostra un riepilogo delle ore caricate su commesse di progetto e restituisce il totale
     */
    function show_commesse_progetto($idEsecuzione, $idProgetto, $commesse_p, &$message) {
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

        $ore_previste = $this->get_ore_previste($idProgetto, $commesse_p);
        
        $message->success .= "<strong>Tot. $totale ore di progetto, contro $ore_previste previste</strong>". NL;

        return $totale;
    }

    /**
     * Mostra un riepilogo delle ore caricate su commesse compatibili e restituisce il totale
     */
    function show_commesse_compatibili($idEsecuzione, $idProgetto, $commesse_c, &$message) {
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        $query = "SELECT COD_COMMESSA,
                    SUM(NUM_ORE_RESIDUE) AS ORE,
                    MAX(PCT_COMPATIBILITA) AS PCT_COMPATIBILITA,
                    ROUND(SUM(NUM_ORE_RESIDUE*PCT_COMPATIBILITA/100),2) AS ORE_COMP
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

        $ore_previste = $this->get_ore_previste($idProgetto, $commesse_c);
        
        $totale = round($totale*100)/100;
        $message->success .= "<strong>Tot. $totale ore su commesse compatibili, contro $ore_previste previste</strong>". NL;

        return $totale;
    }

    function prelievo_commesse_progetto($idEsecuzione, $idProgetto, $commesse_p, $lul, $dataInizio, $dataFine, $message) { //FIXME DATE
        $commesse_imploded = "'" . implode("','", $commesse_p) . "'";
        $query = "SELECT *
            FROM assegnazioni_dettaglio ad
            JOIN progetti_commesse pc ON ad.COD_COMMESSA=pc.COD_COMMESSA
            WHERE ID_ESECUZIONE=$idEsecuzione AND pc.COD_COMMESSA IN ($commesse_imploded)
                AND NUM_ORE_RESIDUE >= 0.25 AND pc.ID_PROGETTO=$idProgetto
            ORDER BY ID_DIPENDENTE, DATA";
        $map = array_group_by(select_list($query), ['ID_DIPENDENTE', 'DATA']);

        $data_corrente = "";
        $totale = 0.0;
        
        foreach($map as $matricola => $map1) {
            foreach($map1 as $data => $caricamenti) {
                $totale_data = 0.0;
                $max_data = (float) $lul[$matricola][$data][0]['ORE_PRESENZA_ORDINARIE']; // SHOULD BE SET!!!
                foreach($caricamenti as $c) {
                    $ore = (float) $c['NUM_ORE_RESIDUE'];
                    // arrotondo al quarto d'ora per difetto
                    $ore = $this->round_quarter($ore);
                    if ($totale_data + $ore < $max_data) {
                        // in questo caso posso prelevare tutto
                        $this->preleva($idEsecuzione, $idProgetto, $c, $ore);
                        $totale_data += $ore;
                    } else {
                        // prelevo quel che posso e poi interrompo
                        $ore = $this->round_quarter($max_data - $totale_data);
                        $this->preleva($idEsecuzione, $idProgetto, $c, $ore);
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

    function preleva($idEsecuzione, $idProgetto, $caricamento, $ore) {
        $query = "UPDATE assegnazioni_dettaglio ad
            SET NUM_ORE_PRELEVATE = $ore, ID_PROGETTO = $idProgetto
            WHERE ID_ESECUZIONE=$idEsecuzione
                AND ID_DIPENDENTE='$caricamento[ID_DIPENDENTE]'
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

    function select_max_per_dipendenti($idEsecuzione, $idProgetto, $commesse_c, $lul_p, $nomiUtenti, &$message) {
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        
        $query = "SELECT NVL(SUM(NUM_ORE_RESIDUE*PCT_COMPATIBILITA/100),0) AS ORE_COMP
                FROM assegnazioni_dettaglio ad
                WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN($commesse_imploded)";

        $totale_ore_compatibili_previste = select_single_value($query);

        $query = "SELECT ad.ID_DIPENDENTE,PCT_UTILIZZO,ROUND(NVL(SUM(NUM_ORE_RESIDUE*PCT_COMPATIBILITA/100),0)*PCT_UTILIZZO/100,2) AS ORE_PREVISTE
                FROM assegnazioni_dettaglio ad
                WHERE ID_ESECUZIONE=$idEsecuzione AND COD_COMMESSA IN($commesse_imploded)
                GROUP BY  ad.ID_DIPENDENTE";
        $list = select_list($query);

        //$lul_d = $this->ore_lul_residue_per_dip($lul_p); // dato interessante ma fuorviante
        $message->success .= "  Suddivisione teorica delle commesse compatibili tra i dipendenti:" . NL;
        foreach($list as $m) {
            $idDip = $m['ID_DIPENDENTE'];
            // $lavorate = isset($lul_d[$idDip]) ? $lul_d[$idDip] : 0; // dato interessante ma fuorviante
            $nome = isset($nomiUtenti[$idDip][0]['DENOMINAZIONE']) ? $nomiUtenti[$idDip][0]['DENOMINAZIONE'] : '';
            $message->success .= "  $idDip $nome al $m[PCT_UTILIZZO] % = $m[ORE_PREVISTE] ore." . NL;
        }
        return array_group_by($list, ['ID_DIPENDENTE']);
    }

    function togli_ore_progetto_dai_lul($idEsecuzione) {
        // rifaccio l'estrazione dei lul, questa volta tenendo conto delle ore già prelevate
        $query = "SELECT t.ID_DIPENDENTE,t.DATA,NVL(l.ORE_PRESENZA_ORDINARIE,0)-ORE_PRELEVATE AS ORE_PRESENZA_ORDINARIE
                FROM (SELECT ID_DIPENDENTE,DATA,SUM(NUM_ORE_PRELEVATE) AS ORE_PRELEVATE
                    FROM assegnazioni_dettaglio ad
                    WHERE ID_ESECUZIONE=$idEsecuzione
                    GROUP BY ID_DIPENDENTE,DATA) t
                LEFT JOIN ore_presenza_lul l
                ON l.ID_DIPENDENTE=t.ID_DIPENDENTE AND l.DATA=t.DATA";
        $array = select_list($query);
        return array_group_by($array, ['ID_DIPENDENTE', 'DATA']);
    }

    /**
     * Solo a scopo diagnostico
     */
    function ore_lul_residue_per_dip($lul_p) {
        $array = [];
        foreach($lul_p as $id_dip => $x) {
            if (!isset($array[$id_dip])) {
                $array[$id_dip] = 0.0;
            }
            foreach($x as $data => $record) {
                $array[$id_dip] += floatval($record[0]['ORE_PRESENZA_ORDINARIE']);
            }
        }
        return $array;
    }

    function prelievo_commesse_compatibili($idEsecuzione, $idProgetto, $commesse_c, $lul_p, $max_compat, $max_dip, $dataInizio, $dataFine, &$message) { // FIXME DATE
        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";
        $query = "SELECT *
            FROM assegnazioni_dettaglio ad
            JOIN progetti_commesse pc ON ad.COD_COMMESSA=pc.COD_COMMESSA AND pc.ID_PROGETTO=$idProgetto
            WHERE ID_ESECUZIONE=$idEsecuzione AND pc.COD_COMMESSA IN ($commesse_imploded)
                AND NUM_ORE_RESIDUE >= 0.25
            ORDER BY ID_DIPENDENTE, DATA";
        $map = array_group_by(select_list($query), ['ID_DIPENDENTE', 'DATA']);

        $data_corrente = "";
        $totale = 0.0;

        foreach($map as $matricola => $map1) {
            foreach($map1 as $data => $caricamenti) {
                $totale_data = 0.0;
                $max_data = (float) $lul_p[$matricola][$data][0]['ORE_PRESENZA_ORDINARIE']; // SHOULD BE SET!!!
                foreach($caricamenti as $c) {
                    $ore = (float) $c['NUM_ORE_RESIDUE'];
                    $ore_max_commessa = (float) $max_compat[$c['COD_COMMESSA']][0]['ORE_PREVISTE'];
                    if (!array_key_exists($c['ID_DIPENDENTE'], $max_dip)) {
                        $message->success .= "<strong>WARNING</strong> Something's wrong, missing key $c[ID_DIPENDENTE]" . NL;

                        var_dump($query);
                        var_dump($max_dip);
                        var_dump($map);
                        return;
                    }
                    $ore_max_matricola = (float) $max_dip[$c['ID_DIPENDENTE']][0]['ORE_PREVISTE'];
                    if ($ore_max_commessa > 0 && $ore_max_matricola > 0) {

                        $ore = min($ore, $ore_max_commessa, $ore_max_matricola);
                        $ore = $this->round_quarter($ore);

                        if ($totale_data + $ore < $max_data) {
                            // in questo caso posso prelevare tutto
                            $this->preleva($idEsecuzione, $idProgetto, $c, $ore);
                            $totale_data += $ore;
                            $max_compat[$c['COD_COMMESSA']][0]['ORE_PREVISTE'] -= $ore;
                            $max_dip[$c['ID_DIPENDENTE']][0]['ORE_PREVISTE'] -= $ore;
                        } else {
                            // prelevo quel che posso e poi interrompo
                            $ore = $this->round_quarter($max_data - $totale_data);
                            $this->preleva($idEsecuzione, $idProgetto, $c, $ore);
                            $totale_data += $ore;
                            $max_compat[$c['COD_COMMESSA']][0]['ORE_PREVISTE'] -= $ore;
                            $max_dip[$c['ID_DIPENDENTE']][0]['ORE_PREVISTE'] -= $ore;
                            break;
                        }
                    }
                }
                $totale += $totale_data;
            }
        }
        return $totale;
    }

    /**
     * Copia le rettifiche dalla tabella di lavoro alla ore_consuntivate_progetti
     */
    function apply($idEsecuzione, $idProgetto, $tot_ore_assegnate) {
        global $con;

        // QUI ASSUMO tot_ore_assegnate > 0

        $con->begin_transaction();
        try {
            $query ="INSERT INTO ore_consuntivate_progetti(ID_PROGETTO, ID_DIPENDENTE, DATA, 
                        NUM_ORE_LAVORATE, ID_ESECUZIONE)
                     SELECT ID_PROGETTO, ID_DIPENDENTE, DATA,
                        SUM(NUM_ORE_PRELEVATE), $idEsecuzione
                     FROM assegnazioni_dettaglio ad
                     WHERE ID_ESECUZIONE=$idEsecuzione AND NUM_ORE_PRELEVATE>0
                     GROUP BY ID_PROGETTO, ID_DIPENDENTE, DATA
                     ";
            execute_update($query);

            $query = "UPDATE assegnazioni
                        SET TOT_ASSEGNATE=$tot_ore_assegnate,IS_ASSEGNATE=1
                        WHERE ID_ESECUZIONE=$idEsecuzione";
            execute_update($query);

            $d = date('Y-m-d');
            $query = "UPDATE progetti
                        SET ORE_GIA_ASSEGNATE=NVL(ORE_GIA_ASSEGNATE,0)+$tot_ore_assegnate,
                        DATA_ULTIMO_REPORT=DATE('$d')
                        WHERE ID_PROGETTO=$idProgetto";
            execute_update($query);

            $con->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();        
            throw $exception;
        }
    }

    /**
     * Stampa il riepilogo per commessa
     */
    function riepilogo_per_commessa($idEsecuzione, &$message) {
        $query = "SELECT ID_PROGETTO, COD_COMMESSA, SUM(NUM_ORE_PRELEVATE) as NUM_ORE_PRELEVATE
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            GROUP BY COD_COMMESSA, ID_PROGETTO
            ORDER BY COD_COMMESSA, ID_PROGETTO";
        $caricamenti = select_list($query);

        $message->success .= NL . "Riepilogo delle ore prelevate per commessa/dipendente:" . NL;
        $message->success .= "<TABLE BORDER='1'>";
        $message->success .= "<THEAD>
                <TR>
                    <TH>COMMESSA</TH>
                    <TH>PROGETTO</TH>
                    <TH>ORE PRELEVATE</TH>
                </TR>
            </THEAD>";
        $message->success .= "<TBODY>";
        if (count($caricamenti) > 0) {
            foreach($caricamenti as $c) {

                $message->success .= "
                    <TR>
                        <TD>$c[COD_COMMESSA]</TD>
                        <TD>$c[ID_PROGETTO]</TD>
                        <TD>$c[NUM_ORE_PRELEVATE]</TD>
                    </TR>";
            }
        } else {
            $message->success .= "<TR><TD COLSPAN=3>Nessuna riga estratta</TD></TR>";
        }
        $message->success .= "</TBODY>";
        $message->success .= "</TABLE>" . NL;
    }

    /**
     * Stampa il riepilogo per commessa/dipendente
     */
    function riepilogo_per_commessa_dipendente($idEsecuzione, &$message) {
        $query = "SELECT ID_DIPENDENTE, ID_PROGETTO, COD_COMMESSA, SUM(NUM_ORE_PRELEVATE) as NUM_ORE_PRELEVATE
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            GROUP BY COD_COMMESSA, ID_PROGETTO, ID_DIPENDENTE
            ORDER BY COD_COMMESSA, ID_PROGETTO, ID_DIPENDENTE";
        $caricamenti = select_list($query);

        $message->success .= NL . "Riepilogo delle ore prelevate per commessa/dipendente:" . NL;
        $message->success .= "<TABLE BORDER='1'>";
        $message->success .= "<THEAD>
                <TR>
                    <TH>COMMESSA</TH>
                    <TH>PROGETTO</TH>
                    <TH>DIPENDENTE</TH>
                    <TH>ORE PRELEVATE</TH>
                </TR>
            </THEAD>";
        $message->success .= "<TBODY>";
        if (count($caricamenti) > 0) {
            foreach($caricamenti as $c) {

                $message->success .= "
                    <TR>
                        <TD>$c[COD_COMMESSA]</TD>
                        <TD>$c[ID_PROGETTO]</TD>
                        <TD>$c[ID_DIPENDENTE]</TD>
                        <TD>$c[NUM_ORE_PRELEVATE]</TD>
                    </TR>";
            }
        } else {
            $message->success .= "<TR><TD COLSPAN=4>Nessuna riga estratta</TD></TR>";
        }
        $message->success .= "</TBODY>";
        $message->success .= "</TABLE>" . NL;
    }

    /**
     * Stampa la tabellina di lavoro
     */
    function log($idEsecuzione, &$message) {
        $query = "SELECT ad.*,
                (SELECT ORE_PRESENZA_ORDINARIE FROM ore_presenza_lul l WHERE l.DATA=ad.DATA and l.ID_DIPENDENTE=ad.ID_DIPENDENTE) AS ORE_LUL
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            ORDER BY ID_DIPENDENTE, DATA, COD_COMMESSA";
        $caricamenti = select_list($query);

        $message->success .= NL . "Dettaglio delle ore prelevate:" . NL;
        $message->success .= "<TABLE BORDER='1'>";
        $message->success .= "<THEAD>
                <TR>
                    <TH>COMMESSA</TH>
                    <TD>PROGETTO</TD>
                    <TH>PCT. COMPAT.</TH>
                    <TH>DIPENDENTE</TH>
                    <TH>PCT. IMPIEGO</TH>
                    <TH>DATA</TH>
                    <TH>ORE RESIDUE</TH>
                    <TH>ORE PRELEVATE</TH>
                    <TH>CFR. LUL</TH>
                </TR>
            </THEAD>";
        $message->success .= "<TBODY>";
        if (count($caricamenti) > 0) {
            foreach($caricamenti as $c) {

                $message->success .= "
                    <TR>
                        <TD>$c[COD_COMMESSA]</TD>
                        <TD>$c[ID_PROGETTO]</TD>
                        <TD>$c[PCT_COMPATIBILITA]</TD>
                        <TD>$c[ID_DIPENDENTE]</TD>
                        <TD>$c[PCT_UTILIZZO]</TD>
                        <TD>$c[DATA]</TD>
                        <TD>$c[NUM_ORE_RESIDUE]</TD>
                        <TD>$c[NUM_ORE_PRELEVATE]</TD>
                        <TD>$c[ORE_LUL]</TD>
                    </TR>";
            }
        } else {
            $message->success .= "<TR><TD COLSPAN=9>Nessuna riga estratta</TD></TR>";
        }
        $message->success .= "</TBODY>";
        $message->success .= "</TABLE>" . NL;
    }
}
?>