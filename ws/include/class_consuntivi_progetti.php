<?php

$consuntiviProgettiManager = new ConsuntiviProgettiManager();

define('NL', "<br/>");

class ConsuntiviProgettiManager {
    
    function run_assegnazione($idProgetto, $date, &$message) {
        
        $message->success .= "Lancio assegnazione progetto n.$idProgetto alla data " . $date->format('d/m/Y') . NL;

        // REPERIRE DATI DA DB
        $dataLimite = "DATE('" . $date->format('Y-m-d') . "')";
        $query_caricamenti = "SELECT oc.*,pr.PCT_IMPIEGO,c.PCT_COMPATIBILITA FROM ore_consuntivate_commesse oc
                    JOIN progetti_persone pr ON pr.MATRICOLA_DIPENDENTE=oc.MATRICOLA_DIPENDENTE
                    JOIN progetti_commesse c ON c.ID_PROGETTO=pr.ID_PROGETTO AND c.COD_COMMESSA=oc.COD_COMMESSA
                    JOIN progetti p ON p.ID_PROGETTO=pr.ID_PROGETTO
                    WHERE p.ID_PROGETTO=$idProgetto AND oc.DATA > p.DATA_ULTIMO_REPORT and oc.DATA < $dataLimite";
        $caricamenti = select_list($query_caricamenti);

        $superquery = "SELECT c.COD_COMMESSA,c.PCT_COMPATIBILITA,p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO,NVL(SUM(oc.ORE_LAVORATE),0) as ORE_LAVORATE
            FROM progetti_commesse c
            JOIN progetti_persone p ON c.ID_PROGETTO=p.ID_PROGETTO
            JOIN progetti pr ON pr.ID_PROGETTO=p.ID_PROGETTO
            LEFT JOIN ore_consuntivate_commesse oc ON oc.COD_COMMESSA=c.COD_COMMESSA AND oc.MATRICOLA_DIPENDENTE=p.MATRICOLA_DIPENDENTE
            WHERE pr.ID_PROGETTO=$idProgetto AND (oc.DATA IS NULL OR (oc.DATA > pr.DATA_ULTIMO_REPORT and oc.DATA < $dataLimite))
            GROUP BY c.COD_COMMESSA,c.PCT_COMPATIBILITA,p.MATRICOLA_DIPENDENTE,p.PCT_IMPIEGO";

    /*  $query_matr = "SELECT DISTINCT MATRICOLA_DIPENDENTE from ore_presenza_lul " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
        $query_date = "SELECT DISTINCT DATA FROM ore_presenza_lul " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
        $query_lul = "SELECT DATA,MATRICOLA_DIPENDENTE,ORE_PRESENZA_ORDINARIE FROM ore_presenza_lul " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
        $query_pregresso = "SELECT ID_PROGETTO,DATA,MATRICOLA_DIPENDENTE,ORE_LAVORATE FROM ore_consuntivate_progetti " .
                    "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_LAVORATE > 0";
        */

        $query_commesse_p = "SELECT COD_COMMESSA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA>=100";
        $commesse_p = select_column($query_commesse_p);
        $supertot = 0.0;

        $message->success .= "Commesse di progetto assegnate: " . implode(", ", $commesse_p) . NL;
        foreach($commesse_p as $comm) {
            $tot = 0.0;
            foreach($caricamenti as $c) {
                if ($c['COD_COMMESSA'] == $comm) {
                    $tot += $c['ORE_LAVORATE'];
                }
            }
            $message->success .= "  $comm: trovate $tot ore" . NL;
            $supertot += $tot;
        }

        $query_commesse_c = "SELECT COD_COMMESSA,PCT_COMPATIBILITA FROM progetti_commesse WHERE id_progetto=$idProgetto and PCT_COMPATIBILITA<100";
        $commesse_c = select_list($query_commesse_c);
        function getCodCommessa($x)
        {
            return $x['COD_COMMESSA'];
        }
        $nomi_commesse_c = array_map('getCodCommessa', $commesse_c); 
        $message->success .= "Commesse compatibili assegnate: " . implode(", ", $nomi_commesse_c) . NL;
        foreach($commesse_c as $comm) {
            $tot = 0.0;
            foreach($caricamenti as $c) {
                if ($c['COD_COMMESSA'] == $comm['COD_COMMESSA']) {
                    $tot += $c['ORE_LAVORATE'];
                    $commesse_p['UTILIZZABILE'] = 1.0 * $c['ORE_LAVORATE'] * $comm['PCT_COMPATIBILITA'];
                }
            }
            $utilizzabile = $tot * $comm['PCT_COMPATIBILITA'] / 100;
            $message->success .= "  $comm[COD_COMMESSA]: trovate $tot ore * $comm[PCT_COMPATIBILITA]% = $utilizzabile" . NL;
            $supertot += $utilizzabile;
        }

        




        
        // NUOVO NUMERO ESECUZIONE
        $query_max = "SELECT NVL(MAX(ID_ESECUZIONE),0)+1 FROM `storico_assegnazioni` WHERE 1";
        $idEsecuzione = select_single_value($query_max);

        $message->success .= "Salvo i dati ottenuti con ID_ESECUZIONE=$idEsecuzione" . NL;

        // SALVO SU DATABASE
        /*for ($i = 0; $i < count($matricole); $i++) {
            $matr = $matricole[$i];
            for ($j = 0; $j < count($wp); $j++) {
                $idprogetto = $wp[$j]["ID_PROGETTO"];
                $idwp = $wp[$j]["ID_WP"];
                for ($k = 0; $k < count($date); $k++) {
                    $data = $date[$k];
                    $ore = $x[$i][$j][$k];
                    $query = "REPLACE INTO ore_consuntivate_progetti(ID_PROGETTO, MATRICOLA_DIPENDENTE, DATA, ORE_LAVORATE) VALUES($idprogetto,'$matr','$data',$ore)";
                    execute_update($query);
                }
            }
        }*/
    }

}
?>