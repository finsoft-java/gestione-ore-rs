<?php

$panthera = new PantheraManager();

class PantheraManager {

    function getUtenti() {
        $query = "SELECT DISTINCT MATRICOLA,NOME,COGNOME FROM THIP.DIPENDENTI_V01 WHERE ID_AZIENDA='001'";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $matricole = select_list($query)
        
        $matricole = [ [ 'MATRICOLA' => '1234', 'NOME' => 'Mario', 'COGNOME' => 'Rossi' ],
                      [ 'MATRICOLA' => '4321', 'NOME' => 'Carlo', 'COGNOME' => 'Verdi' ],
                      [ 'MATRICOLA' => '6666', 'NOME' => 'Gianni', 'COGNOME' => 'Bianchi' ]
                     ];
        $result = [];
        foreach ($matricole as $m) {
            $result[$m['MATRICOLA']] = $m['COGNOME'] . ' ' . $m['NOME'];
        }
        return $result;
    }

    function getUtente($matricola) {
        $query = "SELECT DISTINCT NOME,COGNOME FROM THIP.DIPENDENTI_V01 WHERE ID_AZIENDA='001' AND MATRICOLA='$matricola'";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $matricola = select_single($query)
        
        $matricola = [ 'NOME' => 'Mario', 'COGNOME' => 'Rossi' ];
        return "$nome $cognome";
    }

    function getTipiCosto() {
        $query = "SELECT DISTINCT ID_TIPO_COSTO,DESCRIZIONE FROM THIP.TIPI_COSTO WHERE ID_AZIENDA='001' AND STATO='V'";
        // TODO BISOGNA RICHIAMARE SQL SERVER, $tipiCosto = select_list($query)
        
        $tipiCosto = [ [ 'ID_TIPO_COSTO' => 'A01', 'DESCRIZIONE' => 'Prova 1' ],
                      [ 'ID_TIPO_COSTO' => 'A02', 'DESCRIZIONE' => 'Prova 2' ],
                      [ 'ID_TIPO_COSTO' => 'A03', 'DESCRIZIONE' => 'Prova 3' ]
                     ];
        $result = [];
        foreach ($tipiCosto as $t) {
            $result[$t['ID_TIPO_COSTO']] = $t['DESCRIZIONE'];
        }
        return $result;
    }
    
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
        $result = [];
        foreach ($tipiCosto as $t) {
            $result[$t['ID_RISORSA']] = $t['COSTO'];
        }
        return $result;
    }

}
?>