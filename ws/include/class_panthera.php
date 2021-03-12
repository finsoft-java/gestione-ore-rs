<?php

$panthera = new PantheraManager();

class PantheraManager {

    function getUtenti() {
        if (MOCK_PANTHERA) {
            $matricole = [ [ 'MATRICOLA' => '1234', 'NOME' => 'Rossi Mario' ],
                      [ 'MATRICOLA' => '4321', 'NOME' => 'Verdi Carlo' ],
                      [ 'MATRICOLA' => '6666', 'NOME' => 'Bianchi Gianni' ]
                     ];
        } else {
            $query = "SELECT DISTINCT ID_UTENTE AS MATRICOLA,DENOMINAZIONE AS NOME FROM THIP.UTENTI_AZIENDE_V01 WHERE ID_AZIENDA='001'";
            $matricole = select_list($query, $connPanthera);
        }
        
        return $matricole;
    }

    function getUtente($matricola) {
        if (MOCK_PANTHERA) {
            $matricola = 'Rossi Mario';
        } else {
            $query = "SELECT DISTINCT DENOMINAZIONE FROM THIP.UTENTI_AZIENDE_V01 WHERE ID_AZIENDA='001' AND ID_UTENTE='$matricola'";
            $matricola = select_single_value($query, $connPanthera);
        }
        return $matricola;
    }

    function getTipiCosto() {
        if (MOCK_PANTHERA) {
            $tipiCosto = [ [ 'ID_TIPO_COSTO' => 'A01', 'DESCRIZIONE' => 'Prova 1' ],
                      [ 'ID_TIPO_COSTO' => 'A02', 'DESCRIZIONE' => 'Prova 2' ],
                      [ 'ID_TIPO_COSTO' => 'A03', 'DESCRIZIONE' => 'Prova 3' ]
                     ];
        } else {
            $query = "SELECT DISTINCT ID_TIPO_COSTO,DESCRIZIONE FROM THIP.TIPI_COSTO WHERE ID_AZIENDA='001' AND STATO='V'";
            $tipiCosto = select_list($query, $connPanthera);
        }
        return $tipiCosto;
    }
    
    /**
    * Costi validi alla data specificata
    */
    function getCosti($data, $tipoCosto) {
        if (MOCK_PANTHERA) {
            $costi = [ [ 'ID_RISORSA' => '1234', 'COSTO' => 8.0 ],
                      [ 'ID_RISORSA' => '4321', 'COSTO' => 9.0 ],
                      [ 'ID_RISORSA' => '6666', 'COSTO' => 10.0 ]
                     ];
        } else {
            $query = "SELECT DISTINCT ID_RISORSA AS MATRICOLA,COSTO " .
                "FROM THIP.TIPI_COSTO " .
                "WHERE ID_AZIENDA='001' AND TIPO_RISORSA='U' AND LIVELLO_RISORSA='4' " .
                "AND R_TIPO_COSTO='$tipoCosto' " .
                "AND (DATA_COSTO IS NULL OR DATA_COSTO<='$data') " .
                "AND (DATA_FINE_COSTO IS NULL OR DATA_FINE_COSTO>='$data') ";
            $costi = select_list($query, $connPanthera);
        }
        return $costi;
    }
    
    /**
    * Restituisce tutti i record di costo nel range specificato
    */
    function getMatriceCosti($data1, $data2, $tipoCosto) {
        if (MOCK_PANTHERA) {
            $costi = [ [ 'ID_RISORSA' => '1234', 'COSTO' => 8.0, 'DATA_COSTO' => '2020-01-01', 'DATA_FINE_COSTO' => null ],
                      [ 'ID_RISORSA' => '4321', 'COSTO' => 9.0, 'DATA_COSTO' => '200-01-01', 'DATA_FINE_COSTO' => '2010-12-31' ],
                      [ 'ID_RISORSA' => '6666', 'COSTO' => 10.0, 'DATA_COSTO' => '2020-01-01', 'DATA_FINE_COSTO' => null ]
                     ];
        } else {
            $query = "SELECT DISTINCT ID_RISORSA,COSTO,DATA_COSTO,DATA_FINE_COSTO " .
                "FROM THIP.TIPI_COSTO " .
                "WHERE ID_AZIENDA='001' AND TIPO_RISORSA='U' AND LIVELLO_RISORSA='4' " .
                "AND R_TIPO_COSTO='$tipoCosto' " .
                "AND (DATA_COSTO IS NULL OR DATA_COSTO<='$data2') " .
                "AND (DATA_FINE_COSTO IS NULL OR DATA_FINE_COSTO>='$data1') ";
            $costi = select_list($query, $connPanthera);
        }
        return $costi;
    }
}
?>