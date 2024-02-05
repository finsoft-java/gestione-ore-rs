<?php 


/* sviluppo */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ore-rd-prod');


/* produzione 
define('DB_HOST', 'localhost');
define('DB_USER', 'rd');
define('DB_PASS', '***********');
define('DB_NAME', 'rd');
*/
define('JWT_SECRET_KEY', 'OSAISECRET2021');

define('AD_SERVER', 'ldap://osai.loc');
define('AD_DOMAIN', 'OSAI.LOC');
define('AD_BASE_DN', "dc=OSAI,dc=LOC");
define('AD_FILTER', '(objectclass=person)');
// define('AD_FILTER', '(&(|(objectclass=person))(|(|(memberof=CN=OSAI-IT Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=1202))(|(memberof=CN=OSAI-DE Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=2625))(|(memberof=CN=OSAI-CN Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=3233))(|(memberof=CN=OSAI-US Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=4426))))');
// define('AD_USERNAME', 'surveyosai@OSAI.LOC');
// define('AD_PASSWORD', '************');


define('MOCK_PANTHERA', 'true');
define('DB_PTH_HOST', 'tcp:myserver.database.windows.net,1433');
define('DB_PTH_USER', 'my_user');
define('DB_PTH_PASS', 'my_pwd');
define('DB_PTH_NAME', 'PANTH01');

/* produzione:
define('MOCK_PANTHERA', 'false');
define('DB_PTH_HOST', 'tcp:svr-pth,1433');
define('DB_PTH_USER', 'finsoft');
define('DB_PTH_PASS', '*************');
define('DB_PTH_NAME', 'PANTH01');
*/

// configurazione colonne del file excel dei caricamenti
define('COL_SERIE_DOC',     0);
define('COL_NUMERO_DOC',    1);
define('COL_DATA_DOC',      2);
define('COL_MATRICOLA',     7);
define('COL_ATV',           9);
define('COL_SOTTO_COMM',    12);
define('COL_COMMESSA',      14);
define('COL_NUM_ORE',       16);
define('DATE_FORMAT', 'n/j/Y H:i'); // Anche se a video vedo gg/mm/aaaa hh:mm:ss, PHP lo vede in formato m/g/aaaa hh:mm

define('MAX_EXECUTION_TIME_ASSEGNAZIONE', 15*60); // timeout in secondi

?>