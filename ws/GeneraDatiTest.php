<?php

// Mi aspetto un solo parametro, il periodo di lancio, nel formato YYYY-MM oppure YYYY-MM-DD

include("include/all.php");    
$con = connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //==========================================================

    // CONTROLLO PARAMETRI

    $postdata = file_get_contents("php://input");
    $json_data = json_decode($postdata);
    if (!$json_data) {
        print_error(400, "Missing JSON data");
    }
    if (!$json_data->periodo) {
        print_error(400, "Missing parameter: periodo");
    }
    $anno = substr($json_data->periodo, 0, 4);
    $mese = substr($json_data->periodo, 5, 2);
    if (empty($anno) || empty($mese)) {
        print_error(400, "Il periodo di lancio deve essere nella forma YYYY-MM");
    }

    // REPERIRE DATI DA DB
    $primo = "DATE('$anno-$mese-01')";
    $query_matr = "SELECT DISTINCT MATRICOLA_DIPENDENTE from ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
    $query_wp = "SELECT ID_PROGETTO, ID_WP, DATA_INIZIO, DATA_FINE, MONTE_ORE_RESIDUO " . 
                "FROM progetti_wp_residuo ".
                "WHERE (DATA_INIZIO >= $primo AND DATA_INIZIO <= LAST_DAY($primo)) OR (DATA_FINE >= $primo AND DATA_FINE <= LAST_DAY($primo)) AND MONTE_ORE_RESIDUO > 0";
    $query_date = "SELECT DISTINCT DATA FROM ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
    $query_lul = "SELECT DATA,MATRICOLA_DIPENDENTE,ORE_PRESENZA_ORDINARIE FROM ORE_PRESENZA_LUL " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_PRESENZA_ORDINARIE > 0";
    $query_pregresso = "SELECT ID_PROGETTO_ID_WP,DATA,MATRICOLA_DIPENDENTE,ORE_LAVORATE FROM ORE_CONSUNTIVATE " .
                "WHERE DATA >= $primo AND DATA <= LAST_DAY($primo) AND ORE_LAVORATE > 0";
    
    // TODO selezionare anche i dati preesistenti

    $matricole = select_column($query_matr);
    $wp = select_list($query_wp);
    $date = select_column($query_date);
    $lul = select_list($query_lul);
    $pregresso = select_list($query_pregresso);
    
    // Inizializzo la struttura x (ore lavorate dal dipendente i sul WP j il giorno k)
    $x = array();
    for ($i = 0; $i < count($matricole); $i++) {
        $x[$i] = array();
        for ($j = 0; $j < count($wp); $j++) {
            $x[$i][$j] = array();
            for ($k = 0; $k < count($date); $k++) {
                $x[$i][$j][$k] = 0;
                for ($w = 0; $w < count($pregresso); ++w) {
                    if ($pregresso["MATRICOLA_DIPENDENTE"] == $matricole[$i] and $pregresso["DATA"] == $date[$k] and $pregresso["ID_PROGETTO"] == $wp[$j]["ID_PROGETTO"] and $pregresso["ID_WP"] == $wp[$j]["ID_WP"]) {
                        $x[$i][$j][$k] = $pregresso["ORE_LAVORATE"];
                        break;
                    }
                }
            }
        }
    }

    // Inizializzo la struttura M (monte ore) e la $wp_rsr (progetti-risorse)
    $M = array();
    $wp_rsr = array();
    for ($j = 0; $j < count($wp); $j++) {
        $M[$j] = $wp["MONTE_ORE_RESIDUO"];
        
        $idprogetto = $wp["ID_PROGETTO"];
        $idwp = $wp["ID_WP"];
        $query_wp_rsr = "SELECT MATRICOLA_DIPENDENTE FROM progetti_wp_risorse WHERE ID_PROGETTO='$idprogetto' AND ID_WP='$idwp' ";
        $wp_rsr[$j] = select_list($query_wp_rsr);
    }
    
    // Inizializzo la struttura L (ore lavorate dal dipendente i il giorno k)
    $L = array();
    for ($i = 0; $i < count($matricole); $i++) {
        $L[$i] = array();
        for ($k = 0; $k < count($date); $k++) {
            $L[$i][$k] = 0;
            for ($w = 0; $w < count($lul); ++w) {
                if ($lul["MATRICOLA_DIPENDENTE"] == $matricole[$i] and $lul["DATA"] == $date[$k]) {
                    $L[$i][$k] = $lul["ORE_PRESENZA_ORDINARIE"];
                    break;
                }
            }
        }
    }
    
    // LOOP PRINCIPALE
    for ($k = 0; $k < count($date); $k++) {
        $data = $date[$k];
        for ($i = 0; $i < count($matricole); $i++) {
            $matricola = $matricole[$j];
            $probabilita = array();
            for ($j = 0; $j < count($wp); $j++) {
                $probabilita[$j] = 0;
                if ($M[$j] > 0 and in_array($matricola, $wp_rsr[$j]) and $data >= $wp["DATA_INIZIO"] and $data <= $wp["DATA_FINE"]) {
                    $probabilita[$j] = $M[$j] / date_diff($wp[$j]["DATA_FINE"], $data);
                }
            }
            for ($ora = 0; $ora < $L[$i][$k]; $ora++) {
                $j = random_probability($probabilities);
                $x[$i][$j][$k]++;
                $M[$j]--;
            }
        }
    }
    
    // SALVO SU DATABASE
    for ($i = 0; $i < count($matricole); $i++) {
        $matr = $matricole[$i];
        for ($j = 0; $j < count($wp); $j++) {
            $idprogetto = $wp["ID_PROGETTO"];
            $idwp = $wp["ID_WP"];
            for ($k = 0; $k < count($date); $k++) {
                $data = $date[$k];
                $ore = $x[$i][$j][$k];
                $query = "REPLACE INTO ore_consuntivate(ID_PROGETTO, ID_WP, MATRICOLA_DIPENDENTE, DATA, ORE_LAVORATE) VALUES($idprogetto,$idwp,'$matr','$data',$ore)";
                execute_update($query);
            }
        }
    }
    
    
} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>