<?php
// Database connection settings
$host     = "db";
$user     = "root";
$password = "root";
$database = "edulift_fdb";
 
// Connect to the database
$conn = mysqli_connect($host, $user, $password, $database);
 
// Stop the app if connection fails
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>