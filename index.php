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

    <title>Allergy Map</title>

    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/index.css">
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
                <a href="index.php#notice">店舗一覧</a>
                <a href="map.php">マップ</a>
            </nav>

        </div>
    </header>


    <main>

        <!-- =========================
             サイト紹介
        ========================== -->
        <section class="hero">

            <h2>自分に合ったお店を見つけよう。</h2>

            <p>
                アレルギー情報を確認して、
                安心してお店を選ぶための飲食店検索サイトです。
            </p>

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


        <!-- =========================
             店舗検索
        ========================== -->
        <section class="search-section" id="search">

            <div class="search-heading">
                <h2>お店を探す</h2>
                <p>アレルギー情報をもとに、安心してお店を選ぶことができます。</p>
                <a href="map.php" class="map-search-link">
                    <span>📍</span> マップで探す
                </a>
            </div>

            <form action="index.php#notice" method="GET">

                <!-- 店舗名・地域検索 -->
                <div class="search-box">

                    <input
                        type="text"
                        name="keyword"
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="店名・住所で検索"
                        aria-label="店名や地域で検索"
                    >

                    <button type="submit">検索</button>

                </div>


                <!-- アレルギー検索 -->
                <div class="allergy-filter">

                    <h3>公式資料で確認済みの品目</h3>

                    <div class="allergy-list">
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


                <!-- ジャンル検索 -->


            <?php require __DIR__ . '/app/search-filters.php'; ?>
            </form>

        </section>


        <!-- =========================
             店舗一覧
        ========================== -->
        <section class="restaurant-section" id="notice">

            <div class="section-heading">

                <h2>店舗一覧（梅田周辺：<?= count($restaurants) ?>件）</h2>

                <a href="map.php">マップで見る →</a>

            </div>


            <div class="restaurant-list">

                <?php if (empty($restaurants)): ?>
                    <p>条件に一致する店舗が見つかりませんでした。</p>
                <?php else: ?>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <?php
                        $tags = officialTags($pdo, (int)$restaurant['id']);
                        ?>
                        <article class="restaurant-card">
                            <div class="restaurant-image">
                                <img
                                    src="<?= htmlspecialchars($restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>のイメージ画像"
                                >
                            </div>
                            <div class="restaurant-info">
                                <h3><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="restaurant-genre"><?= htmlspecialchars($restaurant['genre'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="restaurant-address"><?= htmlspecialchars($restaurant['address'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="allergy-info">
                                    <?php foreach ($tags as $tag): ?>
                                        <span><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <p class="information-status">公式情報の確認状態は店舗詳細をご覧ください</p>
                                <a href="detail.php?id=<?= (int)$restaurant['id'] ?>" class="detail-link">詳細を見る</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

        </section>

    </main>


    <!-- =========================
         フッター
    ========================== -->
    <footer class="footer">

        <p>&copy; 2026 Allergy Map. All Rights Reserved.</p>

    </footer>


    <!-- JavaScript -->
    <script src="js/common.js"></script>

</body>
</html>
