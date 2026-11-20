<?php
require_once dirname(__DIR__) . '/app/config/database.php';
require_once dirname(__DIR__) . '/app/services/RestaurantSearchService.php';
// Read production data into an isolated in-memory database. Never modify the live DB.
$original = connectDatabase();
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE restaurants(id INTEGER PRIMARY KEY,name TEXT,address TEXT,genre_code TEXT,prefecture_code TEXT)');
$pdo->exec('CREATE TABLE allergy_sources(id INTEGER PRIMARY KEY,restaurant_id INTEGER,source_url TEXT,verification_status TEXT,checked_at TEXT)');
$pdo->exec('CREATE TABLE source_allergens(source_id INTEGER,allergen_code TEXT,coverage_status TEXT,checked_at TEXT)');
$pdo->exec("INSERT INTO restaurants VALUES(1,'A','大阪府大阪市','cafe','27'),(2,'B','東京都','japanese','13')");
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
check(count($original->query('SELECT id FROM restaurants')->fetchAll())===10,'10 existing restaurants retained');
check(count(searchRestaurants($pdo,['prefecture'=>'27']))===1,'prefecture filter');
check(count(searchRestaurants($pdo,['genre'=>'cafe']))===1,'genre filter');
check(count(searchRestaurants($pdo,['keyword'=>'cafe']))===0,'keyword does not search genre');
$pdo->exec("INSERT INTO allergy_sources VALUES(1,1,'https://example.com/a','pending','2026-11-20')");
$pdo->exec("INSERT INTO source_allergens VALUES(1,'egg','published','2026-11-20')");
check(!searchRestaurants($pdo,['allergies'=>['egg']]),'pending source excluded even with published coverage');
$pdo->exec("UPDATE allergy_sources SET verification_status='verified'");
check(count(searchRestaurants($pdo,['allergies'=>['egg']]))===1,'verified and published included');
check(count(searchRestaurants($pdo,['official'=>'1']))===1,'official source filter');
$pdo->exec("INSERT INTO allergy_sources VALUES(2,1,'https://example.com/b','verified','2026-11-20')");
$pdo->exec("INSERT INTO source_allergens VALUES(2,'egg','published','2026-11-20'),(2,'milk','not_applicable','2026-11-20')");
check(count(searchRestaurants($pdo,['allergies'=>['egg']]))===1,'multiple sources never duplicate restaurants');
check(!searchRestaurants($pdo,['allergies'=>['egg','milk']]),'all selected allergens required; not_applicable excluded');
$pdo->exec("UPDATE source_allergens SET coverage_status='not_confirmed'");
check(!searchRestaurants($pdo,['allergies'=>['egg']]),'not_confirmed excluded');
$pdo->exec("UPDATE source_allergens SET coverage_status='published',checked_at=NULL");
check(!searchRestaurants($pdo,['allergies'=>['egg']]),'missing check date excluded');
check(!searchRestaurants($pdo,['keyword'=>"' OR 1=1 --"]),'keyword parameterized');
check(count(searchRestaurants($pdo,['keyword'=>[], 'allergies'=>[[]]]))===2,'malformed parameters handled');
