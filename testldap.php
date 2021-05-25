<?php 
$ldap = ldap_connect("ldap://osai.loc");
    if (FALSE === $ldap) {
        print_error(500, "Errore interno nella configurazione di Active Directory: " . AD_SERVER);
    }
    // We have to set this option for the version of Active Directory we are using.
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
    $ldaprdn = "lmarosaitest@osai.loc";
    
    $bind = ldap_bind($ldap, $ldaprdn, 'Test2019!');
var_dump($bind);

if ($bind) {
        $filter="(SamAccountName=lmarosaitest)";
        $result = ldap_search($ldap, 'DC=osai,DC=loc', '(memberof=CN=OSAI-IT Users,OU=OU Osai Groups,DC=osai,DC=loc)');
        ldap_sort($ldap,$result,"sn");
        $info = ldap_get_entries($ldap, $result);
        var_dump($info[0]);
	}
