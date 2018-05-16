<?php
/**
 * Created by PhpStorm.
 * User: Shrik
 * Date: 5/15/2018
 * Time: 2:22 PM
 */

$servername = "localhost";
$username = "root";
$password = "";
$db = "connxus";

// Create connection
$conn = new mysqli($servername, $username, $password, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>