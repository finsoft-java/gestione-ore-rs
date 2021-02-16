<?php

$utenteManager = new UtenteManager();

class Utente {
    
}

class UtenteManager {
    
    function get_utenti($top=null, $skip=null, $orderby=null, $search=null) {
        global $con, $RUOLO, $BOOLEAN;
        $arr = array();

        $sql0 = "SELECT COUNT(*) AS cnt ";
        $sql1 = "SELECT * ";
        $sql = "FROM utenti";

        if ($search){
            $search = strtoupper($search);
            $search = $con->escape_string($search);
            $sql .= " WHERE UPPER(username) LIKE '%$search%' OR UPPER(email) LIKE '%$search%' OR UPPER(CONCAT(IFNULL(nome,''), ' ', IFNULL(cognome,''))) LIKE '%$search%'";
        }
        if ($orderby && preg_match("/^[a-zA-Z0-9,_ ]+$/", $orderby)) {
            // avoid SQL-injection
            $sql .= " ORDER BY $orderby";
        } else {
            $sql .= " ORDER BY username";
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
            while($row = mysqli_fetch_assoc($result))
            {
                $utente = new Utente();
                $utente->username   = $row["username"];
                $utente->nome       = $row['nome'];
                $utente->cognome    = $row['cognome'];
                $utente->email      = $row['email'];
                $utente->ruolo      = $row['ruolo'];
                $utente->ruolo_dec  = ($row['ruolo'] != null) ? $RUOLO[$row['ruolo']] : null;
                $utente->from_ldap      = ($row['from_ldap'] == '1' ? true : false);
                $utente->from_ldap_dec  = ($row['from_ldap'] != null) ? $BOOLEAN[$row['from_ldap']] : null;
                
                $arr[$cr++] = $utente;
            }
        } else {
            print_error(500, $con ->error);
        }
        return [$arr, $count];
    }
    
    function insert_password_utente($psw,$username) {
        global $con;
        $psw_md5 = md5($psw);
        $sql = "Update utenti SET password_enc = '$psw_md5' WHERE username = '$username' ";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

    function get_utente($username) {
        global $con, $RUOLO, $BOOLEAN;
        $utente = new Utente();
        $sql = "SELECT * FROM utenti WHERE username = '$username' ";
        
        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result))
            {
                $utente->username   = $row['username'];
                $utente->nome       = $row['nome'];
                $utente->cognome    = $row['cognome'];
                $utente->email      = $row['email'];
                $utente->ruolo      = $row['ruolo'];
                $utente->ruolo_dec  = ($row['ruolo'] != null) ? $RUOLO[$row['ruolo']] : null;
                $utente->from_ldap      = ($row['from_ldap'] == '1' ? true : false);
                $utente->from_ldap_dec  = ($row['from_ldap'] != null) ? $BOOLEAN[$row['from_ldap']] : null;
            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $utente;
    }
    
    function get_utente_locale($username, $password_cleartext) {
        global $con, $RUOLO,$BOOLEAN;
        $password_enc = md5($password_cleartext);
        $utente = new Utente();
        $sql = "SELECT * FROM utenti WHERE username = '$username' and password_enc='$password_enc' and from_ldap='0' ";
       // echo $sql;

        if($result = mysqli_query($con, $sql)) {
            if($row = mysqli_fetch_assoc($result))
            {
                $utente->username   = $row['username'];
                $utente->nome       = $row['nome'];
                $utente->cognome    = $row['cognome'];
                $utente->email      = $row['email'];
                $utente->ruolo      = $row['ruolo'];
                $utente->ruolo_dec  = ($row['ruolo'] != null) ? $RUOLO[$row['ruolo']] : null;
                $utente->from_ldap      = ($row['from_ldap'] == '1' ? true : false);
                $utente->from_ldap_dec  = ($row['from_ldap'] != null) ? $BOOLEAN[$row['from_ldap']] : null;

            } else {
                return null;
            }
        } else {
            print_error(500, $con ->error);
        }
        return $utente;
    }
    
    function crea($json_data) {
        global $con;
        $sql = insert("utenti", ["username" => $con->escape_string($json_data->username),
                             "nome" => $con->escape_string($json_data->nome),
                             "cognome" => $con->escape_string($json_data->cognome),
                             "email" => $con->escape_string($json_data->email),
                             "ruolo" => $json_data->ruolo,
                             "from_ldap" => $json_data->from_ldap]);
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        return $this->get_utente($json_data->username);
    }
    
    function aggiorna($utente, $json_data) {
        global $con, $RUOLO;
        $sql = update("utenti", ["nome" => $con->escape_string($json_data->nome),
                                 "cognome" => $con->escape_string($json_data->cognome),
                                 "email" => $con->escape_string($json_data->email),
                                 "ruolo" => $json_data->ruolo,
                                 "from_ldap" => $json_data->from_ldap],
                                ["username" => $con->escape_string($utente->username)]);
                                //echo $sql;
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
        
        $utente->nome = $json_data->nome;
        $utente->cognome = $json_data->cognome;
        $utente->email = $json_data->email;
        $utente->ruolo = $json_data->ruolo;
        $utente->ruolo_dec = $RUOLO[$utente->ruolo];
        return $utente;
    }
    
    function elimina($username) {
        global $con;
        $sql = "DELETE FROM utenti WHERE username = '$username'";
        mysqli_query($con, $sql);
        if ($con ->error) {
            print_error(500, $con ->error);
        }
    }

    function get_map_utenti() {
        $utenti = $this->get_utenti()[0];
        $map = [];
        foreach ($utenti as $u) {
            $map[$u->username] = $u;
        }
        return $map;
    }

    /**
     * Aggiorna il database in funzione degli utenti presenti su LDAP.
     * Non elimina utenti preesistenti, nel caso dà warning.
     */
    function sync() {

        $utenti_su_db_map = $this->get_map_utenti();
        
        $utenti_aggiornati = 0;
        $utenti_inseriti = 0;
        $utenti_eliminati_su_LDAP = 0;

        $ldap_connection = ldap_connect(AD_SERVER);
        if (FALSE === $ldap_connection) {
            print_error(500, "Errore interno nella configurazione di Active Directory: " . AD_SERVER);
        }

        // We have to set this option for the version of Active Directory we are using.
        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        ini_set('display_errors', 0);  // Se ci sono errori, ldap_bind() scrive sullo stdout e fa casino....
        $bind = ldap_bind($ldap_connection, AD_USERNAME, AD_PASSWORD);
        if ($bind !== TRUE) {
            print_error(502, "Impossibile connettersi al server Active Directory");
        }
        $ldap_base_dn = AD_BASE_DN;
        $search_filter = AD_FILTER;
        $attributes = array();
        $attributes[] = 'givenname';
        $attributes[] = 'mail';
        $attributes[] = 'samaccountname';
        $attributes[] = 'sn';
        $result = ldap_search($ldap_connection, $ldap_base_dn, $search_filter, $attributes);
        
        if (FALSE !== $result){
            $entries = ldap_get_entries($ldap_connection, $result);
            
            for ($x=0; $x < $entries['count']; $x++){
                $entry = $entries[$x];
                if (array_key_exists ($entry['samaccountname'][0], $utenti_su_db_map)) {
                    // UPDATE
                    $u = $utenti_su_db_map[$entry['samaccountname'][0]];
                    $u->nome = $entry['givenname'][0];
                    $u->cognome = $entry['sn'][0]; 
                    $u->email = $entry['mail'][0];
                    $u->from_ldap = 1;
                    $this->aggiorna($u, $u);
                    ++$utenti_aggiornati;
                } else {
                    // INSERT
                    $u = new Utente();
                    $u->username = $entry['samaccountname'][0];
                    $u->nome = $entry['givenname'][0];
                    $u->cognome = $entry['sn'][0]; 
                    $u->email = $entry['mail'][0];
                    $u->ruolo = '0';
                    $u->from_ldap = 1;
                    $this->crea($u);
                    ++$utenti_inseriti;
                }
            }
        }
        ldap_unbind($ldap_connection); // Clean up after ourselves.

        $utenti_eliminati_su_LDAP = count($utenti_su_db_map) - $utenti_aggiornati;

        return [$utenti_aggiornati, $utenti_inseriti, $utenti_eliminati_su_LDAP];
    }
}



?>