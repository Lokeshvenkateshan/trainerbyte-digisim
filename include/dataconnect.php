<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


//for development
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ms_digisim";
// $port = 3306;  

//for production
/* $host = "localhost";
$user = "sarascon_DbC0NT9";
$pass = "SaRC0nT90L!@#";
$db   = "sarascon_gce_mic"; */


$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}