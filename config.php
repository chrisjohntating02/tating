<?php
$host = 'localhost';
$dbname = 'botika_generics_pos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create admin user if not exists (for initial setup)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
$adminCount = $stmt->fetch()['count'];

if ($adminCount == 0) {
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $fullName = 'Administrator';
    
    $pdo->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)")
        ->execute([$username, $password, $fullName]);
}
?>