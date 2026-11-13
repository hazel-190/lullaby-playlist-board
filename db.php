<?php
$host = 'db.zrojgycplhjscyrpglmr.supabase.co';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$pass = 'Hazelann25100190'; 

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}