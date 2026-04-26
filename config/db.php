<?php
require_once "env.php";

// load .env file
loadEnv(__DIR__ . '/../.env');

function getDB() {
    $server = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];
    $database = $_ENV['DB_NAME'];

    $dbc = mysqli_connect($server, $username, $password, $database);

    if (!$dbc) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($dbc, "utf8mb4");

    return $dbc;
}
?>