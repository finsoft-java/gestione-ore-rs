<?php

$tipologiaManager = new TipologiaManager();

class Tipologia {
    private $_tipologia;
    
    function get_tipologia() {
        global $tipologiaManager;
        if (!$this->_tipologia) {
            $this->_tipologia = $tipologiaManager->get_tipologia($this->id_tipologia);
        }
        return $this->_tipologia;
    }
}

class TipologiaManager {
    
    function get_progetti($top=null, $skip=null, $orderby=null, $search=null, $mostra_solo_validi=false) {
        global $con, $BOOLEAN;
        $arr = array();
        $sql1 = "SELECT * ";
        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql = "FROM tipologie_spesa p ";
        
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY p.ID_TIPOLOGIA DESC";
        }

        if($result = mysqli_query($con, $sql0 . $sql)) {
            $count = mysqli_fetch_assoc($result)["cnt"];
        } else {
            print_error(500, $con ->error);
        }

        if ($top){
            if ($skip) {
                $sql .= " LIMIT $skip,$top";
            } else {
                $sql .= " LIMIT $top";
            }
        }
        if($result = mysqli_query($con, $sql1 . $sql)) {
            $cr = 0;
            while($row = mysqli_fetch_assoc($result)) {
                $tipologia = (object)[];
                $tipologia->ID_TIPOLOGIA = $row['ID_TIPOLOGIA'];
                $tipologia->DESCRIZIONE  = $row['DESCRIZIONE'];
                $arr[$cr++] = $tipologia;
            }
        } else {
            print_error(500, $con ->error);
        }
        return [$arr, $count];
    }
    
    function get_tipologia($id_tipologia) {
        global $con, $BOOLEAN;
        $sql = "SELECT * FROM tipologie_spesa p WHERE ID_TIPOLOGIA = '$id_tipologia'";
        return select_list($sql);
    }
    

    function crea($json_data) {
        global $con, $logged_user;
        $sql = insert("tipologie_spesa", ["ID_TIPOLOGIA" => null, "DESCRIZIONE" => $json_data->DESCRIZIONE ]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $id_tipologia = mysqli_insert_id($con);
        return $this->get_tipologia($id_tipologia);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con;
        $sql = update("tipologie_spesa", [ "DESCRIZIONE" => $json_data->DESCRIZIONE ], ["ID_TIPOLOGIA" => $json_data->ID_TIPOLOGIA]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
    
    function elimina($id_tipologia) {
        global $con;
        $sql = "DELETE FROM tipologie_spesa WHERE id_tipologia = '$id_tipologia'";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
}
?>