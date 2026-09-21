<?php

$conn = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "home service provider",
    3306
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
