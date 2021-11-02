<?php

$consuntiviProgettiManager = new ConsuntiviProgettiManager();

define('NL', "<br/>");
define('PRE', "1");
define('POST', "2");

class ConsuntiviProgettiManager {
    
    function run_assegnazione($idProgetto, $dataLimite, &$message) {
        try {
            $message->success .= "Lancio assegnazione ore progetto n.$idProgetto alla data " . $dataLimite->format('d/m/Y') . NL;

            $query_monte_ore = "select MONTE_ORE_TOT-ORE_GIA_ASSEGNATE from progetti p where id_progetto=$idProgetto";
            $monte_ore = select_single_value($query_monte_ore);
            if ($monte_ore <= 0) {
                $message->error .= "Monte ore di progetto esaurito!" . NL;
                return;
            }
            $message->success .= "Monte ore residuo $monte_ore ore." . NL;

            $idEsecuzione = $this->get_id_esecuzione($idProgetto, $message);

            $this->estrazione_caricamenti($idEsecuzione, $idProgetto, $dataLimite, $message);

            $this->estrazione_lul($idEsecuzione, $message);

            $query = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA>=100";
            $commesse_p = select_column($query);
            $query = "SELECT DISTINCT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA<100";
            $commesse_c = select_column($query);

            
            $tot_ore_assegnate = $this->show_commesse($idEsecuzione, $commesse_p, $commesse_c, PRE, $message);

            $lul_ok = $this->verifica_lul($idEsecuzione, $message);

            if (!$lul_ok) {
                $tot_ore_assegnate = $this->show_commesse($idEsecuzione, $commesse_p, $commesse_c, POST, $message);
            }

            $message->success .= "<b>Tot. $tot_ore_assegnate ore assegnate</b>". NL;
            
            // TODO: salvare anche la ore_consuntivate_progetti
            $this->apply($idEsecuzione, $tot_ore_assegnate);
            $message->success .= "Ore assegnate." . NL;

            $this->log($idEsecuzione, $message);

            $message->success .= "Fine." . NL;
        
        } catch (Exception $exception) {
            $message->error .= $exception->getMessage();
        }
    }

    function get_id_esecuzione($idProgetto, &$message) {
        global $con, $logged_user;
        
        $con->begin_transaction();
        try {
            $query_max = "SELECT NVL(MAX(ID_ESECUZIONE),0)+1 FROM assegnazioni WHERE 1";
            $idEsecuzione = select_single_value($query_max);

            $message->success .= "Salvo i dati ottenuti con <b>ID_ESECUZIONE=$idEsecuzione</b>" . NL;

            $query ="INSERT INTO assegnazioni (ID_ESECUZIONE, ID_PROGETTO, UTENTE) VALUES ('$idEsecuzione', '$idProgetto', '$logged_user->nome_utente')";
            execute_update($query);

            $con->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();        
            throw $exception;
        }
        return $idEsecuzione;
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
                DATA, RIF_DOC, RIF_RIGA_DOC,
                NUM_ORE_LAVORATE, NUM_ORE_COMPATIBILI)
            SELECT
                $idEsecuzione, $idProgetto,
                c.COD_COMMESSA,c.PCT_COMPATIBILITA,
                p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO,
                oc.DATA,oc.RIF_DOC,oc.RIF_RIGA_DOC,
                NVL(oc.NUM_ORE_LAVORATE,0) as NUM_ORE_LAVORATE,
                FLOOR(p.PCT_IMPIEGO*NVL(oc.NUM_ORE_LAVORATE,0)*4/100)/4 as NUM_ORE_COMPATIBILI
            FROM progetti_commesse c
            JOIN progetti_persone p ON c.ID_PROGETTO=p.ID_PROGETTO
            JOIN progetti pr ON pr.ID_PROGETTO=p.ID_PROGETTO
            LEFT JOIN ore_consuntivate_residuo oc ON oc.COD_COMMESSA=c.COD_COMMESSA 
                AND oc.MATRICOLA_DIPENDENTE=p.MATRICOLA_DIPENDENTE
            WHERE pr.ID_PROGETTO=$idProgetto AND (oc.DATA IS NULL OR (oc.DATA > pr.DATA_ULTIMO_REPORT and oc.DATA < $d))";
        execute_update($query);
    }

    /**
     * Aggiorna la tabella di lavoro con le informazioni derivanti dai LUL
     */
    function estrazione_lul($idEsecuzione, &$message) {
        $query = "UPDATE assegnazioni_dettaglio ad
            SET NUM_ORE_LUL=
                (SELECT NVL(SUM(ORE_PRESENZA_ORDINARIE),0)
                FROM ore_presenza_lul l
                WHERE l.MATRICOLA_DIPENDENTE=ad.MATRICOLA_DIPENDENTE AND l.DATA=ad.DATA)
            WHERE ID_ESECUZIONE=$idEsecuzione";
        execute_update($query);
    }

    /**
     * Carica la tabella di lavoro
     */
    function get_data($idEsecuzione, &$message) {
        $query = "SELECT * FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            ORDER BY DATA,MATRICOLA_DIPENDENTE,COD_COMMESSA";
        return select_list($query);
    }

    /**
     * Mostra all'utente un report delle varie commesse legate al progetto, e restituisce
     * il totale delle ore assegnabili
     * 
     * @param if_lul puo' valere PRE o POST, a seconda che si voglia tenere conto o meno dei LUL
     */
    function show_commesse($idEsecuzione, $commesse_p, $commesse_c, $if_lul, &$message) {
        $NOME_CAMPO_ORE_LAVORATE = $if_lul == PRE ? 'NUM_ORE_LAVORATE' : 'NUM_ORE_UTILIZZABILI_LUL';
        $NOME_CAMPO_ORE_COMPATIBILI = $if_lul == PRE ? 'NUM_ORE_COMPATIBILI' : 'NUM_ORE_COMPATIBILI_LUL';
        $UTILIZZABILI = $if_lul == PRE ? "" : " utilizzabili";

        $caricamenti = $this->get_data($idEsecuzione, $message);

        $message->success .= "Commesse di progetto assegnate: " . implode(", ", $commesse_p) . NL;
        $tot_di_progetto = 0.0;
        foreach($commesse_p as $comm) {
            $tot = 0.0;
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c[$NOME_CAMPO_ORE_LAVORATE];
                }
            }
            $message->success .= "  $comm: trovate $tot ore" . NL;
            $tot_di_progetto += $tot;
        }
        $message->success .= "<b>Tot. $tot_di_progetto ore di progetto$UTILIZZABILI</b>". NL;
        
        $message->success .= "Commesse compatibili assegnate: " . implode(", ", $commesse_c) . NL;
        $tot_compat = 0.0;
        foreach($commesse_c as $comm) {
            $tot = 0.0;
            $compatibili = 0.0;
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c[$NOME_CAMPO_ORE_LAVORATE];
                    $compatibili += $c[$NOME_CAMPO_ORE_COMPATIBILI];
                }
            }
            $message->success .= "  $comm: trovate $tot ore * $c[PCT_COMPATIBILITA]% = $compatibili ore compatibili" . NL;
            $tot_compat += $compatibili;
        }

        $message->success .= "<b>Tot. $tot_compat ore$UTILIZZABILI su commesse compatibili</b>". NL;

        return $tot_di_progetto+$tot_compat;
    }

    /**
     * Cerca i dipendenti le cui dichiarazioni sforano i LUL, e cerca di "sistemarli".
     * 
     * PUNTI DI ATTENZIONE:
     * (1) controllo la differenza ore solo sul progetto corrente!
     * (2) trovo una discrepanza tra la somma ore e il LUL, ma non so a quale commessa imputare tale differenza
     */
    function verifica_lul($idEsecuzione, &$message) {

        $query = "SELECT MATRICOLA_DIPENDENTE,DATA,SUM(NUM_ORE_LAVORATE)AS NUM_ORE_LAVORATE, MAX(NUM_ORE_LUL)AS ORE_LUL
            FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            GROUP BY MATRICOLA_DIPENDENTE,DATA
            HAVING SUM(NUM_ORE_LAVORATE) > MAX(NUM_ORE_LUL)";
        $ore_in_eccesso = select_list($query);

        $query = "UPDATE assegnazioni_dettaglio ad
            SET NUM_ORE_UTILIZZABILI_LUL=NUM_ORE_LAVORATE,
            NUM_ORE_COMPATIBILI_LUL=NUM_ORE_COMPATIBILI
            WHERE ID_ESECUZIONE=$idEsecuzione";
        execute_update($query);

        if (count($ore_in_eccesso) == 0) {
            $message->success .= "Verifica LUL: ok." . NL;
            return true;
        } else {
            $message->success .= "Verifica LUL" . NL;
            foreach($ore_in_eccesso as $r) {
                $sum = $r['NUM_ORE_LAVORATE'];
                $lul = $r['ORE_LUL'];
                $diff = $sum - $lul;
                $message->success .= "$r[MATRICOLA_DIPENDENTE] il $r[DATA] ha $diff ore in eccesso" . NL;

                // Ora tolgo le ore in eccesso da una o più commesse,
                // in ordine di PCT_COMPATIBILITA ASC

                $query = "SELECT * FROM assegnazioni_dettaglio ad 
                    WHERE ID_ESECUZIONE=$idEsecuzione AND MATRICOLA_DIPENDENTE='$r[MATRICOLA_DIPENDENTE]' AND DATA='$r[DATA]'
                    ORDER BY PCT_COMPATIBILITA ASC";
                $rows = select_list($query);

                foreach($rows as $row) {
                    $available = $row['NUM_ORE_LAVORATE'];
                    $newValue = max($row['NUM_ORE_LAVORATE'] - $diff, 0);

                    $query = "UPDATE assegnazioni_dettaglio ad
                        SET NUM_ORE_UTILIZZABILI_LUL=$newValue,
                            NUM_ORE_COMPATIBILI_LUL=FLOOR(PCT_IMPIEGO*$newValue*4/100)/4
                        WHERE ID_ESECUZIONE=$idEsecuzione AND MATRICOLA_DIPENDENTE='$r[MATRICOLA_DIPENDENTE]' AND DATA='$r[DATA]' AND
                        COD_COMMESSA='$row[COD_COMMESSA]' AND RIF_DOC='$row[RIF_DOC]' AND RIF_RIGA_DOC='$row[RIF_RIGA_DOC]'";
                    execute_update($query);

                    $diff -= $available;
                    if ($diff <= 0) {
                        break;
                    }
                }

            }
        }

        return false;
    }

    /**
     * Copia le rettifiche dalla tabella di lavoro alla ore_consuntivate_progetti
     */
    function apply($idEsecuzione, $tot_ore_assegnate) {
        global $con;

        $con->begin_transaction();
        try {
            $query ="INSERT INTO ore_consuntivate_progetti(ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA, NUM_ORE_LAVORATE, ID_ESECUZIONE)
                     SELECT ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA, SUM(NUM_ORE_COMPATIBILI_LUL), $idEsecuzione
                     FROM assegnazioni_dettaglio ad
                     WHERE ID_ESECUZIONE=$idEsecuzione AND NUM_ORE_COMPATIBILI_LUL>0
                     GROUP BY ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA
                     ";
            execute_update($query);

            $query = "UPDATE assegnazioni SET TOT_ASSEGNATE=$tot_ore_assegnate,IS_ASSEGNATE=1 WHERE ID_ESECUZIONE=$idEsecuzione";
            execute_update($query);

            $con->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();        
            throw $exception;
        }
    }

    /**
     * Elimina le righe dalla tabella ore_consuntivate_progetti
     */
    function unapply($idEsecuzione) {
        global $con;

        $con->begin_transaction();
        try {
            $query ="DELETE FROM ore_consuntivate_progetti WHERE ID_ESECUZIONE=$idEsecuzione";
            execute_update($query);

            $query = "UPDATE assegnazioni SET IS_ASSEGNATE=0 WHERE ID_ESECUZIONE=$idEsecuzione";
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
        $caricamenti = $this->get_data($idEsecuzione, $message);
        $message->success .= NL . "La seguente tabella viene mostrata a scopo di debug:" . NL;
        $message->success .= "<TABLE BORDER='1'>";
        $message->success .= "<THEAD>
                <TR>
                    <TH>COMMESSA</TH>
                    <TH>PCT. COMPATIBILITA</TH>
                    <TH>MATRICOLA</TH>
                    <TH>PCT. IMPIEGO</TH>
                    <TH>DATA</TH>
                    <TH>ORE LAVORATE</TH>
                    <TH>ORE COMPATIBILI</TH>
                    <TH>ORE LUL</TH>
                    <TH>ORE UTILIZZABILI LUL</TH>
                    <TH>ORE COMPATIBILI LUL</TH>
                </TR>
            </THEAD>";
        $message->success .= "<TBODY>";
        if (count($caricamenti) > 0) {
            foreach($caricamenti as $c) {

                if ($c['NUM_ORE_UTILIZZABILI_LUL'] != $c['NUM_ORE_LAVORATE']) {
                    $c['NUM_ORE_UTILIZZABILI_LUL'] .= '*';
                }

                $message->success .= "
                    <TR>
                        <TD>$c[COD_COMMESSA]</TD>
                        <TD>$c[PCT_COMPATIBILITA]</TD>
                        <TD>$c[MATRICOLA_DIPENDENTE]</TD>
                        <TD>$c[PCT_IMPIEGO]</TD>
                        <TD>$c[DATA]</TD>
                        <TD>$c[NUM_ORE_LAVORATE]</TD>
                        <TD>$c[NUM_ORE_COMPATIBILI]</TD>
                        <TD>$c[NUM_ORE_LUL]</TD>
                        <TD>$c[NUM_ORE_UTILIZZABILI_LUL]</TD>
                        <TD>$c[NUM_ORE_COMPATIBILI_LUL]</TD>
                    </TR>";
            }
        } else {
            $message->success .= "<TR><TD COLSPAN=10>Nessuna riga estratta</TD></TR>";
        }
        $message->success .= "</TBODY>";
        $message->success .= "</TABLE>" . NL;
    }
}
?>