<?php
$db_host     = getenv('DB_HOST');
$db_port     = getenv('DB_PORT') ?: '5432';
$db_name     = getenv('DB_NAME') ?: 'postgres';
$db_user     = getenv('DB_USER') ?: 'postgres';
$db_password = getenv('DB_PASSWORD');

try {
    $pdo = new PDO(
        "pgsql:host=$db_host;port=$db_port;dbname=$db_name",
        $db_user,
        $db_password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}
?>