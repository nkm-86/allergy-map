<?php
require_once __DIR__ . '/db_connect.php';

// 検索パラメータの取得
$keyword = isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : '';
$selected_allergies = isset($_GET['allergies']) && is_array($_GET['allergies']) ? $_GET['allergies'] : [];

// SQLクエリの組み立て
$sql = 'SELECT * FROM restaurants WHERE 1=1';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (name LIKE :kw1 OR address LIKE :kw2 OR genre LIKE :kw3)';
    $like_kw = '%' . $keyword . '%';
    $params[':kw1'] = $like_kw;
    $params[':kw2'] = $like_kw;
    $params[':kw3'] = $like_kw;
}

foreach ($selected_allergies as $index => $allergy_code) {
    if (array_key_exists($allergy_code, $ALLERGY_OPTIONS)) {
        $param_key = ':allergy_' . $index;
        $sql .= " AND FIND_IN_SET({$param_key}, allergy_codes) > 0";
        $params[$param_key] = $allergy_code;
    }
}

$sql .= ' ORDER BY id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>マップから探す | Allergy Map</title>

    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/map.css">
</head>

<body>

    <!-- =========================
         ヘッダー
    ========================== -->
    <header class="header">
        <div class="header-inner">

            <h1 class="logo">
                <a href="index.php">Allergy Map</a>
            </h1>

            <nav class="navigation">
                <a href="index.php">HOME</a>
                <a href="index.php#search">検索</a>
                <a href="map.php">マップ</a>
            </nav>

        </div>
    </header>


    <main>

        <!-- =========================
             ページタイトル
        ========================== -->
        <section class="map-hero">
            <h2>マップからお店を探す</h2>
            <p>地図から近くのアレルギー情報が書いてあるお店を見つけましょう。</p>
        </section>


        <!-- =========================
             検索・絞り込み
        ========================== -->
        <section class="map-search-section">

            <form action="map.php" method="GET">

                <div class="map-search-box">
                    <input
                        type="text"
                        name="keyword"
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="地域・駅名・店名などで検索"
                        aria-label="地域や駅名で検索"
                    >

                    <button type="submit">検索</button>
                </div>

                <div class="map-filter">

                    <h3>アレルギーで絞り込む</h3>

                    <div class="map-filter-list">
                        <?php foreach ($ALLERGY_OPTIONS as $code => $label): ?>
                            <label>
                                <input
                                    type="checkbox"
                                    name="allergies[]"
                                    value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= in_array($code, $selected_allergies, true) ? 'checked' : '' ?>
                                > <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                </div>

            </form>

        </section>


        <!-- =========================
             マップ・店舗一覧
        ========================== -->
        <section class="map-content">

            <!-- 地図 -->
            <div class="map-area">

                <div class="map-placeholder">

                    <p class="map-placeholder-text">
                        MAP AREA
                    </p>

                    <!-- 店舗マーカー（仮） -->
                    <span class="map-pin pin-1">📍</span>
                    <span class="map-pin pin-2">📍</span>
                    <span class="map-pin pin-3">📍</span>
                    <span class="map-pin pin-4">📍</span>

                    <div class="map-message">
                        地図は今後実装予定です
                    </div>

                </div>

            </div>


            <!-- 店舗一覧 -->
            <aside class="map-restaurant-list">

                <h3>周辺のお店</h3>

                <p class="result-count">
                    <?= count($restaurants) ?>件の店舗が見つかりました
                </p>

                <?php foreach ($restaurants as $restaurant): ?>
                    <?php
                    $tags = array_filter(array_map('trim', explode(',', $restaurant['allergy_tags'])));
                    ?>
                    <article class="map-restaurant-card">

                        <div class="map-restaurant-image">
                            <img
                                src="<?= htmlspecialchars($restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="map-restaurant-info">

                            <h4><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></h4>

                            <p><?= htmlspecialchars($restaurant['genre'], ENT_QUOTES, 'UTF-8') ?></p>

                            <div class="map-allergy-info">
                                <?php foreach ($tags as $tag): ?>
                                    <span><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>

                            <a href="detail.php?id=<?= (int)$restaurant['id'] ?>">詳細を見る →</a>

                        </div>

                    </article>
                <?php endforeach; ?>

            </aside>

        </section>

        <!-- =========================
             注意事項
        ========================== -->
        <section class="notice-section">

            <h2>ご利用上の注意</h2>

            <p>
                掲載されているアレルギー情報は、
                すべてのアレルギー反応を防ぐことを保証するものではありません。
            </p>

            <p>
                調理器具や設備の共用などにより、
                アレルゲンが混入する可能性があります。
            </p>

            <p>
                ご利用前に必ず店舗へ直接ご確認ください。
            </p>

        </section>

    </main>


    <!-- =========================
         フッター
    ========================== -->
    <footer class="footer">
        <p>&copy; 2026 Allergy Map. All Rights Reserved.</p>
    </footer>

    <script src="js/common.js"></script>
</body>
</html>
