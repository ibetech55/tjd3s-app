<?php

$servername = "db";
$username = "tjd3s";
$password = "admin";
$dbname = "tjd3s_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Close connection (optional)
// $conn->close();
