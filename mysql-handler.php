<?php
/*Production Server*/
$hostname_gemmysql = "localhost";
$database_gemmysql = "greenegy_gmoney_reports";
$username_gemmysql = "greenegy_wrdp1";
$password_gemmysql = "Xi1nnJoAaOOd1";

$DBH = new PDO("mysql:host=$hostname_gemmysql;dbname=$database_gemmysql", $username_gemmysql, $password_gemmysql);
