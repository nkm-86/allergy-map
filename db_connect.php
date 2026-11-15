<?php
// =========================
// データベース接続設定
// =========================

$db_host = 'localhost';
$db_name = 'allergy_map';
$db_user = 'root';
$db_pass = ''; // MySQLを使用する場合はパスワードを入力（未設定・接続不可時は自動でSQLiteデータベースを使用します）
$db_charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 初期データ（梅田周辺10店舗：情報提供元URL付き）
$INITIAL_RESTAURANTS = [
    ['スシロー 梅田茶屋町店', 'japanese', '和食・回転寿司', '大阪府大阪市北区茶屋町2-16 イースクエア茶屋町6階', 'img/SUSHIRO.jpeg', '卵不使用あり,乳不使用あり,小麦不使用あり', 'egg,milk,wheat', '情報提供元：公式HP(2026年11月時点)', 'https://www.akindo-sushiro.co.jp/allergy/'],
    ['びっくりドンキー 梅田東店', 'western', '洋食・ハンバーグ', '大阪府大阪市北区堂山町1-5 三共梅田ビル2F', 'img/BikkuriDonki.jpg', '卵不使用あり,乳不使用あり,小麦不使用あり', 'egg,milk,wheat', '情報提供元：公式HP(2026年11月時点)', 'https://www.bikkuri-donkey.com/allergy/'],
    ['吉野家 新梅田食堂街店', 'japanese', '和食・牛丼', '大阪府大阪市北区角田町9-17', 'img/Yoshinoya.png', 'アレルゲン一覧表あり,一部要確認', '', '情報提供元：公式HP(2026年11月時点)', 'https://www.yoshinoya.com/Menu/allergy/'],
    ['スープストック東京 ルクア大阪店', 'cafe', 'カフェ・スープ', '大阪府大阪市北区梅田3-1-3 ルクア大阪内B1F', 'img/Supustock.jpg', 'アレルギー一覧表あり', '', '情報提供元：公式HP(2026年11月時点)', 'https://www.soup-stock-tokyo.com/allergy/'],
    ['コスメキッチンアダプテーション', 'cafe', 'カフェ・レストラン', '大阪府大阪市北区梅田1-13-13 阪神百貨店3F', 'img/KosmeKitchen.jpg', 'アレルゲン記載メニュー表あり', '', '情報提供元：公式HP', 'https://ck-adaptation.com/'],
    ['アノサキコノサキ', 'cafe', 'カフェ・ランチ', '大阪府大阪市北区中崎西1丁目2-5 エスパシオン中崎 1階', 'img/AnosakiKonosaki.jpg', '卵不使用ランチ,乳不使用ランチ,小麦不使用ランチ', 'egg,milk,wheat', '情報提供元：公式HP(2026年11月時点)', 'https://anosakikonosaki.com/'],
    ['お野菜ガーデンTIERRA', 'western', '洋食・レストラン', '大阪府大阪市北区角田町8-7 阪急うめだ本店13F', 'img/TIERRA.png', '卵不使用あり,乳不使用あり,店頭表あり', 'egg,milk', '情報提供元：まいどOSAKA MAP（2025年3月時点）／店舗確認', 'https://www.hankyu-dept.co.jp/honten/restaurantguide/'],
    ['ラーメン横綱（梅田阪急三番街）', 'chinese', '中華・ラーメン', '大阪府大阪市北区芝田1-1-3 梅田阪急三番街B2F', 'img/YOKOZUNA.png', '卵不使用あり,乳不使用あり,アレルゲン表あり', 'egg,milk', '情報提供元：公式HP(2026年11月時点)', 'https://www.4527.com/menu/allergy.html'],
    ['鼎泰豊（ディンタイフォン）ルクア大阪店', 'chinese', '中華・点心', '大阪府大阪市北区梅田3丁目1-3 ルクア大阪 10F', 'img/Dlintaru.jpg', '卵不使用あり,乳不使用あり,アレルギー表あり', 'egg,milk', '情報提供元：公式HP(2026年11月時点)', 'https://d.rt-c.co.jp/allergy/'],
    ['ユンユン ルクア大阪店（YUNYUN）', 'chinese', '中華料理・飲茶・点心', '大阪府大阪市北区梅田3-1-3 ルクアイーレ B2F キッチン&マーケット内', 'img/YUNYUN.jpg', '米粉麺あり,卵抜き相談可', 'egg,wheat', '情報提供元：公式HP(2026年11月時点)／体験情報', 'https://www.k-yunyun.jp/wp-content/uploads/pdf/menu_lucua.pdf'],
];

try {
    // 1. MySQLへの接続を試行
    $pdo = new PDO("mysql:host={$db_host};charset={$db_charset}", $db_user, $db_pass, $options);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db_name}`");

    $table_check = $pdo->query("SHOW TABLES LIKE 'restaurants'")->fetch();
    if (!$table_check) {
        $pdo->exec("
            CREATE TABLE restaurants (
                id INT AUTO_INCREMENT PRIMARY KEY COMMENT '店舗ID',
                name VARCHAR(150) NOT NULL COMMENT '店舗名',
                genre_code VARCHAR(50) NOT NULL COMMENT '検索用ジャンルコード',
                genre VARCHAR(100) NOT NULL COMMENT '表示用ジャンル名',
                address VARCHAR(255) NOT NULL COMMENT '住所',
                image_path VARCHAR(255) NOT NULL COMMENT '店舗画像パス',
                allergy_tags VARCHAR(255) NOT NULL COMMENT '画面表示用アレルギー情報タグ（カンマ区切り）',
                allergy_codes VARCHAR(255) NOT NULL DEFAULT '' COMMENT '絞り込み用アレルギーコード（カンマ区切り）',
                information_status VARCHAR(255) NOT NULL COMMENT '情報提供元・確認時期',
                source_url VARCHAR(500) NOT NULL DEFAULT '' COMMENT '情報提供元のURL',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $insert_stmt = $pdo->prepare("
            INSERT INTO restaurants (name, genre_code, genre, address, image_path, allergy_tags, allergy_codes, information_status, source_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($INITIAL_RESTAURANTS as $row) {
            $insert_stmt->execute($row);
        }
    } else {
        // 既存テーブルに source_url カラムがない場合は自動追加して初期URLを反映
        $col_check = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'source_url'")->fetch();
        if (!$col_check) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN source_url VARCHAR(500) NOT NULL DEFAULT '' COMMENT '情報提供元のURL' AFTER information_status");
            $upd = $pdo->prepare("UPDATE restaurants SET source_url = ? WHERE name = ?");
            foreach ($INITIAL_RESTAURANTS as $row) {
                $upd->execute([$row[8], $row[0]]);
            }
        }
    }
} catch (PDOException $mysql_error) {
    // 2. MySQL接続失敗時（パスワード未設定等）は、パスワード不要のSQLiteデータベースへ自動切り替え
    $sqlite_dir = __DIR__ . '/sql';
    if (!is_dir($sqlite_dir)) {
        mkdir($sqlite_dir, 0777, true);
    }
    $sqlite_path = $sqlite_dir . '/allergy_map.sqlite';

    $pdo = new PDO('sqlite:' . $sqlite_path, null, null, $options);

    // MySQLの FIND_IN_SET 関数と互換性を持たせるカスタム関数を登録
    $pdo->sqliteCreateFunction('FIND_IN_SET', function ($needle, $haystack) {
        if ($haystack === null || $haystack === '') {
            return 0;
        }
        $items = array_map('trim', explode(',', (string)$haystack));
        $pos = array_search(trim((string)$needle), $items, true);
        return $pos === false ? 0 : ($pos + 1);
    }, 2);

    // テーブル作成と初期データ投入
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            genre_code TEXT NOT NULL,
            genre TEXT NOT NULL,
            address TEXT NOT NULL,
            image_path TEXT NOT NULL,
            allergy_tags TEXT NOT NULL,
            allergy_codes TEXT NOT NULL DEFAULT '',
            information_status TEXT NOT NULL,
            source_url TEXT NOT NULL DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 既存SQLiteテーブルに source_url カラムがない場合は自動追加して初期URLを反映
    $cols = $pdo->query("PRAGMA table_info(restaurants)")->fetchAll();
    $has_source_url = false;
    foreach ($cols as $col) {
        if (isset($col['name']) && $col['name'] === 'source_url') {
            $has_source_url = true;
            break;
        }
    }
    if (!$has_source_url) {
        $pdo->exec("ALTER TABLE restaurants ADD COLUMN source_url TEXT NOT NULL DEFAULT ''");
        $upd = $pdo->prepare("UPDATE restaurants SET source_url = ? WHERE name = ?");
        foreach ($INITIAL_RESTAURANTS as $row) {
            $upd->execute([$row[8], $row[0]]);
        }
    }

    $count = (int)$pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
    if ($count === 0) {
        $insert_stmt = $pdo->prepare("
            INSERT INTO restaurants (name, genre_code, genre, address, image_path, allergy_tags, allergy_codes, information_status, source_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($INITIAL_RESTAURANTS as $row) {
            $insert_stmt->execute($row);
        }
    }
}

// 共通マスタ定義（検索フィルター表示用）
$ALLERGY_OPTIONS = [
    'egg'       => '卵',
    'milk'      => '乳',
    'wheat'     => '小麦',
    'peanut'    => '落花生',
    'shrimp'    => 'えび',
    'crab'      => 'かに',
    'buckwheat' => 'そば',
    'walnut'    => 'くるみ',
];

$GENRE_OPTIONS = [
    'japanese' => '和食',
    'western'  => '洋食',
    'chinese'  => '中華',
    'cafe'     => 'カフェ',
    'fastfood' => 'ファストフード',
];
