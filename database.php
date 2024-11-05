<?php
$host = 'localhost';
$dbname = 'ECOM';
$username = 'root';
$password = 'Varsha@SQL123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional: Connection successful message
    echo "<script>alert('Database connection successful!');</script>";
} catch (PDOException $e) {
    // Log error and show an alert for connection failure
    error_log("Connection failed: " . $e->getMessage());
    echo "<script>alert('Connection failed. Please try again later.');</script>"; // Alert for user
}
?>
