<?php
$host = "******";
$port = "****";
$dbname = "*****";
$user = "******";
$password = "*****";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Error: Unable to connect to database.");
}
?>

