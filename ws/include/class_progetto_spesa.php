<?php

$progettiSpesaManager = new ProgettiSpesaManager();

class ProgettoSpesa {
    private $_progetto;
    
    function get_progetto() {
        global $progettiSpesaManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
}

class ProgettiSpesaManager {
    
    function get_spese_progetto($id_progetto) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arrProgettiSpesa = array();
        $sql = "SELECT * FROM progetti_spese p WHERE id_progetto = '$id_progetto'";
        $arrProgettiSpesa = select_list($sql);
        return $this->decodeTipologia($arrProgettiSpesa);
    }

    function get_spesa_by_id($id_progetto, $id_spesa) {
        global $con, $STATO_PROGETTO, $BOOLEAN;
        $arrProgettiSpesa = array();
        $sql = "SELECT * FROM progetti_spese p WHERE id_progetto=$id_progetto AND id_spesa = '$id_spesa'";
        $arrProgettiSpesa = select_list($sql); // array con 1 solo record
        if ($arrProgettiSpesa == null || count($arrProgettiSpesa) == 0) {
            return null;
        }
        return $this->decodeTipologia($arrProgettiSpesa)[0];
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
        $sql = "SELECT nvl(max(ID_SPESA)+1,1) FROM progetti_spese WHERE id_progetto=$json_data->ID_PROGETTO";
        $id_spesa = select_single_value($sql);

        $sql = insert("progetti_spese", ["ID_PROGETTO" => $json_data->ID_PROGETTO,
                                        "ID_SPESA" => $id_spesa,
                                        "IMPORTO" => $json_data->IMPORTO,
                                        "ID_TIPOLOGIA" => $json_data->TIPOLOGIA->ID_TIPOLOGIA,
                                        "DESCRIZIONE" => $json_data->DESCRIZIONE
                                  ]);
        execute_update($sql);
        return $this->get_spesa_by_id($json_data->ID_PROGETTO, $id_spesa);
    }
    
    function aggiorna($progetto, $json_data) {
        $sql = update("progetti_spese", [
                                    "IMPORTO" => $json_data->IMPORTO,
                                    "ID_TIPOLOGIA" => $json_data->TIPOLOGIA->ID_TIPOLOGIA,
                                    "DESCRIZIONE" => $json_data->DESCRIZIONE
                                  ], [
                                     "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                     "ID_SPESA" => $json_data->ID_SPESA
                                  ]);
        execute_update($sql);
        return $this->get_spesa_by_id($json_data->ID_PROGETTO, $json_data->ID_SPESA);
    }
    
    function elimina($id_progetto, $id_spesa) {
        $sql = "DELETE FROM progetti_spese WHERE id_progetto=$id_progetto AND id_spesa=$id_spesa";  //on delete cascade! (FIXME funziona anche con i questionari?!?)
        execute_update($sql);
    }

}
?>