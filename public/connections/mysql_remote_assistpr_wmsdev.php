<?php
function db_assistpr_wmsdev() {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=assistpr_wmsdev;charset=utf8mb4',
        'advlsystem','AdvLogMysql21#',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    return $pdo;
}
?>