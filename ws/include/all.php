<?php 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,PUT,POST,DELETE");
header("Access-Control-Allow-Headers: Accept,Content-Type,Authorization");
# Chrome funziona anche con Access-Control-Allow-Headers: *  invece Firefox no

#costants
include("config.php");
include("costanti.php");
#third-party libraries
include("JWT.php");
include("xlsxwriter.class.php");
#functions and classes
include("functions.php");
