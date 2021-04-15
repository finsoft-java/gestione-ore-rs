<?php

$progettiWpManager = new ProgettiWpManager();

class ProgettoWp {
    private $_progetto;
    
    function get_progetto() {
        global $progettiWpManager;
        if (!$this->_progetto) {
            $this->_progetto = $progettiManager->get_progetto($this->id_progetto);
        }
        return $this->_progetto;
    }
}

class ProgettiWpManager {
    
    function get_wp_progetto($id_progetto) {
        $arrProgettiWp = array();
        $sql = "SELECT * FROM progetti_wp WHERE id_progetto = '$id_progetto'";
        $arrProgettiWp = select_list($sql);
        return $this->addRisorse($arrProgettiWp);
    }
    
    function get_wp($id_progetto, $id_wp) {
        $arrProgettiWp = array();
        $sql = "SELECT * FROM progetti_wp WHERE id_progetto = '$id_progetto' and id_wp=$id_wp";
        $arrProgettiWp = select_list($sql); // lista con max 1 elemento
        if ($arrProgettiWp == null || count($arrProgettiWp) == 0) {
            return null;
        }
        return $this->addRisorse($arrProgettiWp)[0];
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
    
    function aggiornaRisorse($id_wp, $id_progetto, $json_data_wp) {
        $sql = "DELETE FROM progetti_wp_risorse WHERE id_wp = '$id_wp' AND id_progetto = '$id_progetto'";
        execute_update($sql);

        $risorse = $json_data_wp->RISORSE;

        if ($risorse) {
            for($i = 0; $i < count($risorse); $i++) {
                $sql = insert("progetti_wp_risorse", 
                                        [
                                            "MATRICOLA_DIPENDENTE" => $risorse[$i],
                                            "ID_PROGETTO" => $id_progetto,
                                            "ID_WP" => $id_wp
                                        ]);
                execute_update($sql);
            }
        }
    }
    
    function crea($json_data) {
        global $con;
        $this->controllo_date($json_data->DATA_INIZIO, $json_data->DATA_FINE);
        $this->controllo_ore($json_data->MONTE_ORE);

        //select max per id_wp
        $id_wp = select_single_value("Select nvl(max(ID_WP)+1,1) FROM progetti_wp WHERE id_progetto = '$json_data->ID_PROGETTO'");
        $sql = insert("progetti_wp", [
                                    "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                    "ID_WP" => $id_wp,
                                    "TITOLO" => $con->escape_string($json_data->TITOLO),
                                    "DESCRIZIONE" => $con->escape_string($json_data->DESCRIZIONE),
                                    "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                    "DATA_FINE" => $json_data->DATA_FINE,
                                    "MONTE_ORE" => $json_data->MONTE_ORE
                                  ]);
        execute_update($sql);

        $this->aggiornaRisorse($id_wp, $json_data->ID_PROGETTO, $json_data);

        return $this->get_wp($json_data->ID_PROGETTO, $id_wp);
    }
    
    function aggiorna($progetto, $json_data) {
        global $con;
        $this->controllo_date($json_data->DATA_INIZIO, $json_data->DATA_FINE);
        $this->controllo_ore($json_data->MONTE_ORE);

        $sql = update("progetti_wp", ["TITOLO" => $con->escape_string($json_data->TITOLO),
                                        "DESCRIZIONE" => $con->escape_string($json_data->DESCRIZIONE),
                                        "DATA_INIZIO" => $json_data->DATA_INIZIO,
                                        "DATA_FINE" => $json_data->DATA_FINE,
                                        "MONTE_ORE" => $json_data->MONTE_ORE
                                  ], [
                                        "ID_PROGETTO" => $json_data->ID_PROGETTO,
                                        "ID_WP" => $json_data->ID_WP
                                  ]);
        execute_update($sql);        

        $this->aggiornaRisorse($json_data->ID_WP, $json_data->ID_PROGETTO, $json_data);

        return $this->get_wp($json_data->ID_PROGETTO, $json_data->ID_WP);
    }
    
    function elimina($id_wp, $id_progetto) {
        $sql = "DELETE FROM progetti_wp_risorse WHERE id_wp = '$id_wp' AND id_progetto = '$id_progetto'";
        execute_update($sql);
        $sql = "DELETE FROM progetti_wp WHERE id_wp = '$id_wp' AND id_progetto = '$id_progetto'";
        execute_update($sql);
    }

    function controllo_date($data_inizio, $data_fine) {
        if ($data_inizio == null) {
            print_error(400, 'DATA_INIZIO non valorizzata');
        }
        if ($data_fine == null) {
            print_error(400, 'DATA_FINE non valorizzata');
        }
        if ($data_inizio > $data_fine) {
            print_error(400, 'La DATA_INIZIO non puo\' essere successiva alla DATA_FINE');
        }
    }

    function controllo_ore($monte_ore) {
        if ($monte_ore < 0) {
            print_error(400, 'Il monte ore non puo\' essere negativo');
        }
    }

}
?>