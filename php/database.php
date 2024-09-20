<?php

$servername = "papedins_db";
$username = "admin";
$password = "admin";
$dbname = "papedins_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Close connection (optional)
// $conn->close();
