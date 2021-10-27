<?php 
use Firebase\JWT\JWT;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,PUT,POST,DELETE");
header("Access-Control-Allow-Headers: Accept,Content-Type,Authorization");
# Chrome funziona anche con Access-Control-Allow-Headers: *  invece Firefox no

require 'vendor/autoload.php';

include("config.php");
include("costanti.php");
include("functions.php");
include("class_progetto.php");
include("class_progetto_spesa.php");
include("class_progetto_persone.php");
include("class_tipologia.php");
include("class_panthera.php");
include("class_rapportini.php");
include("class_lul.php");
include("class_budget.php");