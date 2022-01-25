<?php

$esecuzioniManager = new EsecuzioniManager();

class EsecuzioniManager {

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
                    SELECT NVL(MAX(`DATA`),DATE('0001-01-01'))
                    FROM ore_consuntivate_progetti
                    WHERE ID_PROGETTO = '$idProgetto'
                ) WHERE ID_PROGETTO = '$idProgetto'";
        execute_update($sql);
    }
}
?>