<?php

$progettiSpesaManager = new ProgettiSpesaManager();

class ProgettoSpesa {
    private $_progetto;
    
    function get_progetto() {
        global $progettiSpesaManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiSpesaManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
}

class ProgettiSpesaManager {
    
    function get_progetto($id_progetto) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arrProgettiSpesa = array();
        $sql = "SELECT * FROM progetti_spese p WHERE id_progetto = '$id_progetto'";
        $arrProgettiSpesa = select_list($sql);
        return $this->decodeTipologia($arrProgettiSpesa);
    }   
    function get_progetto_byspesa($id_spesa) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arrProgettiSpesa = array();
        $sql = "SELECT * FROM progetti_spese p WHERE id_spesa = '$id_spesa'";
        $arrProgettiSpesa = select_list($sql);
        return $this->decodeTipologia($arrProgettiSpesa);
    }  
    function decodeTipologia($arrProgettiSpesa){
        $tipologia = '';
        for($i = 0; $i < count($arrProgettiSpesa); $i++){
            $id_tipologia = $arrProgettiSpesa[$i]["ID_TIPOLOGIA"];
            $sql = "SELECT DESCRIZIONE FROM tipologie_spesa WHERE ID_TIPOLOGIA = '$id_tipologia'";
            $tipologia = select_list($sql);
            unset($arrProgettiSpesa[$i]["ID_TIPOLOGIA"]);
            $arrProgettiSpesa[$i]["TIPOLOGIA"]["ID_TIPOLOGIA"] = $id_tipologia;
            $arrProgettiSpesa[$i]["TIPOLOGIA"]["DESCRIZIONE"] = $tipologia[0]["DESCRIZIONE"];
        }
        return $arrProgettiSpesa;
    }
    function crea($json_data) {
        global $con;
        $sql = insert("progetti_spese", ["ID_SPESA" => null,
                                   "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                   "IMPORTO" => $json_data->IMPORTO,
                                   "ID_TIPOLOGIA" => $json_data->TIPOLOGIA->ID_TIPOLOGIA,
                                   "DESCRIZIONE" => $json_data->DESCRIZIONE
                                  ]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        $id_progetto = mysqli_insert_id($con);
        return $this->get_progetto_byspesa($id_progetto);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con;        
        $sql = update("progetti_spese", [
                                    "IMPORTO" => $json_data->IMPORTO,
                                    "ID_TIPOLOGIA" => $json_data->TIPOLOGIA->ID_TIPOLOGIA,
                                    "DESCRIZIONE" => $json_data->DESCRIZIONE
                                  ], ["ID_SPESA" => $json_data->ID_SPESA]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }
    
    function elimina($id_spesa) {
        global $con;
        $sql = "DELETE FROM progetti_spese WHERE id_spesa = '$id_spesa'";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

}
?>