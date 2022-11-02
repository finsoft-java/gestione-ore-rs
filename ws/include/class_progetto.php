<?php

$progettiManager = new ProgettiManager();

class ProgettiManager
{

    public function get_progetti($skip = null, $top = null, $orderby = null)
    {
        global $con;

        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT ID_PROGETTO, ACRONIMO, TITOLO, GRANT_NUMBER, ABSTRACT, MONTE_ORE_TOT,
                        OBIETTIVO_BUDGET_ORE, DATA_INIZIO, DATA_FINE, COSTO_MEDIO_UOMO,
                        COD_TIPO_COSTO_PANTHERA, ID_SUPERVISOR, DATA_ULTIMO_REPORT,
                        ORE_GIA_ASSEGNATE, GIUSTIFICATIVO_FILENAME,
                        CASE WHEN GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO ";
        $sql = "FROM progetti p ";

        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injectio
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.id_progetto DESC";
        }

        $count = select_single_value($sql0 . $sql);

        if ($top != null) {
            if ($skip != null) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }
        $progetti = select_list($sql1 . $sql);

        return [$progetti, $count];
    }

    public function get_progetto($id_progetto)
    {
        $sql = "SELECT ID_PROGETTO, ACRONIMO, TITOLO, GRANT_NUMBER, ABSTRACT, MONTE_ORE_TOT,
                    OBIETTIVO_BUDGET_ORE, DATA_INIZIO, DATA_FINE, COSTO_MEDIO_UOMO,
                    COD_TIPO_COSTO_PANTHERA, ID_SUPERVISOR, DATA_ULTIMO_REPORT,
                    ORE_GIA_ASSEGNATE, GIUSTIFICATIVO_FILENAME,
                    CASE WHEN GIUSTIFICATIVO IS NULL THEN 'N' ELSE 'Y' END AS HAS_GIUSTIFICATIVO
                    FROM progetti p WHERE id_progetto = '$id_progetto'";
        return select_single($sql);
    }

    public function crea($json_data)
    {
        global $con, $logged_user;

        $dataUltRep = null;
        if ($json_data->DATA_ULTIMO_REPORT) {
            // mi aspetto una data del tipo 2020-05-31T22:00:00.000Z
            // non funziona... c'è un baco nella conversione della data, decresce sempre di 1g
            $dataUltRep = substr($con->escape_string($json_data->DATA_ULTIMO_REPORT), 0, 10);
        }

        $sql = insert("progetti", ["ID_PROGETTO" => null,
            "TITOLO" => $con->escape_string($json_data->TITOLO),
            "ACRONIMO" => $con->escape_string($json_data->ACRONIMO),
            "GRANT_NUMBER" => $con->escape_string($json_data->GRANT_NUMBER),
            "ABSTRACT" => $con->escape_string($json_data->ABSTRACT),
            "MONTE_ORE_TOT" => $json_data->MONTE_ORE_TOT,
            "OBIETTIVO_BUDGET_ORE" => $json_data->OBIETTIVO_BUDGET_ORE,
            "DATA_INIZIO" => $json_data->DATA_INIZIO,
            "DATA_FINE" => $json_data->DATA_FINE,
            "COSTO_MEDIO_UOMO" => $json_data->COSTO_MEDIO_UOMO,
            "COD_TIPO_COSTO_PANTHERA" => $json_data->COD_TIPO_COSTO_PANTHERA,
            "ID_SUPERVISOR" => $json_data->ID_SUPERVISOR,
            "ORE_GIA_ASSEGNATE" => $json_data->ORE_GIA_ASSEGNATE,
            "DATA_ULTIMO_REPORT" => $dataUltRep,
        ]);
        execute_update($sql);
        $id_progetto = mysqli_insert_id($con);
        return $this->get_progetto($id_progetto);
    }

    public function aggiorna($progetto, $json_data)
    {
        global $con, $STATO_PROGETTO;
        $titolo = $con->escape_string($json_data->TITOLO);

        $dataUltRep = null;
        if ($json_data->DATA_ULTIMO_REPORT) {
            // mi aspetto una data del tipo 2020-05-31T22:00:00.000Z
            // non funziona... c'è un baco nella conversione della data, decresce sempre di 1g
            // $dataUltRep = substr($con->escape_string($json_data->DATA_ULTIMO_REPORT),0,10);
            $dataUltRep = $con->escape_string($json_data->DATA_ULTIMO_REPORT);
        }

        $sql = update("progetti", [
            "TITOLO" => $con->escape_string($json_data->TITOLO),
            "ACRONIMO" => $con->escape_string($json_data->ACRONIMO),
            "GRANT_NUMBER" => $con->escape_string($json_data->GRANT_NUMBER),
            "ABSTRACT" => $con->escape_string($json_data->ABSTRACT),
            "MONTE_ORE_TOT" => $json_data->MONTE_ORE_TOT,
            "OBIETTIVO_BUDGET_ORE" => $json_data->OBIETTIVO_BUDGET_ORE,
            "DATA_INIZIO" => $json_data->DATA_INIZIO,
            "DATA_FINE" => $json_data->DATA_FINE,
            "COSTO_MEDIO_UOMO" => $json_data->COSTO_MEDIO_UOMO,
            "COD_TIPO_COSTO_PANTHERA" => $json_data->COD_TIPO_COSTO_PANTHERA,
            "ID_SUPERVISOR" => $json_data->ID_SUPERVISOR,
            "ORE_GIA_ASSEGNATE" => $json_data->ORE_GIA_ASSEGNATE,
            "DATA_ULTIMO_REPORT" => $dataUltRep,
        ], ["ID_PROGETTO" => $json_data->ID_PROGETTO]);
        execute_update($sql);
    }

    public function elimina($id_progetto)
    {
        $sql = "DELETE FROM progetti WHERE id_progetto = '$id_progetto'"; //on delete cascade! (FIXME funziona anche con i questionari?!?)
        execute_update($sql);
    }

    public function download_giustificativo($idProgetto)
    {
        //TODO: cambiare la query perchè prende il cod commessa ma ora è progetto
        $sql = "SELECT GIUSTIFICATIVO_FILENAME, LENGTH(GIUSTIFICATIVO) AS LEN, GIUSTIFICATIVO
                FROM commesse
                WHERE cod_commessa = '$idProgetto'";
        $result = select_single($sql);

        header("Content-length: $result[LEN]");
        // header("Content-type: ???");
        header("Content-Disposition: attachment; filename=$result[GIUSTIFICATIVO_FILENAME]");
        ob_clean();
        flush();
        echo $result["GIUSTIFICATIVO"];
    }

    public function upload_giustificativo($idProgetto, $tmpfilename, $origfilename)
    {
        global $con;

        $fileContent = addslashes(file_get_contents($tmpfilename));
        // speriamo non sia enorme

        $origfilename = $con->escape_string($origfilename);
        $sql = "UPDATE commesse
                SET GIUSTIFICATIVO_FILENAME='$origfilename', GIUSTIFICATIVO='$fileContent'
                WHERE cod_commessa = '$idProgetto'";
        execute_update($sql);
    }

    public function elimina_giustificativo($idProgetto)
    {
        $sql = "UPDATE commesse
                SET GIUSTIFICATIVO=NULL,GIUSTIFICATIVO_FILENAME=NULL
                WHERE cod_commessa = '$idProgetto'";
        execute_update($sql);
    }

}
