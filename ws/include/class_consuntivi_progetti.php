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
            if ($monte_ore > 0) {
                $message->success .= "Monte ore residuo $monte_ore ore." . NL;
            }

            $idEsecuzione = $this->get_id_esecuzione($idProgetto, $message);

            $this->estrazione_caricamenti($idEsecuzione, $idProgetto, $dataLimite, $message);
            $this->estrazione_lul($idEsecuzione, $message);

            list($commesse_p,$commesse_c,$matricole) = $this->load_commesse_e_dipendenti($idProgetto);

            if (count($commesse_p) == 0 && count($commesse_c) == 0) {
                $message->error .= "Nessuna commessa &egrave; stata configurata su questo progetto!" . NL;
                return;
            }
            if (count($matricole) == 0) {
                $message->error .= "Nessun dipendente &egrave; stato configurato su questo progetto!" . NL;
                return;
            }
            $this->check_commesse_dipendenti($idProgetto, $dataLimite, $message);

            $ore_progetto = $this->show_commesse_progetto($idEsecuzione, $commesse_p, $message);
            $message->success .= "<strong>Tot. $ore_progetto ore di progetto</strong>". NL;

            $ore_in_eccesso_lul = $this->verifica_lul_commesse_progetto($idEsecuzione, $idProgetto, $commesse_p, $monte_ore, $message);
            if ($ore_in_eccesso_lul > 0) {
                $ore_progetto -= $ore_in_eccesso_lul;
                $message->success .= "<strong>Tot. $ore_progetto ore di progetto utilizzabili</strong>" . NL;
            }

            $monte_ore -= $ore_progetto;

            if ($monte_ore <= 0) {
                $message->error .= "Monte ore esaurito, le ore delle commesse compatibili non verranno assegnate " .
                                                                    "(verranno comunque mostrate qui di seguito)" . NL;
            }
            $ore_compat = $this->show_commesse_compatibili($idEsecuzione, $commesse_c, $message);
            $message->success .= "<strong>Tot. $ore_compat ore su commesse compatibili</strong>". NL;

            if ($monte_ore > 0) {
                $ore_in_eccesso_lul = $this->verifica_lul_commesse_compatibili($idEsecuzione, $idProgetto, $commesse_c, $monte_ore, $message);

                if ($ore_in_eccesso_lul > 0) {
                    $ore_compat = $this->show_commesse_compatibili($idEsecuzione, $commesse_c, $message);
                    $message->success .= "<strong>Tot. $ore_compat ore utilizzabili su commesse compatibili</strong>" . NL;
                }
                $monte_ore -= $ore_compat;
            } else {
                $ore_compat = 0.0;
            }

            $tot_ore_assegnate = $ore_progetto + $ore_compat;
            $message->success .= "<strong>Tot. $tot_ore_assegnate ore assegnate</strong>". NL;
            
            if ($tot_ore_assegnate < 0 ) {
                // in caso di errori evitiamo di sottrarre ore!!! 
                $tot_ore_assegnate = 0.0;
            }

            $this->apply($idEsecuzione, $tot_ore_assegnate);
            $message->success .= "Ore assegnate." . NL;
            
            $message->success .= "Monte ore residuo dopo l'assegnazione: $monte_ore ore." . NL;
            
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

            $message->success .= "Salvo i dati ottenuti con <strong>ID_ESECUZIONE=$idEsecuzione</strong>" . NL;

            $query ="INSERT INTO assegnazioni (ID_ESECUZIONE, ID_PROGETTO, UTENTE) VALUES ('$idEsecuzione', '$idProgetto', '$logged_user->nome_utente')";
            execute_update($query);

            $con->commit();
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();        
            throw $exception;
        }
        return $idEsecuzione;
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
                NUM_ORE_RESIDUE, NUM_ORE_COMPATIBILI)
            SELECT
                $idEsecuzione, $idProgetto,
                c.COD_COMMESSA,c.PCT_COMPATIBILITA,
                p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO,
                oc.DATA,oc.RIF_SERIE_DOC,oc.RIF_NUMERO_DOC,oc.RIF_ATV,oc.RIF_SOTTO_COMMESSA,
                NVL(oc.NUM_ORE_RESIDUE,0) as NUM_ORE_RESIDUE,
                FLOOR(p.PCT_IMPIEGO*NVL(oc.NUM_ORE_RESIDUE,0)*4/100)/4 as NUM_ORE_COMPATIBILI
            FROM progetti_commesse c
            JOIN progetti_persone p ON c.ID_PROGETTO=p.ID_PROGETTO
            JOIN progetti pr ON pr.ID_PROGETTO=p.ID_PROGETTO
            LEFT JOIN ore_consuntivate_residuo oc ON oc.COD_COMMESSA=c.COD_COMMESSA 
                AND oc.MATRICOLA_DIPENDENTE=p.MATRICOLA_DIPENDENTE
                AND oc.DATA > pr.DATA_ULTIMO_REPORT AND oc.DATA < $d
            WHERE pr.ID_PROGETTO=$idProgetto";
        execute_update($query);
        
        // pre-compilo queste colonne con valori non veritieri, da usare come contatori
        $query = "UPDATE assegnazioni_dettaglio ad
            SET NUM_ORE_UTILIZZABILI_LUL=NUM_ORE_RESIDUE,
            NUM_ORE_COMPATIBILI_LUL=NUM_ORE_COMPATIBILI
            WHERE ID_ESECUZIONE=$idEsecuzione";
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
    function get_data($idEsecuzione) {
        $query = "SELECT * FROM assegnazioni_dettaglio ad
            WHERE ID_ESECUZIONE=$idEsecuzione
            ORDER BY COD_COMMESSA,MATRICOLA_DIPENDENTE,DATA";
        return select_list($query);
    }

    function show_commesse_progetto($idEsecuzione, $commesse_p, &$message) {
        $NOME_CAMPO_ORE_LAVORATE = 'NUM_ORE_RESIDUE';

        $caricamenti = $this->get_data($idEsecuzione);

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
        return $tot_di_progetto;
    }

    function show_commesse_compatibili($idEsecuzione, $commesse_c, &$message) {
        $NOME_CAMPO_ORE_LAVORATE = 'NUM_ORE_UTILIZZABILI_LUL';
        $NOME_CAMPO_ORE_COMPATIBILI = 'NUM_ORE_COMPATIBILI_LUL';

        $caricamenti = $this->get_data($idEsecuzione);

        $message->success .= "Commesse compatibili assegnate: " . implode(", ", $commesse_c) . NL;
        $tot_compat = 0.0;
        foreach($commesse_c as $comm) {
            $tot = 0.0;
            $compatibili = 0.0;
            $pct_comp = null; // peccato non averla già...
            foreach($caricamenti as $id => $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c[$NOME_CAMPO_ORE_LAVORATE];
                    $compatibili += $c[$NOME_CAMPO_ORE_COMPATIBILI];
                    $pct_comp = $c['PCT_COMPATIBILITA'];
                }
            }
            if ($compatibili > 0) {
                $message->success .= "  $comm: trovate $tot ore * $pct_comp% = $compatibili ore compatibili" . NL;
            } else {
                $message->success .= "  $comm: trovate 0 ore compatibili" . NL;
            }
            $tot_compat += $compatibili;
        }

        return $tot_compat;
    }

    /**
     * Cerca i dipendenti le cui dichiarazioni sforano i LUL, e cerca di "sistemarli".
     * 
     * Le ore di progetto saranno assegnate tutte, LUL permettendo, ignorando sia il monte ore previsto
     * sia le % di assegnazione dei dipendenti 
     * 
     * PUNTI DI ATTENZIONE:
     * (1) controllo la differenza ore solo sul progetto corrente!
     * (2) trovo una discrepanza tra la somma ore e il LUL, ma non so a quale commessa imputare tale differenza
     */
    function verifica_lul_commesse_progetto($idEsecuzione, $idProgetto, $commesse_p, $monte_ore, &$message) {

        $commesse_imploded = "'" . implode("','", $commesse_p) . "'";

        $query = "SELECT MATRICOLA_DIPENDENTE,DATA,SUM(NUM_ORE_RESIDUE)-MAX(NUM_ORE_LUL) AS ORE_ECCESSO
            FROM assegnazioni_dettaglio ad
            WHERE ad.ID_ESECUZIONE=$idEsecuzione
                AND ad.COD_COMMESSA IN ($commesse_imploded)
                AND NUM_ORE_RESIDUE>0
            GROUP BY MATRICOLA_DIPENDENTE,DATA
            HAVING SUM(NUM_ORE_RESIDUE) > MAX(NUM_ORE_LUL)";
        $lista_ore_eccesso = select_list($query);

        $ore_in_eccesso = array_sum(array_column($lista_ore_eccesso,'ORE_ECCESSO'));

        if ($ore_in_eccesso == 0.0) {
            $message->success .= "Verifica LUL: ok." . NL;
            return $ore_in_eccesso;
        }
        
        $message->success .= "Verifica LUL: Ci sono $ore_in_eccesso ore in eccesso non utilizzabili." . NL;
        
        foreach ($lista_ore_eccesso as $ore) {
            // so che quel giorno, per quel dipendente, ho xxx ore in eccesso
            // ma magari il dipendente ha lavorato su 2 commesse di progetto differenti
            // decurterò in ordine casuale
            
            $query = "SELECT *
                FROM assegnazioni_dettaglio ad
                WHERE ad.ID_ESECUZIONE=$idEsecuzione
                    AND ad.MATRICOLA_DIPENDENTE=$ore[MATRICOLA_DIPENDENTE] AND ad.DATA='$ore[DATA]'
                    AND ad.COD_COMMESSA in ($commesse_imploded) AND NUM_ORE_RESIDUE>0";
            $dettagli = select_list($query);
            $ore_eccesso_data = 0.0 + $ore["ORE_ECCESSO"];
            foreach ($dettagli as $dett) {
                if (0.0 + $dett["NUM_ORE_UTILIZZABILI_LUL"] >= $ore_eccesso_data) {
                    $this->update_ore_post_lul($idEsecuzione, $dett, $ore_eccesso_data);
                    break;
                } else {
                    $ore_eccesso_data -= $dett["NUM_ORE_UTILIZZABILI_LUL"];
                    $this->update_ore_post_lul($idEsecuzione, $dett, $dett["NUM_ORE_UTILIZZABILI_LUL"]);
                }
            }
        }

        $this->update_ore_compatibili($idEsecuzione);
        return $ore_in_eccesso;
    }

    /**
     * Cerca i dipendenti le cui dichiarazioni sforano i LUL, e cerca di "sistemarli".
     * 
     * Non modifichiamo più nessuna delle commesse di progetto, solo quelle compatibili,
     * e solo fintanto che non superiamo il monte ore
     * 
     * PUNTI DI ATTENZIONE:
     * (1) controllo la differenza ore solo sul progetto corrente!
     * (2) trovo una discrepanza tra la somma ore e il LUL, ma non so a quale commessa imputare tale differenza
     */
    function verifica_lul_commesse_compatibili($idEsecuzione, $idProgetto, $commesse_c, $monte_ore, &$message) {

        $commesse_imploded = "'" . implode("','", $commesse_c) . "'";

        $query = "SELECT MATRICOLA_DIPENDENTE,DATA,SUM(NUM_ORE_UTILIZZABILI_LUL)-MAX(NUM_ORE_LUL) AS ORE_ECCESSO
            FROM assegnazioni_dettaglio ad
            WHERE ad.ID_ESECUZIONE=$idEsecuzione AND NUM_ORE_RESIDUE>0
            GROUP BY MATRICOLA_DIPENDENTE,DATA
            HAVING SUM(NUM_ORE_UTILIZZABILI_LUL) > MAX(NUM_ORE_LUL)";
        $lista_ore_eccesso = select_list($query);
        // La query prende tutte le commesse, ma le ore in eccesso sono tutte dovute
        // alle commesse compatibili, perchè quelle di progetto le ho già decurtate
        // nota che guardo NUM_ORE_UTILIZZABILI_LUL e non NUM_ORE_RESIDUE

        $ore_in_eccesso = array_sum(array_column($lista_ore_eccesso,'ORE_ECCESSO'));

        if ($ore_in_eccesso == 0.0) {
            $message->success .= "Verifica LUL: ok." . NL;
            return $ore_in_eccesso;
        }
        
        $message->success .= "Verifica LUL: Ci sono $ore_in_eccesso ore in eccesso (a meno di compatibilit&agrave;) non utilizzabili" . NL;
        
        foreach ($lista_ore_eccesso as $ore) {
            // so che quel giorno, per quel dipendente, ho xxx ore in eccesso
            // ma magari il dipendente ha lavorato su 2 commesse compatibili differenti
            // decurterò in ordine (quasi) casuale, per PCT_COMPATIBILITA ASC
            
            $query = "SELECT ad.*
                FROM assegnazioni_dettaglio ad
                JOIN progetti_commesse pc ON pc.ID_PROGETTO=$idProgetto AND pc.COD_COMMESSA=ad.COD_COMMESSA
                WHERE ad.ID_ESECUZIONE=$idEsecuzione
                    AND ad.MATRICOLA_DIPENDENTE=$ore[MATRICOLA_DIPENDENTE] AND ad.DATA='$ore[DATA]'
                    AND ad.COD_COMMESSA in ($commesse_imploded) AND NUM_ORE_RESIDUE>0
                ORDER BY PCT_COMPATIBILITA ASC";
            $dettagli = select_list($query);

            $ore_eccesso_data = 0.0 + $ore["ORE_ECCESSO"];
            foreach ($dettagli as $dett) {
                if (0.0 + $dett["NUM_ORE_UTILIZZABILI_LUL"] >= $ore_eccesso_data) {
                    $this->update_ore_post_lul($idEsecuzione, $dett, $ore_eccesso_data);
                    break;
                } else {
                    $ore_eccesso_data -= $dett["NUM_ORE_UTILIZZABILI_LUL"];
                    $this->update_ore_post_lul($idEsecuzione, $dett, $dett["NUM_ORE_UTILIZZABILI_LUL"]);
                }
            }
        }
        
        $this->update_ore_compatibili($idEsecuzione);
        return $ore_in_eccesso;
    }

    function update_ore_post_lul($idEsecuzione, $dett, $oreDaTogliere) {
        $query = "UPDATE assegnazioni_dettaglio
                SET NUM_ORE_UTILIZZABILI_LUL=NUM_ORE_UTILIZZABILI_LUL-$oreDaTogliere
                WHERE ID_ESECUZIONE=$idEsecuzione
                    AND MATRICOLA_DIPENDENTE=$dett[MATRICOLA_DIPENDENTE] AND DATA='$dett[DATA]'
                    AND RIF_SERIE_DOC='$dett[RIF_SERIE_DOC]' AND RIF_NUMERO_DOC='$dett[RIF_NUMERO_DOC]'
                    AND RIF_ATV='$dett[RIF_ATV]' AND  RIF_SOTTO_COMMESSA ='$dett[RIF_SOTTO_COMMESSA]'";
            execute_update($query);
    }

    function update_ore_compatibili($idEsecuzione) {
        $query = "UPDATE assegnazioni_dettaglio
                SET NUM_ORE_COMPATIBILI_LUL=FLOOR(PCT_IMPIEGO*(NUM_ORE_UTILIZZABILI_LUL)*4/100)/4
                WHERE ID_ESECUZIONE=$idEsecuzione";
            execute_update($query);
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
                        SUM(NUM_ORE_COMPATIBILI_LUL), $idEsecuzione
                     FROM assegnazioni_dettaglio ad
                     WHERE ID_ESECUZIONE=$idEsecuzione AND NUM_ORE_COMPATIBILI_LUL>0
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

                if ($c['NUM_ORE_UTILIZZABILI_LUL'] != $c['NUM_ORE_RESIDUE']) {
                    $c['NUM_ORE_UTILIZZABILI_LUL'] .= '*';
                }

                $message->success .= "
                    <TR>
                        <TD>$c[COD_COMMESSA]</TD>
                        <TD>$c[PCT_COMPATIBILITA]</TD>
                        <TD>$c[MATRICOLA_DIPENDENTE]</TD>
                        <TD>$c[PCT_IMPIEGO]</TD>
                        <TD>$c[DATA]</TD>
                        <TD>$c[NUM_ORE_RESIDUE]</TD>
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
    
    function get_esecuzioni($skip=null, $top=null, $orderby=null) {
        global $con;
        
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM assegnazioni p ";
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.id_esecuzione DESC";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null) {
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }        
        $oggetti = select_list($sql1 . $sql);
        
        return [$oggetti, $count];
    }
    
    function get_esecuzione($id_esecuzione) {
        $sql = "SELECT * FROM assegnazioni WHERE id_esecuzione = '$id_esecuzione'";
        return select_single($sql);
    }
    
    function elimina_esecuzione($idEsecuzione) {
        $query ="SELECT ID_PROGETTO,TOT_ASSEGNATE FROM assegnazioni WHERE ID_ESECUZIONE=$idEsecuzione";
        $result = select_single($query);
        $idProgetto = $result['ID_PROGETTO'];
        $oreDaTogliere = $result['TOT_ASSEGNATE'];

        $query ="DELETE FROM ore_consuntivate_progetti WHERE ID_ESECUZIONE=$idEsecuzione";
        execute_update($query);
        $sql = "DELETE FROM assegnazioni_dettaglio WHERE ID_ESECUZIONE = '$idEsecuzione'";
        execute_update($sql);
        $sql = "DELETE FROM assegnazioni WHERE ID_ESECUZIONE = '$idEsecuzione'";
        execute_update($sql);

        if ($oreDaTogliere != null && $oreDaTogliere > 0) {
            $sql = "UPDATE progetti SET ORE_GIA_ASSEGNATE=ORE_GIA_ASSEGNATE-$oreDaTogliere WHERE ID_PROGETTO = '$idProgetto'";
        }
        execute_update($sql);
        $sql = "UPDATE progetti SET DATA_ULTIMO_REPORT=(
                    SELECT NVL(MAX(`DATA`),DATE('0000-01-01'))
                    FROM ore_consuntivate_progetti
                    WHERE ID_PROGETTO = '$idProgetto'
                ) WHERE ID_PROGETTO = '$idProgetto'";
        execute_update($sql);
    }
}
?>