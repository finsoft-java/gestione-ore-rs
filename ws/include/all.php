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
