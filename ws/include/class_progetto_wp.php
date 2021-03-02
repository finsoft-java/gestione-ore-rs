<?php

$progettiWpManager = new ProgettiWpManager();

class ProgettoWp {
    private $_progetto;
    
    function get_progetto() {
        global $progettiWpManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiWpManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
}

class ProgettiWpManager {
    
    function get_progetto($id_progetto) {
        $arrProgettiWp = array();
        $sql = "SELECT * FROM progetti_wp WHERE id_progetto = '$id_progetto'";
        $arrProgettiWp = select_list($sql);
        return $this->addRisorse($arrProgettiWp);
    }   

    function addRisorse($arrProgettiWp){
        $risorse = '';
        for($i = 0; $i < count($arrProgettiWp); $i++){
            $id_progetto = $arrProgettiWp[$i]["ID_PROGETTO"];
            $id_wp = $arrProgettiWp[$i]["ID_WP"];
            $sql = "SELECT MATRICOLA_DIPENDENTE FROM progetti_wp_risorse WHERE ID_PROGETTO = '$id_progetto' and ID_WP = '$id_wp'";
            $risorse = select_column($sql);
            $arrProgettiWp[$i]["RISORSE"] = $risorse;
        }
        return $arrProgettiWp;
    }
    function crea($json_data) {
        global $con;
        $sql = insert("progetti_wp", ["TITOLO" => $json_data->TITOLO,
                                   "DESCRIZIONE" => $json_data->DESCRIZIONE,
                                   "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                   "DATA_FINE" => $json_data->DATA_FINE,
                                   "MONTE_ORE" => $json_data->MONTE_ORE
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
        $sql = update("progetti_wp", ["TITOLO" => $json_data->TITOLO,
                                        "DESCRIZIONE" => $json_data->DESCRIZIONE,
                                        "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                        "DATA_FINE" => $json_data->DATA_FINE,
                                        "MONTE_ORE" => $json_data->MONTE_ORE
                                  ], ["ID_WP" => $json_data->ID_WP,
                                  "ID_PROGETTO" => $json_data->ID_PROGETTO]);
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