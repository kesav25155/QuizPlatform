<?php
$host = "aws-0-ap-southeast-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.yafbexaaxzpxrrrdnzen";
$password = "Karadi@2025";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Error: Unable to connect to database.");
}
?>
