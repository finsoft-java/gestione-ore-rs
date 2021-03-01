<?php

$panthera = new PantheraManager();

class PantheraManager {

    function getUtenti() {
        $query = "SELECT DISTINCT MATRICOLA,COGNOME||' '||NOME AS NOME FROM THIP.DIPENDENTI_V01 WHERE ID_AZIENDA='001'";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $matricole = select_list($query)
        
        $matricole = [ [ 'MATRICOLA' => '1234', 'NOME' => 'Rossi Mario' ],
                      [ 'MATRICOLA' => '4321', 'NOME' => 'Verdi Carlo' ],
                      [ 'MATRICOLA' => '6666', 'NOME' => 'Bianchi Gianni' ]
                     ];
        return $matricole;
    }

    function getUtente($matricola) {
        $query = "SELECT DISTINCT COGNOME||' '||NOME AS NOME FROM THIP.DIPENDENTI_V01 WHERE ID_AZIENDA='001' AND MATRICOLA='$matricola'";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $matricola = select_single($query)
        
        $matricola = 'Rossi Mario';
        return $matricola;
    }

    function getTipiCosto() {
        $query = "SELECT DISTINCT ID_TIPO_COSTO,DESCRIZIONE FROM THIP.TIPI_COSTO WHERE ID_AZIENDA='001' AND STATO='V'";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $tipiCosto = select_list($query)
        
        $tipiCosto = [ [ 'ID_TIPO_COSTO' => 'A01', 'DESCRIZIONE' => 'Prova 1' ],
                      [ 'ID_TIPO_COSTO' => 'A02', 'DESCRIZIONE' => 'Prova 2' ],
                      [ 'ID_TIPO_COSTO' => 'A03', 'DESCRIZIONE' => 'Prova 3' ]
                     ];
        return $tipiCosto;
    }
    
    /**
    * Costi validi alla data specificata
    */
    function getCosti($data) {
        $query = "SELECT DISTINCT ID_RISORSA,COSTO " .
            "FROM THIP.TIPI_COSTO " .
            "WHERE ID_AZIENDA='001' AND TIPO_RISORSA='U' AND LIVELLO_RISORSA='4' " .
            "AND (DATA_COSTO IS NULL OR DATA_COSTO<='$data') " .
            "AND (DATA_FINE_COSTO IS NULL OR DATA_FINE_COSTO>='$data') ";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $costi = select_list($query)
        
        $costi = [ [ 'ID_RISORSA' => '1234', 'COSTO' => 8.0 ],
                      [ 'ID_RISORSA' => '4321', 'COSTO' => 9.0 ],
                      [ 'ID_RISORSA' => '6666', 'COSTO' => 10.0 ]
                     ];
        return $costi;
    }
    
    /**
    * Restituisce tutti i record di costo nel range specificato
    */
    function getMatriceCosti($data1, $data2) {
        $query = "SELECT DISTINCT ID_RISORSA,COSTO,DATA_COSTO,DATA_FINE_COSTO " .
            "FROM THIP.TIPI_COSTO " .
            "WHERE ID_AZIENDA='001' AND TIPO_RISORSA='U' AND LIVELLO_RISORSA='4' " .
            "AND (DATA_COSTO IS NULL OR DATA_COSTO<='$data2') " .
            "AND (DATA_FINE_COSTO IS NULL OR DATA_FINE_COSTO>='$data1') ";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $costi = select_list($query)
        
        $costi = [ [ 'ID_RISORSA' => '1234', 'COSTO' => 8.0, 'DATA_COSTO' => '2020-01-01', 'DATA_FINE_COSTO' => null ],
                      [ 'ID_RISORSA' => '4321', 'COSTO' => 9.0, 'DATA_COSTO' => '200-01-01', 'DATA_FINE_COSTO' => '2010-12-31' ],
                      [ 'ID_RISORSA' => '6666', 'COSTO' => 10.0, 'DATA_COSTO' => '2020-01-01', 'DATA_FINE_COSTO' => null ]
                     ];
        return $costi;
    }

}
?>