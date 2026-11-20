<?php
require_once __DIR__ . '/db_connect.php';

$keyword = requestText($_GET,'keyword');
$selected_allergies = isset($_GET['allergies']) && is_array($_GET['allergies']) ? array_values(array_filter($_GET['allergies'],'is_string')) : [];
$selected_genre = requestText($_GET,'genre');
$selected_prefecture = requestText($_GET,'prefecture');
$restaurants = searchRestaurants($pdo, $_GET);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>マップから探す | Allergy Map</title>

    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="css/map.css">
</head>

<body class="map-page">

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
            <div class="map-eyebrow">EXPLORE RESTAURANTS</div>
            <h2>あなたに合うお店を、地図から。</h2>
            <p>エリアとアレルギー情報を見比べながら、次の食事の行き先を。</p>
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
                        placeholder="店名・住所で検索"
                        aria-label="地域や駅名で検索"
                    >

                    <button type="submit">検索</button>
                </div>

                <div class="map-filter">

                    <h3>公式資料で確認済みの品目</h3>

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

            <?php require __DIR__ . '/app/search-filters.php'; ?>
            </form>

        </section>


        <!-- =========================
             マップ・店舗一覧
        ========================== -->
        <section class="map-content">

            <!-- 地図 -->
            <div class="map-area">

                <div class="map-panel-heading"><div><span class="map-live-dot"></span> 梅田・大阪駅周辺</div><button type="button" id="show-all">すべて表示 ↗</button></div>
                <div id="restaurant-map" role="region" aria-label="店舗の位置を表示する地図"><p class="map-fallback">地図を読み込み中です。店舗情報は一覧からご覧いただけます。</p></div>
                <p class="map-location-note">座標登録済みの店舗のみ地図に表示します。座標未登録の店舗も一覧から確認できます。</p>
                <div class="map-selected" id="map-selected" aria-live="polite">店舗の「地図で見る」か、地図の番号を選んでください。</div>
            </div>



            <!-- 店舗一覧 -->
            <aside class="map-restaurant-list" aria-label="検索結果の店舗一覧">

                <div class="results-eyebrow">RESTAURANTS NEAR YOU</div><h3>周辺のお店</h3>

                <p class="result-count">
                    <?= count($restaurants) ?>件の店舗が見つかりました
                </p>

                <div class="map-card-grid">
                <?php foreach ($restaurants as $index => $restaurant): ?>
                    <?php
                    $tags = officialTags($pdo, (int)$restaurant['id']);
                    ?>
                    <article class="map-restaurant-card" data-latitude="<?= htmlspecialchars((string)$restaurant['latitude'], ENT_QUOTES, 'UTF-8') ?>" data-longitude="<?= htmlspecialchars((string)$restaurant['longitude'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>" data-address="<?= htmlspecialchars($restaurant['address'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="map-restaurant-image"><span class="card-number"><?= $index + 1 ?></span>
                            <img
                                src="<?= htmlspecialchars($restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="map-restaurant-info">

                            <h4><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></h4>

                            <p class="card-genre"><?= htmlspecialchars($restaurant['genre'], ENT_QUOTES, 'UTF-8') ?></p><p class="card-address"><?= htmlspecialchars($restaurant['address'], ENT_QUOTES, 'UTF-8') ?></p>

                            <div class="map-allergy-info">
                                <?php foreach ($tags as $tag): ?>
                                    <span><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div class="card-actions"><button type="button" class="show-on-map">地図で見る</button><a href="detail.php?id=<?= (int)$restaurant['id'] ?>">店舗詳細 ↗</a></div>

                        </div>

                    </article>
                <?php endforeach; ?>
                </div>
                <?php if (!$restaurants): ?><p class="map-empty">条件に合うお店が見つかりませんでした。条件を変えて検索してください。</p><?php endif; ?>
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/map.js"></script>
</body>
</html>
