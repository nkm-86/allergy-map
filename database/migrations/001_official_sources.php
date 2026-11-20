<?php
// Explicit CLI migration; never run DDL or data migration from page requests.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/config/database.php';
$pdo = connectDatabase();
$sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
$directory = dirname(__DIR__, 2) . '/storage/backups';
if (!is_dir($directory)) mkdir($directory, 0700, true);
$backup = $directory . '/before-v3-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
if ($sqlite) {
    $pdo->exec('VACUUM INTO ' . $pdo->quote($backup . '.sqlite'));
} else {
    // Logical data backup of all existing tables. Keep the original schema intact.
    $data = [];
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $data[$table] = ['schema' => $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM)[1], 'rows' => $pdo->query('SELECT * FROM ' . $quoted)->fetchAll()];
    }
    if (file_put_contents($backup . '.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) === false) throw new RuntimeException('Backup failed');
}
$original = $pdo->query('SELECT * FROM restaurants ORDER BY id')->fetchAll();
$columns = $sqlite ? array_column($pdo->query('PRAGMA table_info(restaurants)')->fetchAll(), 'name') : array_column($pdo->query('SHOW COLUMNS FROM restaurants')->fetchAll(), 'Field');
foreach (['prefecture_code' => 'CHAR(2)', 'city' => 'VARCHAR(100)', 'latitude' => 'DECIMAL(10,7)', 'longitude' => 'DECIMAL(10,7)', 'updated_at' => 'DATETIME'] as $name => $type) {
    if (!in_array($name, $columns, true)) $pdo->exec("ALTER TABLE restaurants ADD COLUMN $name $type NULL");
}
$idType = $sqlite ? 'INTEGER' : $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'id'")->fetch()['Type'];
$auto = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
$sourceId = $sqlite ? 'INTEGER' : 'BIGINT UNSIGNED';
$pdo->exec("CREATE TABLE IF NOT EXISTS allergy_sources (
 id $auto, restaurant_id $idType NOT NULL, source_url VARCHAR(2048) NOT NULL,
 source_type VARCHAR(30) NOT NULL, title VARCHAR(200) NOT NULL,
 verification_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (verification_status IN ('verified','pending','invalid')),
 checked_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE)");
$pdo->exec("CREATE TABLE IF NOT EXISTS allergens (code VARCHAR(30) PRIMARY KEY, name_ja VARCHAR(50) NOT NULL, sort_order INT NOT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS source_allergens (
 source_id $sourceId NOT NULL, allergen_code VARCHAR(30) NOT NULL,
 coverage_status VARCHAR(20) NOT NULL DEFAULT 'not_confirmed' CHECK (coverage_status IN ('published','not_confirmed','not_applicable')),
 checked_at DATETIME NULL, PRIMARY KEY(source_id,allergen_code),
 FOREIGN KEY(source_id) REFERENCES allergy_sources(id) ON DELETE CASCADE,
 FOREIGN KEY(allergen_code) REFERENCES allergens(code))");
foreach ([['restaurants','idx_location','prefecture_code,city'],['restaurants','idx_genre','genre_code'],['allergy_sources','idx_restaurant_status','restaurant_id,verification_status'],['source_allergens','idx_allergen_status','allergen_code,coverage_status']] as [$table,$index,$fields]) {
    $indexes = $sqlite ? array_column($pdo->query("PRAGMA index_list($table)")->fetchAll(),'name') : array_column($pdo->query("SHOW INDEX FROM $table")->fetchAll(),'Key_name');
    if (!in_array($index,$indexes,true)) $pdo->exec("CREATE INDEX $index ON $table($fields)");
}
$pdo->beginTransaction();
try {
    $master = ['egg'=>'卵','milk'=>'乳','wheat'=>'小麦','peanut'=>'落花生','shrimp'=>'えび','crab'=>'かに','buckwheat'=>'そば','walnut'=>'くるみ'];
    $insert = $pdo->prepare('INSERT INTO allergens(code,name_ja,sort_order) SELECT ?,?,? WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE code=?)');
    foreach (array_keys($master) as $order => $code) $insert->execute([$code,$master[$code],$order,$code]);
    $source = $pdo->prepare("INSERT INTO allergy_sources(restaurant_id,source_url,source_type,title) SELECT ?,?,?,? WHERE NOT EXISTS (SELECT 1 FROM allergy_sources WHERE restaurant_id=? AND source_url=?)");
    foreach ($original as $row) {
        if (str_starts_with($row['address'], '大阪府')) $pdo->prepare("UPDATE restaurants SET prefecture_code=COALESCE(prefecture_code,'27'),city=COALESCE(city,'大阪市北区') WHERE id=?")->execute([$row['id']]);
        if (!empty($row['source_url'])) $source->execute([$row['id'],$row['source_url'],str_contains(strtolower($row['source_url']),'.pdf')?'pdf':'web','既存データから移行（内容未確認）',$row['id'],$row['source_url']]);
    }
    $pdo->exec("INSERT INTO source_allergens(source_id,allergen_code) SELECT s.id,a.code FROM allergy_sources s CROSS JOIN allergens a WHERE NOT EXISTS (SELECT 1 FROM source_allergens sa WHERE sa.source_id=s.id AND sa.allergen_code=a.code)");
    // Legacy values are deliberately retained. No old allergy code becomes published.
    $after = $pdo->query('SELECT * FROM restaurants ORDER BY id')->fetchAll();
    if (count($original) !== count($after)) throw new RuntimeException('Restaurant count changed');
    foreach ($original as $i=>$row) foreach ($row as $key=>$value) if ($after[$i][$key] != $value) throw new RuntimeException('Legacy data changed');
    $pdo->commit();
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
echo 'Migration completed: ' . count($original) . " restaurants preserved. Backup: $backup\n";
