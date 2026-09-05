<?php
// db.php
$host = "localhost";
$dbname = "sdg4_education"; // Change to your database name if different
$user = "root";
$pass = "";

/* Uncomment below if using your team's shared database IP
$host = "172.20.10.3";
$dbname = "cyber_fitness_gym";
$user = "teamuser";
$pass = "team123";
*/

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>