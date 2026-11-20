<?php
function connectDatabase(): PDO {
    $driver = getenv('DB_DRIVER') ?: 'sqlite';
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if ($driver === 'sqlite') {
        $path = getenv('DB_SQLITE_PATH') ?: dirname(__DIR__, 2) . '/sql/allergy_map.sqlite';
        if (!is_file($path)) throw new RuntimeException('DBがありません。既存DBを配置して移行コマンドを実行してください。');
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }
    if ($driver !== 'mysql') throw new RuntimeException('DB_DRIVER must be sqlite or mysql');
    return new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'allergy_map') . ';charset=utf8mb4', getenv('DB_USER') ?: '', getenv('DB_PASSWORD') ?: '', $options + [PDO::ATTR_EMULATE_PREPARES => false]);
}
