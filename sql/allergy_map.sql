-- =========================================
-- Allergy Map データベース・テーブル作成SQL
-- =========================================

CREATE DATABASE IF NOT EXISTS allergy_map
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE allergy_map;

DROP TABLE IF EXISTS restaurants;

CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '店舗ID',
    name VARCHAR(150) NOT NULL COMMENT '店舗名',
    genre_code VARCHAR(50) NOT NULL COMMENT '検索用ジャンルコード (japanese, western, chinese, cafe, fastfood)',
    genre VARCHAR(100) NOT NULL COMMENT '表示用ジャンル名',
    address VARCHAR(255) NOT NULL COMMENT '住所',
    image_path VARCHAR(255) NOT NULL COMMENT '店舗画像パス',
    allergy_tags VARCHAR(255) NOT NULL COMMENT '画面表示用アレルギー情報タグ（カンマ区切り）',
    allergy_codes VARCHAR(255) NOT NULL DEFAULT '' COMMENT '絞り込み用アレルギーコード（カンマ区切り: egg,milk,wheat等）',
    information_status VARCHAR(255) NOT NULL COMMENT '情報提供元・確認時期',
    source_url VARCHAR(500) NOT NULL DEFAULT '' COMMENT '情報提供元のURL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='飲食店情報テーブル';

-- =========================================
-- 初期データ登録（梅田周辺10店舗）
-- =========================================

INSERT INTO restaurants (
    name,
    genre_code,
    genre,
    address,
    image_path,
    allergy_tags,
    allergy_codes,
    information_status,
    source_url
) VALUES
(
    'スシロー 梅田茶屋町店',
    'japanese',
    '和食・回転寿司',
    '大阪府大阪市北区茶屋町2-16 イースクエア茶屋町6階',
    'img/SUSHIRO.jpeg',
    '卵不使用あり,乳不使用あり,小麦不使用あり',
    'egg,milk,wheat',
    '情報提供元：公式HP(2026年11月時点)',
    'https://www.akindo-sushiro.co.jp/allergy/'
),
(
    'びっくりドンキー 梅田東店',
    'western',
    '洋食・ハンバーグ',
    '大阪府大阪市北区堂山町1-5 三共梅田ビル2F',
    'img/BikkuriDonki.jpg',
    '卵不使用あり,乳不使用あり,小麦不使用あり',
    'egg,milk,wheat',
    '情報提供元：公式HP(2026年11月時点)',
    'https://www.bikkuri-donkey.com/allergy/'
),
(
    '吉野家 新梅田食堂街店',
    'japanese',
    '和食・牛丼',
    '大阪府大阪市北区角田町9-17',
    'img/Yoshinoya.png',
    'アレルゲン一覧表あり,一部要確認',
    '',
    '情報提供元：公式HP(2026年11月時点)',
    'https://www.yoshinoya.com/Menu/allergy/'
),
(
    'スープストック東京 ルクア大阪店',
    'cafe',
    'カフェ・スープ',
    '大阪府大阪市北区梅田3-1-3 ルクア大阪内B1F',
    'img/Supustock.jpg',
    'アレルギー一覧表あり',
    '',
    '情報提供元：公式HP(2026年11月時点)',
    'https://www.soup-stock-tokyo.com/allergy/'
),
(
    'コスメキッチンアダプテーション',
    'cafe',
    'カフェ・レストラン',
    '大阪府大阪市北区梅田1-13-13 阪神百貨店3F',
    'img/KosmeKitchen.jpg',
    'アレルゲン記載メニュー表あり',
    '',
    '情報提供元：公式HP',
    'https://ck-adaptation.com/'
),
(
    'アノサキコノサキ',
    'cafe',
    'カフェ・ランチ',
    '大阪府大阪市北区中崎西1丁目2-5 エスパシオン中崎 1階',
    'img/AnosakiKonosaki.jpg',
    '卵不使用ランチ,乳不使用ランチ,小麦不使用ランチ',
    'egg,milk,wheat',
    '情報提供元：公式HP(2026年11月時点)',
    'https://anosakikonosaki.com/'
),
(
    'お野菜ガーデンTIERRA',
    'western',
    '洋食・レストラン',
    '大阪府大阪市北区角田町8-7 阪急うめだ本店13F',
    'img/TIERRA.png',
    '卵不使用あり,乳不使用あり,店頭表あり',
    'egg,milk',
    '情報提供元：まいどOSAKA MAP（2025年3月時点）／店舗確認',
    'https://www.hankyu-dept.co.jp/honten/restaurantguide/'
),
(
    'ラーメン横綱（梅田阪急三番街）',
    'chinese',
    '中華・ラーメン',
    '大阪府大阪市北区芝田1-1-3 梅田阪急三番街B2F',
    'img/YOKOZUNA.png',
    '卵不使用あり,乳不使用あり,アレルゲン表あり',
    'egg,milk',
    '情報提供元：公式HP(2026年11月時点)',
    'https://www.4527.com/menu/allergy.html'
),
(
    '鼎泰豊（ディンタイフォン）ルクア大阪店',
    'chinese',
    '中華・点心',
    '大阪府大阪市北区梅田3丁目1-3 ルクア大阪 10F',
    'img/Dlintaru.jpg',
    '卵不使用あり,乳不使用あり,アレルギー表あり',
    'egg,milk',
    '情報提供元：公式HP(2026年11月時点)',
    'https://d.rt-c.co.jp/allergy/'
),
(
    'ユンユン ルクア大阪店（YUNYUN）',
    'chinese',
    '中華料理・飲茶・点心',
    '大阪府大阪市北区梅田3-1-3 ルクアイーレ B2F キッチン&マーケット内',
    'img/YUNYUN.jpg',
    '米粉麺あり,卵抜き相談可',
    'egg,wheat',
    '情報提供元：公式HP(2026年11月時点)／体験情報',
    'https://www.k-yunyun.jp/wp-content/uploads/pdf/menu_lucua.pdf'
);
