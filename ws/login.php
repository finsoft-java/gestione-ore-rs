<?php

// Per i test: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJub21lX3V0ZW50ZSI6ImZpbnNvZnQiLCJub21lIjoiTWFyaW8iLCJjb2dub21lIjoiUm9zc2kiLCJlbWFpbCI6ImZpbnNvZnRAZXhhbXBsZS5jb20iLCJydW9sbyI6IjIiLCJydW9sb19kZWMiOiJBbW1pbmlzdHJhdG9yZSJ9.uGM9xHtv8dYMrjPL5suIh8I2gY1lOPaZ7QNtXsBJ45A

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
include("./include/all.php");
$con = connect();

$user = '';
$postdata = file_get_contents("php://input");

$request = json_decode($postdata);

if($request != ''){
    $username = $request->username;
    $password = $request->password;
    $user = check_and_load_user($username, $password);
}

if ($user) {
    load_user_role($user);
    try {
        $user->username = JWT::encode($user, JWT_SECRET_KEY);
        $user->login = date("Y-m-d H:i:s");
        echo json_encode(['value' => $user]);
    } catch(Exception $e) {
        print_error(403, $e->getMessage());
    } catch (Error $e) {
        print_error(403, $e->getMessage());
     }
   
} else {
    session_unset();
    print_error(403, "Invalid credentials");
}




/*
    $ldap = ldap_connect(AD_SERVER);
    if (FALSE === $ldap) {
        print_error(500, "Errore interno nella configurazione di Active Directory: " . AD_SERVER);
    }

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

    $ldaprdn = $username . "@" . AD_DOMAIN;
    $bind = @ldap_bind($ldap, $ldaprdn, $pwd);
    if ($bind) {
        $filter="(SamAccountName=$username)";
        $result = ldap_search($ldap, AD_BASE_DN, $filter);
        ldap_sort($ldap,$result,"sn");
        $info = ldap_get_entries($ldap, $result);

        $user = new Utente();
        $user->nome_utente = $info[0]["samaccountname"][0];
        $user->nome = $info[0]["sn"][0];
        $user->cognome = $info[0]["givenname"][0];
        $user->email = $info[0]["mail"][0];
        
        @ldap_close($ldap);
        return $user;
    }
    */

function check_and_load_user($username, $pwd) {
    // PRIMA, vediamo se l'utente è un utente locale
    global $utenteManager;
    $utente_locale = $utenteManager->get_utente_locale($username, $pwd);
    
    if ($utente_locale) {
        $utente_locale->nome_utente = $utente_locale->username; // TODO questo prima o poi e' da risolvere
        return $utente_locale;
    }

    // POI, proviamo su LDAP

    $ldap = ldap_connect(AD_SERVER);
    if (FALSE === $ldap) {
        print_error(500, "Errore interno nella configurazione di Active Directory: " . AD_SERVER);
    }

    // We have to set this option for the version of Active Directory we are using.
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

    $ldaprdn = $username . "@" . AD_DOMAIN;
    $bind = @ldap_bind($ldap, $ldaprdn, $pwd);
    if ($bind) {
        $filter="(SamAccountName=$username)";
        $result = ldap_search($ldap, AD_BASE_DN, $filter);
        ldap_sort($ldap,$result,"sn");
        $info = ldap_get_entries($ldap, $result);

        $user = new Utente();
        $user->nome_utente = $info[0]["samaccountname"][0];
        $user->nome = $info[0]["sn"][0];
        $user->cognome = $info[0]["givenname"][0];
        $user->email = $info[0]["mail"][0];
        
        @ldap_close($ldap);
        return $user;
    }
}

/**
* Se l'utente è già censito su DB, ne carica il ruolo
* Altrimenti lo salva su DB con ruolo '0' (utente normale)
*/
function load_user_role(&$user) {
    global $RUOLO, $utenteManager;
    
    $user_su_db = $utenteManager->get_utente($user->nome_utente);
    if ($user_su_db) {
        $user->ruolo = $user_su_db->ruolo;
        $user->ruolo_dec = $user_su_db->ruolo_dec;
    } else {
        $user->ruolo = '0';
        $user->ruolo_dec = $RUOLO[$user_su_db->ruolo_dec];
        
        $user_su_db = new Utente();
        $user_su_db->username = $user->nome_utente;
        $user_su_db->nome = $user->nome;
        $user_su_db->cognome = $user->cognome;
        $user_su_db->email = $user->email;
        $user_su_db->ruolo = $user->ruolo;
        $utenteManager->crea($user_su_db);
    }
}


?>