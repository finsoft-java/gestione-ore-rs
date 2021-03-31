<?php

$panthera = new PantheraManager();

class PantheraManager {

    function __construct() {
        $this->mock = (MOCK_PANTHERA == 'true');
        $this->conn = null;
    }
    
    function fmt_errors() {
        $errors = sqlsrv_errors();
        if (count($errors) >= 1) {
            $error = $errors[0]; // ne prendo uno a caso
            return "[SQLSTATE $error[SQLSTATE]] [SQLCODE $error[code]] $error[message]"; 
        } else {
            return "No error";
        }
    }

    function connect() {
        if (!$this->mock) {
            // echo "Connecting..." . DB_PTH_HOST;
            $this->conn = sqlsrv_connect(DB_PTH_HOST, array(
                                    "Database" => DB_PTH_NAME,  
                                    "UID" => DB_PTH_USER,
                                    "PWD" => DB_PTH_PASS));
            // echo "Done.";
            // var_dump($this->conn);
            if ($this->conn == false) {
                print_error(500, "Failed to connect: " . $this->fmt_errors());
            }
        }
    }

    /*
    Esegue un comado SQL SELECT e lo ritorna come array di oggetti, oppure lancia un print_error
    */
    function select_list($sql) {
        
        // SE TI SERVE FARE DEBUG: print_r($sql); print("\n");
        
        if ($result = sqlsrv_query($this->conn, $sql)) {
            $arr = array();
            while ($row = sqlsrv_fetch_array($result))
            {
                $arr[] = $row;
            }
            return $arr;
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL SELECT ritorna solo la prima colonna come array, oppure lancia un print_error
    */
    function select_column($sql) {
        if ($result = sqlsrv_query($this->conn, $sql)) {
            $arr = array();
            while ($row = sqlsrv_fetch_array($result))
            {
                $arr[] = $row[0];
            }
            return $arr;
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL SELECT e lo ritorna come singolo oggetto, oppure lancia un print_error
    */
    function select_single($sql) {
        if ($result = sqlsrv_query($this->conn, $sql)) {
            if ($row = sqlsrv_fetch_array($result))
            {
                return $row;
            } else {
                return null;
            }
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL SELECT e si aspetta una singola cella come risultato, oppure lancia un print_error
    */
    function select_single_value($sql) {
        if ($result = sqlsrv_query($connessione, $sql)) {
            if ($row = sqlsrv_fetch_array($result))
            {
                return $row[0];
            } else {
                return null;
            }
        } else {
            print_error(500, $this->fmt_errors());
        }
    }


    /*
    Esegue un comado SQL UPDATE/INSERT/DELETE e se serve lancia un print_error
    */
    function execute_update($sql) {
        $result = sqlsrv_query($this->conn, $sql);
        if ($result === false) {
            print_error(500, $this->fmt_errors());
        }
    }

    function getUtenti() {
        if ($this->mock) {
            $matricole = [ [ 'MATRICOLA' => '1234', 'NOME' => 'Rossi Mario' ],
                      [ 'MATRICOLA' => '4321', 'NOME' => 'Verdi Carlo' ],
                      [ 'MATRICOLA' => '6666', 'NOME' => 'Bianchi Gianni' ]
                     ];
        } else {
            $query = "SELECT DISTINCT ID_UTENTE AS MATRICOLA,DENOMINAZIONE AS NOME FROM THIP.UTENTI_AZIENDE_V01 WHERE ID_AZIENDA='001'";
            $matricole = $this->select_list($query);
        }
        
        return $matricole;
    }

    function getUtente($matricola) {
        if ($this->mock) {
            $matricola = 'Rossi Mario';
        } else {
            $query = "SELECT DISTINCT DENOMINAZIONE FROM THIP.UTENTI_AZIENDE_V01 WHERE ID_AZIENDA='001' AND ID_UTENTE='$matricola'";
            $matricola = $this->select_single_value($query);
        }
        return $matricola;
    }

    function getTipiCosto() {
        if ($this->mock) {
            $tipiCosto = [ [ 'ID_TIPO_COSTO' => 'A01', 'DESCRIZIONE' => 'Prova 1' ],
                      [ 'ID_TIPO_COSTO' => 'A02', 'DESCRIZIONE' => 'Prova 2' ],
                      [ 'ID_TIPO_COSTO' => 'A03', 'DESCRIZIONE' => 'Prova 3' ]
                     ];
        } else {
            $query = "SELECT DISTINCT ID_TIPO_COSTO,DESCRIZIONE FROM THIP.TIPI_COSTO WHERE ID_AZIENDA='001' AND STATO='V'";
            $tipiCosto = $this->select_list($query);
        }
        return $tipiCosto;
    }
    
    /**
    * Costi validi alla data specificata
    */
    function getCosti($data, $tipoCosto) {
        if ($this->mock) {
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
            $costi = $this->select_list($query);
        }
        return $costi;
    }
    
    /**
    * Restituisce tutti i record di costo nel range specificato
    */
    function getMatriceCosti($data1, $data2, $tipoCosto) {
        if ($this->mock) {
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
            $costi = $this->select_list($query);
        }
        return $costi;
    }
}
?>